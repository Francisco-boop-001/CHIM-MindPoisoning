<?php
declare(strict_types=1);

require_once __DIR__ . '/../server/influence.php';
require_once __DIR__ . '/../server/store.php';
require_once __DIR__ . '/../server/prerequest.php';

use ChimMindPoisoning\StoreDb;
use function ChimMindPoisoning\assertIdleTransactionStatus;
use function ChimMindPoisoning\assertPgSqlConnection;
use function ChimMindPoisoning\eventAlreadyProcessed;
use function ChimMindPoisoning\handleSpeechAck;
use function ChimMindPoisoning\nextLedger;
use function ChimMindPoisoning\normalizeEventRow;
use function ChimMindPoisoning\persistJudgments;

function chimInteractionAllowed(): bool
{
    chimInteractionBegin();
    $state = chimInteractionState();
    return ($_SERVER['HTTP_X_CHIM_PASSIVE'] ?? '') !== '1'
        && $state['enabled']
        && $state['generation'] === $GLOBALS['chim_interaction_generation'];
}

function chimInteractionState(): array
{
    return [
        'enabled' => $GLOBALS['runtime_test_interaction_allowed'] ?? true,
        'generation' => $GLOBALS['runtime_test_interaction_generation'] ?? 1,
    ];
}

function chimInteractionBegin(): void
{
    if (isset($GLOBALS['chim_interaction_generation'])) {
        return;
    }
    $state = chimInteractionState();
    $GLOBALS['chim_interaction_generation'] = isset($_SERVER['HTTP_X_CHIM_GENERATION'])
        ? (int)$_SERVER['HTTP_X_CHIM_GENERATION']
        : $state['generation'];
}

function resetInteractionTestRequest(int $requestGeneration, int $currentGeneration, bool $enabled, bool $passive): void
{
    $GLOBALS['runtime_test_interaction_generation'] = $currentGeneration;
    $GLOBALS['runtime_test_interaction_allowed'] = $enabled;
    $_SERVER['HTTP_X_CHIM_GENERATION'] = (string)$requestGeneration;
    if ($passive) {
        $_SERVER['HTTP_X_CHIM_PASSIVE'] = '1';
    } else {
        unset($_SERVER['HTTP_X_CHIM_PASSIVE']);
    }
    unset($GLOBALS['chim_interaction_generation']);
    chimInteractionBegin();
}

function chimIsGlobalLlmConnectorEnabled(string $connectorField): bool
{
    return $connectorField === 'RELLLM_CONNECTOR' && ($GLOBALS['runtime_test_relationship_enabled'] ?? true);
}

function extractSpeakerNameFromChatEvent($eventData): string
{
    $eventData = preg_replace('/^\s*\(\s*context[^)]*\)\s*/iu', '', trim((string)$eventData));
    return preg_match('/^\s*([^:]{1,128})\s*:/u', (string)$eventData, $matches) === 1
        ? trim($matches[1], " |\t\n\r\0\x0B")
        : '';
}

function extractCoreUtteranceFromChatEvent($eventData): string
{
    $eventData = preg_replace('/^\s*\(\s*context[^)]*\)\s*/iu', '', trim((string)$eventData));
    if (preg_match('/^\s*[^:]{1,128}\s*:\s*(.*)$/us', (string)$eventData, $matches) === 1) {
        $eventData = trim($matches[1]);
    }
    return trim((string)preg_replace('/\s*\(\s*(?:(?:talking|whispering|shouting)\s+to|speaking\s+(?:loudly|privately)\s+to)\s+[^)]*\)\s*$/iu', '', (string)$eventData));
}

function extractTalkTargetMetadata($eventData): array
{
    if (!preg_match('/\(\s*(?:(?:talking|whispering|shouting)\s+to|speaking\s+(?:loudly|privately)\s+to)\s+([^()]+?)(?:\s+from\s+far\s+away)?\s*\)/i', (string)$eventData, $matches)) {
        return ['hasExplicitTarget' => false, 'isBroadcast' => false, 'targets' => []];
    }
    $hint = trim($matches[1]);
    if (strcasecmp($hint, 'everyone') === 0) {
        return ['hasExplicitTarget' => true, 'isBroadcast' => true, 'targets' => []];
    }
    $targets = preg_split('/\s*(?:,|&| and )\s*/i', $hint);
    return ['hasExplicitTarget' => true, 'isBroadcast' => false, 'targets' => array_values(array_filter(array_map('trim', $targets ?: [])))];
}

function talkTargetsIncludeName($targetNames, $candidateName): bool
{
    foreach (is_array($targetNames) ? $targetNames : [] as $targetName) {
        if (is_string($targetName) && is_string($candidateName) && strcasecmp(trim($targetName), trim($candidateName)) === 0) {
            return true;
        }
    }
    return false;
}

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function same(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . '\nexpected: ' . var_export($expected, true) . '\nactual: ' . var_export($actual, true));
    }
}

final class MemoryStoreDb implements StoreDb
{
    public array $npcs = [];
    public array $events = [];
    public array $history = [];
    public string $profileId = '1';
    public string $playerName = 'Dragonborn';
    public bool $busy = false;
    public bool $failSnapshot = false;
    public int $beginCalls = 0;
    private bool $transaction = false;
    private ?array $before = null;

    public function activePlaythrough(): ?array
    {
        return ['id' => $this->profileId, 'player_name' => $this->playerName];
    }

    public function acknowledgedEvent(string $utteranceId): ?array
    {
        foreach ($this->events as $event) {
            if (($event['utterance_id'] ?? null) === $utteranceId) {
                return $event;
            }
        }
        return null;
    }

    public function npcIdentities(): array
    {
        return array_map(
            static fn(array $npc): array => ['id' => $npc['id'], 'npc_name' => $npc['npc_name']],
            array_values($this->npcs)
        );
    }

    public function eventById(int $eventId, string $utteranceId): ?array
    {
        $event = $this->events[$eventId] ?? null;
        return is_array($event) && ($event['utterance_id'] ?? null) === $utteranceId ? $event : null;
    }

    public function npcById(int $npcId, bool $forUpdate = false): ?array
    {
        return $this->npcs[$npcId] ?? null;
    }

    public function beginForListener(int $listenerId): bool
    {
        $this->beginCalls++;
        if ($this->busy || $this->transaction || !isset($this->npcs[$listenerId])) {
            return false;
        }
        $this->before = unserialize(serialize([$this->npcs, $this->history]));
        $this->transaction = true;
        return true;
    }

    public function writeNpc(int $npcId, array $relationshipEdges, object $mindPoisoningData, float $gamets): bool
    {
        if (!$this->transaction || !isset($this->npcs[$npcId])) {
            return false;
        }
        $extendedData = $this->npcs[$npcId]['extended_data'];
        $relationships = isset($extendedData->relationships)
            ? clone $extendedData->relationships
            : new stdClass();
        foreach ($relationshipEdges as $name => $edge) {
            $relationships->{$name} = clone $edge;
        }
        if ($relationshipEdges !== []) {
            $extendedData->relationships = $relationships;
        }
        $this->npcs[$npcId]['extended_data'] = $extendedData;
        $this->npcs[$npcId]['plugin_extended_data']->mind_poisoning = json_decode(
            json_encode($mindPoisoningData, JSON_THROW_ON_ERROR),
            false,
            512,
            JSON_THROW_ON_ERROR
        );
        $this->npcs[$npcId]['gamets_last_updated'] = $gamets;
        return true;
    }

    public function backupAndVerify(int $npcId, array $expected): bool
    {
        if (!$this->transaction || !isset($this->npcs[$npcId])) {
            return false;
        }
        $npc = $this->npcs[$npcId];
        $npc['extended_data']->_chim_history_source = 'relationship';
        $this->history[] = $npc;
        $expectedExtended = clone $expected['extended_data'];
        $expectedExtended->_chim_history_source = 'relationship';
        return !$this->failSnapshot
            && ChimMindPoisoning\sameJsonValue($npc['extended_data'], $expectedExtended)
            && ChimMindPoisoning\sameJsonValue($npc['plugin_extended_data'], $expected['plugin_extended_data'])
            && (float)$npc['gamets_last_updated'] === (float)$expected['gamets_last_updated'];
    }

    public function commit(): bool
    {
        if (!$this->transaction) {
            return false;
        }
        $this->transaction = false;
        $this->before = null;
        return true;
    }

    public function rollback(): void
    {
        if ($this->transaction && $this->before !== null) {
            [$this->npcs, $this->history] = $this->before;
        }
        $this->transaction = false;
        $this->before = null;
    }

    public function release(): void
    {
        // The fixture has no session-level advisory lock state beyond the transaction.
    }
}

function baseFixture(): array
{
    $event = [
        'utterance_id' => 'utt_0123456789abcdef',
        'speaker_id' => 11,
        'listener_id' => 22,
        'speaker_name' => 'Aela',
        'listener_name' => 'Lydia',
        'text' => 'I trust Jarl Balgruuf. The Dragonborn is brave.',
        'gamets' => 10.0,
        'event_id' => 100,
        'playthrough_id' => '1',
        'player_name' => 'Dragonborn',
        'source_data' => 'Aela: I trust Jarl Balgruuf. The Dragonborn is brave. (Talking to Lydia)',
    ];
    $subjects = [
        'npc:33' => ['name' => 'Jarl Balgruuf', 'id' => 33],
        'player' => ['name' => 'Player', 'id' => null],
    ];
    $judgments = [
        'npc:33' => ['delta' => 5, 'reason' => 'Trusting claim', 'evidence' => 'I trust Jarl Balgruuf.'],
        'player' => ['delta' => -2, 'reason' => 'Negative reaction', 'evidence' => 'The Dragonborn is brave.'],
    ];
    $db = new MemoryStoreDb();
    $db->events[100] = $event + ['type' => 'chat', 'delivery_state' => 'spoken'];
    $db->npcs = [
        11 => [
            'id' => 11, 'npc_name' => 'Aela',
            'extended_data' => (object)['relationships' => (object)['Jarl Balgruuf' => (object)['aff' => 25, 'type' => 'ally']]],
            'plugin_extended_data' => new stdClass(),
        ],
        22 => [
            'id' => 22,
            'npc_name' => 'Lydia',
            'gamets_last_updated' => 20.0,
            'extended_data' => (object)[
                'unrelated' => (object)['preserve' => true, 'empty_object' => new stdClass()],
                'relationships' => (object)[
                    'Aela' => (object)['aff' => -50, 'type' => 'rival'],
                    'Jarl Balgruuf' => (object)['aff' => 99, 'type' => 'ally', 'note' => 'existing', 'nested_empty' => new stdClass()],
                    'Other NPC' => (object)['aff' => -40, 'type' => 'enemy', 'x' => 9],
                ],
            ],
            'plugin_extended_data' => (object)['other_plugin' => (object)['keep' => 'yes']],
        ],
        33 => ['id' => 33, 'npc_name' => 'Jarl Balgruuf', 'extended_data' => (object)['relationships' => new stdClass()], 'plugin_extended_data' => new stdClass()],
    ];
    return [$event, $subjects, $judgments, $db];
}

check(
    ChimMindPoisoning\sameJsonValue(['second' => 2, 'first' => 1], json_decode('{"first":1,"second":2}', false, 512, JSON_THROW_ON_ERROR)),
    'Associative PHP arrays must compare as JSON objects regardless of key order.'
);
check(!ChimMindPoisoning\sameJsonValue([1, 2], [2, 1]), 'JSON list ordering must remain significant.');

assertIdleTransactionStatus(PGSQL_TRANSACTION_IDLE);
$activeRejected = false;
try {
    assertIdleTransactionStatus(PGSQL_TRANSACTION_INTRANS);
} catch (RuntimeException) {
    $activeRejected = true;
}
check($activeRejected, 'Active caller transaction must be rejected before the adapter begins work.');
$unsupportedRejected = false;
try {
    assertPgSqlConnection(new stdClass());
} catch (RuntimeException) {
    $unsupportedRejected = true;
}
check($unsupportedRejected, 'Unsupported connection objects must fail closed before BEGIN.');
same(null, normalizeEventRow([
    'rowid' => 1, 'type' => 'script', 'utterance_id' => 'utt_0123456789abcdef',
    'delivery_state' => 'spoken', 'gamets' => 1, 'data' => 'Speaker: text',
]), 'Non-chat events must not normalize as acknowledged speech.');
same(null, normalizeEventRow([
    'rowid' => 1, 'type' => 'chat', 'utterance_id' => 'utt_0123456789abcdef',
    'gamets' => 1, 'data' => 'Speaker: text',
]), 'Missing delivery state must not be defaulted to emitted.');
same(null, normalizeEventRow([
    'rowid' => 1, 'type' => 'chat', 'utterance_id' => 'utt_0123456789abcdef',
    'delivery_state' => 'aborted', 'gamets' => 1, 'data' => 'Speaker: text',
]), 'Aborted chat events must not normalize as acknowledged speech.');
same(null, normalizeEventRow([
    'rowid' => 1, 'type' => 'chat', 'utterance_id' => 'utt_0123456789abcdef',
    'delivery_state' => 'pending', 'gamets' => 1, 'data' => 'Speaker: text',
]), 'Unknown chat delivery states must fail closed.');
same([
    'event_id' => 884,
    'utterance_id' => 'utt_0123456789abcdef',
    'gamets' => 12.5,
    'source_data' => 'Aela: I trust Jarl Balgruuf. (Talking to Lydia)',
    'delivery_state' => 'emitted',
], normalizeEventRow([
    'rowid' => '884', 'type' => 'chat', 'utterance_id' => 'utt_0123456789abcdef',
    'delivery_state' => 'emitted', 'gamets' => '12.5',
    'data' => 'Aela: I trust Jarl Balgruuf. (Talking to Lydia)',
]), 'Actual rowid-shaped eventlog rows must normalize using rowid as the event ID.');

[$event, $subjects, $judgments, $db] = baseFixture();
same('committed', persistJudgments($event, $subjects, $judgments, $db), 'Valid judgments should commit.');
same(100, $db->npcs[22]['extended_data']->relationships->{'Jarl Balgruuf'}->aff, 'Affinity must clamp at +100.');
same('ally', $db->npcs[22]['extended_data']->relationships->{'Jarl Balgruuf'}->type, 'Existing relation type must be preserved.');
same('existing', $db->npcs[22]['extended_data']->relationships->{'Jarl Balgruuf'}->note, 'Other fields on the changed edge must be preserved.');
same(true, $db->npcs[22]['extended_data']->relationships->{'Jarl Balgruuf'}->nested_empty instanceof stdClass, 'An empty object nested in an edge must remain an object.');
same(-2, $db->npcs[22]['extended_data']->relationships->Player->aff, 'The Player edge should receive its own judgment.');
same(-40, $db->npcs[22]['extended_data']->relationships->{'Other NPC'}->aff, 'Unrelated relationship edges must remain untouched.');
same(true, $db->npcs[22]['extended_data']->unrelated->empty_object instanceof stdClass, 'An unrelated empty object must not become an array.');
same('yes', $db->npcs[22]['plugin_extended_data']->other_plugin->keep, 'Other plugin namespaces must remain untouched.');
same(20.0, $db->npcs[22]['gamets_last_updated'], 'An out-of-order ACK must not move the whole-NPC timestamp backward.');
same(1, count($db->history), 'A committed event must create one full history snapshot.');
same('relationship', $db->history[0]['extended_data']->_chim_history_source, 'History snapshot must use the relationship marker.');
same(100, $db->history[0]['extended_data']->relationships->{'Jarl Balgruuf'}->aff, 'History must contain the committed relationship state.');

[$event, $subjects, $judgments, $db] = baseFixture();
$judgments['npc:33']['delta'] = 0;
$judgments['player']['delta'] = 0;
$beforeRelationships = clone $db->npcs[22]['extended_data']->relationships;
same('committed', persistJudgments($event, $subjects, $judgments, $db), 'Zero judgments still need an atomic dedupe snapshot.');
same(true, ChimMindPoisoning\sameJsonValue($beforeRelationships, $db->npcs[22]['extended_data']->relationships), 'Zero decisions must not write affinity.');
same(1, count($db->history), 'Zero decisions must still snapshot listener state.');

[$event, $subjects, $judgments, $db] = baseFixture();
$db->failSnapshot = true;
$beforeNpc = unserialize(serialize($db->npcs[22]));
same('failed', persistJudgments($event, $subjects, $judgments, $db), 'Snapshot failure must fail the commit.');
check(ChimMindPoisoning\sameJsonValue($beforeNpc, $db->npcs[22]), 'Snapshot failure must roll back affinity and dedupe state.');
same([], $db->history, 'A failed snapshot must not remain in history after rollback.');

[$event, $subjects, $judgments, $db] = baseFixture();
same('committed', persistJudgments($event, $subjects, $judgments, $db), 'First delivery should commit.');
$afterFirst = $db->npcs[22];
same('duplicate', persistJudgments($event, $subjects, $judgments, $db), 'The same listener/event pair must be idempotent.');
check(ChimMindPoisoning\sameJsonValue($afterFirst, $db->npcs[22]), 'A duplicate must not change state.');
same(1, count($db->history), 'A duplicate must not write another snapshot.');

[$event, $subjects, $judgments, $db] = baseFixture();
$db->profileId = '2';
same('stale', persistJudgments($event, $subjects, $judgments, $db), 'Profile switch after evaluation must reject the stale event.');
same([], $db->history, 'Stale profile must not snapshot.');

[$event, $subjects, $judgments, $db] = baseFixture();
$db->npcs[22]['extended_data']->relationships_locked = true;
same('locked', persistJudgments($event, $subjects, $judgments, $db), 'Manual relationship lock must prevail after model evaluation.');
same([], $db->history, 'Locked listener must not snapshot.');

[$event, $subjects, $judgments, $db] = baseFixture();
$db->npcs[22]['extended_data']->relationships_locked = 'false';
same('locked', persistJudgments($event, $subjects, $judgments, $db), 'Core treats any nonempty legacy lock value as locked.');

[$event, $subjects, $judgments, $db] = baseFixture();
$db->npcs[22]['lock_profile'] = 1;
same('locked', persistJudgments($event, $subjects, $judgments, $db), 'Core-locked profiles must fail closed.');
same([], $db->history, 'Core-locked listener must not snapshot.');

[$event, $subjects, $judgments, $db] = baseFixture();
$db->npcs[22]['extended_data'] = (object)['other_data' => (object)['empty' => new stdClass()]];
same('committed', persistJudgments($event, $subjects, $judgments, $db), 'A missing relationships parent must be created for a nonzero edge.');
same(5, $db->npcs[22]['extended_data']->relationships->{'Jarl Balgruuf'}->aff, 'The new edge should be written under a new relationships object.');
same(true, $db->npcs[22]['extended_data']->other_data->empty instanceof stdClass, 'Creating the parent must preserve unrelated JSON objects.');

[$event, $subjects, $judgments, $db] = baseFixture();
$db->npcs[22]['extended_data']->relationships->Dragonborn = (object)['aff' => 0, 'type' => 'friend', 'extra' => new stdClass()];
same('committed', persistJudgments($event, $subjects, $judgments, $db), 'One legacy Player alias should receive the update.');
same(-2, $db->npcs[22]['extended_data']->relationships->Dragonborn->aff, 'Existing Player alias affinity must update in place.');
check(!property_exists($db->npcs[22]['extended_data']->relationships, 'Player'), 'Do not create a duplicate canonical Player edge beside an alias.');

[$event, $subjects, $judgments, $db] = baseFixture();
$db->npcs[22]['extended_data']->relationships->Dragonborn = (object)['aff' => 0, 'type' => 'friend'];
$db->npcs[22]['extended_data']->relationships->Player = (object)['aff' => 0, 'type' => 'neutral'];
$beforeNpc = unserialize(serialize($db->npcs[22]));
same('failed', persistJudgments($event, $subjects, $judgments, $db), 'Multiple Player aliases must reject rather than guess which core reads.');
check(ChimMindPoisoning\sameJsonValue($beforeNpc, $db->npcs[22]), 'Ambiguous aliases must roll back without mutation.');

$ledger = ['playthrough_id' => '1', 'floor_event_id' => 0, 'events' => []];
for ($id = 1; $id <= 129; $id++) {
    $ledger = nextLedger($ledger, '1', $id, 'utt_' . str_pad((string)$id, 16, '0', STR_PAD_LEFT), [
        'player' => ['delta' => 0, 'reason' => 'neutral', 'evidence' => 'line'],
    ]);
}
same(128, count($ledger['events']), 'The idempotency ledger must remain bounded at 128 events.');
same(1, $ledger['floor_event_id'], 'Eviction must advance the numeric event floor.');
$belowFloorRejected = false;
try {
    nextLedger($ledger, '1', 1, 'utt_0000000000000001', [
        'player' => ['delta' => 0, 'reason' => 'neutral', 'evidence' => 'line'],
    ]);
} catch (RuntimeException) {
    $belowFloorRejected = true;
}
check($belowFloorRejected, 'Evicted event ids at or below the floor must not become eligible again.');

[$event, $subjects, $judgments, $db] = baseFixture();
$db->npcs[22]['plugin_extended_data']->mind_poisoning = (object)[
    'playthrough_id' => '1', 'floor_event_id' => 0,
    'events' => [['event_id' => 100, 'utterance_id' => $event['utterance_id']]],
];
check(eventAlreadyProcessed($db->npcs[22], '1', 100, $event['utterance_id']), 'Preflight should recognize an exact dedupe hit.');

$GLOBALS['runtime_test_interaction_allowed'] = true;
$GLOBALS['runtime_test_relationship_enabled'] = true;
$GLOBALS['RELLLM_CONNECTOR'] = 7;
[$event, $subjects, $judgments, $db] = baseFixture();
$ack = ['_speech', 0, 10, json_encode([
    'speaker' => 'Aela',
    'listener' => 'Lydia',
    'speech' => $event['text'],
    'utterance_id' => $event['utterance_id'],
], JSON_THROW_ON_ERROR)];
$modelCalls = 0;
$capturedMessages = null;
$model = static function (array $messages) use (&$modelCalls, &$capturedMessages): string {
    $modelCalls++;
    $capturedMessages = $messages;
    return json_encode(['judgments' => [
        ['subject' => 'npc:33', 'delta' => 2, 'reason' => 'Credible praise', 'evidence' => 'I trust Jarl Balgruuf.'],
        ['subject' => 'player', 'delta' => -1, 'reason' => 'Listener reaction', 'evidence' => 'The Dragonborn is brave.'],
    ]], JSON_THROW_ON_ERROR);
};
same('committed', handleSpeechAck($ack, $db, $model), 'A matching direct NPC speech acknowledgment should compose through persistence.');
same(1, $modelCalls, 'One acknowledged utterance should make one model request.');
$promptContext = json_decode($capturedMessages[1]['content'], true, 512, JSON_THROW_ON_ERROR)['untrusted_data'];
same(-50, $promptContext['listener_prior_relation_to_speaker']['aff'], 'The current listener credibility must reach model context.');
same(99, $promptContext['candidates']['npc:33']['listener_prior_relation']['aff'], 'The listener subject affinity must reach model context.');
same(25, $promptContext['candidates']['npc:33']['speaker_bias']['aff'], 'The speaker subject bias must reach model context.');
same(100, $db->npcs[22]['extended_data']->relationships->{'Jarl Balgruuf'}->aff, 'Composed hook should apply the validated NPC subject judgment.');
same(-1, $db->npcs[22]['extended_data']->relationships->Player->aff, 'Composed hook should apply the validated Player judgment.');
same(1, count($db->history), 'Composed hook should snapshot the changed listener once.');
same('duplicate', handleSpeechAck($ack, $db, $model), 'An acknowledged utterance should be deduped before another model call.');
same(1, $modelCalls, 'Preflight dedupe must avoid a second model call.');
same(1, count($db->history), 'Preflight duplicate must not create a second snapshot.');

[$event, $subjects, $judgments, $db] = baseFixture();
$event['text'] = 'I trust Jarl Balgruuf. The Dragonborn is brave.';
$event['source_data'] = 'Aela: I trust Jarl Balgruuf. The Dragonborn is brave. (Talking to Lydia)';
$db->events[100] = $event + ['type' => 'chat', 'delivery_state' => 'spoken'];
$truncatedAck = ['_speech', 0, 10, json_encode([
    'speaker' => 'Aela',
    'listener' => 'Lydia',
    'speech' => 'I trust Jarl Balgruuf.',
    'utterance_id' => $event['utterance_id'],
], JSON_THROW_ON_ERROR)];
$capturedAckMessages = null;
$ackTextModel = static function (array $messages) use (&$capturedAckMessages): string {
    $capturedAckMessages = $messages;
    return json_encode(['judgments' => [
        ['subject' => 'npc:33', 'delta' => 1, 'reason' => 'Credible praise', 'evidence' => 'I trust Jarl Balgruuf.'],
    ]], JSON_THROW_ON_ERROR);
};
same('committed', handleSpeechAck($truncatedAck, $db, $ackTextModel), 'A transformed ACK matching the exact utterance ID should not be rejected for text inequality.');
$ackPrompt = json_decode($capturedAckMessages[1]['content'], true, 512, JSON_THROW_ON_ERROR)['untrusted_data'];
same('I trust Jarl Balgruuf.', $ackPrompt['utterance']['text'], 'Only the client ACK speech may be used as the judged utterance.');
check(!array_key_exists('player', $ackPrompt['candidates']), 'Logged-only truncated tail must not add a Player candidate.');

foreach ([
    ['utterance_id', 'utt_ffffffffffffffff', 'event-unmatched'],
    ['speaker', 'Jarl Balgruuf', 'event-mismatch'],
    ['listener', 'Jarl Balgruuf', 'event-mismatch'],
] as [$field, $wrongValue, $expectedStatus]) {
    [$event, $subjects, $judgments, $db] = baseFixture();
    $payload = json_decode($truncatedAck[3], true, 32, JSON_THROW_ON_ERROR);
    $payload[$field] = $wrongValue;
    $wrongBindingAck = ['_speech', 0, 10, json_encode($payload, JSON_THROW_ON_ERROR)];
    $modelCalls = 0;
    $mustNotRunModel = static function (array $messages) use (&$modelCalls): string {
        $modelCalls++;
        return '{}';
    };
    same($expectedStatus, handleSpeechAck($wrongBindingAck, $db, $mustNotRunModel), 'Transformed ACKs must retain exact utterance and actor binding.');
    same(0, $modelCalls, 'A wrong utterance ID, speaker, or listener must not reach the model.');
    same([], $db->history, 'A wrong utterance ID, speaker, or listener must not create history.');
}

[$event, $subjects, $judgments, $db] = baseFixture();
resetInteractionTestRequest(1, 1, true, true);
$modelCalls = 0;
same('committed', handleSpeechAck($ack, $db, $model), 'Passive speech ACKs must remain eligible while the interaction switch is On.');
same(1, $modelCalls, 'A current passive ACK should reach the model once.');

[$event, $subjects, $judgments, $db] = baseFixture();
resetInteractionTestRequest(1, 1, false, true);
$modelCalls = 0;
same('interaction-off', handleSpeechAck($ack, $db, $model), 'Passive speech ACKs must still honor CHIM Off.');
same(0, $modelCalls, 'CHIM Off must reject a passive ACK before the model request.');

[$event, $subjects, $judgments, $db] = baseFixture();
resetInteractionTestRequest(1, 2, true, true);
$modelCalls = 0;
same('interaction-stale', handleSpeechAck($ack, $db, $model), 'A passive ACK from an older interaction generation must be rejected.');
same(0, $modelCalls, 'A stale interaction generation must not reach the model.');

[$event, $subjects, $judgments, $db] = baseFixture();
resetInteractionTestRequest(1, 1, true, true);
$modelCalls = 0;
$beforeInteractionChange = unserialize(serialize($db->npcs[22]));
$toggleOffModel = static function (array $messages) use (&$modelCalls): string {
    $modelCalls++;
    $GLOBALS['runtime_test_interaction_allowed'] = false;
    $GLOBALS['runtime_test_interaction_generation'] = 2;
    return json_encode(['judgments' => [
        ['subject' => 'npc:33', 'delta' => 1, 'reason' => 'Credible praise', 'evidence' => 'I trust Jarl Balgruuf.'],
        ['subject' => 'player', 'delta' => -1, 'reason' => 'Listener reaction', 'evidence' => 'The Dragonborn is brave.'],
    ]], JSON_THROW_ON_ERROR);
};
same('interaction-stale', handleSpeechAck($ack, $db, $toggleOffModel), 'An Off/generation change during model evaluation must reject persistence.');
same(1, $modelCalls, 'A request interrupted during evaluation should make only its original model call.');
check(ChimMindPoisoning\sameJsonValue($beforeInteractionChange, $db->npcs[22]), 'An Off/generation change during evaluation must not mutate listener state.');
same([], $db->history, 'An Off/generation change during evaluation must not snapshot the listener.');
resetInteractionTestRequest(2, 2, true, false);

[$event, $subjects, $judgments, $db] = baseFixture();
$db->npcs[22]['extended_data']->relationships->Dragonborn = (object)['aff' => 0, 'type' => 'friend'];
$db->npcs[22]['extended_data']->relationships->Player = (object)['aff' => 0, 'type' => 'neutral'];
$beforeAmbiguousPlayer = unserialize(serialize($db->npcs[22]));
$modelCalls = 0;
$ambiguousStatus = handleSpeechAck($ack, $db, $model);
same(0, $modelCalls, 'Ambiguous Player aliases must not spend a model request.');
same('listener-invalid', $ambiguousStatus, 'Ambiguous Player aliases must be rejected before model evaluation.');
check(ChimMindPoisoning\sameJsonValue($beforeAmbiguousPlayer, $db->npcs[22]), 'The pre-model alias gate must preserve the listener relationship map.');
same([], $db->history, 'Ambiguous Player aliases must not create a snapshot.');

[$event, $subjects, $judgments, $db] = baseFixture();
$event['text'] = 'I trust Jarl Balgruuf.';
$event['source_data'] = 'Aela: I trust Jarl Balgruuf. (Talking to Lydia)';
$db->events[100] = $event + ['type' => 'chat', 'delivery_state' => 'spoken'];
$db->npcs[22]['extended_data']->relationships->Dragonborn = (object)['aff' => 7, 'type' => 'friend'];
$db->npcs[22]['extended_data']->relationships->Player = (object)['aff' => -3, 'type' => 'neutral'];
$npcOnlyWithAliasesAck = ['_speech', 0, 10, json_encode([
    'speaker' => 'Aela',
    'listener' => 'Lydia',
    'speech' => $event['text'],
    'utterance_id' => $event['utterance_id'],
], JSON_THROW_ON_ERROR)];
$modelCalls = 0;
$npcOnlyWithAliasesModel = static function (array $messages) use (&$modelCalls): string {
    $modelCalls++;
    return json_encode(['judgments' => [
        ['subject' => 'npc:33', 'delta' => 1, 'reason' => 'Credible praise', 'evidence' => 'I trust Jarl Balgruuf.'],
    ]], JSON_THROW_ON_ERROR);
};
same('committed', handleSpeechAck($npcOnlyWithAliasesAck, $db, $npcOnlyWithAliasesModel), 'NPC-only judgments must remain eligible with ambiguous Player aliases.');
same(1, $modelCalls, 'NPC-only judgments should still reach the model.');
same(7, $db->npcs[22]['extended_data']->relationships->Dragonborn->aff, 'NPC-only updates must leave the legacy Player alias unchanged.');
same(-3, $db->npcs[22]['extended_data']->relationships->Player->aff, 'NPC-only updates must leave the canonical Player alias unchanged.');

[$event, $subjects, $judgments, $db] = baseFixture();
$event['player_name'] = 'Dovah';
$event['text'] = 'I trust Jarl Balgruuf. Dovah is brave.';
$event['source_data'] = 'Aela: I trust Jarl Balgruuf. Dovah is brave. (Talking to Lydia)';
$db->playerName = 'Dovah';
$db->events[100] = $event + ['type' => 'chat', 'delivery_state' => 'spoken'];
$playerAck = ['_speech', 0, 10, json_encode([
    'speaker' => 'Aela',
    'listener' => 'Lydia',
    'speech' => $event['text'],
    'utterance_id' => $event['utterance_id'],
], JSON_THROW_ON_ERROR)];
$beforePlayerDrift = unserialize(serialize($db->npcs[22]));
$renamingModel = static function (array $messages) use ($db): string {
    $db->playerName = 'Dovah Prime';
    return json_encode(['judgments' => [
        ['subject' => 'npc:33', 'delta' => 1, 'reason' => 'Credible praise', 'evidence' => 'I trust Jarl Balgruuf.'],
        ['subject' => 'player', 'delta' => -1, 'reason' => 'Named Player reaction', 'evidence' => 'Dovah is brave.'],
    ]], JSON_THROW_ON_ERROR);
};
same('stale', handleSpeechAck($playerAck, $db, $renamingModel), 'A same-profile Player rename during evaluation must reject stale Player identity context.');
check(ChimMindPoisoning\sameJsonValue($beforePlayerDrift, $db->npcs[22]), 'A same-profile Player rename must not mutate listener state.');
same([], $db->history, 'A same-profile Player rename must not create a history snapshot.');

[$event, $subjects, $judgments, $db] = baseFixture();
$event['player_name'] = 'Dovah';
$event['text'] = 'I trust Jarl Balgruuf.';
$event['source_data'] = 'Aela: I trust Jarl Balgruuf. (Talking to Lydia)';
$db->playerName = 'Dovah';
$db->events[100] = $event + ['type' => 'chat', 'delivery_state' => 'spoken'];
$npcOnlyAck = ['_speech', 0, 10, json_encode([
    'speaker' => 'Aela',
    'listener' => 'Lydia',
    'speech' => $event['text'],
    'utterance_id' => $event['utterance_id'],
], JSON_THROW_ON_ERROR)];
$npcOnlyModel = static function (array $messages) use ($db): string {
    $db->playerName = 'Dovah Prime';
    return json_encode(['judgments' => [
        ['subject' => 'npc:33', 'delta' => 1, 'reason' => 'Credible praise', 'evidence' => 'I trust Jarl Balgruuf.'],
    ]], JSON_THROW_ON_ERROR);
};
same('committed', handleSpeechAck($npcOnlyAck, $db, $npcOnlyModel), 'A same-profile Player rename must not reject an NPC-only judgment.');
same(1, count($db->history), 'NPC-only judgments remain snapshot eligible after a Player rename.');

[$event, $subjects, $judgments, $db] = baseFixture();
$db->npcs[22]['plugin_extended_data']->mind_poisoning = 'corrupt';
$beforeMalformedLedger = unserialize(serialize($db->npcs[22]));
same('invalid', persistJudgments($event, $subjects, $judgments, $db), 'A present malformed ledger namespace must fail closed rather than reset dedupe state.');
check(ChimMindPoisoning\sameJsonValue($beforeMalformedLedger, $db->npcs[22]), 'Malformed ledger state must not mutate the listener.');
same([], $db->history, 'Malformed ledger state must not create a history snapshot.');
check(eventAlreadyProcessed($db->npcs[22], '1', 100, $event['utterance_id']), 'Preflight must fail closed for a present malformed ledger namespace.');
foreach ([null, new stdClass()] as $malformedLedger) {
    [$event, $subjects, $judgments, $db] = baseFixture();
    $db->npcs[22]['plugin_extended_data']->mind_poisoning = $malformedLedger;
    same('invalid', persistJudgments($event, $subjects, $judgments, $db), 'Present null and empty-object ledgers must fail closed.');
    check(eventAlreadyProcessed($db->npcs[22], '1', 100, $event['utterance_id']), 'Preflight must fail closed for present null and empty-object ledgers.');
    same([], $db->history, 'Invalid present ledger state must not create history.');
}

[$event, $subjects, $judgments, $db] = baseFixture();
$db->npcs[22]['plugin_extended_data']->mind_poisoning = (object)[
    'playthrough_id' => '2', 'floor_event_id' => 0, 'events' => [],
];
same('committed', persistJudgments($event, $subjects, $judgments, $db), 'A valid ledger from another playthrough may start a fresh playthrough ledger.');

[$event, $subjects, $judgments, $db] = baseFixture();
$modelCalls = 0;
same('ignored', handleSpeechAck(['_speech_abort', 0, 10, $ack[3]], $db, $model), 'Abort acknowledgments must not enter the evaluation path.');
same(0, $modelCalls, 'Abort acknowledgment must not call the model.');
$GLOBALS['runtime_test_interaction_allowed'] = false;
same('interaction-off', handleSpeechAck($ack, $db, $model), 'The hook must honor CHIM interaction Off before the core Off handler runs.');
same(0, $modelCalls, 'Interaction Off must skip model evaluation.');
$GLOBALS['runtime_test_interaction_allowed'] = true;
$GLOBALS['runtime_test_relationship_enabled'] = false;
same('disabled', handleSpeechAck($ack, $db, $model), 'The global relationship setting must gate evaluation.');
same(0, $modelCalls, 'Disabled relationship processing must not call the model.');
$GLOBALS['runtime_test_relationship_enabled'] = true;
unset($GLOBALS['RELLLM_CONNECTOR']);
same('connector-invalid', handleSpeechAck($ack, $db, $model), 'A missing relationship connector ID must fail closed before evaluation.');
same(0, $modelCalls, 'A missing connector must not call the model.');
$GLOBALS['RELLLM_CONNECTOR'] = 7;

[$event, $subjects, $judgments, $db] = baseFixture();
$db->events[100]['source_data'] = 'Aela: I trust Jarl Balgruuf. The Dragonborn is brave. (Talking to Everyone)';
$modelCalls = 0;
same('event-mismatch', handleSpeechAck($ack, $db, $model), 'Broadcast events must not be assigned to one direct listener.');
same(0, $modelCalls, 'Broadcast event must not call the model.');

[$event, $subjects, $judgments, $db] = baseFixture();
$db->npcs[22]['extended_data']->relationships_locked = 'false';
$modelCalls = 0;
same('locked', handleSpeechAck($ack, $db, $model), 'Legacy nonempty relationship lock values must gate before model evaluation.');
same(0, $modelCalls, 'Locked listener must not call the model.');

[$event, $subjects, $judgments, $db] = baseFixture();
$GLOBALS['NEVER_CLEAR_RELATIONSHIP_DATA'] = true;
$modelCalls = 0;
same('restore-policy', handleSpeechAck($ack, $db, $model), 'Relationship-preserving restore mode must skip before model evaluation.');
same(0, $modelCalls, 'Restore-policy gate must not call the model.');
unset($GLOBALS['NEVER_CLEAR_RELATIONSHIP_DATA']);

[$event, $subjects, $judgments, $db] = baseFixture();
$GLOBALS['NEVER_CLEAR_RELATIONSHIP_DATA'] = 'false';
same('committed', handleSpeechAck($ack, $db, $model), 'A string false restore setting must retain core boolean semantics.');
unset($GLOBALS['NEVER_CLEAR_RELATIONSHIP_DATA']);

[$event, $subjects, $judgments, $db] = baseFixture();
$modelCalls = 0;
$badModel = static function (array $messages) use (&$modelCalls): string {
    $modelCalls++;
    return '{';
};
same('failed', handleSpeechAck($ack, $db, $badModel), 'Invalid model output must fail closed at composition.');
same(1, $modelCalls, 'Malformed model output should be rejected after one request.');
same([], $db->history, 'Invalid model output must not snapshot or persist.');

$GLOBALS['NEVER_CLEAR_RELATIONSHIP_DATA'] = true;
[$event, $subjects, $judgments, $db] = baseFixture();
same('restore-policy', persistJudgments($event, $subjects, $judgments, $db), 'Unsafe relationship-preserving restore policy must fail closed.');
same(0, $db->beginCalls, 'Restore-policy rejection must happen before transaction begin.');
unset($GLOBALS['NEVER_CLEAR_RELATIONSHIP_DATA']);

unset($GLOBALS['runtime_test_interaction_allowed'], $GLOBALS['runtime_test_relationship_enabled'], $GLOBALS['RELLLM_CONNECTOR']);

echo "runtime store checks passed\n";
