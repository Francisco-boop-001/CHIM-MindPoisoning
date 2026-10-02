<?php
declare(strict_types=1);

use function ChimMindPoisoning\mindPoisoningEvaluateReflection;
use function ChimMindPoisoning\mindPoisoningEvaluateReflectionReply;

final class Logger
{
    public static array $calls = [];

    public static function debug(string $message): void { self::$calls[] = ['debug', $message]; }
    public static function info(string $message): void { self::$calls[] = ['info', $message]; }
    public static function warn(string $message): void { self::$calls[] = ['warn', $message]; }
    public static function error(string $message): void { self::$calls[] = ['error', $message]; }
}

define('CHIM_MIND_POISONING_TEST_FIXTURES_ONLY', true);
require_once __DIR__ . '/runtime_test.php';
require_once __DIR__ . '/../server/reflection.php';

function diagnosticV1Fixture(string $speech = 'Lydia earned my trust again.'): array
{
    [, , , $db] = baseFixture();
    $actor = $db->npcs[11];
    $actor['extended_data']->relationships->Lydia = (object)['aff' => 10, 'type' => 'ally'];
    $actor['personality'] = 'A cautious companion';
    $db->npcs[11] = $actor;
    $utteranceId = 'utt_diag_00000001';
    $eventId = 100;
    $db->events = [$eventId => [
        'event_id' => $eventId,
        'utterance_id' => $utteranceId,
        'gamets' => 10.0,
        'source_data' => 'Aela: ' . $speech . ' (Talking to explicit_disable_rechat)',
        'delivery_state' => 'emitted',
        'type' => 'chat',
    ]];
    $registration = [
        'event_id' => $eventId,
        'utterance_id' => $utteranceId,
        'actor_id' => 11,
        'actor_name' => 'Aela',
        'playthrough_id' => '1',
        'config_id' => '12345678-1234-4234-8234-123456789abc',
        'rechat_target_hint' => 'explicit_disable_rechat',
        'speech_hash' => hash('sha256', $speech),
    ];
    $ack = ['_speech', 0, 10, json_encode([
        'speaker' => 'Aela',
        'listener' => 'Dragonborn',
        'speech' => $speech,
        'utterance_id' => $utteranceId,
    ], JSON_THROW_ON_ERROR)];
    return [$registration, $ack, $db];
}

function diagnosticReplyFixture(array $bodies): array
{
    [, , , $db] = baseFixture();
    $actor = $db->npcs[11];
    $actor['extended_data']->relationships->Lydia = (object)['aff' => 10, 'type' => 'ally'];
    $actor['personality'] = 'A cautious companion';
    $db->npcs[11] = $actor;
    $lines = [];
    $db->events = [];
    $ack = null;
    foreach ($bodies as $index => $body) {
        $eventId = 100 + $index;
        $utteranceId = 'utt_diag_' . str_pad((string)($index + 1), 8, '0', STR_PAD_LEFT);
        $db->events[$eventId] = [
            'event_id' => $eventId,
            'utterance_id' => $utteranceId,
            'gamets' => 10.0 + $index,
            'source_data' => 'Aela: ' . $body . ' (Talking to explicit_disable_rechat)',
            'delivery_state' => 'emitted',
            'type' => 'chat',
        ];
        $lines[] = ['event_id' => $eventId, 'utterance_id' => $utteranceId, 'speech_hash' => hash('sha256', $body)];
        $ack = ['_speech', 0, 10 + $index, json_encode([
            'speaker' => 'Aela',
            'listener' => 'Dragonborn',
            'speech' => $body,
            'utterance_id' => $utteranceId,
        ], JSON_THROW_ON_ERROR)];
    }
    $last = $lines[array_key_last($lines)];
    return [[
        'event_id' => $last['event_id'],
        'utterance_id' => $last['utterance_id'],
        'actor_id' => 11,
        'actor_name' => 'Aela',
        'playthrough_id' => '1',
        'config_id' => '12345678-1234-4234-8234-123456789abc',
        'rechat_target_hint' => 'explicit_disable_rechat',
        'speech_hash' => $last['speech_hash'],
        'lines' => $lines,
    ], $ack, $db, $lines];
}

function diagnosticResponse(string $evidence = 'Lydia earned my trust again.'): string
{
    return validModelResponse([[
        'subject' => 'npc:22',
        'delta' => 3,
        'reason' => 'Lydia earned trust.',
        'evidence' => $evidence,
    ]]);
}

function diagnosticTerminal(array $records, string $outcome, string $reason, string $level): array
{
    $terminals = array_values(array_filter($records, static fn(array $record): bool => ($record['event'] ?? null) === 'request_finished'));
    same(1, count($terminals), 'Each invoked reflection call must emit exactly one terminal record.');
    $record = $terminals[0];
    same($outcome, $record['outcome'] ?? null, 'The terminal outcome must describe the evaluator result.');
    same($reason, $record['reason'] ?? null, 'The terminal reason must be a fixed code.');
    same($level, $record['level'] ?? null, 'The terminal level must match the outcome.');
    foreach (['dialogue', 'speech', 'text', 'prompt', 'speech_hash', 'speech_digest', 'claim_token', 'exception', 'exception_message'] as $field) {
        check(!array_key_exists($field, $record), "The terminal record must omit {$field}.");
    }
    return $record;
}

function diagnosticNativeTerminal(): array
{
    $records = [];
    foreach (Logger::$calls as [, $json]) {
        $record = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (($record['event'] ?? null) === 'request_finished') {
            $records[] = $record;
        }
    }
    return $records;
}

resetAckLoggingInteraction();
[$registration, $ack, $db] = diagnosticV1Fixture();
Logger::$calls = [];
same('committed', mindPoisoningEvaluateReflection($registration, $ack, $db, static fn(): bool => true, static fn(): string => diagnosticResponse()), 'Omitting RequestLog must preserve a v1 evaluation.');
$defaultV1 = diagnosticTerminal(diagnosticNativeTerminal(), 'committed', 'committed', 'info');
same('100', $defaultV1['event_id'] ?? null, 'Default v1 delivery must retain the final event correlation.');

resetAckLoggingInteraction();
[$registration, $ack, $db, $lines] = diagnosticReplyFixture(['Lydia betrayed me.', 'I remember the evening.']);
$earlyAck = $ack;
$earlyPayload = json_decode($earlyAck[3], true, 32, JSON_THROW_ON_ERROR);
$earlyPayload['speech'] = 'Lydia betrayed me.';
$earlyPayload['utterance_id'] = $lines[0]['utterance_id'];
$earlyAck[3] = json_encode($earlyPayload, JSON_THROW_ON_ERROR);
Logger::$calls = [];
same('non-final', mindPoisoningEvaluateReflectionReply($registration, $earlyAck, $db, static fn(array $_registration, string $_phase): bool => true, null), 'An earlier v2 ACK must remain an informational skip.');
diagnosticTerminal(diagnosticNativeTerminal(), 'skipped', 'reflection-non-final-ack', 'info');

resetAckLoggingInteraction();
[$registration, $ack, $db] = diagnosticV1Fixture();
$registration['actor_id'] = '11';
$sinkRecords = [];
$observed = [];
$providedLog = captureRequestLog($sinkRecords);
$providedLog->observe(static function (array $record, string $level) use (&$observed): void {
    if (($record['event'] ?? null) === 'request_finished') {
        $observed[] = $record + ['level' => $level];
    }
});
Logger::$calls = [];
same('invalid-payload', mindPoisoningEvaluateReflection($registration, $ack, $db, static fn(): bool => true, null, $providedLog), 'A malformed registration must keep its existing status.');
diagnosticTerminal($sinkRecords, 'rejected', 'reflection-registration-invalid', 'warning');
same(1, count($observed), 'A supplied observer must receive the terminal record once.');
same([], Logger::$calls, 'A supplied RequestLog must not cause a second default logger.');

resetAckLoggingInteraction();
[$registration, $ack, $db] = diagnosticV1Fixture();
$ack[3] = '{malformed';
$records = [];
$malformedAckLog = captureRequestLog($records);
same('event-mismatch', mindPoisoningEvaluateReflection($registration, $ack, $db, static fn(): bool => true, null, $malformedAckLog), 'A malformed ACK must keep its existing status.');
diagnosticTerminal($records, 'rejected', 'reflection-ack-mismatch', 'warning');

resetAckLoggingInteraction();
[$registration, $ack, $db] = diagnosticV1Fixture();
$records = [];
$firstLog = captureRequestLog($records);
same('committed', mindPoisoningEvaluateReflection($registration, $ack, $db, static fn(): bool => true, static fn(): string => diagnosticResponse(), $firstLog), 'The replay fixture must commit once.');
$records = [];
$replayLog = captureRequestLog($records);
same('duplicate', mindPoisoningEvaluateReflection($registration, $ack, $db, static fn(): bool => true, null, $replayLog), 'A replay must remain an informational skip.');
diagnosticTerminal($records, 'skipped', 'duplicate-event', 'info');

resetAckLoggingInteraction();
[$registration, $ack, $db] = diagnosticV1Fixture();
$db->npcs[11]['extended_data']->relationships_locked = true;
$records = [];
$lockedLog = captureRequestLog($records);
same('locked', mindPoisoningEvaluateReflection($registration, $ack, $db, static fn(): bool => true, null, $lockedLog), 'A locked actor must remain an informational skip.');
diagnosticTerminal($records, 'skipped', 'relationship-locked', 'info');

resetAckLoggingInteraction();
[$registration, $ack, $db] = diagnosticV1Fixture('It rained yesterday.');
$records = [];
$noSubjectLog = captureRequestLog($records);
same('no-subjects', mindPoisoningEvaluateReflection($registration, $ack, $db, static fn(): bool => true, null, $noSubjectLog), 'A reflection with no eligible subjects must remain an informational skip.');
diagnosticTerminal($records, 'skipped', 'reflection-no-subjects', 'info');

resetAckLoggingInteraction();
[$registration, $ack, $db, $lines] = diagnosticReplyFixture(['Lydia betrayed me.', 'I remember the evening.']);
$db->events[$lines[0]['event_id']]['source_data'] = 'Aela: A different line. (Talking to explicit_disable_rechat)';
$records = [];
$sourceLog = captureRequestLog($records);
same('event-mismatch', mindPoisoningEvaluateReflectionReply($registration, $ack, $db, static fn(array $_registration, string $_phase): bool => true, null, $sourceLog), 'A source-body digest mismatch must fail closed.');
diagnosticTerminal($records, 'rejected', 'reflection-source-mismatch', 'warning');

resetAckLoggingInteraction();
[$registration, $ack, $db] = diagnosticReplyFixture(array_fill(0, 9, 'Lydia betrayed me.'));
$records = [];
$capLog = captureRequestLog($records);
same('invalid-payload', mindPoisoningEvaluateReflectionReply($registration, $ack, $db, static fn(array $_registration, string $_phase): bool => true, null, $capLog), 'The reply line cap must retain its established status.');
diagnosticTerminal($records, 'rejected', 'reflection-reply-too-many-lines', 'warning');

resetAckLoggingInteraction();
[$registration, $ack, $db] = diagnosticReplyFixture(['Lydia betrayed me.']);
$db->failCommit = true;
$records = [];
$failureLog = captureRequestLog($records);
same('failed', mindPoisoningEvaluateReflectionReply($registration, $ack, $db, static fn(array $_registration, string $_phase): bool => true, static fn(): string => diagnosticResponse('Lydia betrayed me.'), $failureLog), 'An uncertain persistence commit must remain failed.');
$uncertain = diagnosticTerminal($records, 'failed', 'failed', 'error');
same('unconfirmed', $uncertain['commit_state'] ?? null, 'An uncertain persistence result must remain explicit.');

resetAckLoggingInteraction();
[$registration, $ack, $db] = diagnosticV1Fixture();
$sinkFailureLog = new ChimMindPoisoning\RequestLog(static function (): void {
    throw new RuntimeException('sink exception fixture');
});
Logger::$calls = [];
same('committed', mindPoisoningEvaluateReflection($registration, $ack, $db, static fn(): bool => true, static fn(): string => diagnosticResponse(), $sinkFailureLog), 'A throwing supplied sink must not change the domain result.');
same(1, count($db->history), 'Sink failure must not prevent the relationship history snapshot.');
same([], Logger::$calls, 'A throwing supplied sink must not trigger a fallback duplicate logger.');

fwrite(STDOUT, "reflection diagnostics checks passed\n");
