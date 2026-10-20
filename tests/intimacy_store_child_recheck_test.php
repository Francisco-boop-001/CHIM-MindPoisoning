<?php
declare(strict_types=1);

require __DIR__ . '/intimacy_state_test.php';
require_once __DIR__ . '/fixtures/relationship_manager.cf5030f15781637498be86debe26fcf102f5690d.php';
require_once __DIR__ . '/../server/influence.php';

$fixtureRelationshipTypes = ChimMindPoisoning\relationshipTypeAllowedTypes();
intimacySame(
    true,
    is_array($fixtureRelationshipTypes)
        && in_array('neutral', $fixtureRelationshipTypes, true)
        && in_array('rival', $fixtureRelationshipTypes, true),
    'The pinned RelationshipManager fixture must expose the neutral-to-rival relationship types used below.'
);

$enginePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mp-intimacy-store-child-recheck-' . getmypid();
if (!is_dir($enginePath) && !mkdir($enginePath) && !is_dir($enginePath)) {
    throw new RuntimeException('Could not create the isolated pause-control fixture.');
}
file_put_contents($enginePath . DIRECTORY_SEPARATOR . 'main.php', "<?php\n");
$GLOBALS['ENGINE_PATH'] = $enginePath . DIRECTORY_SEPARATOR;
$GLOBALS['chim_interaction_generation'] = 1;
$GLOBALS['intimacy_enabled'] = true;
$GLOBALS['NEVER_CLEAR_RELATIONSHIP_DATA'] = false;
register_shutdown_function(static function () use ($enginePath): void {
    @unlink($enginePath . DIRECTORY_SEPARATOR . 'main.php');
    @rmdir($enginePath);
});

final class IntimacyChildFlipStore implements ChimMindPoisoning\StoreDb
{
    public int $beginCount = 0;

    public function __construct(
        public IntimacyMemoryStore $inner,
        private array $childNpcIds,
        private array $unavailableNames = []
    ) {
    }

    public function activePlaythrough(): ?array { return $this->inner->activePlaythrough(); }
    public function acknowledgedEvent(string $utteranceId): ?array { return $this->inner->acknowledgedEvent($utteranceId); }
    public function playerInputEvent(array $source): ?array { return $this->inner->playerInputEvent($source); }
    public function eventById(int $eventId, string $utteranceId): ?array { return $this->inner->eventById($eventId, $utteranceId); }
    public function npcIdentities(): array { return $this->inner->npcIdentities(); }
    public function npcById(int $npcId, bool $forUpdate = false): ?array { return $this->inner->npcById($npcId, $forUpdate); }
    public function reflectionHistory(string $actorName, int $beforeEventId): array { return $this->inner->reflectionHistory($actorName, $beforeEventId); }

    public function beginForListener(int $listenerId): bool
    {
        $this->beginCount++;
        if (!$this->inner->beginForListener($listenerId)) {
            return false;
        }
        foreach ($this->childNpcIds as $npcId) {
            $row = $this->inner->npcs[$npcId];
            $plugins = $row['plugin_extended_data'] ?? null;
            if (!$plugins instanceof stdClass) {
                $plugins = new stdClass();
            }
            $plugins->sharmat = (object)['is_child' => true];
            $row['plugin_extended_data'] = $plugins;
            $this->inner->npcs[$npcId] = $row;
        }
        return true;
    }

    public function intimacyChildDataByName(string $name): ?array
    {
        $unavailable = array_map(static fn(string $item): string => strtolower(trim($item)), $this->unavailableNames);
        return $this->beginCount > 0 && in_array(strtolower(trim($name)), $unavailable, true) ? null : [];
    }

    public function writeNpc(int $npcId, array $relationshipEdges, object $mindPoisoningData, float $gamets, array $relationshipKeysToRemove = []): bool
    {
        $nextExtendedData = null;
        if ($relationshipEdges !== [] || $relationshipKeysToRemove !== []) {
            $row = $this->inner->npcs[$npcId] ?? null;
            $extendedData = is_array($row) ? ($row['extended_data'] ?? null) : null;
            if (!$extendedData instanceof stdClass) {
                return false;
            }
            $nextExtendedData = clone $extendedData;
            $relationships = $nextExtendedData->relationships ?? null;
            if ($relationships === null || $relationships === []) {
                $relationships = new stdClass();
            } elseif (!$relationships instanceof stdClass) {
                return false;
            } else {
                $relationships = clone $relationships;
            }
            foreach ($relationshipKeysToRemove as $key) {
                unset($relationships->{$key});
            }
            foreach ($relationshipEdges as $name => $edge) {
                if (!is_string($name) || $name === '' || !$edge instanceof stdClass) {
                    return false;
                }
                $relationships->{$name} = clone $edge;
            }
            $nextExtendedData->relationships = $relationships;
        }
        if (!$this->inner->writeNpc($npcId, $relationshipEdges, $mindPoisoningData, $gamets, $relationshipKeysToRemove)) {
            return false;
        }
        if ($nextExtendedData instanceof stdClass) {
            $this->inner->npcs[$npcId]['extended_data'] = $nextExtendedData;
        }
        return true;
    }

    public function backupAndVerify(int $npcId, array $expected): bool { return $this->inner->backupAndVerify($npcId, $expected); }
    public function commit(): bool { return $this->inner->commit(); }
    public function rollback(): void { $this->inner->rollback(); }
    public function release(): void { $this->inner->release(); }
}

$GLOBALS['intimacy_enabled'] = true;

$directOwner = intimacyCloneRow($ownerRow);
$directStoreInner = new IntimacyMemoryStore();
$directStoreInner->npcs = [11 => $directOwner, 22 => intimacyCloneRow($targetRow)];
$directStoreInner->events = [101 => $event];
$directExpected = ChimMindPoisoning\mindPoisoningCaptureIntimacyRecord($directOwner, 'npc:22', '1');
$lateChildDirectEvent = $intimacyEvent;
$lateChildDirectEvent['intimacy_expected_record'] = $directExpected;
$lateChildDirectEvent['ack_identity'] = ChimMindPoisoning\mindPoisoningIntimacyAckIdentity($lateChildDirectEvent);
$directStore = new IntimacyChildFlipStore($directStoreInner, [22]);
$directStatus = ChimMindPoisoning\persistJudgments($lateChildDirectEvent, [], [], $directStore);
intimacySame('stale', $directStatus, 'A newly recognized child target must refuse the direct arrangement before the owner write.');
intimacySame(0, $directStoreInner->writeCount, 'The child-ineligible direct source must not write an arrangement or receipt.');
intimacySame(null, $directStoreInner->npcs[11]['plugin_extended_data']->mind_poisoning ?? null, 'A refused direct source must leave owner arrangement state untouched.');
$directLogRows = [];
$directLog = new ChimMindPoisoning\RequestLog(static function (string $json, string $_level) use (&$directLogRows): void {
    $row = json_decode($json, true);
    if (is_array($row)) {
        $directLogRows[] = $row;
    }
}, false);
$unknownDirectInner = new IntimacyMemoryStore();
$unknownDirectOwner = intimacyCloneRow($ownerRow);
$unknownDirectInner->npcs = [11 => $unknownDirectOwner, 22 => intimacyCloneRow($targetRow)];
$unknownDirectInner->events = [101 => $event];
$unknownDirectEvent = $intimacyEvent;
$unknownDirectEvent['intimacy_expected_record'] = ChimMindPoisoning\mindPoisoningCaptureIntimacyRecord($unknownDirectOwner, 'npc:22', '1');
$unknownDirectEvent['ack_identity'] = ChimMindPoisoning\mindPoisoningIntimacyAckIdentity($unknownDirectEvent);
$unknownDirectStore = new IntimacyChildFlipStore($unknownDirectInner, [], [$unknownDirectEvent['addressed_listener_name']]);
intimacySame(false, ChimMindPoisoning\mindPoisoningIntimacyChildStatus($unknownDirectStore, $unknownDirectEvent['addressed_listener_name'], $unknownDirectInner->npcs[22]), 'The direct target is eligible before the transaction-time metadata read becomes unavailable.');
$unknownDirectStatus = ChimMindPoisoning\persistJudgments($unknownDirectEvent, [], [], $unknownDirectStore, $directLog);
intimacySame('stale', $unknownDirectStatus, 'A direct arrangement must fail closed when optional child metadata is unavailable.');
intimacySame(0, $unknownDirectInner->writeCount, 'Unavailable child metadata must not write a direct arrangement or receipt.');
$directPersistence = array_values(array_filter($directLogRows, static fn(array $row): bool => ($row['event'] ?? null) === 'persistence_finished'))[0] ?? [];
intimacySame('intimacy-child-status-unavailable', $directPersistence['persistence_reason'] ?? null, 'The unavailable direct-child status must use its fixed reason code.');

$reflectionText = 'I argued with Lydia. Lydia and I agreed to be romantic. Jenassa and I have a casual arrangement. Aerin and I have a romantic arrangement.';
$reflectionSourceData = 'Aela: ' . $reflectionText . ' (Talking to explicit_disable_rechat)';
$reflectionSource = [
    'event_id' => 102,
    'utterance_id' => 'utt_intimacy_child_recheck_0001',
    'gamets' => 26.0,
    'source_data' => $reflectionSourceData,
    'delivery_state' => 'spoken',
    'type' => 'chat',
    'speaker_kind' => 'npc',
];
$reflectionOwner = intimacyCloneRow($ownerRow);
$reflectionOwner['personality'] = 'A cautious companion';
$reflectionOwner['extended_data']->relationships->Lydia = (object)['aff' => 10, 'type' => 'neutral'];
$reflectionOwner['extended_data']->relationships->Jenassa = (object)['aff' => 4, 'type' => 'friend'];
$oldTransition = ChimMindPoisoning\mindPoisoningTransitionIntimacyArrangement([], 'npc:22', [
    'status' => 'casual', 'evidence' => 'We had an earlier casual arrangement.',
], [
    'owner_id' => 11,
    'basis' => 'direct_npc_dialogue',
    'playthrough_id' => '1',
    'sources' => [[
        'event_id' => 100,
        'utterance_id' => 'utt_intimacy_previous_0001',
        'speech_hash' => str_repeat('a', 64),
    ]],
    'gamets' => 24.0,
]);
$reflectionOwner['plugin_extended_data']->mind_poisoning = (object)[
    'playthrough_id' => '1',
    'floor_event_id' => 0,
    'events' => [],
    'arrangements' => (object)$oldTransition['arrangements'],
];
$priorChildArrangement = $oldTransition['arrangements']['npc:22'] ?? null;
intimacySame(true, is_array($priorChildArrangement), 'The child-veto control must start with a complete stored arrangement record.');
$reflectionInner = new IntimacyMemoryStore();
$reflectionInner->npcs = [
    11 => $reflectionOwner,
    22 => intimacyCloneRow($targetRow),
    23 => [
        'id' => 23,
        'npc_name' => 'Jenassa',
        'extended_data' => (object)['relationships' => new stdClass()],
        'plugin_extended_data' => new stdClass(),
        'gamets_last_updated' => 0.0,
    ],
    24 => [
        'id' => 24,
        'npc_name' => 'Aerin',
        'extended_data' => (object)['relationships' => new stdClass()],
        'plugin_extended_data' => new stdClass(),
        'gamets_last_updated' => 0.0,
    ],
];
$reflectionInner->events = [102 => $reflectionSource];
$reflectionEvent = [
    'event_id' => 102,
    'utterance_id' => $reflectionSource['utterance_id'],
    'speaker_kind' => 'npc',
    'speaker_id' => 11,
    'listener_id' => null,
    'speaker_name' => 'Aela',
    'opinion_owner_id' => 11,
    'source_kind' => 'reflection',
    'reflection_basis' => '',
    'speech_hash' => hash('sha256', $reflectionText),
    'text' => $reflectionText,
    'playthrough_id' => '1',
    'player_name' => 'Dragonborn',
    'source_data' => $reflectionSourceData,
    'gamets' => 26.0,
];
$basisHistory = null;
$basisReason = null;
$reflectionEvent['reflection_basis'] = ChimMindPoisoning\reflectionBasisForEvent(
    $reflectionOwner,
    $reflectionInner,
    $reflectionEvent,
    $basisHistory,
    $basisReason
);
$subjects = [
    'npc:22' => ['id' => 22, 'name' => 'Lydia'],
    'npc:23' => ['id' => 23, 'name' => 'Jenassa'],
    'npc:24' => ['id' => 24, 'name' => 'Aerin'],
];
$resolvedSubjectTokens = array_keys(ChimMindPoisoning\findSubjects($reflectionEvent, $reflectionInner->npcIdentities(), 'Dragonborn'));
$expectedSubjectTokens = array_keys($subjects);
sort($resolvedSubjectTokens, SORT_STRING);
sort($expectedSubjectTokens, SORT_STRING);
intimacySame($expectedSubjectTokens, $resolvedSubjectTokens, 'Each test subject must resolve from the production reflection body and current NPC catalog.');
$reflectionEvent['intimacy_expected_records'] = [
    'npc:22' => ChimMindPoisoning\mindPoisoningCaptureIntimacyRecord($reflectionOwner, 'npc:22', '1'),
    'npc:23' => ChimMindPoisoning\mindPoisoningCaptureIntimacyRecord($reflectionOwner, 'npc:23', '1'),
    'npc:24' => ChimMindPoisoning\mindPoisoningCaptureIntimacyRecord($reflectionOwner, 'npc:24', '1'),
];
$judgments = [
    'npc:22' => [
        'delta' => 1,
        'reason' => 'A normal friendship judgment.',
        'evidence' => 'I argued with Lydia.',
        'type_change' => ['from' => 'neutral', 'to' => 'rival'],
        'arrangement_change' => ['status' => 'romantic', 'evidence' => 'Lydia and I agreed to be romantic.'],
    ],
    'npc:23' => [
        'delta' => 0,
        'reason' => 'No new affinity judgment.',
        'evidence' => 'Jenassa and I have a casual arrangement.',
        'arrangement_change' => ['status' => 'casual', 'evidence' => 'Jenassa and I have a casual arrangement.'],
    ],
    'npc:24' => [
        'delta' => 2,
        'reason' => 'A normal friendship judgment.',
        'evidence' => 'Aerin and I have a romantic arrangement.',
        'arrangement_change' => ['status' => 'romantic', 'evidence' => 'Aerin and I have a romantic arrangement.'],
    ],
];
$registration = [
    'event_id' => 102,
    'utterance_id' => $reflectionSource['utterance_id'],
    'actor_id' => 11,
    'actor_name' => 'Aela',
    'playthrough_id' => '1',
    'config_id' => '12345678-1234-4234-8234-123456789abc',
    'rechat_target_hint' => 'explicit_disable_rechat',
    'speech_hash' => hash('sha256', $reflectionText),
];
$reflectionStore = new IntimacyChildFlipStore($reflectionInner, [22], ['Aerin']);
intimacySame(false, ChimMindPoisoning\mindPoisoningIntimacyChildStatus($reflectionStore, 'Aela', $reflectionOwner), 'The reflection owner is eligible before the transaction-time child recheck.');
intimacySame(false, ChimMindPoisoning\mindPoisoningIntimacyChildStatus($reflectionStore, 'Lydia', $reflectionInner->npcs[22]), 'The known-child test target is eligible before its transaction-time signal appears.');
intimacySame(false, ChimMindPoisoning\mindPoisoningIntimacyChildStatus($reflectionStore, 'Jenassa', $reflectionInner->npcs[23]), 'The adult sibling target is eligible before transaction-time persistence.');
intimacySame(false, ChimMindPoisoning\mindPoisoningIntimacyChildStatus($reflectionStore, 'Aerin', $reflectionInner->npcs[24]), 'The reflection target is eligible before its transaction-time metadata lookup becomes unavailable.');
$logRows = [];
$requestLog = new ChimMindPoisoning\RequestLog(static function (string $json, string $_level) use (&$logRows): void {
    $row = json_decode($json, true);
    if (is_array($row)) {
        $logRows[] = $row;
    }
}, false);
$reflectionStatus = ChimMindPoisoning\persistJudgments(
    $reflectionEvent,
    $subjects,
    $judgments,
    $reflectionStore,
    $requestLog,
    static fn(array $given, string $_stage): bool => $given === $registration,
    $registration
);
$reflectionPersistence = array_values(array_filter($logRows, static fn(array $row): bool => ($row['event'] ?? null) === 'persistence_finished'))[0] ?? [];
$reflectionReason = is_string($reflectionPersistence['persistence_reason'] ?? null) ? $reflectionPersistence['persistence_reason'] : 'missing';
$reflectionFailurePhase = $reflectionStore->beginCount > 0 ? 'transaction' : 'validation';
intimacySame('committed', $reflectionStatus, 'A late child veto must not cancel valid ordinary reflection judgments (reason=' . $reflectionReason . ', phase=' . $reflectionFailurePhase . ').');
intimacySame(11, $reflectionInner->npcs[11]['extended_data']->relationships->Lydia->aff ?? null, 'The ordinary affinity judgment must still commit.');
intimacySame('rival', $reflectionInner->npcs[11]['extended_data']->relationships->Lydia->type ?? null, 'The ordinary relationship type must still commit.');
intimacySame(2, $reflectionInner->npcs[11]['extended_data']->relationships->Aerin->aff ?? null, 'Unavailable child metadata must not cancel an ordinary reflection judgment.');
$savedArrangements = $reflectionInner->npcs[11]['plugin_extended_data']->mind_poisoning->arrangements ?? null;
$savedChild = is_object($savedArrangements) ? ($savedArrangements->{'npc:22'} ?? null) : ($savedArrangements['npc:22'] ?? null);
$savedAdult = is_object($savedArrangements) ? ($savedArrangements->{'npc:23'} ?? null) : ($savedArrangements['npc:23'] ?? null);
$savedUnavailable = is_object($savedArrangements) ? ($savedArrangements->{'npc:24'} ?? null) : ($savedArrangements['npc:24'] ?? null);
intimacySame('casual', is_array($savedChild) ? ($savedChild['kind'] ?? null) : ($savedChild->kind ?? null), 'A late child veto must preserve an existing arrangement unchanged.');
intimacySame(
    true,
    ChimMindPoisoning\sameJsonValue($priorChildArrangement, $savedChild),
    'A late child veto must preserve the full existing arrangement record, including source provenance and scope.'
);
intimacySame('casual', is_array($savedAdult) ? ($savedAdult['kind'] ?? null) : ($savedAdult->kind ?? null), 'An eligible adult arrangement in the same reflection must still commit.');
intimacySame(null, $savedUnavailable, 'Unavailable optional child metadata must suppress only that arrangement proposal.');
$persistenceLog = $reflectionPersistence;
intimacySame(1, $persistenceLog['arrangement_changed_count'] ?? null, 'Only the eligible adult arrangement may count as changed.');
intimacySame('npc:23', $persistenceLog['arrangement_changes'][0]['subject'] ?? null, 'The persistence receipt must omit the child-ineligible arrangement candidate.');

fwrite(STDOUT, "intimacy store child recheck tests passed\n");
