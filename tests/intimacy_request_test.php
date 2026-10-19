<?php
declare(strict_types=1);

function chimGetGeneralSettingBool(string $id, bool $default = false): bool
{
    if ($id === 'mind_poisoning_intimacy_enabled') {
        return ($GLOBALS['runtime_test_intimacy_enabled'] ?? false) === true;
    }
    return $id === 'mind_poisoning_overhearing_enabled' ? false : $default;
}

require_once __DIR__ . '/runtime_test.php';

use function ChimMindPoisoning\handleSpeechAck;
use ChimMindPoisoning\RestorePolicyStoreDb;
use ChimMindPoisoning\StoreDb;

function intimacyAckFixture(array $options = []): array
{
    [, , , $db] = baseFixture();
    $db = $options['store'] ?? $db;
    $eventId = $options['event_id'] ?? 101;
    $utteranceId = $options['utterance_id'] ?? 'utt_intimacy_0001';
    $speaker = $options['speaker'] ?? 'Aela';
    $listener = $options['listener'] ?? 'Lydia';
    $target = $options['target'] ?? $listener;
    $speech = $options['speech'] ?? 'We have a casual arrangement.';
    if (isset($options['player_name'])) {
        $db->playerName = $options['player_name'];
    }
    if (isset($options['catalog_collision'])) {
        $db->npcs[33]['npc_name'] = $options['catalog_collision'];
    }
    if (($options['speaker_locked'] ?? false) === true) {
        $db->npcs[11]['extended_data']->relationships_locked = true;
    }
    if (($options['listener_locked'] ?? false) === true) {
        $db->npcs[22]['extended_data']->relationships_locked = true;
        $db->npcs[22]['lock_profile'] = 1;
    }
    if (($options['add_listener'] ?? false) === true && $listener === 'Inigo') {
        $db->npcs[44] = ['id' => 44, 'npc_name' => 'Inigo', 'extended_data' => (object)['relationships' => new stdClass()], 'plugin_extended_data' => new stdClass()];
    }
    $sourceData = $options['source_data'] ?? ($speaker . ': ' . $speech . ' (Talking to ' . $target . ')');
    $db->events = [
        $eventId => [
            'event_id' => $eventId,
            'utterance_id' => $utteranceId,
            'gamets' => 25.0,
            'source_data' => $sourceData,
            'delivery_state' => 'spoken',
            'type' => 'chat',
        ],
    ];
    $ack = [
        '_speech',
        '1740000001',
        '25',
        json_encode([
            'speaker' => $speaker,
            'listener' => $listener,
            'speech' => $speech,
            'utterance_id' => $utteranceId,
        ], JSON_THROW_ON_ERROR),
    ];
    return [$ack, $db, $eventId, $utteranceId, $speech];
}

function intimacyField(mixed $value, string $key): mixed
{
    if (is_array($value)) {
        return $value[$key] ?? null;
    }
    return is_object($value) ? ($value->{$key} ?? null) : null;
}

function intimacyStoredRecord(object $db, int $ownerId, string $target): mixed
{
    $pluginData = $db->npcs[$ownerId]['plugin_extended_data'] ?? null;
    $ledger = intimacyField($pluginData, 'mind_poisoning');
    $arrangements = intimacyField($ledger, 'arrangements');
    return intimacyField($arrangements, $target);
}

function intimacyHasReceipt(object $db, int $ownerId, int $eventId, string $utteranceId): bool
{
    $pluginData = $db->npcs[$ownerId]['plugin_extended_data'] ?? null;
    $ledger = intimacyField($pluginData, 'mind_poisoning');
    $events = intimacyField($ledger, 'events');
    foreach (is_array($events) ? $events : [] as $entry) {
        if (
            (int)intimacyField($entry, 'event_id') === $eventId
            && intimacyField($entry, 'utterance_id') === $utteranceId
            && intimacyField($entry, 'source_kind') === 'intimacy'
        ) {
            return true;
        }
    }
    return false;
}

function runIntimacyAck(array $options, string $response, int &$calls): array
{
    resetAckLoggingInteraction();
    $GLOBALS['runtime_test_intimacy_enabled'] = $options['enabled'] ?? true;
    if (($options['restore_policy'] ?? false) === true) {
        $GLOBALS['NEVER_CLEAR_RELATIONSHIP_DATA'] = true;
        $GLOBALS['all_fast_commands'] = ['_speech', 'request'];
    }
    if (($options['interaction_off'] ?? false) === true) {
        resetInteractionTestRequest(1, 1, false, false);
    }
    if (($options['interaction_stale'] ?? false) === true) {
        resetInteractionTestRequest(1, 2, true, false);
    }
    if (($options['connector_off'] ?? false) === true) {
        $GLOBALS['runtime_test_relationship_enabled'] = false;
    }
    [$ack, $db, $eventId, $utteranceId, $speech] = intimacyAckFixture($options);
    $request = static function (array $messages) use (&$calls, $response): string {
        $calls++;
        return $response;
    };
    $records = [];
    $status = handleSpeechAck($ack, $db, $request, captureRequestLog($records));
    return [$status, $db, $eventId, $utteranceId, $speech, $records];
}

/** Adds the real optional restore-policy store capability to the shared in-memory DB. */
final class IntimacyRestoreMemoryStore implements StoreDb, RestorePolicyStoreDb
{
    public function __construct(
        private MemoryStoreDb $memory,
        public int $latestInitEventId = 50,
        public int $maxEventId = 101
    ) {}

    public function &__get(string $name): mixed { return $this->memory->{$name}; }
    public function __set(string $name, mixed $value): void { $this->memory->{$name} = $value; }
    public function activePlaythrough(): ?array { return $this->memory->activePlaythrough(); }
    public function acknowledgedEvent(string $utteranceId): ?array { return $this->memory->acknowledgedEvent($utteranceId); }
    public function playerInputEvent(array $source): ?array { return $this->memory->playerInputEvent($source); }
    public function eventById(int $eventId, string $utteranceId): ?array { return $this->memory->eventById($eventId, $utteranceId); }
    public function npcIdentities(): array { return $this->memory->npcIdentities(); }
    public function npcById(int $npcId, bool $forUpdate = false): ?array { return $this->memory->npcById($npcId, $forUpdate); }
    public function reflectionHistory(string $actorName, int $beforeEventId): array { return $this->memory->reflectionHistory($actorName, $beforeEventId); }
    public function beginForListener(int $listenerId): bool { return $this->memory->beginForListener($listenerId); }
    public function writeNpc(int $npcId, array $relationshipEdges, object $mindPoisoningData, float $gamets, array $relationshipKeysToRemove = []): bool
    {
        return $this->memory->writeNpc($npcId, $relationshipEdges, $mindPoisoningData, $gamets, $relationshipKeysToRemove);
    }
    public function backupAndVerify(int $npcId, array $expected): bool { return $this->memory->backupAndVerify($npcId, $expected); }
    public function commit(): bool { return $this->memory->commit(); }
    public function rollback(): void { $this->memory->rollback(); }
    public function release(): void { $this->memory->release(); }
    public function restoreEventBounds(): ?array
    {
        return ['latest_init_event_id' => $this->latestInitEventId, 'max_event_id' => $this->maxEventId];
    }
    public function acquireRestorePolicyBarrier(): bool { return true; }
    public function releaseRestorePolicyBarrier(): void {}
}

function intimacyOldScopeStore(): IntimacyRestoreMemoryStore
{
    [, , , $memory] = baseFixture();
    $oldArrangement = \ChimMindPoisoning\mindPoisoningTransitionIntimacyArrangement(
        [],
        'npc:22',
        ['status' => 'romantic', 'evidence' => 'We were together.'],
        [
            'owner_id' => 11,
            'basis' => 'direct_npc_dialogue',
            'playthrough_id' => 'old-scope',
            'sources' => [[
                'event_id' => 12,
                'utterance_id' => 'utt_oldscope_12345678',
                'speech_hash' => hash('sha256', 'old source'),
            ]],
            'gamets' => 12.0,
        ]
    );
    $memory->npcs[11]['plugin_extended_data']->mind_poisoning = (object)[
        'playthrough_id' => 'old-scope',
        'floor_event_id' => 0,
        'events' => [],
        'arrangements' => $oldArrangement['arrangements'],
    ];
    $memory->npcs[11]['gamets_last_updated'] = 12.0;
    return new IntimacyRestoreMemoryStore($memory);
}

resetAckLoggingInteraction();
$calls = 0;
[$status, $db, $eventId, $utteranceId, $speech, $records] = runIntimacyAck(
    [],
    json_encode(['intimacy' => ['status' => 'casual', 'evidence' => 'We have a casual arrangement.']], JSON_THROW_ON_ERROR),
    $calls
);
same('no-subjects', $status, 'A direct-only ACK should leave ordinary gossip status unchanged.');
same(1, $calls, 'An enabled, exact addressed direct source should make one separate arrangement call.');
$record = intimacyStoredRecord($db, 11, 'npc:22');
same('casual', intimacyField($record, 'kind'), 'A typed direct result should persist on the speaker-owned target record.');
same(11, (int)intimacyField($record, 'owner_id'), 'The direct record must name the speaker as owner.');
same('npc:22', intimacyField($record, 'target'), 'The direct record must retain the exact addressed NPC target.');
check(intimacyHasReceipt($db, 11, $eventId, $utteranceId), 'The speaker row must receive an idempotency receipt even when ordinary gossip has no subjects.');
same(null, intimacyStoredRecord($db, 22, 'npc:11'), 'The addressed listener must not receive the speaker’s arrangement.');
$directTerminal = array_values(array_filter($records, static fn(array $entry): bool => ($entry['event'] ?? null) === 'intimacy_finished'));
same(1, count($directTerminal), 'A direct arrangement result must have one terminal event independent of ordinary ACK status.');
same('intimacy', $directTerminal[0]['source_kind'] ?? null, 'The terminal event must identify the distinct intimacy source.');
same('11', $directTerminal[0]['opinion_owner_id'] ?? null, 'The terminal event must identify the NPC opinion owner.');
same('committed', $directTerminal[0]['outcome'] ?? null, 'The terminal event must preserve the direct persistence result.');
same('valid', $directTerminal[0]['model_outcome'] ?? null, 'The terminal record should distinguish a valid model response from persistence outcome.');
check(is_float($directTerminal[0]['model_ms'] ?? null) || is_int($directTerminal[0]['model_ms'] ?? null), 'The terminal record should include bounded model latency.');
check(!array_key_exists('speech_hash', $directTerminal[0]) && !array_key_exists('text', $directTerminal[0]), 'The terminal event must not expose source hashes or utterance text.');
$directPersistence = array_values(array_filter(
    $records,
    static fn(array $entry): bool => ($entry['event'] ?? null) === 'persistence_finished' && ($entry['source_kind'] ?? null) === 'intimacy'
));
same(1, count($directPersistence), 'The direct speaker-owned write should have one distinct persistence event.');
same($directPersistence[0]['request_id'] ?? null, $directTerminal[0]['request_id'] ?? null, 'The direct terminal and persistence events should correlate on the child request.');
$ordinaryTerminal = array_values(array_filter($records, static fn(array $entry): bool => ($entry['event'] ?? null) === 'request_finished'));
same(1, count($ordinaryTerminal), 'The ordinary ACK should still finish independently after its direct sidecar.');
check(!array_key_exists('source_kind', $ordinaryTerminal[0]) && !array_key_exists('opinion_owner_id', $ordinaryTerminal[0]), 'The direct source context must not bleed into ordinary ACK completion.');
check(($ordinaryTerminal[0]['request_id'] ?? null) !== ($directTerminal[0]['request_id'] ?? null), 'The direct sidecar should use its forked request logger.');

[$replayAck] = intimacyAckFixture(['event_id' => $eventId, 'utterance_id' => $utteranceId]);
$replayRecords = [];
same('no-subjects', handleSpeechAck($replayAck, $db, static function () use (&$calls): string { $calls++; return '{}'; }, captureRequestLog($replayRecords)), 'A replay must leave the ordinary no-subject status intact.');
same(1, $calls, 'An exact source receipt must suppress a duplicate provider call before model work.');

$calls = 0;
[$disabledStatus, $disabledDb] = runIntimacyAck(
    ['enabled' => false],
    json_encode(['intimacy' => ['status' => 'romantic', 'evidence' => 'We have a casual arrangement.']], JSON_THROW_ON_ERROR),
    $calls
);
same('no-subjects', $disabledStatus, 'The disabled feature must not change the ordinary ACK status.');
same(0, $calls, 'The global opt-in defaults off and gates direct classification.');
same(null, intimacyStoredRecord($disabledDb, 11, 'npc:22'), 'Disabled classification must not write arrangement state.');

$oldScopeStore = intimacyOldScopeStore();
$calls = 0;
[, $newScopeDb] = runIntimacyAck(
    ['store' => $oldScopeStore, 'event_id' => 101],
    json_encode(['intimacy' => ['status' => 'casual', 'evidence' => 'We have a casual arrangement.']], JSON_THROW_ON_ERROR),
    $calls
);
same(1, $calls, 'With restore preservation disabled, a new-scope direct report should proceed without inheriting old arrangement state.');
$newScopeRecord = intimacyStoredRecord($newScopeDb, 11, 'npc:22');
same('1', intimacyField($newScopeRecord, 'playthrough_id'), 'A new-scope report must be stored under the active playthrough.');
same('casual', intimacyField($newScopeRecord, 'kind'), 'The new-scope source should replace the prior-scope target state.');
same(101, intimacyField(intimacyField($newScopeRecord, 'sources')[0] ?? null, 'event_id'), 'The current arrangement must cite only its new-scope source.');

$restoreStore = intimacyOldScopeStore();
$calls = 0;
[$baselineAckStatus, $baselineDb, , , , $baselineRecords] = runIntimacyAck(
    ['store' => $restoreStore, 'restore_policy' => true, 'event_id' => 101],
    json_encode(['intimacy' => ['status' => 'casual', 'evidence' => 'We have a casual arrangement.']], JSON_THROW_ON_ERROR),
    $calls
);
same('no-subjects', $baselineAckStatus, 'Speaker baseline preparation must preserve the ordinary ACK result.');
same(0, $calls, 'A restore-scope baseline must skip classification of the triggering source.');
$baselineLedger = intimacyField(intimacyField($baselineDb->npcs[11]['plugin_extended_data'], 'mind_poisoning'), 'arrangements');
same('1', intimacyField(intimacyField($baselineDb->npcs[11]['plugin_extended_data'], 'mind_poisoning'), 'playthrough_id'), 'The baseline must update the speaker-owned ledger scope.');
same(null, $baselineLedger, 'The speaker baseline must clear prior-scope arrangement state.');
$baselineEvents = array_values(array_filter(
    $baselineRecords,
    static fn(array $entry): bool => ($entry['event'] ?? null) === 'restore_policy_preflight'
        && ($entry['reason'] ?? null) === 'restore-policy-scope-baselined'
));
same(1, count($baselineEvents), 'The direct source should report the speaker scope baseline.');

$restoreStore->maxEventId = 102;
$calls = 0;
[, $restoredScopeDb] = runIntimacyAck(
    ['store' => $restoreStore, 'restore_policy' => true, 'event_id' => 102, 'utterance_id' => 'utt_intimacy_0002'],
    json_encode(['intimacy' => ['status' => 'casual', 'evidence' => 'We have a casual arrangement.']], JSON_THROW_ON_ERROR),
    $calls
);
same(1, $calls, 'A later source after the completed speaker baseline should be eligible.');
$restoredScopeRecord = intimacyStoredRecord($restoredScopeDb, 11, 'npc:22');
same('casual', intimacyField($restoredScopeRecord, 'kind'), 'The later source should persist after the baseline.');
same(102, intimacyField(intimacyField($restoredScopeRecord, 'sources')[0] ?? null, 'event_id'), 'The post-baseline record must cite the later exact source.');

foreach ([
    ['interaction_off' => true],
    ['interaction_stale' => true],
    ['connector_off' => true],
    ['speaker' => 'Dragonborn', 'listener' => 'Lydia', 'target' => 'Lydia'],
    ['listener' => 'explicit_disable_rechat', 'target' => 'explicit_disable_rechat'],
    ['listener' => 'Lydia', 'target' => 'everyone', 'source_data' => 'Aela: We have a casual arrangement. (Talking to everyone)'],
    ['listener' => 'Hawke', 'target' => 'Hawke', 'player_name' => 'Hawke', 'catalog_collision' => 'Hawke'],
    ['listener' => 'Inigo', 'target' => 'Lydia', 'add_listener' => true],
    ['speaker_locked' => true],
] as $skippedOptions) {
    $calls = 0;
    runIntimacyAck($skippedOptions, json_encode(['intimacy' => null], JSON_THROW_ON_ERROR), $calls);
    same(0, $calls, 'Invalid, ambiguous, non-addressed, or gated source must be rejected before classifier work.');
}

foreach ([
    ['target' => 'Player', 'listener' => 'Player', 'catalog_collision' => 'Dragonborn'],
    ['target' => 'Dragonborn', 'listener' => 'Dragonborn'],
] as $playerRoute) {
    $calls = 0;
    [, $playerDb] = runIntimacyAck($playerRoute, json_encode(['intimacy' => ['status' => 'romantic', 'evidence' => 'We have a casual arrangement.']], JSON_THROW_ON_ERROR), $calls);
    same(1, $calls, 'A unique exact source Player/canonical route should be eligible.');
    same('romantic', intimacyField(intimacyStoredRecord($playerDb, 11, 'player'), 'kind'), 'A Player-directed report must be stored only on the NPC speaker as target `player`.');
    same(null, intimacyStoredRecord($playerDb, 22, 'npc:11'), 'A Player-directed report must not be written to another NPC.');
}

$calls = 0;
[, $listenerLockedDb] = runIntimacyAck(
    ['listener_locked' => true],
    json_encode(['intimacy' => ['status' => 'casual', 'evidence' => 'We have a casual arrangement.']], JSON_THROW_ON_ERROR),
    $calls
);
same(1, $calls, 'A target-row relationship lock must not veto the speaker-owned arrangement classifier.');
same('casual', intimacyField(intimacyStoredRecord($listenerLockedDb, 11, 'npc:22'), 'kind'), 'A locked target row must still permit the speaker-owned plugin state transition.');
same(null, intimacyStoredRecord($listenerLockedDb, 22, 'npc:11'), 'The locked target row must remain free of copied arrangement state.');
same(true, $listenerLockedDb->npcs[22]['extended_data']->relationships_locked, 'The classifier must not mutate the target row lock flag.');
same(1, $listenerLockedDb->npcs[22]['lock_profile'], 'The classifier must not mutate the target profile lock.');

echo "intimacy request tests passed\n";
