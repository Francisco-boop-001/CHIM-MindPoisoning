<?php
declare(strict_types=1);

use ChimMindPoisoning\RequestLog;
use function ChimMindPoisoning\dashboardBuildInteractions;
use function ChimMindPoisoning\dashboardLogInteractions;
use function ChimMindPoisoning\dashboardFilters;
use function ChimMindPoisoning\dashboardParseLogLine;
use function ChimMindPoisoning\dashboardRecordMatches;
use function ChimMindPoisoning\renderDashboard;

require_once __DIR__ . '/../server/logging.php';
require_once __DIR__ . '/../server/dashboard_data.php';
require_once __DIR__ . '/../server/dashboard_view.php';

function writtenDashboardAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$rawRecords = [];
$log = new RequestLog(static function (string $json, string $level) use (&$rawRecords): void {
    $rawRecords[] = ['level' => $level, 'json' => $json];
}, true);
$log->context([
    'source_kind' => 'written_evidence',
    'event_id' => 444,
    'playthrough_id' => '7',
    'opinion_owner_id' => 22,
    'recipient_id' => 22,
    'speaker_kind' => 'npc',
    'speaker_id' => 99,
    'listener_id' => 22,
    'utterance_id' => 'utt_forged_written_source',
    'source_data' => 'private document body must not be logged',
    'source_digest' => str_repeat('a', 64),
]);
$log->event('persistence_finished', 'info', [
    'stage' => 'persist',
    'persistence_outcome' => 'committed',
    'persistence_reason' => 'committed',
    'commit_state' => 'confirmed',
    'committed' => true,
    'changed_count' => 1,
    'affinity_changed_count' => 1,
    'type_changed_count' => 1,
    'changes' => [['subject' => 'npc:33', 'delta' => 2, 'before' => 5.0, 'after' => 7.0]],
    'type_changes' => [['subject' => 'npc:33', 'before' => 'neutral', 'after' => 'ally']],
    'source_data' => 'private document body must not be logged',
    'source_digest' => str_repeat('b', 64),
    'model_reason' => 'private explanation that repeats the document body',
]);
$log->finish('processed', 'committed', ['commit_state' => 'confirmed', 'committed' => true]);

$records = [];
foreach ($rawRecords as $raw) {
    $record = dashboardParseLogLine('[' . $raw['level'] . '] ' . $raw['json']);
    writtenDashboardAssert(is_array($record), 'The closed written-evidence log records should parse.');
    $records[] = $record;
}
writtenDashboardAssert(
    count($records) === 2
        && ($records[0]['source_kind'] ?? null) === 'written_evidence'
        && ($records[0]['opinion_owner_id'] ?? null) === '22'
        && ($records[0]['recipient_id'] ?? null) === '22'
        && !isset($records[0]['speaker_id'], $records[0]['speaker_kind'], $records[0]['listener_id'], $records[0]['utterance_id']),
    'The parsed written record must retain recipient ownership without a claimed speaker, listener, or utterance.'
);
$serialized = json_encode($records, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
writtenDashboardAssert(
    !str_contains($serialized, 'private document body')
        && !str_contains($serialized, 'source_digest')
        && !str_contains($serialized, 'source_data')
        && !str_contains($serialized, 'model_reason'),
    'Parsed written history must not expose document text, digest, or model rationale.'
);

$diagnosticRawRecords = [];
$diagnosticLog = new RequestLog(static function (string $json, string $level) use (&$diagnosticRawRecords): void {
    $diagnosticRawRecords[] = ['level' => $level, 'json' => $json];
}, true);
$diagnosticLog->context([
    'source_kind' => 'written_evidence',
    'event_id' => 445,
    'playthrough_id' => '7',
    'opinion_owner_id' => 22,
    'recipient_id' => 22,
    'source_data' => 'private diagnostic document body',
    'source_digest' => str_repeat('c', 64),
    'speaker_id' => 99,
    'utterance_id' => 'utt_forged_diagnostic_source',
]);
$diagnosticLog->event('written_evidence_model_finished', 'info', [
    'stage' => 'model',
    'model_outcome' => 'valid',
    'model_ms' => 12.5,
    'subject_count' => 2,
    'model_reason' => 'private rationale',
    'source_data' => 'private diagnostic document body',
]);
$diagnosticLog->event('persistence_cleanup_failed', 'error', [
    'stage' => 'release',
    'reason' => 'advisory-unlock-not-confirmed',
    'committed' => true,
    'cleanup_failed' => true,
    'source_digest' => str_repeat('d', 64),
]);
$diagnosticRecords = [];
foreach ($diagnosticRawRecords as $raw) {
    $record = dashboardParseLogLine('[' . $raw['level'] . '] ' . $raw['json']);
    writtenDashboardAssert(is_array($record), 'The fixed written model and cleanup records should parse.');
    $diagnosticRecords[] = $record;
}
$diagnosticSerialized = json_encode($diagnosticRecords, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
writtenDashboardAssert(
    count($diagnosticRecords) === 2
        && ($diagnosticRecords[0]['event'] ?? null) === 'written_evidence_model_finished'
        && ($diagnosticRecords[0]['stage'] ?? null) === 'model'
        && ($diagnosticRecords[0]['model_outcome'] ?? null) === 'valid'
        && ($diagnosticRecords[1]['event'] ?? null) === 'persistence_cleanup_failed'
        && ($diagnosticRecords[1]['stage'] ?? null) === 'release'
        && ($diagnosticRecords[1]['reason'] ?? null) === 'advisory-unlock-not-confirmed'
        && ($diagnosticRecords[1]['committed'] ?? null) === true
        && ($diagnosticRecords[1]['cleanup_failed'] ?? null) === true
        && !str_contains($diagnosticSerialized, 'private diagnostic document body')
        && !str_contains($diagnosticSerialized, 'source_digest')
        && !str_contains($diagnosticSerialized, 'private rationale')
        && !str_contains($diagnosticSerialized, 'speaker_id')
        && !str_contains($diagnosticSerialized, 'utterance_id'),
    'The dashboard should retain only sanitized written model and cleanup event fields.'
);

$preflightRawRecords = [];
$preflightLog = new RequestLog(static function (string $json, string $level) use (&$preflightRawRecords): void {
    $preflightRawRecords[] = ['level' => $level, 'json' => $json];
}, true);
$preflightLog->context([
    'source_kind' => 'written_evidence',
    'event_id' => 446,
    'speaker_kind' => 'npc',
    'speaker_id' => 99,
    'listener_id' => 22,
    'utterance_id' => 'utt_forged_preflight_source',
    'source_data' => 'private preflight document body',
    'source_digest' => str_repeat('e', 64),
]);
$preflightLog->event('written_evidence_preflight_finished', 'info', [
    'stage' => 'preflight',
    'outcome' => 'processed',
    'reason' => 'receipt_confirmed',
    'model_outcome' => 'not_called',
    'model_ms' => 0.0,
    'persistence_outcome' => 'not_attempted',
    'commit_state' => 'not_attempted',
    'committed' => false,
    'elapsed_ms' => 1.2,
    'source_data' => 'private preflight document body',
    'source_digest' => str_repeat('f', 64),
    'model_reason' => 'private preflight rationale',
]);
$preflightRaw = $preflightRawRecords[0] ?? null;
writtenDashboardAssert(is_array($preflightRaw), 'The fixed preflight diagnostic should be emitted.');
$preflightRecord = dashboardParseLogLine('[' . $preflightRaw['level'] . '] ' . $preflightRaw['json']);
writtenDashboardAssert(
    is_array($preflightRecord)
        && ($preflightRecord['event'] ?? null) === 'written_evidence_preflight_finished'
        && ($preflightRecord['source_kind'] ?? null) === 'written_evidence'
        && ($preflightRecord['event_id'] ?? null) === '446'
        && !isset($preflightRecord['playthrough_id'])
        && !isset($preflightRecord['opinion_owner_id'])
        && !isset($preflightRecord['recipient_id'])
        && ($preflightRecord['stage'] ?? null) === 'preflight'
        && ($preflightRecord['outcome'] ?? null) === 'processed'
        && ($preflightRecord['reason'] ?? null) === 'receipt_confirmed'
        && ($preflightRecord['model_outcome'] ?? null) === 'not_called'
        && (is_int($preflightRecord['model_ms'] ?? null) || is_float($preflightRecord['model_ms'] ?? null))
        && (float)$preflightRecord['model_ms'] === 0.0
        && ($preflightRecord['persistence_outcome'] ?? null) === 'not_attempted'
        && ($preflightRecord['commit_state'] ?? null) === 'not_attempted'
        && ($preflightRecord['committed'] ?? null) === false
        && ($preflightRecord['elapsed_ms'] ?? 0) > 0,
    'Only the fixed preflight event may retain a valid event ID while scope and recipient identity are unknown.'
);
$preflightJson = json_encode($preflightRecord, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
writtenDashboardAssert(
    !str_contains($preflightJson, 'private preflight document body')
        && !str_contains($preflightJson, 'private preflight rationale')
        && !str_contains($preflightJson, 'source_digest')
        && !str_contains($preflightJson, 'speaker_id')
        && !str_contains($preflightJson, 'utterance_id')
        && dashboardRecordMatches($preflightRecord, dashboardFilters(['outcome' => 'processed'])),
    'The sanitized preflight record should remain available to diagnostics/filter JSONL without private or speech fields.'
);
$forgedPreflight = json_decode($preflightRaw['json'], true, 512, JSON_THROW_ON_ERROR);
$forgedPreflight['committed'] = true;
$forgedPreflight['commit_state'] = 'confirmed';
$forgedPreflight['changes'] = [['subject' => 'npc:33', 'before' => 5.0, 'after' => 7.0]];
$forgedPreflight['type_changes'] = [['subject' => 'npc:33', 'before' => 'neutral', 'after' => 'ally']];
$forgedPreflightRecord = dashboardParseLogLine('[info] ' . json_encode($forgedPreflight, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
writtenDashboardAssert(
    $forgedPreflightRecord === null
        || (($forgedPreflightRecord['committed'] ?? false) === false
            && ($forgedPreflightRecord['commit_state'] ?? 'not_attempted') === 'not_attempted'
            && ($forgedPreflightRecord['changes'] ?? []) === []
            && ($forgedPreflightRecord['type_changes'] ?? []) === []),
    'Preflight diagnostics must reject or strip any claim of a commit or applied relationship changes.'
);
$unsafePreflight = json_decode($preflightRaw['json'], true, 512, JSON_THROW_ON_ERROR);
$unsafePreflight['speaker_id'] = 99;
$unsafePreflight['utterance_id'] = 'utt_forged_preflight_actor';
writtenDashboardAssert(
    dashboardParseLogLine('[info] ' . json_encode($unsafePreflight, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)) === null,
    'Preflight diagnostics must reject speech or actor attribution.'
);
$prosePreflight = json_decode($preflightRaw['json'], true, 512, JSON_THROW_ON_ERROR);
$prosePreflight['reason'] = 'private letter text';
writtenDashboardAssert(
    dashboardParseLogLine('[info] ' . json_encode($prosePreflight, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)) === null,
    'Preflight diagnostics must reject raw prose in fixed reason fields.'
);
$invalidPreflightId = json_decode($preflightRaw['json'], true, 512, JSON_THROW_ON_ERROR);
$invalidPreflightId['playthrough_id'] = 'unknown-scope';
writtenDashboardAssert(
    dashboardParseLogLine('[info] ' . json_encode($invalidPreflightId, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)) === null,
    'A partial preflight identity may omit an unknown scope but may not invent an invalid one.'
);
$missingEventIdPreflight = json_decode($preflightRaw['json'], true, 512, JSON_THROW_ON_ERROR);
unset($missingEventIdPreflight['event_id']);
$missingEventIdPreflight['outcome'] = 'invalid';
$missingEventIdPreflight['reason'] = 'event_id_invalid';
$missingEventIdRecord = dashboardParseLogLine('[info] ' . json_encode($missingEventIdPreflight, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
writtenDashboardAssert(
    is_array($missingEventIdRecord)
        && !array_key_exists('event_id', $missingEventIdRecord)
        && ($missingEventIdRecord['outcome'] ?? null) === 'invalid'
        && ($missingEventIdRecord['reason'] ?? null) === 'event_id_invalid'
        && dashboardRecordMatches($missingEventIdRecord, dashboardFilters(['outcome' => 'invalid'])),
    'Only an invalid event-ID preflight may be retained without an event ID.'
);
$unverifiedMissingId = $missingEventIdPreflight;
$unverifiedMissingId['playthrough_id'] = '7';
$unverifiedMissingId['opinion_owner_id'] = 22;
$unverifiedMissingId['recipient_id'] = 22;
writtenDashboardAssert(
    dashboardParseLogLine('[info] ' . json_encode($unverifiedMissingId, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)) === null,
    'An invalid event ID cannot be correlated to an unverified scope or recipient.'
);
foreach ([0, null] as $presentInvalidEventId) {
    $presentInvalidPreflight = $missingEventIdPreflight;
    $presentInvalidPreflight['event_id'] = $presentInvalidEventId;
    writtenDashboardAssert(
        dashboardParseLogLine('[info] ' . json_encode($presentInvalidPreflight, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)) === null,
        'A present but invalid event ID must not be treated as an absent event ID.'
    );
}
$partialOrdinary = json_decode($rawRecords[1]['json'], true, 512, JSON_THROW_ON_ERROR);
unset($partialOrdinary['playthrough_id'], $partialOrdinary['opinion_owner_id'], $partialOrdinary['recipient_id']);
writtenDashboardAssert(
    dashboardParseLogLine('[info] ' . json_encode($partialOrdinary, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)) === null,
    'Ordinary written terminal records must still require verified scope and recipient identity.'
);

$forgedSpeaker = json_decode($rawRecords[1]['json'], true, 512, JSON_THROW_ON_ERROR);
$forgedSpeaker['speaker_kind'] = 'npc';
$forgedSpeaker['speaker_id'] = 99;
$forgedSpeaker['listener_id'] = 22;
$forgedSpeaker['utterance_id'] = 'utt_claimed_written_0001';
writtenDashboardAssert(
    dashboardParseLogLine('[info] ' . json_encode($forgedSpeaker, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)) === null,
    'The written parser must reject a record carrying forged speaker, listener, or utterance attribution.'
);
$wrongRecipient = json_decode($rawRecords[1]['json'], true, 512, JSON_THROW_ON_ERROR);
$wrongRecipient['recipient_id'] = 23;
writtenDashboardAssert(
    dashboardParseLogLine('[info] ' . json_encode($wrongRecipient, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)) === null,
    'A written record must reject a recipient that differs from its opinion owner.'
);

$limited = false;
$interactions = dashboardLogInteractions($records, null, false, $limited);
writtenDashboardAssert(
    count($interactions) === 1
        && !$limited
        && ($interactions[0]['source_kind'] ?? null) === 'written_evidence'
        && ($interactions[0]['opinion_owner'] ?? null) === 'NPC #22'
        && ($interactions[0]['speaker'] ?? null) === null
        && ($interactions[0]['listener'] ?? null) === null
        && ($interactions[0]['utterance_id'] ?? null) === null
        && ($interactions[0]['outcome'] ?? null) === 'processed'
        && ($interactions[0]['commit_state'] ?? null) === 'confirmed'
        && ($interactions[0]['changes'][0]['subject'] ?? null) === 'npc:33'
        && (float)($interactions[0]['changes'][0]['before'] ?? NAN) === 5.0
        && (float)($interactions[0]['changes'][0]['after'] ?? NAN) === 7.0
        && ($interactions[0]['type_changes'][0]['before'] ?? null) === 'neutral'
        && ($interactions[0]['type_changes'][0]['after'] ?? null) === 'ally',
    'The log-only interaction should join actual confirmed changes by request and scope without synthesizing an event identity.'
);
writtenDashboardAssert(
    (dashboardFilters(['outcome' => 'processed'])['outcome'] ?? null) === 'processed',
    'The dashboard should accept the actual closed written-terminal outcome in its filter.'
);
$database = [
    'active_playthrough' => '7',
    'player_name' => 'Test Player',
    'identities' => ['22' => 'Recipient', '33' => 'Changed NPC'],
    'rows' => [[
        'id' => '22',
        'npc_name' => 'Recipient',
        'extended_data' => (object)['relationships' => (object)[]],
        'plugin_extended_data' => (object)[],
    ]],
    'limited' => false,
    'invalid_ledgers' => false,
];
$preflightLogLimited = false;
writtenDashboardAssert(
    dashboardLogInteractions([$preflightRecord], null, false, $preflightLogLimited) === []
        && !$preflightLogLimited,
    'A preflight diagnostic must not be interpreted as a log-only interaction card.'
);
$preflightActiveLimited = false;
writtenDashboardAssert(
    dashboardBuildInteractions($database, [$preflightRecord], $preflightActiveLimited) === []
        && !$preflightActiveLimited,
    'A preflight diagnostic must not be interpreted as a database-backed effect card.'
);
$activeLimited = false;
$activeInteractions = dashboardBuildInteractions($database, $records, $activeLimited);
writtenDashboardAssert(
    count($activeInteractions) === 1
        && !$activeLimited
        && ($activeInteractions[0]['source_kind'] ?? null) === 'written_evidence'
        && ($activeInteractions[0]['attribution'] ?? null) === 'active'
        && ($activeInteractions[0]['speaker'] ?? null) === null
        && ($activeInteractions[0]['utterance_id'] ?? null) === null
        && ($activeInteractions[0]['commit_state'] ?? null) === 'confirmed'
        && ($activeInteractions[0]['changes'][0]['after'] ?? null) == 7,
    'The database-backed dashboard must recover the same written log card without creating a ledger event.'
);
$wrongScopeDatabase = $database;
$wrongScopeDatabase['active_playthrough'] = '8';
$wrongScopeLimited = false;
writtenDashboardAssert(
    dashboardBuildInteractions($wrongScopeDatabase, $records, $wrongScopeLimited) === [] && !$wrongScopeLimited,
    'A written record from another playthrough must not appear in the active-scope dashboard.'
);

$terminalOnlyRecords = array_values(array_filter(
    $records,
    static fn(array $record): bool => ($record['event'] ?? null) !== 'persistence_finished'
));
$terminalOnlyLimited = false;
$terminalOnlyInteractions = dashboardLogInteractions($terminalOnlyRecords, null, false, $terminalOnlyLimited);
writtenDashboardAssert(
    count($terminalOnlyInteractions) === 1
        && !$terminalOnlyLimited
        && ($terminalOnlyInteractions[0]['commit_state'] ?? null) === 'none'
        && ($terminalOnlyInteractions[0]['changes'] ?? null) === []
        && ($terminalOnlyInteractions[0]['type_changes'] ?? null) === [],
    'A terminal outcome without a retained persistence record must not imply a write or reconstruct changes from a receipt.'
);

$htmlModel = [
    'version' => '0.1.25',
    'generated_at' => '2026-10-19T12:00:00Z',
    'notices' => [],
    'source' => ['logs' => 'available', 'database' => 'unavailable', 'limited' => false],
    'interactions' => $interactions,
    'records' => [],
];
ob_start();
renderDashboard($htmlModel, ['tab' => 'interactions', 'theme' => 'day', 'q' => '', 'outcome' => '', 'level' => '']);
$html = ob_get_clean();
writtenDashboardAssert(
    str_contains($html, 'Written evidence evaluation')
        && str_contains($html, 'Recipient-side processing of a claimed document')
        && str_contains($html, 'claimed author and readership are not verified')
        && str_contains($html, 'Persistence <code>confirmed</code>')
        && str_contains($html, 'NPC #33')
        && !str_contains($html, 'private document body')
        && !str_contains($html, 'utt_forged_written_source')
        && !str_contains($html, 'NPC #99'),
    'The interactions card must show recipient-side logged results without document text or a claimed sender/utterance.'
);

$htmlModel['interactions'] = $terminalOnlyInteractions;
ob_start();
renderDashboard($htmlModel, ['tab' => 'interactions', 'theme' => 'day', 'q' => '', 'outcome' => '', 'level' => '']);
$terminalOnlyHtml = ob_get_clean();
writtenDashboardAssert(
    str_contains($terminalOnlyHtml, 'Persistence <code>unavailable from retained logs</code>')
        && !str_contains($terminalOnlyHtml, 'NPC #33'),
    'Without a retained persistence record, the card must say persistence is unavailable and show no inferred subject change.'
);

echo "written dashboard tests passed\n";
