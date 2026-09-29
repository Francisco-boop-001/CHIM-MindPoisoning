<?php
declare(strict_types=1);

$fallbackChild = ($argv[1] ?? '') === 'fallback-child';
if (!$fallbackChild) {
class Logger
{
    public static array $calls = [];
    public static bool $warnOnNextCall = false;

    private static function record(string $level, string $message): void
    {
        if (self::$warnOnNextCall) {
            self::$warnOnNextCall = false;
            trigger_error('native sink warning', E_USER_WARNING);
        }
        self::$calls[] = [$level, $message];
    }

    public static function debug(string $message): void { self::record('debug', $message); }
    public static function info(string $message): void { self::record('info', $message); }
    public static function warn(string $message): void { self::record('warn', $message); }
    public static function error(string $message): void { self::record('error', $message); }
}
}

require __DIR__ . '/../server/logging.php';

use ChimMindPoisoning\RequestLog;

if ($fallbackChild) {
    (new RequestLog())->event('fallback_check', 'info');
    exit(0);
}

function loggingCheck(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$records = [];
$log = new RequestLog(static function (string $json, string $level) use (&$records): void {
    $records[] = [$json, $level];
}, false);
$log->context([
    'event_id' => 47,
    'utterance_id' => 'utt_ABCDEFGH',
    'playthrough_id' => '82',
    'speaker_id' => 5,
    'listener_id' => 9,
    'payload_bytes' => 20000,
    'subject_count' => 9,
    'speech' => 'private speech must not be logged',
    'speaker_name' => 'Private Name',
    'api_key' => 'private credential',
]);
$log->event('model_started', 'info', [
    'speech_bytes' => 1200,
    'connector_id' => 50,
    'unknown_field' => 'drop me',
]);
$log->event('model_detail', 'debug', ['model_reason' => 'debug-only rationale']);
$firstOutcome = $log->finish('committed', 'ok', ['committed' => true, 'changed_count' => 1]);
$secondOutcome = $log->finish('failed', 'changed', ['committed' => false]);

loggingCheck($firstOutcome === 'committed' && $secondOutcome === 'committed', 'finish must return its first outcome idempotently.');
loggingCheck(count($records) === 2, 'default mode should emit info and one finish record, but no debug record.');
$decoded = array_map(static fn(array $record): array => json_decode($record[0], true, 512, JSON_THROW_ON_ERROR), $records);
loggingCheck($decoded[0]['schema_version'] === 1 && $decoded[0]['plugin'] === 'mind_poisoning', 'schema/plugin identity should be present.');
$manifest = json_decode((string)file_get_contents(__DIR__ . '/../server/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
loggingCheck($decoded[0]['version'] === $manifest['version'], 'version should come from the package manifest.');
loggingCheck(preg_match('/Z\z/', $decoded[0]['timestamp']) === 1, 'timestamp should be UTC.');
loggingCheck($decoded[0]['request_id'] === $decoded[1]['request_id'], 'records should share one request ID.');
loggingCheck($decoded[0]['event_id'] === '47' && $decoded[0]['utterance_id'] === 'utt_ABCDEFGH', 'validated context IDs should be retained.');
loggingCheck($decoded[0]['payload_bytes'] === 20000 && $decoded[0]['subject_count'] === 9, 'context should retain safe operational counts, including rejected sizes.');
loggingCheck($decoded[0]['speech_bytes'] === 1200, 'bounded speech size should be retained without speech content.');
loggingCheck(!isset($decoded[0]['speech'], $decoded[0]['speaker_name'], $decoded[0]['api_key'], $decoded[0]['unknown_field']), 'unallowlisted content must be dropped.');
loggingCheck(strpos($records[0][0], 'private') === false, 'raw speech and credential values must not appear.');
loggingCheck(count(array_filter($decoded, static fn(array $record): bool => ($record['event'] ?? '') === 'request_finished')) === 1, 'finish must emit exactly one summary.');

$inputIdRecords = [];
foreach (['input_1', 'input_9223372036854775807'] as $inputId) {
    $inputIdLog = new RequestLog(static function (string $json) use (&$inputIdRecords): void {
        $inputIdRecords[] = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }, false);
    $inputIdLog->context(['utterance_id' => $inputId]);
    $inputIdLog->event('input_id_check', 'info');
}
loggingCheck(
    array_column($inputIdRecords, 'utterance_id') === ['input_1', 'input_9223372036854775807'],
    'Positive CHIM input row IDs should be retained without changing legacy utt_ validation.'
);
$invalidInputIdRecords = [];
foreach (['input_0', 'input_01', 'input_9223372036854775808', 'input_123x'] as $inputId) {
    $inputIdLog = new RequestLog(static function (string $json) use (&$invalidInputIdRecords): void {
        $invalidInputIdRecords[] = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }, false);
    $inputIdLog->context(['utterance_id' => $inputId]);
    $inputIdLog->event('invalid_input_id_check', 'info');
}
loggingCheck(
    count($invalidInputIdRecords) === 4
        && array_reduce($invalidInputIdRecords, static fn(bool $valid, array $record): bool => $valid && !isset($record['utterance_id']), true),
    'Zero, leading-zero, overflow, and malformed input IDs must be dropped.'
);

$playerSourceRecords = [];
$playerSourceLog = new RequestLog(static function (string $json, string $level) use (&$playerSourceRecords): void {
    $playerSourceRecords[] = [$json, $level];
}, false);
$playerSourceLog->context([
    'speaker_kind' => 'player',
    'speaker_id' => 51,
    'speaker_name' => 'Private Player Name',
    'listener_id' => 9,
]);
$playerSourceLog->event('request_finished', 'info', ['outcome' => 'skipped', 'speaker_kind' => 'npc', 'speaker_id' => 77]);
$playerSource = json_decode($playerSourceRecords[0][0], true, 512, JSON_THROW_ON_ERROR);
loggingCheck(
    ($playerSource['speaker_kind'] ?? null) === 'player'
        && !isset($playerSource['speaker_id'], $playerSource['speaker_name'])
        && !str_contains($playerSourceRecords[0][0], 'Private Player Name'),
    'Player source logs should retain only the explicit kind, never a speaker ID or raw name.'
);

$invalidSpeakerKindRecords = [];
$invalidSpeakerKindLog = new RequestLog(static function (string $json, string $level) use (&$invalidSpeakerKindRecords): void {
    $invalidSpeakerKindRecords[] = $json;
}, false);
$invalidSpeakerKindLog->context(['speaker_kind' => 'Player Name', 'speaker_id' => 8]);
$invalidSpeakerKindLog->event('legacy_source', 'info');
$invalidSpeakerKind = json_decode($invalidSpeakerKindRecords[0], true, 512, JSON_THROW_ON_ERROR);
loggingCheck(
    !isset($invalidSpeakerKind['speaker_kind']) && ($invalidSpeakerKind['speaker_id'] ?? null) === '8',
    'Invalid source kinds must be dropped without inferring Player from the missing marker.'
);
$npcSourceRecords = [];
$npcSourceLog = new RequestLog(static function (string $json) use (&$npcSourceRecords): void {
    $npcSourceRecords[] = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
}, false);
$npcSourceLog->context(['speaker_kind' => 'npc', 'speaker_id' => 8]);
$npcSourceLog->event('npc_source', 'info');
loggingCheck(
    ($npcSourceRecords[0]['speaker_kind'] ?? null) === 'npc' && ($npcSourceRecords[0]['speaker_id'] ?? null) === '8',
    'The explicit NPC source kind should remain valid alongside legacy IDs.'
);

$debugRecords = [];
$debugLog = new RequestLog(static function (string $json, string $level) use (&$debugRecords): void {
    $debugRecords[] = [$json, $level];
}, true);
$changes = [];
for ($id = 1; $id <= 9; $id++) {
    $changes[] = [
        'subject' => 'npc:' . $id,
        'delta' => -1,
        'before' => $id === 1 ? 250.12345 : 2,
        'after' => 1,
        'name' => 'drop',
    ];
}
$debugLog->event('proposal', 'debug', [
    'subject' => 'npc:12',
    'delta' => -3,
    'model_reason' => "\n" . str_repeat('r', 300),
    'changes' => $changes,
]);
loggingCheck(count($debugRecords) === 1 && $debugRecords[0][1] === 'debug', 'diagnostic mode should enable debug records.');
$debugLine = $debugRecords[0][0];
loggingCheck(!str_contains($debugLine, "\n"), 'serialized JSON must occupy one physical line.');
$debugData = json_decode($debugLine, true, 512, JSON_THROW_ON_ERROR);
loggingCheck(strlen($debugData['model_reason']) <= 240, 'diagnostic rationale must be bounded.');
loggingCheck(count($debugData['changes']) === 8, 'nested change details must be capped at eight.');
loggingCheck(!isset($debugData['changes'][0]['name']), 'nested change fields must use the explicit allowlist.');
loggingCheck($debugData['changes'][0]['before'] === 250.12345, 'observed prior affinity must not be clamped or rounded.');
loggingCheck($debugData['subject'] === 'npc:12' && $debugData['delta'] === -3, 'subject tokens and bounded deltas should pass.');
loggingCheck(str_starts_with($debugData['model_reason'], "\n"), 'JSON escaping should preserve but not physically split embedded newlines.');

$previousLogLevel = getenv('MIND_POISONING_LOG_LEVEL');
$envRecords = [];
putenv('MIND_POISONING_LOG_LEVEL');
(new RequestLog(static function (string $json, string $level) use (&$envRecords): void {
    $envRecords[] = [$json, $level];
}))->event('env_default', 'debug');
loggingCheck($envRecords === [], 'debug should be off without the plugin-specific environment opt-in.');
putenv('MIND_POISONING_LOG_LEVEL=debug');
(new RequestLog(static function (string $json, string $level) use (&$envRecords): void {
    $envRecords[] = [$json, $level];
}))->event('env_debug', 'debug', ['model_reason' => 'diagnostic rationale']);
loggingCheck(count($envRecords) === 1, 'the exact debug environment value should enable diagnostics.');
if ($previousLogLevel === false) {
    putenv('MIND_POISONING_LOG_LEVEL');
} else {
    putenv('MIND_POISONING_LOG_LEVEL=' . $previousLogLevel);
}

$nativeLog = new RequestLog(null, false);
$nativeLog->event('warning_case', 'warning');
$nativeLog->event('error_case', 'error');
loggingCheck(Logger::$calls[0][0] === 'warn' && Logger::$calls[1][0] === 'error', 'native warning/error levels should preserve CHIM mappings.');
$nativeWarnings = [];
$oldDisplayErrors = ini_get('display_errors');
ini_set('display_errors', '1');
set_error_handler(static function (int $severity, string $message) use (&$nativeWarnings): bool {
    if ((error_reporting() & $severity) !== 0) {
        $nativeWarnings[] = $message;
    }
    return true;
});
ob_start();
Logger::$warnOnNextCall = true;
$nativeLog->event('native_sink_warning', 'warning');
$warningOutput = ob_get_clean();
restore_error_handler();
if ($oldDisplayErrors !== false) {
    ini_set('display_errors', $oldDisplayErrors);
}
loggingCheck($nativeWarnings === [] && $warningOutput === '', 'native sink warnings must not escape into the response.');

$severityRecords = [];
(new RequestLog(static function (string $json, string $level) use (&$severityRecords): void {
    $severityRecords[] = [$json, $level];
}, false))->finish('model-invalid', 'invalid-payload');
(new RequestLog(static function (string $json, string $level) use (&$severityRecords): void {
    $severityRecords[] = [$json, $level];
}, false))->finish('failed', 'write-failed');
(new RequestLog(static function (string $json, string $level) use (&$severityRecords): void {
    $severityRecords[] = [$json, $level];
}, false))->finish('rejected', 'model_response_invalid');
(new RequestLog(static function (string $json, string $level) use (&$severityRecords): void {
    $severityRecords[] = [$json, $level];
}, false))->finish('failed', 'judgment_validation_failed');
loggingCheck(
    $severityRecords[0][1] === 'warning'
    && $severityRecords[1][1] === 'error'
    && $severityRecords[2][1] === 'warning'
    && $severityRecords[3][1] === 'warning',
    'malformed/model-invalid outcomes should warn and failures should error except invalid model responses.'
);

$cleanupRecords = [];
$cleanupLog = new RequestLog(static function (string $json, string $level) use (&$cleanupRecords): void {
    $cleanupRecords[] = [$json, $level];
}, false);
$cleanupLog->context(['cleanup_failed' => true]);
$cleanupLog->finish('committed', 'committed', ['committed' => true, 'commit_state' => 'confirmed']);
$cleanupRecord = json_decode($cleanupRecords[0][0], true, 512, JSON_THROW_ON_ERROR);
loggingCheck(
    $cleanupRecords[0][1] === 'error'
    && ($cleanupRecord['outcome'] ?? null) === 'committed'
    && ($cleanupRecord['committed'] ?? null) === true
    && ($cleanupRecord['cleanup_failed'] ?? null) === true
    && ($cleanupRecord['commit_state'] ?? null) === 'confirmed',
    'cleanup failure should raise terminal severity without changing confirmed commit status.'
);

$commitStateRecords = [];
$commitStateLog = new RequestLog(static function (string $json) use (&$commitStateRecords): void {
    $commitStateRecords[] = $json;
}, false);
$commitStateLog->event('commit_state_check', 'info', ['commit_state' => 'unconfirmed', 'unknown' => 'drop']);
$commitStateLog->event('commit_state_invalid', 'info', ['commit_state' => 'maybe']);
$commitStateData = json_decode($commitStateRecords[0], true, 512, JSON_THROW_ON_ERROR);
$invalidCommitStateData = json_decode($commitStateRecords[1], true, 512, JSON_THROW_ON_ERROR);
loggingCheck(
    ($commitStateData['commit_state'] ?? null) === 'unconfirmed'
    && !isset($invalidCommitStateData['commit_state']),
    'commit_state should retain only its documented enum values.'
);

$fallbackPath = tempnam(__DIR__, '.logging-fallback-');
loggingCheck(is_string($fallbackPath), 'fallback log scratch file should be created.');
try {
    $command = [PHP_BINARY, '-d', 'error_log=' . $fallbackPath, __FILE__, 'fallback-child'];
    $pipes = [];
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    loggingCheck(is_resource($process), 'fallback subprocess should start.');
    fclose($pipes[0]);
    $childOutput = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $childError = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);
    loggingCheck($exitCode === 0, 'fallback subprocess should exit cleanly: ' . $childError . $childOutput);
    $fallbackLines = file($fallbackPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    loggingCheck(is_array($fallbackLines) && count($fallbackLines) === 1, 'PHP error_log fallback should write one record.');
    $jsonStart = strpos($fallbackLines[0], '{');
    loggingCheck($jsonStart !== false, 'PHP error_log fallback should contain a JSON payload.');
    $fallbackData = json_decode(substr($fallbackLines[0], $jsonStart), true, 512, JSON_THROW_ON_ERROR);
    loggingCheck(($fallbackData['event'] ?? null) === 'fallback_check', 'PHP error_log fallback should receive the structured record.');
} finally {
    if (is_file($fallbackPath)) {
        unlink($fallbackPath);
    }
}

$throwingLog = new RequestLog(static function (string $json, string $level): void {
    throw new RuntimeException('sink failure');
}, false);
$throwingLog->context(['event_id' => 'not-an-id', 'utterance_id' => "bad\nvalue"]);
$throwingLog->event('sink_failure', 'error', ['persistence_reason' => 'rollback']);
loggingCheck($throwingLog->finish('failed', 'sink-error') === 'failed', 'logging failures must never escape or prevent a result.');

echo "logging checks passed\n";
