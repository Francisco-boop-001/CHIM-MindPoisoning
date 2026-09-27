<?php
declare(strict_types=1);

use ChimMindPoisoning\RequestLog;
use function ChimMindPoisoning\dashboardBuildInteractions;
use function ChimMindPoisoning\dashboardCurrent;
use function ChimMindPoisoning\dashboardFilters;
use function ChimMindPoisoning\dashboardIndexRecords;
use function ChimMindPoisoning\dashboardLoad;
use function ChimMindPoisoning\dashboardLogInteraction;
use function ChimMindPoisoning\dashboardNormalizedOutcome;
use function ChimMindPoisoning\dashboardParseLogLine;
use function ChimMindPoisoning\dashboardRecordMatches;

require_once __DIR__ . '/../server/dashboard_data.php';

function dashboardDataAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function dashboardDataThrows(callable $callable, string $message): void
{
    try {
        $callable();
    } catch (InvalidArgumentException) {
        return;
    }
    throw new RuntimeException($message);
}

dashboardDataThrows(static fn() => dashboardFilters(['tab' => 'other']), 'Unknown tab was accepted.');
dashboardDataThrows(static fn() => dashboardFilters(['q' => str_repeat('é', 121)]), 'Overlong UTF-8 query was accepted.');
dashboardDataThrows(static fn() => dashboardFilters(['q' => "\xFF"]), 'Invalid UTF-8 query was accepted.');
dashboardDataAssert(
    dashboardFilters(['tab' => 'diagnostics', 'q' => 'Aela']) === ['tab' => 'diagnostics', 'q' => 'Aela', 'outcome' => '', 'level' => ''],
    'Valid filters were not normalized.'
);

$writes = [];
$requestLog = new RequestLog(static function (string $json, string $level) use (&$writes): void {
    $writes[] = [$json, $level];
}, false);
$requestLog->context([
    'event_id' => 42,
    'utterance_id' => 'utt_abcdefgh',
    'playthrough_id' => 17,
    'speaker_id' => 8,
    'listener_id' => 7,
]);
$requestLog->finish('committed', 'committed', [
    'stage' => 'request',
    'outcome' => 'committed',
    'commit_state' => 'confirmed',
    'committed' => true,
    'changed_count' => 0,
    'model_reason' => 'private rationale',
]);
dashboardDataAssert(count($writes) === 1, 'RequestLog fixture did not emit one terminal record.');
[$wireJson, $wireLevel] = $writes[0];
$wireRecord = json_decode($wireJson, false, 32, JSON_THROW_ON_ERROR);
$wireRecord->raw_speech = 'must not escape';
$wireRecord->unknown_provider_field = ['secret' => 'must not escape'];
$wireLine = '[2026-09-27 16:00:00] [' . $wireLevel . '] ' . json_encode($wireRecord, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$parsed = dashboardParseLogLine($wireLine);
dashboardDataAssert(is_array($parsed) && $parsed['event'] === 'request_finished', 'Native Logger envelope rejected a RequestLog record.');
foreach (['raw_speech', 'unknown_provider_field', 'model_reason'] as $forbiddenField) {
    dashboardDataAssert(!array_key_exists($forbiddenField, $parsed), 'Private or unknown log field escaped sanitization: ' . $forbiddenField);
}
dashboardDataAssert(dashboardNormalizedOutcome($parsed) === 'zero-change', 'Diagnostics did not normalize confirmed zero changes.');
dashboardDataAssert(
    dashboardRecordMatches($parsed, ['tab' => 'diagnostics', 'q' => '', 'outcome' => 'zero-change', 'level' => ''])
        && !dashboardRecordMatches($parsed, ['tab' => 'diagnostics', 'q' => '', 'outcome' => 'unconfirmed', 'level' => '']),
    'Diagnostics outcome filtering did not match normalized zero-change state.'
);
$wireRecord->commit_state = 'unconfirmed';
$unconfirmed = dashboardParseLogLine('[2026-09-27 16:00:00] [' . $wireLevel . '] ' . json_encode($wireRecord, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
dashboardDataAssert(is_array($unconfirmed) && dashboardNormalizedOutcome($unconfirmed) === 'unconfirmed', 'Unconfirmed commit state was not normalized.');

$player = ['id' => '7', 'extended_data' => (object)['relationships' => new stdClass()]];
$missing = dashboardCurrent($player, 'npc:9', 'Subject', '', ['Subject' => 1]);
$player['extended_data']->relationships->Subject = (object)['aff' => 'not-a-number'];
$malformed = dashboardCurrent($player, 'npc:9', 'Subject', '', ['Subject' => 1]);
dashboardDataAssert($missing === ['value' => null, 'state' => 'unset_default_zero'], 'Missing edge was not kept distinct from observed zero.');
dashboardDataAssert($malformed === ['value' => null, 'state' => 'invalid'], 'Malformed affinity was coerced to a numeric value.');
$player['extended_data']->relationships = null;
dashboardDataAssert(
    dashboardCurrent($player, 'npc:9', 'Subject', '', ['Subject' => 1]) === ['value' => null, 'state' => 'invalid'],
    'Unavailable oversized relationship data was treated as an empty object.'
);

$emptyServer = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mp-dashboard-data-' . bin2hex(random_bytes(6));
dashboardDataAssert(mkdir($emptyServer), 'Could not create an isolated empty server fixture.');
try {
    $emptyModel = dashboardLoad($emptyServer, []);
} finally {
    rmdir($emptyServer);
}
dashboardDataAssert(
    $emptyModel['source'] === ['logs' => 'unavailable', 'database' => 'unavailable', 'limited' => false]
        && in_array('CHIM logging is best-effort; suppressed or fallback log writes may be missing.', $emptyModel['notices'], true),
    'Unavailable fixture sources were not reported safely.'
);

$active = '17';
$event = [
    'event_id' => 42,
    'utterance_id' => 'utt_abcdefgh',
    'judgments' => [['subject' => 'npc:9', 'delta' => 2]],
];
$database = [
    'active_playthrough' => $active,
    'player_name' => 'Player One',
    'identities' => ['7' => 'Listener', '8' => 'Speaker', '9' => 'Subject'],
    'rows' => [[
        'id' => '7',
        'extended_data' => (object)['relationships' => (object)['Subject' => (object)['aff' => 2]]],
        'plugin_extended_data' => (object)['mind_poisoning' => (object)[
            'playthrough_id' => $active,
            'floor_event_id' => 0,
            'events' => [$event],
        ]],
    ]],
];
$baseRecord = [
    'timestamp' => '2026-09-27T16:00:00Z',
    'event_id' => '42',
    'utterance_id' => 'utt_abcdefgh',
    'playthrough_id' => $active,
    'speaker_id' => '8',
    'listener_id' => '7',
];
$records = [
    $baseRecord + [
        'request_id' => 'req_confirmed',
        'event' => 'persistence_finished',
        'persistence_outcome' => 'committed',
        'persistence_reason' => 'committed',
        'commit_state' => 'confirmed',
        'committed' => true,
        'changed_count' => 1,
        'persistence_ms' => 8.5,
        'changes' => [['subject' => 'npc:9', 'delta' => 2, 'before' => 0, 'after' => 2]],
    ],
    $baseRecord + [
        'request_id' => 'req_confirmed',
        'event' => 'request_finished',
        'outcome' => 'committed',
        'reason' => 'committed',
        'model_ms' => 120.0,
    ],
    array_merge($baseRecord, [
        'request_id' => 'req_old_profile',
        'event' => 'persistence_finished',
        'playthrough_id' => '18',
        'commit_state' => 'confirmed',
        'committed' => true,
        'changed_count' => 1,
        'changes' => [['subject' => 'npc:9', 'delta' => 2, 'before' => 50, 'after' => 52]],
    ]),
];
$limited = false;
$interactions = dashboardBuildInteractions($database, $records, $limited);
dashboardDataAssert(count($interactions) === 1 && !$limited, 'Active ledger did not produce exactly one bounded interaction.');
dashboardDataAssert(
    $interactions[0]['outcome'] === 'committed'
        && $interactions[0]['changes'][0]['before'] === 0
        && $interactions[0]['changes'][0]['after'] === 2
        && $interactions[0]['changes'][0]['applied'] === 2,
    'Confirmed persistence values did not correlate to the active playthrough.'
);
$databaseWithoutSubjectName = $database;
unset($databaseWithoutSubjectName['identities']['9']);
$limited = false;
$unknownSubjectName = dashboardBuildInteractions($databaseWithoutSubjectName, [], $limited);
dashboardDataAssert(
    $unknownSubjectName[0]['changes'][0]['current'] === null
        && $unknownSubjectName[0]['changes'][0]['current_state'] === 'unavailable',
    'A fallback NPC label was treated as a real relationship key.'
);

$otherRequestRecords = [
    $baseRecord + [
        'request_id' => 'req_other',
        'event' => 'persistence_finished',
        'commit_state' => 'unconfirmed',
        'committed' => false,
        'changed_count' => 0,
    ],
];
$skippedRecord = $baseRecord + ['request_id' => 'req_skip', 'event' => 'request_finished', 'outcome' => 'skipped', 'reason' => 'no_subject'];
$crossRequest = dashboardLogInteraction(
    $skippedRecord,
    $active,
    'active',
    [],
    [],
    dashboardIndexRecords(array_merge($otherRequestRecords, [$skippedRecord])),
    true,
    ''
);
dashboardDataAssert($crossRequest['outcome'] === 'skipped' && $crossRequest['changes'] === [], 'Another request’s uncertain commit contaminated this row.');

$logDerivedRecords = [
    $baseRecord + [
        'request_id' => 'req_cleanup',
        'event' => 'persistence_finished',
        'persistence_outcome' => 'failed',
        'persistence_reason' => 'release-failed',
        'commit_state' => 'confirmed',
        'committed' => true,
        'changed_count' => 1,
        'changes' => [['subject' => 'npc:9', 'delta' => 2, 'before' => 0, 'after' => 2]],
    ],
    $baseRecord + [
        'request_id' => 'req_cleanup',
        'event' => 'request_finished',
        'outcome' => 'failed',
        'reason' => 'persistence_failed',
    ],
];
$logIndex = dashboardIndexRecords($logDerivedRecords);
$cleanupRecord = $logDerivedRecords[1];
$listenerRows = ['7' => $database['rows'][0]];
$missingLogIdentity = dashboardLogInteraction(
    $cleanupRecord,
    $active,
    'active',
    ['7' => 'Listener', '8' => 'Speaker'],
    $listenerRows,
    $logIndex,
    true,
    'Player One'
);
$duplicateLogIdentity = dashboardLogInteraction(
    $cleanupRecord,
    $active,
    'active',
    ['7' => 'Listener', '8' => 'Speaker', '9' => 'Subject', '10' => 'Subject'],
    $listenerRows,
    $logIndex,
    true,
    'Player One'
);
dashboardDataAssert(
    $missingLogIdentity['changes'][0]['current'] === null
        && $missingLogIdentity['changes'][0]['current_state'] === 'unavailable',
    'Log-derived fallback labels were treated as real subject identities.'
);
dashboardDataAssert(
    $duplicateLogIdentity['changes'][0]['current'] === null
        && $duplicateLogIdentity['changes'][0]['current_state'] === 'ambiguous',
    'Log-derived duplicate subject names were reported as a known current affinity.'
);

$noProfileRecord = $baseRecord + ['request_id' => 'req_no_profile', 'event' => 'request_finished', 'outcome' => 'skipped'];
$limited = false;
$noProfile = dashboardBuildInteractions(['active_playthrough' => null, 'rows' => []], [$noProfileRecord], $limited);
dashboardDataAssert(count($noProfile) === 1 && $noProfile[0]['attribution'] === 'unverified', 'Missing active profile was reported as verified attribution.');

$manyRows = [];
foreach (['7', '8'] as $listenerId) {
    $events = [];
    for ($eventId = 1; $eventId <= 128; $eventId++) {
        $events[] = [
            'event_id' => $eventId,
            'utterance_id' => 'utt_' . str_pad((string)$eventId, 8, '0', STR_PAD_LEFT),
            'judgments' => [],
        ];
    }
    $manyRows[] = [
        'id' => $listenerId,
        'extended_data' => (object)['relationships' => new stdClass()],
        'plugin_extended_data' => (object)['mind_poisoning' => (object)[
            'playthrough_id' => $active,
            'floor_event_id' => 0,
            'events' => $events,
        ]],
    ];
}
$limited = false;
$bounded = dashboardBuildInteractions(['active_playthrough' => $active, 'rows' => $manyRows], [], $limited);
dashboardDataAssert(count($bounded) === 200 && $limited, 'Interaction truncation was not surfaced to the caller.');

echo "dashboard_data_test: ok\n";
