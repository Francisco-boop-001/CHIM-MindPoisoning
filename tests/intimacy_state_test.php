<?php
declare(strict_types=1);

require_once __DIR__ . '/../server/store.php';
require_once __DIR__ . '/../server/controls.php';
require_once __DIR__ . '/../server/intimacy.php';

function chimInteractionBegin(): void
{
    $GLOBALS['chim_interaction_generation'] ??= 1;
}

function chimInteractionState(): array
{
    return ['enabled' => true, 'generation' => 1];
}

function chimGetGeneralSettingBool(string $id, bool $default = false): bool
{
    return $id === 'mind_poisoning_intimacy_enabled'
        ? (($GLOBALS['intimacy_enabled'] ?? false) === true)
        : $default;
}

function extractSpeakerNameFromChatEvent(string $sourceData): string
{
    $colon = strpos($sourceData, ':');
    return $colon === false ? '' : trim(substr($sourceData, 0, $colon));
}

function extractTalkTargetMetadata(string $sourceData): array
{
    if (preg_match('/\(Talking to ([^()]*)\)\s*$/u', $sourceData, $matches) !== 1) {
        return ['hasExplicitTarget' => false, 'isBroadcast' => true, 'targets' => []];
    }
    return ['hasExplicitTarget' => true, 'isBroadcast' => false, 'targets' => [trim($matches[1])]];
}

function intimacySame(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . "\nexpected: " . var_export($expected, true) . "\nactual: " . var_export($actual, true));
    }
}

function intimacyCloneRow(array $row): array
{
    return unserialize(serialize($row), ['allowed_classes' => true]);
}

final class IntimacyMemoryStore implements ChimMindPoisoning\StoreDb
{
    public array $npcs = [];
    public array $events = [];
    public array $writeArgs = [];
    public ?array $snapshot = null;
    public int $lockedOwner = 0;
    public int $writeCount = 0;
    public bool $committed = false;
    public bool $failWrite = false;
    public bool $failCommit = false;
    public int $rollbackCount = 0;
    private array $transactionRows = [];

    public function activePlaythrough(): ?array
    {
        return ['id' => '1', 'player_name' => 'Dragonborn'];
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

    public function playerInputEvent(array $source): ?array
    {
        return null;
    }

    public function eventById(int $eventId, string $utteranceId): ?array
    {
        $event = $this->events[$eventId] ?? null;
        return is_array($event) && ($event['utterance_id'] ?? null) === $utteranceId ? $event : null;
    }

    public function npcIdentities(): array
    {
        return array_values(array_map(static fn(array $npc): array => ['id' => $npc['id'], 'npc_name' => $npc['npc_name']], $this->npcs));
    }

    public function npcById(int $npcId, bool $forUpdate = false): ?array
    {
        return $this->npcs[$npcId] ?? null;
    }

    public function reflectionHistory(string $actorName, int $beforeEventId): array
    {
        return [];
    }

    public function beginForListener(int $listenerId): bool
    {
        $this->lockedOwner = $listenerId;
        $this->transactionRows = unserialize(serialize($this->npcs), ['allowed_classes' => true]);
        return true;
    }

    public function writeNpc(int $npcId, array $relationshipEdges, object $mindPoisoningData, float $gamets, array $relationshipKeysToRemove = []): bool
    {
        $this->writeCount++;
        $this->writeArgs = [$relationshipEdges, $relationshipKeysToRemove];
        if ($this->failWrite) {
            return false;
        }
        if (!isset($this->npcs[$npcId])) {
            return false;
        }
        $this->npcs[$npcId]['plugin_extended_data']->mind_poisoning = $mindPoisoningData;
        $this->npcs[$npcId]['gamets_last_updated'] = $gamets;
        return true;
    }

    public function backupAndVerify(int $npcId, array $expected): bool
    {
        $this->snapshot = $expected;
        return isset($this->npcs[$npcId]);
    }

    public function commit(): bool
    {
        if ($this->failCommit) {
            return false;
        }
        $this->committed = true;
        return true;
    }

    public function rollback(): void
    {
        $this->rollbackCount++;
        if ($this->transactionRows !== []) {
            $this->npcs = $this->transactionRows;
        }
    }

    public function release(): void
    {
    }
}

$source = ['event_id' => 100, 'utterance_id' => 'utt_0123456789abcdef', 'speech_hash' => str_repeat('a', 64)];
intimacySame(true, ChimMindPoisoning\validIntimacyChange(['status' => 'casual', 'evidence' => str_repeat('x', 120)], str_repeat('x', 120)), 'Arrangement evidence at the documented 120-byte limit should validate.');
intimacySame(false, ChimMindPoisoning\validIntimacyChange(['status' => 'casual', 'evidence' => str_repeat('x', 121)], str_repeat('x', 121)), 'Arrangement evidence above the documented 120-byte limit should be rejected before transition.');
$transition = ChimMindPoisoning\mindPoisoningTransitionIntimacyArrangement([], 'npc:33', ['status' => 'casual', 'evidence' => 'We have an arrangement.'], [
    'owner_id' => 11,
    'basis' => 'direct_npc_dialogue',
    'playthrough_id' => '1',
    'sources' => [$source],
    'gamets' => 10.0,
]);

intimacySame('applied', $transition['outcome'], 'A valid first arrangement should be accepted.');
intimacySame('casual', $transition['arrangements']['npc:33']['kind'] ?? null, 'The map should retain the closed arrangement kind.');
$sameScopeLedger = ChimMindPoisoning\nextLedger([
    'playthrough_id' => '1',
    'floor_event_id' => 0,
    'events' => [],
    'arrangements' => $transition['arrangements'],
], '1', 104, 'utt_0123456789abcd10', []);
intimacySame($transition['arrangements'], $sameScopeLedger['arrangements'] ?? null, 'Same-scope ledger updates must carry the arrangement map forward.');
$newScopeLedger = ChimMindPoisoning\nextLedger([
    'playthrough_id' => '1',
    'floor_event_id' => 0,
    'events' => [],
    'arrangements' => $transition['arrangements'],
], '2', 105, 'utt_0123456789abcd11', []);
intimacySame(false, array_key_exists('arrangements', $newScopeLedger), 'A new-scope ledger baseline must clear arrangements.');
$fullMap = [];
for ($index = 1; $index <= 64; $index++) {
    $subject = 'npc:' . (1000 + $index);
    $fullMap[$subject] = [
        'owner_id' => 11,
        'target' => $subject,
        'playthrough_id' => '1',
        'kind' => 'casual',
        'basis' => 'direct_npc_dialogue',
        'sources' => [[
            'event_id' => $index,
            'utterance_id' => sprintf('utt_%012d', $index),
            'speech_hash' => str_repeat('a', 64),
        ]],
        'gamets' => (float)$index,
    ];
}
$capacityTransition = ChimMindPoisoning\mindPoisoningTransitionIntimacyArrangement($fullMap, 'npc:2000', ['status' => 'romantic', 'evidence' => 'A new arrangement.'], [
    'owner_id' => 11,
    'basis' => 'direct_npc_dialogue',
    'playthrough_id' => '1',
    'sources' => [[
        'event_id' => 65,
        'utterance_id' => 'utt_0123456789abcdee',
        'speech_hash' => str_repeat('b', 64),
    ]],
    'gamets' => 65.0,
]);
intimacySame('capacity', $capacityTransition['outcome'], 'The arrangement map must refuse a new target after 64 entries.');

$enginePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mp-intimacy-state-' . getmypid();
if (!is_dir($enginePath) && !mkdir($enginePath) && !is_dir($enginePath)) {
    throw new RuntimeException('Could not create the temporary pause-control fixture.');
}
register_shutdown_function(static function () use ($enginePath): void {
    @unlink($enginePath . DIRECTORY_SEPARATOR . 'main.php');
    @rmdir($enginePath);
});
file_put_contents($enginePath . DIRECTORY_SEPARATOR . 'main.php', "<?php\n");
$GLOBALS['ENGINE_PATH'] = $enginePath . DIRECTORY_SEPARATOR;
$GLOBALS['chim_interaction_generation'] = 1;
$GLOBALS['intimacy_enabled'] = true;
$legacyEdge = (object)['aff' => 37, 'type' => 'friend', 'notes' => 'Keep these exact notes.'];
$playerAlias = (object)['aff' => -8, 'type' => 'enemy', 'notes' => 'Legacy alias survives.'];
$extended = (object)['relationships' => (object)['Player' => $legacyEdge, 'Dragonborn' => $playerAlias]];
$initialExtendedBytes = serialize($extended);
$ownerRow = [
    'id' => 11,
    'npc_name' => 'Aela',
    'extended_data' => $extended,
    'plugin_extended_data' => new stdClass(),
    'gamets_last_updated' => 5.0,
];
$targetRow = [
    'id' => 22,
    'npc_name' => 'Lydia',
    'extended_data' => (object)['relationships' => new stdClass()],
    'plugin_extended_data' => new stdClass(),
    'gamets_last_updated' => 0.0,
];
$speech = 'We have a casual arrangement.';
$utterance = 'utt_intimacy_state_0001';
$sourceData = 'Aela: ' . $speech . ' (Talking to Lydia)';
$event = [
    'event_id' => 101,
    'utterance_id' => $utterance,
    'gamets' => 25.0,
    'source_data' => $sourceData,
    'delivery_state' => 'spoken',
    'speaker_kind' => 'npc',
];
$intimacyEvent = [
    'event_id' => 101,
    'utterance_id' => $utterance,
    'speaker_kind' => 'npc',
    'speaker_id' => 11,
    'speaker_name' => 'Aela',
    'listener_id' => 22,
    'listener_name' => 'Lydia',
    'listener_role' => 'addressed',
    'addressed_listener_id' => 22,
    'addressed_listener_name' => 'Lydia',
    'opinion_owner_id' => 11,
    'source_kind' => 'intimacy',
    'target_subject' => 'npc:22',
    'interaction_generation' => 1,
    'speech_hash' => hash('sha256', $speech),
    'intimacy_change' => ['status' => 'casual', 'evidence' => $speech],
    'intimacy_expected_record' => null,
    'text' => $speech,
    'playthrough_id' => '1',
    'player_name' => 'Dragonborn',
    'source_data' => $sourceData,
    'gamets' => 25.0,
];
$intimacyEvent['ack_identity'] = ChimMindPoisoning\mindPoisoningIntimacyAckIdentity($intimacyEvent);
$memory = new IntimacyMemoryStore();
$memory->npcs = [11 => intimacyCloneRow($ownerRow), 22 => intimacyCloneRow($targetRow)];
$memory->events = [101 => $event];
$logRows = [];
$requestLog = new ChimMindPoisoning\RequestLog(static function (string $json, string $_level) use (&$logRows): void {
    $row = json_decode($json, true);
    if (is_array($row)) {
        $logRows[] = $row;
    }
}, false);
$status = ChimMindPoisoning\persistJudgments($intimacyEvent, [], [], $memory, $requestLog);
intimacySame('committed', $status, 'A valid direct arrangement should commit on the speaker owner row.');
intimacySame(11, $memory->lockedOwner, 'The speaker must own the only arrangement transaction.');
intimacySame([[], []], $memory->writeArgs, 'An arrangement-only write must not pass relationship edges or removals to the store.');
intimacySame($initialExtendedBytes, serialize($memory->npcs[11]['extended_data']), 'Core affinity, type, notes, and Player aliases must remain byte-equivalent.');
intimacySame('casual', $memory->npcs[11]['plugin_extended_data']->mind_poisoning->arrangements['npc:22']['kind'] ?? null, 'The speaker plugin namespace must receive the arrangement.');
intimacySame(null, $memory->npcs[22]['plugin_extended_data']->mind_poisoning ?? null, 'The listener row must not receive the speaker arrangement.');
intimacySame('intimacy', $memory->npcs[11]['plugin_extended_data']->mind_poisoning->events[0]['source_kind'] ?? null, 'The speaker ledger must retain the direct idempotency receipt.');
$persistenceLog = array_values(array_filter($logRows, static fn(array $row): bool => ($row['event'] ?? null) === 'persistence_finished'))[0] ?? [];
intimacySame('intimacy', $persistenceLog['source_kind'] ?? null, 'Safe persistence logs must retain the fixed intimacy source kind.');
intimacySame(1, $persistenceLog['arrangement_changed_count'] ?? null, 'Safe persistence logs must count a confirmed arrangement change.');
intimacySame('applied', $persistenceLog['arrangement_changes'][0]['outcome'] ?? null, 'Safe persistence logs must retain the fixed arrangement outcome.');
intimacySame(false, array_key_exists('speech_hash', $persistenceLog), 'Persistence logs must not expose source hashes.');
intimacySame(false, array_key_exists('text', $persistenceLog), 'Persistence logs must not expose source text.');
intimacySame(false, array_key_exists('evidence', $persistenceLog), 'Persistence logs must not expose model evidence.');

$expectedCurrentRecord = ChimMindPoisoning\mindPoisoningCaptureIntimacyRecord($memory->npcs[11], 'npc:22', '1');
$staleOwnerRow = intimacyCloneRow($memory->npcs[11]);
$staleOwnerRow['plugin_extended_data']->mind_poisoning->arrangements['npc:22']['kind'] = 'romantic';
$staleStore = new IntimacyMemoryStore();
$staleStore->npcs = [11 => $staleOwnerRow, 22 => intimacyCloneRow($memory->npcs[22])];
$staleSource = $event;
$staleSource['event_id'] = 102;
$staleSource['utterance_id'] = 'utt_intimacy_state_0002';
$staleStore->events = [102 => $staleSource];
$staleEvent = $intimacyEvent;
$staleEvent['event_id'] = 102;
$staleEvent['utterance_id'] = 'utt_intimacy_state_0002';
$staleEvent['intimacy_expected_record'] = $expectedCurrentRecord;
$staleEvent['ack_identity'] = ChimMindPoisoning\mindPoisoningIntimacyAckIdentity($staleEvent);
intimacySame('stale', ChimMindPoisoning\persistIntimacyArrangement(
    $staleEvent,
    ['status' => 'romantic', 'evidence' => $speech],
    $expectedCurrentRecord,
    $staleStore
), 'A full-record CAS mismatch must reject the direct state write.');
intimacySame(0, $staleStore->writeCount, 'A stale full-record CAS must perform no row write.');
intimacySame('romantic', $staleStore->npcs[11]['plugin_extended_data']->mind_poisoning->arrangements['npc:22']['kind'] ?? null, 'A rejected CAS must preserve the concurrent record as observed.');

$siblingOwnerRow = intimacyCloneRow($memory->npcs[11]);
$siblingLedger = $siblingOwnerRow['plugin_extended_data']->mind_poisoning;
$siblingTransition = ChimMindPoisoning\mindPoisoningTransitionIntimacyArrangement(
    $siblingLedger->arrangements,
    'npc:33',
    ['status' => 'romantic', 'evidence' => 'We have another arrangement.'],
    [
        'owner_id' => 11,
        'basis' => 'direct_npc_dialogue',
        'playthrough_id' => '1',
        'sources' => [[
            'event_id' => 95,
            'utterance_id' => 'utt_intimacy_sibling_0001',
            'speech_hash' => hash('sha256', 'We have another arrangement.'),
        ]],
        'gamets' => 20.0,
    ]
);
$siblingLedger->arrangements = $siblingTransition['arrangements'];
$siblingBefore = $siblingTransition['arrangements']['npc:33'];
$siblingStore = new IntimacyMemoryStore();
$siblingStore->npcs = [11 => $siblingOwnerRow, 22 => intimacyCloneRow($memory->npcs[22])];
$siblingSource = $event;
$siblingSource['event_id'] = 103;
$siblingSource['utterance_id'] = 'utt_intimacy_state_0003';
$siblingStore->events = [103 => $siblingSource];
$siblingExpectedRecord = ChimMindPoisoning\mindPoisoningCaptureIntimacyRecord($siblingOwnerRow, 'npc:22', '1');
$siblingEvent = $intimacyEvent;
$siblingEvent['event_id'] = 103;
$siblingEvent['utterance_id'] = 'utt_intimacy_state_0003';
$siblingEvent['intimacy_expected_record'] = $siblingExpectedRecord;
$siblingEvent['ack_identity'] = ChimMindPoisoning\mindPoisoningIntimacyAckIdentity($siblingEvent);
intimacySame('committed', ChimMindPoisoning\persistIntimacyArrangement(
    $siblingEvent,
    ['status' => 'romantic', 'evidence' => $speech],
    $siblingExpectedRecord,
    $siblingStore
), 'A matching CAS should commit the target update.');
$siblingAfter = $siblingStore->npcs[11]['plugin_extended_data']->mind_poisoning->arrangements['npc:33'] ?? null;
if (is_array($siblingAfter)) {
    $siblingBefore['gamets'] = (float)$siblingBefore['gamets'];
    $siblingAfter['gamets'] = (float)$siblingAfter['gamets'];
}
intimacySame($siblingBefore, $siblingAfter, 'Updating one target must preserve the owner’s unrelated arrangement-map siblings.');

$oldScopeTransition = ChimMindPoisoning\mindPoisoningTransitionIntimacyArrangement([], 'npc:33', ['status' => 'romantic', 'evidence' => 'An older arrangement.'], [
    'owner_id' => 11,
    'basis' => 'direct_npc_dialogue',
    'playthrough_id' => 'old-scope',
    'sources' => [[
        'event_id' => 90,
        'utterance_id' => 'utt_0123456789abcd12',
        'speech_hash' => str_repeat('d', 64),
    ]],
    'gamets' => 15.0,
]);
$oldScopeOwner = intimacyCloneRow($ownerRow);
$oldScopeOwner['plugin_extended_data']->mind_poisoning = (object)[
    'playthrough_id' => 'old-scope',
    'floor_event_id' => 0,
    'events' => [],
    'arrangements' => $oldScopeTransition['arrangements'],
];
$newScopeStore = new IntimacyMemoryStore();
$newScopeStore->npcs = [11 => $oldScopeOwner, 22 => intimacyCloneRow($targetRow)];
$newScopeStore->events = [101 => $event];
$GLOBALS['NEVER_CLEAR_RELATIONSHIP_DATA'] = false;
intimacySame('committed', ChimMindPoisoning\persistJudgments($intimacyEvent, [], [], $newScopeStore), 'An ordinary new-scope direct source should commit against a fresh baseline.');
$newScopeLedger = $newScopeStore->npcs[11]['plugin_extended_data']->mind_poisoning;
intimacySame(false, property_exists($newScopeLedger, 'arrangements') && array_key_exists('npc:33', (array)$newScopeLedger->arrangements), 'A mismatched-scope baseline must discard the old arrangement map.');
intimacySame(101, $newScopeLedger->arrangements['npc:22']['sources'][0]['event_id'] ?? null, 'The new-scope direct arrangement must cite the current source.');
$nullClaimStore = new IntimacyMemoryStore();
$nullClaimStore->npcs = [11 => intimacyCloneRow($ownerRow), 22 => intimacyCloneRow($targetRow)];
$nullClaimStore->events = [101 => $event];
$nullClaimEvent = $intimacyEvent;
$nullClaimEvent['intimacy_change'] = null;
$nullClaimEvent['intimacy_expected_record'] = null;
$nullClaimEvent['ack_identity'] = ChimMindPoisoning\mindPoisoningIntimacyAckIdentity($nullClaimEvent);
intimacySame('committed', ChimMindPoisoning\persistJudgments($nullClaimEvent, [], [], $nullClaimStore), 'A null direct result must commit its dedupe receipt.');
intimacySame(null, $nullClaimStore->npcs[11]['plugin_extended_data']->mind_poisoning->arrangements['npc:22'] ?? null, 'A null direct result must not create arrangement state.');
intimacySame('no_claim', $nullClaimStore->npcs[11]['plugin_extended_data']->mind_poisoning->events[0]['arrangement_changes'][0]['outcome'] ?? null, 'A null direct result must retain a fixed no-claim history outcome.');
intimacySame(null, $nullClaimStore->npcs[11]['plugin_extended_data']->mind_poisoning->events[0]['arrangement_changes'][0]['proposal'] ?? null, 'A null direct result must not retain a proposed kind.');
$nullClaimWrites = $nullClaimStore->writeCount;
intimacySame('duplicate', ChimMindPoisoning\persistJudgments($nullClaimEvent, [], [], $nullClaimStore), 'A repeated null result must be idempotently rejected.');
intimacySame($nullClaimWrites, $nullClaimStore->writeCount, 'A repeated null result must not write again.');
$failedWriteStore = new IntimacyMemoryStore();
$failedWriteStore->npcs = [11 => intimacyCloneRow($ownerRow), 22 => intimacyCloneRow($targetRow)];
$failedWriteStore->events = [101 => $event];
$failedWriteStore->failWrite = true;
intimacySame('failed', ChimMindPoisoning\persistJudgments($intimacyEvent, [], [], $failedWriteStore), 'A failed write must not report a committed arrangement.');
intimacySame(1, $failedWriteStore->rollbackCount, 'A failed write must roll back the owner transaction.');
intimacySame(null, $failedWriteStore->npcs[11]['plugin_extended_data']->mind_poisoning ?? null, 'A failed write must leave the owner state unchanged.');
$failedCommitStore = new IntimacyMemoryStore();
$failedCommitStore->npcs = [11 => intimacyCloneRow($ownerRow), 22 => intimacyCloneRow($targetRow)];
$failedCommitStore->events = [101 => $event];
$failedCommitStore->failCommit = true;
$failedCommitLogs = [];
$failedCommitLog = new ChimMindPoisoning\RequestLog(static function (string $json, string $_level) use (&$failedCommitLogs): void {
    $row = json_decode($json, true);
    if (is_array($row)) {
        $failedCommitLogs[] = $row;
    }
}, false);
intimacySame('failed', ChimMindPoisoning\persistJudgments($intimacyEvent, [], [], $failedCommitStore, $failedCommitLog), 'A rejected commit must report failure.');
intimacySame(1, $failedCommitStore->rollbackCount, 'A rejected commit must attempt rollback.');
intimacySame(null, $failedCommitStore->npcs[11]['plugin_extended_data']->mind_poisoning ?? null, 'A rejected commit must restore the synthetic row.');
$failedCommitPersistence = array_values(array_filter($failedCommitLogs, static fn(array $row): bool => ($row['event'] ?? null) === 'persistence_finished'))[0] ?? [];
intimacySame('unconfirmed', $failedCommitPersistence['commit_state'] ?? null, 'A rejected commit must remain unconfirmed in the log.');
intimacySame(0, $failedCommitPersistence['arrangement_changed_count'] ?? null, 'Unconfirmed changes must not be reported as applied.');
$writesBeforeRead = $memory->writeCount;
$projection = ChimMindPoisoning\mindPoisoningReadIntimacyArrangement(11, 'npc:22', $memory);
intimacySame('active', $projection['state'] ?? null, 'The public API must expose the committed direct arrangement as active.');
intimacySame('casual', $projection['kind'] ?? null, 'The public API must expose the committed arrangement kind.');
intimacySame($writesBeforeRead, $memory->writeCount, 'Public intimacy reads must not write state.');
$endedTransition = ChimMindPoisoning\mindPoisoningTransitionIntimacyArrangement(
    $memory->npcs[11]['plugin_extended_data']->mind_poisoning->arrangements,
    'npc:22',
    ['status' => 'none', 'evidence' => 'Our arrangement ended.'],
    [
        'owner_id' => 11,
        'basis' => 'registered_reflection',
        'playthrough_id' => '1',
        'sources' => [[
            'event_id' => 102,
            'utterance_id' => 'utt_0123456789abcdee',
            'speech_hash' => str_repeat('b', 64),
        ]],
        'gamets' => 26.0,
    ]
);
intimacySame('ended', $endedTransition['outcome'], 'An explicit current ending should be retained as an ended arrangement.');
$reaffirmedEnd = ChimMindPoisoning\mindPoisoningTransitionIntimacyArrangement(
    $endedTransition['arrangements'],
    'npc:22',
    ['status' => 'none', 'evidence' => 'There is no arrangement now.'],
    [
        'owner_id' => 11,
        'basis' => 'registered_reflection',
        'playthrough_id' => '1',
        'sources' => [[
            'event_id' => 103,
            'utterance_id' => 'utt_0123456789abcded',
            'speech_hash' => str_repeat('c', 64),
        ]],
        'gamets' => 27.0,
    ]
);
intimacySame('casual', $reaffirmedEnd['arrangements']['npc:22']['prior_kind'] ?? null, 'A repeated explicit absence must preserve the prior kind needed to report an ending.');
unlink($enginePath . DIRECTORY_SEPARATOR . 'main.php');
rmdir($enginePath);

fwrite(STDOUT, "intimacy state tests passed\n");
