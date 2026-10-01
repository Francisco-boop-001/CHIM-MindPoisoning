<?php
declare(strict_types=1);

if (!defined('CHIM_MIND_POISONING_TEST_FIXTURES_ONLY')) {
    define('CHIM_MIND_POISONING_TEST_FIXTURES_ONLY', true);
}
require_once __DIR__ . '/runtime_test.php';
require_once __DIR__ . '/../server/logging.php';

use ChimMindPoisoning\RequestLog;
use ChimMindPoisoning\StoreDb;
use function ChimMindPoisoning\persistJudgments;

final class LoggingStoreDb implements StoreDb
{
    public bool $released = false;
    public bool $failCommit = false;
    public ?string $throwOnCleanup = null;

    public function __construct(public StoreDb $inner) {}

    public function activePlaythrough(): ?array { return $this->inner->activePlaythrough(); }
    public function acknowledgedEvent(string $utteranceId): ?array { return $this->inner->acknowledgedEvent($utteranceId); }
    public function playerInputEvent(array $source): ?array { return $this->inner->playerInputEvent($source); }
    public function eventById(int $eventId, string $utteranceId): ?array { return $this->inner->eventById($eventId, $utteranceId); }
    public function npcIdentities(): array { return $this->inner->npcIdentities(); }
    public function npcById(int $npcId, bool $forUpdate = false): ?array { return $this->inner->npcById($npcId, $forUpdate); }
    public function reflectionHistory(string $actorName, int $beforeEventId): array { return $this->inner->reflectionHistory($actorName, $beforeEventId); }
    public function beginForListener(int $listenerId): bool { return $this->inner->beginForListener($listenerId); }
    public function writeNpc(int $npcId, array $relationshipEdges, object $mindPoisoningData, float $gamets): bool
    {
        return $this->inner->writeNpc($npcId, $relationshipEdges, $mindPoisoningData, $gamets);
    }
    public function backupAndVerify(int $npcId, array $expected): bool { return $this->inner->backupAndVerify($npcId, $expected); }
    public function commit(): bool { return $this->failCommit ? false : $this->inner->commit(); }
    public function rollback(): void
    {
        $this->inner->rollback();
        if ($this->throwOnCleanup === 'rollback') {
            throw new RuntimeException('fixture rollback failure');
        }
    }
    public function release(): void
    {
        $this->inner->release();
        $this->released = true;
        if ($this->throwOnCleanup === 'release') {
            throw new RuntimeException('fixture release failure');
        }
    }
}

function logRecords(callable $run): array
{
    $records = [];
    $sink = static function (string $json, string $level) use (&$records): void {
        $records[] = json_decode($json, true, 512, JSON_THROW_ON_ERROR) + ['sink_level' => $level];
    };
    $run($records, $sink);
    return $records;
}

function persistenceRecord(array $records): array
{
    foreach ($records as $record) {
        if (($record['event'] ?? null) === 'persistence_finished') {
            return $record;
        }
    }
    throw new RuntimeException('Missing persistence_finished record.');
}

[$event, $subjects, $judgments, $memory] = baseFixture();
$db = new LoggingStoreDb($memory);
$releasedBeforeLog = false;
$records = [];
$log = new RequestLog(static function (string $json) use (&$records, &$db, &$releasedBeforeLog): void {
    $releasedBeforeLog = $db->released;
    $records[] = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
}, false);
same('committed', persistJudgments($event, $subjects, $judgments, $db, $log), 'Logging must preserve a successful status.');
same(true, $releasedBeforeLog, 'Persistence summary must emit after release cleanup.');
$record = persistenceRecord($records);
same('committed', $record['persistence_outcome'] ?? null, 'Committed outcomes should be logged.');
same('committed', $record['persistence_reason'] ?? null, 'Committed reason should be stable.');
same('confirmed', $record['commit_state'] ?? null, 'Successful commits should be explicitly confirmed.');
same(true, $record['committed'] ?? null, 'The commit flag should reflect verified commit.');
same(2, $record['changed_count'] ?? null, 'Only changed edges should be counted.');
same([
    ['subject' => 'npc:33', 'delta' => 5, 'before' => 99, 'after' => 100],
    ['subject' => 'player', 'delta' => -2, 'before' => 0, 'after' => -2],
], $record['changes'] ?? null, 'Committed change summaries should contain the actual bounded edges.');
same(0, count(array_filter($records, static fn(array $record): bool => ($record['event'] ?? null) === 'request_finished')), 'The store must not finish the enclosing request.');
$log->finish('handled', 'speech-ack');
$summaries = array_values(array_filter($records, static fn(array $record): bool => ($record['event'] ?? null) === 'request_finished'));
same(1, count($summaries), 'The caller should emit one final request summary.');
$summary = $summaries[0];
same('committed', $summary['persistence_outcome'] ?? null, 'Persistence context should be available to the caller final summary.');
same(2, $summary['changed_count'] ?? null, 'Final request summary should retain the changed-edge count.');

[$event, $subjects, $judgments, $memory] = baseFixture();
$judgments['npc:33']['delta'] = 0;
$judgments['player']['delta'] = 0;
$records = logRecords(static function (&$records, callable $sink) use ($event, $subjects, $judgments, $memory): void {
    same('committed', persistJudgments($event, $subjects, $judgments, $memory, new RequestLog($sink, false)), 'Zero decisions still commit the ledger.');
});
$record = persistenceRecord($records);
same(true, $record['committed'] ?? null, 'Zero judgments still verify the commit.');
same(0, $record['changed_count'] ?? null, 'Zero judgments must report no changed edges.');
same([], $record['changes'] ?? null, 'Zero judgments must not report affinity values.');

[$event, $subjects, $judgments, $memory] = baseFixture();
$memory->npcs[22]['extended_data']->relationships->{'Jarl Balgruuf'}->aff = 100;
$judgments['npc:33']['delta'] = 5;
$judgments['player']['delta'] = 0;
$records = logRecords(static function (&$records, callable $sink) use ($event, $subjects, $judgments, $memory): void {
    same('committed', persistJudgments($event, $subjects, $judgments, $memory, new RequestLog($sink, false)), 'A clamped judgment still commits its ledger.');
});
$record = persistenceRecord($records);
same(0, $record['changed_count'] ?? null, 'Clamping to the existing affinity must not count as a value change.');
same([
    ['subject' => 'npc:33', 'delta' => 5, 'before' => 100, 'after' => 100],
], $record['changes'] ?? null, 'The committed summary may retain the proposed delta with equal before/after values.');

foreach ([
    ['value' => 250, 'delta' => -5, 'label' => 'above the affinity range'],
    ['value' => -250, 'delta' => 5, 'label' => 'below the affinity range'],
    ['value' => '1e309', 'delta' => -5, 'label' => 'numeric overflow'],
] as $invalidAffinity) {
    [$event, $subjects, $judgments, $memory] = baseFixture();
    $memory->npcs[22]['extended_data']->relationships->{'Jarl Balgruuf'}->aff = $invalidAffinity['value'];
    $judgments['npc:33']['delta'] = $invalidAffinity['delta'];
    $beforeOutOfRange = serialize($memory->npcs[22]);
    $records = logRecords(static function (&$records, callable $sink) use ($event, $subjects, $judgments, $memory, $invalidAffinity): void {
        same('invalid', persistJudgments($event, $subjects, $judgments, $memory, new RequestLog($sink, false)),
            'A stored affinity ' . $invalidAffinity['label'] . ' must be rejected.');
    });
    same($beforeOutOfRange, serialize($memory->npcs[22]), 'An invalid stored affinity must remain unchanged after rejection.');
    $record = persistenceRecord($records);
    same('affinity-invalid', $record['persistence_reason'] ?? null, 'Invalid stored affinity should use the fixed reason.');
    same('not_attempted', $record['commit_state'] ?? null, 'Invalid stored affinity must not reach COMMIT.');
    same([], $record['changes'] ?? null, 'Rejected invalid state must not report affinity changes.');
}

[$event, $subjects, $judgments, $memory] = baseFixture();
$event['event_id'] = 'bad';
$records = logRecords(static function (&$records, callable $sink) use ($event, $subjects, $judgments, $memory): void {
    same('invalid', persistJudgments($event, $subjects, $judgments, $memory, new RequestLog($sink, false)), 'Invalid data must preserve its status.');
});
$record = persistenceRecord($records);
same('invalid', $record['persistence_outcome'] ?? null, 'Invalid outcomes should be logged.');
same('invalid-event', $record['persistence_reason'] ?? null, 'The invalid-input rejection reason should be specific.');
same('not_attempted', $record['commit_state'] ?? null, 'Validation rejection means commit was not attempted.');
same(false, $record['committed'] ?? null, 'Rejected data must not claim a commit.');
same([], $record['changes'] ?? null, 'Rejected data must not include affinity values.');

[$event, $subjects, $judgments, $memory] = baseFixture();
$memory->profileId = '2';
$records = logRecords(static function (&$records, callable $sink) use ($event, $subjects, $judgments, $memory): void {
    same('stale', persistJudgments($event, $subjects, $judgments, $memory, new RequestLog($sink, false)), 'Stale profiles must preserve their status.');
});
$record = persistenceRecord($records);
same('event-stale', $record['persistence_reason'] ?? null, 'Profile drift should have a specific reason.');
same('info', $record['sink_level'] ?? null, 'Expected stale outcomes should log at info.');

[$event, $subjects, $judgments, $memory] = baseFixture();
$memory->npcs[22]['extended_data']->relationships_locked = true;
$records = logRecords(static function (&$records, callable $sink) use ($event, $subjects, $judgments, $memory): void {
    same('locked', persistJudgments($event, $subjects, $judgments, $memory, new RequestLog($sink, false)), 'Locked profiles must preserve their status.');
});
$record = persistenceRecord($records);
same('relationship-locked', $record['persistence_reason'] ?? null, 'Relationship lock should have a specific reason.');
same('info', $record['sink_level'] ?? null, 'Expected locked outcomes should log at info.');

[$event, $subjects, $judgments, $memory] = baseFixture();
$memory->npcs[22]['extended_data']->relationships->Dragonborn = (object)['aff' => 0, 'type' => 'friend'];
$memory->npcs[22]['extended_data']->relationships->Player = (object)['aff' => 0, 'type' => 'neutral'];
$records = logRecords(static function (&$records, callable $sink) use ($event, $subjects, $judgments, $memory): void {
    same('failed', persistJudgments($event, $subjects, $judgments, $memory, new RequestLog($sink, false)), 'Ambiguous Player aliases must stay fail-closed.');
});
$record = persistenceRecord($records);
same('player-alias-ambiguous', $record['persistence_reason'] ?? null, 'Player alias ambiguity should have a specific reason.');
same([], $record['changes'] ?? null, 'An ambiguous target must not report uncommitted values.');

[$event, $subjects, $judgments, $memory] = baseFixture();
$memory->failSnapshot = true;
$records = logRecords(static function (&$records, callable $sink) use ($event, $subjects, $judgments, $memory): void {
    same('failed', persistJudgments($event, $subjects, $judgments, $memory, new RequestLog($sink, false)), 'Snapshot failure must remain failed.');
});
$record = persistenceRecord($records);
same('failed', $record['persistence_outcome'] ?? null, 'Snapshot failure should be logged as failed.');
same('snapshot-verification-failed', $record['persistence_reason'] ?? null, 'Snapshot failure reason should identify the failed stage.');
same(false, $record['committed'] ?? null, 'Snapshot failure must not claim commit.');
same([], $record['changes'] ?? null, 'Uncommitted affinity plans must not be logged as changes.');

[$event, $subjects, $judgments, $memory] = baseFixture();
$db = new LoggingStoreDb($memory);
$db->failCommit = true;
$records = logRecords(static function (&$records, callable $sink) use ($event, $subjects, $judgments, $db): void {
    same('failed', persistJudgments($event, $subjects, $judgments, $db, new RequestLog($sink, false)), 'Commit failure must remain failed.');
});
$record = persistenceRecord($records);
same('commit-failed', $record['persistence_reason'] ?? null, 'Commit failure should identify the failed stage.');
same('unconfirmed', $record['commit_state'] ?? null, 'An attempted but unverified commit must remain explicitly uncertain.');
same(false, $record['committed'] ?? null, 'Commit failure must not claim commit.');
same([], $record['changes'] ?? null, 'Commit failure must not report planned affinity values.');
same(99, $memory->npcs[22]['extended_data']->relationships->{'Jarl Balgruuf'}->aff, 'Commit failure must roll back the fixture write.');

[$event, $subjects, $judgments, $memory] = baseFixture();
$db = new LoggingStoreDb($memory);
$db->throwOnCleanup = 'release';
$records = [];
$log = new RequestLog(static function (string $json) use (&$records): void {
    $records[] = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
}, false);
$threw = false;
try {
    persistJudgments($event, $subjects, $judgments, $db, $log);
} catch (RuntimeException) {
    $threw = true;
}
same(true, $threw, 'Release exceptions should keep propagating as before.');
$record = persistenceRecord($records);
same('failed', $record['persistence_outcome'] ?? null, 'Cleanup exception must not be reported as a successful request.');
same('release-failed', $record['persistence_reason'] ?? null, 'Release failure should be distinguished.');
same(true, $record['committed'] ?? null, 'A confirmed commit remains true when later release cleanup fails.');
same(true, $record['cleanup_failed'] ?? null, 'Release cleanup failure should be explicit.');
same(100, $memory->npcs[22]['extended_data']->relationships->{'Jarl Balgruuf'}->aff, 'Release failure happens after commit and must not claim rollback.');

[$event, $subjects, $judgments, $memory] = baseFixture();
$memory->failSnapshot = true;
$db = new LoggingStoreDb($memory);
$db->throwOnCleanup = 'rollback';
$records = [];
$log = new RequestLog(static function (string $json) use (&$records): void {
    $records[] = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
}, false);
$threw = false;
try {
    persistJudgments($event, $subjects, $judgments, $db, $log);
} catch (RuntimeException) {
    $threw = true;
}
same(true, $threw, 'Rollback exceptions should keep propagating as before.');
$record = persistenceRecord($records);
same('rollback-failed', $record['persistence_reason'] ?? null, 'Rollback failure should be distinguished.');
same(false, $record['committed'] ?? null, 'Rollback failure must not claim commit.');
same(true, $record['cleanup_failed'] ?? null, 'Rollback cleanup failure should be explicit.');
same([], $record['changes'] ?? null, 'Rollback failure must not report affinity values as committed.');
same(99, $memory->npcs[22]['extended_data']->relationships->{'Jarl Balgruuf'}->aff, 'The fixture confirms the attempted rollback restored the write.');

[$event, $subjects, $judgments, $memory] = baseFixture();
$beforeNpc = unserialize(serialize($memory->npcs[22]));
$throwingLog = new RequestLog(static function (): void {
    throw new RuntimeException('fixture sink failure');
}, false);
same('committed', persistJudgments($event, $subjects, $judgments, $memory, $throwingLog), 'A throwing log sink must not change persistence status.');
check(!ChimMindPoisoning\sameJsonValue($beforeNpc, $memory->npcs[22]), 'Sink failure must not undo a verified committed write.');

[$event, $subjects, $judgments, $memory] = baseFixture();
$memory->npcs[22]['plugin_extended_data']->mind_poisoning = 'corrupt';
$ledgerReason = null;
same(true, ChimMindPoisoning\eventAlreadyProcessed($memory->npcs[22], '1', 100, $event['utterance_id'], $ledgerReason), 'Malformed ledger state must still fail closed in preflight.');
same('ledger-invalid', $ledgerReason, 'Malformed ledger preflight should expose its rejection reason.');

[$event, $subjects, $judgments, $memory] = baseFixture();
$memory->npcs[22]['plugin_extended_data']->mind_poisoning = (object)[
    'playthrough_id' => '1', 'floor_event_id' => 100, 'events' => [],
];
$ledgerReason = null;
same(true, ChimMindPoisoning\eventAlreadyProcessed($memory->npcs[22], '1', 100, $event['utterance_id'], $ledgerReason), 'The bounded floor must continue to reject old events.');
same('ledger-floor', $ledgerReason, 'Floor rejection should be distinguished from a duplicate.');

[$event, $subjects, $judgments, $memory] = baseFixture();
$memory->npcs[22]['plugin_extended_data']->mind_poisoning = (object)[
    'playthrough_id' => '1', 'floor_event_id' => 0,
    'events' => [['event_id' => 100, 'utterance_id' => $event['utterance_id']]],
];
$ledgerReason = null;
same(true, ChimMindPoisoning\eventAlreadyProcessed($memory->npcs[22], '1', 100, $event['utterance_id'], $ledgerReason), 'Exact duplicate events must continue to be detected.');
same('duplicate-event', $ledgerReason, 'A recorded event should carry the duplicate reason.');

echo "store logging checks passed\n";
