<?php
declare(strict_types=1);

define('CHIM_MIND_POISONING_TEST_FIXTURES_ONLY', true);
require_once __DIR__ . '/runtime_test.php';
require_once __DIR__ . '/../server/reflection.php';

use function ChimMindPoisoning\mindPoisoningEvaluateReflection;
use function ChimMindPoisoning\mindPoisoningEvaluateReflectionReply;

function reflectionReplyFixture(array $bodies, array $states = []): array
{
    [, , , $db] = baseFixture();
    $actor = $db->npcs[11];
    $actor['extended_data']->relationships->Lydia = (object)['aff' => 10, 'type' => 'ally'];
    $actor['personality'] = 'A cautious companion';
    $db->npcs[11] = $actor;
    $db->events = [];
    $lines = [];
    $ack = null;
    foreach ($bodies as $index => $body) {
        $eventId = 100 + $index;
        $utteranceId = 'utt_reply_' . str_pad((string)($index + 1), 4, '0', STR_PAD_LEFT);
        $sourceData = 'Aela: ' . $body . ' (Talking to explicit_disable_rechat)';
        $db->events[$eventId] = [
            'event_id' => $eventId,
            'utterance_id' => $utteranceId,
            'gamets' => 10.0 + $index,
            'source_data' => $sourceData,
            'delivery_state' => $states[$index] ?? 'emitted',
            'type' => 'chat',
        ];
        $lines[] = [
            'event_id' => $eventId,
            'utterance_id' => $utteranceId,
            'speech_hash' => hash('sha256', $body),
        ];
        $ack = ['_speech', 0, 10 + $index, json_encode([
            'speaker' => 'Aela',
            'listener' => 'Dragonborn',
            'speech' => $body,
            'utterance_id' => $utteranceId,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)];
    }
    $last = $lines[array_key_last($lines)];
    $registration = [
        'event_id' => $last['event_id'],
        'utterance_id' => $last['utterance_id'],
        'actor_id' => 11,
        'actor_name' => 'Aela',
        'playthrough_id' => '1',
        'config_id' => '12345678-1234-4234-8234-123456789abc',
        'rechat_target_hint' => 'explicit_disable_rechat',
        'speech_hash' => $last['speech_hash'],
        'lines' => $lines,
    ];
    return [$registration, $ack, $db, $lines];
}

function reflectionReplyRevalidator(array $expected, array &$stages, ?callable $hook = null): Closure
{
    return static function (array $given, string $stage) use ($expected, &$stages, $hook): bool {
        $stages[] = $stage;
        if ($hook !== null) {
            $hook($given, $stage, count($stages));
        }
        return $given === $expected;
    };
}

function reflectionReplyResponse(string $evidence, int $delta = 3): string
{
    return validModelResponse([[
        'subject' => 'npc:22',
        'subject_mentioned' => true,
        'delta' => $delta,
        'reason' => 'The actor revises her opinion based on this reflection.',
        'evidence' => $evidence,
    ]]);
}

check(defined('ChimMindPoisoning\\MIND_POISONING_REFLECTION_REPLY_API_VERSION')
    && constant('ChimMindPoisoning\\MIND_POISONING_REFLECTION_REPLY_API_VERSION') === 2,
    'The separate full-reply capability must advertise version 2.');
check(function_exists('ChimMindPoisoning\\mindPoisoningEvaluateReflectionReply'),
    'The full-reply evaluator must be an opt-in entry point.');
same(1, constant('ChimMindPoisoning\\MIND_POISONING_REFLECTION_API_VERSION'), 'The v1 capability remains available.');

resetAckLoggingInteraction();
[$registration, $ack, $db, $lines] = reflectionReplyFixture([
    'Lydia deceived me after I trusted her.',
    'I still remember that evening clearly.',
]);
$stages = [];
$joined = 'Lydia deceived me after I trusted her. I still remember that evening clearly.';
$modelCalls = 0;
$revalidator = reflectionReplyRevalidator($registration, $stages, static function (array $_given, string $stage, int $call) use ($db): void {
    if ($stage === 'transaction' && $call === 3) {
        $db->events[100]['delivery_state'] = 'spoken';
    }
});
$status = mindPoisoningEvaluateReflectionReply(
    $registration,
    $ack,
    $db,
    $revalidator,
    static function (array $messages) use (&$modelCalls, $joined): string {
        $modelCalls++;
        $payload = json_decode($messages[1]['content'], true, 32, JSON_THROW_ON_ERROR);
        same($joined, $payload['untrusted_data']['current_reflection'] ?? null, 'The evaluator receives the ordered, joined source bodies.');
        check(isset($payload['untrusted_data']['candidates']['npc:22']), 'A subject mentioned only in an earlier line remains eligible.');
        return reflectionReplyResponse('Lydia deceived me');
    }
);
same('committed', $status, 'A matching final ACK evaluates the whole registered reply.');
same(1, $modelCalls, 'A reply uses one provider call.');
same(['pre_model', 'pre_model', 'transaction', 'transaction'], $stages, 'The complete registration is revalidated at every v2 checkpoint.');
same(13, $db->npcs[11]['extended_data']->relationships->Lydia->aff, 'The earlier-line subject receives one reply-level update.');
same(1, count($db->history), 'A reply creates one relationship history snapshot.');
$ledger = $db->npcs[11]['plugin_extended_data']->mind_poisoning;
same(2, count($ledger->events), 'Every source line is atomically entered in the existing ledger.');
same('reflection', $ledger->events[0]->source_kind ?? null, 'Every earlier source entry remains explicitly marked as reflection.');
same('reflection', $ledger->events[1]->source_kind ?? null, 'The final source entry remains explicitly marked as reflection.');
same([], $ledger->events[0]->judgments, 'Earlier reply lines have empty judgments.');
same(1, count($ledger->events[1]->judgments), 'Only the final ledger entry carries the reply judgment.');

resetAckLoggingInteraction();
[$registration, $finalAck, $db, $lines] = reflectionReplyFixture(['First line mentions Lydia.', 'Final line follows.']);
$earlierAck = ['_speech', 0, 10, json_encode([
    'speaker' => 'Aela', 'listener' => 'Dragonborn', 'speech' => 'First line mentions Lydia.',
    'utterance_id' => $lines[0]['utterance_id'],
], JSON_THROW_ON_ERROR)];
$earlyCalls = 0;
$earlyStages = [];
same('non-final', mindPoisoningEvaluateReflectionReply(
    $registration, $earlierAck, $db, reflectionReplyRevalidator($registration, $earlyStages),
    static function () use (&$earlyCalls): string { $earlyCalls++; return ''; }
), 'A matching earlier ACK is an informational non-final skip.');
same(0, $earlyCalls, 'An earlier ACK never reaches the provider.');
same([], $earlyStages, 'A non-final ACK does not enter model registration checkpoints.');

$badEarlyAck = $earlierAck;
$badPayload = json_decode($badEarlyAck[3], true, 32, JSON_THROW_ON_ERROR);
$badPayload['speaker'] = 'Serana';
$badEarlyAck[3] = json_encode($badPayload, JSON_THROW_ON_ERROR);
same('event-mismatch', mindPoisoningEvaluateReflectionReply(
    $registration, $badEarlyAck, $db, reflectionReplyRevalidator($registration, $earlyStages),
    static fn(): string => ''
), 'A different-actor early ACK is rejected instead of being treated as non-final.');

$wrongSpeechEarlyAck = $earlierAck;
$badPayload = json_decode($wrongSpeechEarlyAck[3], true, 32, JSON_THROW_ON_ERROR);
$badPayload['speech'] = 'A different line with the same utterance ID.';
$wrongSpeechEarlyAck[3] = json_encode($badPayload, JSON_THROW_ON_ERROR);
same('event-mismatch', mindPoisoningEvaluateReflectionReply(
    $registration, $wrongSpeechEarlyAck, $db, reflectionReplyRevalidator($registration, $earlyStages),
    static fn(): string => ''
), 'An early ACK with only the right identity but a different body is rejected.');

$malformedEarlyAck = $earlierAck;
$malformedEarlyAck[3] = '{';
same('event-mismatch', mindPoisoningEvaluateReflectionReply(
    $registration, $malformedEarlyAck, $db, reflectionReplyRevalidator($registration, $earlyStages),
    static fn(): string => ''
), 'A malformed ACK is rejected instead of being treated as non-final.');

resetAckLoggingInteraction();
[$registration, $ack, $db] = reflectionReplyFixture(['Lydia made a promise.', 'Aela remembers it.']);
$calls = 0;
$stages = [];
$mutatingPreModel = reflectionReplyRevalidator($registration, $stages, static function (array $_given, string $stage, int $call) use ($db): void {
    if ($stage === 'pre_model' && $call === 2) {
        $db->events[100]['source_data'] = 'Aela: This source changed. (Talking to explicit_disable_rechat)';
    }
});
same('stale', mindPoisoningEvaluateReflectionReply(
    $registration, $ack, $db, $mutatingPreModel,
    static function () use (&$calls): string { $calls++; return reflectionReplyResponse('Lydia made a promise.'); }
), 'A source changed by the second pre-model callback is caught before provider work.');
same(0, $calls, 'Changed pre-model sources do not reach the provider.');

foreach ([
    'aborted' => static function (MemoryStoreDb $db): void { $db->events[100]['delivery_state'] = 'aborted'; },
    'missing' => static function (MemoryStoreDb $db): void { unset($db->events[100]); },
    'foreign actor' => static function (MemoryStoreDb $db): void { $db->events[100]['source_data'] = 'Serana: Lydia made a promise. (Talking to explicit_disable_rechat)'; },
    'foreign target' => static function (MemoryStoreDb $db): void { $db->events[100]['source_data'] = 'Aela: Lydia made a promise. (Talking to Lydia)'; },
    'malformed' => static function (MemoryStoreDb $db): void { $db->events[100]['source_data'] = 'This source has no speaker separator.'; },
] as $label => $breakSource) {
    resetAckLoggingInteraction();
    [$registration, $ack, $db] = reflectionReplyFixture(['Lydia made a promise.', 'Aela remembers it.']);
    $breakSource($db);
    $calls = 0;
    $result = mindPoisoningEvaluateReflectionReply(
        $registration, $ack, $db, reflectionReplyRevalidator($registration, $stages),
        static function () use (&$calls): string { $calls++; return ''; }
    );
    check(in_array($result, ['event-unmatched', 'event-mismatch'], true), ucfirst($label) . ' reply sources fail closed.');
    same(0, $calls, ucfirst($label) . ' source fails before provider work.');
}

resetAckLoggingInteraction();
[$registration, $ack, $db] = reflectionReplyFixture(['Lydia made a promise.', 'Aela remembers it.']);
$registration['lines'][0]['speech_hash'] = str_repeat('a', 64);
$registration['speech_hash'] = $registration['lines'][1]['speech_hash'];
$calls = 0;
same('event-mismatch', mindPoisoningEvaluateReflectionReply(
    $registration, $ack, $db, reflectionReplyRevalidator($registration, $stages),
    static function () use (&$calls): string { $calls++; return ''; }
), 'A source body hash mismatch rejects before provider work.');
same(0, $calls, 'Hash-mismatched sources do not reach the provider.');

foreach (['reversed', 'duplicate utterance', 'duplicate event'] as $case) {
    resetAckLoggingInteraction();
    [$registration, $ack, $db] = reflectionReplyFixture(['Lydia made a promise.', 'Aela remembers it.']);
    if ($case === 'reversed') {
        $registration['lines'] = array_reverse($registration['lines']);
    } elseif ($case === 'duplicate utterance') {
        $registration['lines'][1]['utterance_id'] = $registration['lines'][0]['utterance_id'];
    } else {
        $registration['lines'][1]['event_id'] = $registration['lines'][0]['event_id'];
    }
    $calls = 0;
    same('invalid-payload', mindPoisoningEvaluateReflectionReply(
        $registration, $ack, $db, reflectionReplyRevalidator($registration, $stages),
        static function () use (&$calls): string { $calls++; return ''; }
    ), ucfirst($case) . ' registration is rejected.');
    same(0, $calls, ucfirst($case) . ' registration does not reach provider work.');
}

resetAckLoggingInteraction();
[$registration, $ack, $db] = reflectionReplyFixture(['Lydia made a promise.', 'Aela remembers it.']);
$mixed = $registration;
$foreignText = 'Aela answers in another reply.';
$foreignLine = ['event_id' => 105, 'utterance_id' => 'utt_foreign_0001', 'speech_hash' => hash('sha256', $foreignText)];
$db->events[105] = [
    'event_id' => 105, 'utterance_id' => $foreignLine['utterance_id'], 'gamets' => 15.0,
    'source_data' => 'Aela: ' . $foreignText . ' (Talking to explicit_disable_rechat)',
    'delivery_state' => 'emitted', 'type' => 'chat',
];
$mixed['lines'][1] = $foreignLine;
$mixed['event_id'] = $foreignLine['event_id'];
$mixed['utterance_id'] = $foreignLine['utterance_id'];
$mixed['speech_hash'] = $foreignLine['speech_hash'];
$ack = ['_speech', 0, 15, json_encode([
    'speaker' => 'Aela', 'listener' => 'Dragonborn', 'speech' => $foreignText,
    'utterance_id' => $foreignLine['utterance_id'],
], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)];
$calls = 0;
same('stale', mindPoisoningEvaluateReflectionReply(
    $mixed, $ack, $db, reflectionReplyRevalidator($registration, $stages),
    static function () use (&$calls): string { $calls++; return ''; }
), 'The server registration callback rejects a list mixed across replies.');
same(0, $calls, 'Mixed reply membership never reaches provider work.');

resetAckLoggingInteraction();
$nineBodies = array_fill(0, 9, 'Aela remembers Lydia.');
[$registration, $ack, $db] = reflectionReplyFixture($nineBodies);
$calls = 0;
$records = [];
same('invalid-payload', mindPoisoningEvaluateReflectionReply(
    $registration, $ack, $db, reflectionReplyRevalidator($registration, $stages),
    static function () use (&$calls): string { $calls++; return ''; }, captureRequestLog($records)
), 'More than eight reply lines are rejected instead of truncated.');
same(0, $calls, 'An over-count reply does not reach provider work.');
$overCountSummary = lastRequestSummary($records);
same('reflection-reply-too-many-lines', $overCountSummary['reason'] ?? null, 'The line cap is reported with its fixed source-safe reason.');
same((string)$registration['event_id'], $overCountSummary['event_id'] ?? null, 'The cap summary retains final event correlation.');
same($registration['utterance_id'], $overCountSummary['utterance_id'] ?? null, 'The cap summary retains final utterance correlation.');

resetAckLoggingInteraction();
[$registration, $ack, $db] = reflectionReplyFixture([str_repeat('é', 2001)]);
$calls = 0;
$records = [];
same('too-large', mindPoisoningEvaluateReflectionReply(
    $registration, $ack, $db, reflectionReplyRevalidator($registration, $stages),
    static function () use (&$calls): string { $calls++; return ''; }, captureRequestLog($records)
), 'The Unicode code-point cap rejects an overlong joined reply without truncation.');
same(0, $calls, 'An overlong reply does not reach provider work.');
$overTextSummary = lastRequestSummary($records);
same('reflection-reply-too-large', $overTextSummary['reason'] ?? null, 'The joined-text cap is reported with its fixed source-safe reason.');
same((string)$registration['event_id'], $overTextSummary['event_id'] ?? null, 'The text cap summary retains final event correlation.');
same($registration['utterance_id'], $overTextSummary['utterance_id'] ?? null, 'The text cap summary retains final utterance correlation.');

resetAckLoggingInteraction();
[$registration, $ack, $db] = reflectionReplyFixture(['Lydia made a promise.', 'Aela remembers it.']);
$db->npcs[11]['plugin_extended_data']->mind_poisoning = (object)[
    'playthrough_id' => '1', 'floor_event_id' => 100, 'events' => [],
];
$calls = 0;
same('duplicate', mindPoisoningEvaluateReflectionReply(
    $registration, $ack, $db, reflectionReplyRevalidator($registration, $stages),
    static function () use (&$calls): string { $calls++; return ''; }
), 'An earlier reply member at the existing floor blocks a final member above the floor.');
same(0, $calls, 'A reply crossing the ledger floor is rejected before provider work.');

resetAckLoggingInteraction();
[$registration, $ack, $db] = reflectionReplyFixture([str_repeat('😀', 2000)]);
$db->npcs[22]['npc_name'] = '😀';
$calls = 0;
same('committed', mindPoisoningEvaluateReflectionReply(
    $registration, $ack, $db, reflectionReplyRevalidator($registration, $stages),
    static function (array $messages) use (&$calls): string {
        $calls++;
        return validModelResponse([[
            'subject' => 'npc:22', 'subject_mentioned' => true, 'delta' => 0,
            'reason' => 'No supported change.', 'evidence' => '😀',
        ]]);
    }
), 'Two thousand four-byte characters fit both inclusive reply caps and retain a matched subject.');
same(1, $calls, 'A multibyte reply at both inclusive caps reaches provider work once.');

resetAckLoggingInteraction();
[$registration, $ack, $db, $lines] = reflectionReplyFixture(['Lydia betrayed Aela.', 'Aela remembers.']);
$v1Registration = $registration;
unset($v1Registration['lines']);
$firstAck = ['_speech', 0, 10, json_encode([
    'speaker' => 'Aela', 'listener' => 'Dragonborn', 'speech' => 'Lydia betrayed Aela.',
    'utterance_id' => $lines[0]['utterance_id'],
], JSON_THROW_ON_ERROR)];
$v1Registration['event_id'] = $lines[0]['event_id'];
$v1Registration['utterance_id'] = $lines[0]['utterance_id'];
$v1Registration['speech_hash'] = $lines[0]['speech_hash'];
$v1Calls = 0;
same('committed', mindPoisoningEvaluateReflection(
    $v1Registration, $firstAck, $db, static fn(): bool => true,
    static function () use (&$v1Calls): string { $v1Calls++; return reflectionReplyResponse('Lydia betrayed Aela.'); }
), 'The original v1 evaluator still records its exact single event.');
$v2Calls = 0;
same('duplicate', mindPoisoningEvaluateReflectionReply(
    $registration, $ack, $db, reflectionReplyRevalidator($registration, $stages),
    static function () use (&$v2Calls): string { $v2Calls++; return ''; }
), 'A v2 reply overlapping an event recorded by v1 is rejected before model work.');
same(0, $v2Calls, 'Cross-version overlap is deduplicated before provider work.');

resetAckLoggingInteraction();
[$registration, $ack, $db] = reflectionReplyFixture(['Lydia broke her promise.', 'Aela still remembers.']);
$calls = 0;
same('stale', mindPoisoningEvaluateReflectionReply(
    $registration,
    $ack,
    $db,
    reflectionReplyRevalidator($registration, $stages),
    static function () use (&$calls, $db): string {
        $calls++;
        $db->events[100]['source_data'] = 'Aela: changed after model work. (Talking to explicit_disable_rechat)';
        return reflectionReplyResponse('Lydia broke her promise.');
    }
), 'A source changed after model work is caught under the actor transaction.');
same(1, $calls, 'The post-model source mutation is caught after exactly one provider call.');
same(10, $db->npcs[11]['extended_data']->relationships->Lydia->aff, 'A post-model source mutation preserves affinity.');
same([], $db->history, 'A post-model source mutation creates no history snapshot.');
check(!property_exists($db->npcs[11]['plugin_extended_data'], 'mind_poisoning'), 'A post-model source mutation writes no ledger entries.');

resetAckLoggingInteraction();
[$registration, $ack, $db] = reflectionReplyFixture(['Lydia made a promise.', 'Aela remembers it.']);
$zeroCalls = 0;
$zeroModel = static fn(): string => validModelResponse([[
    'subject' => 'npc:22', 'subject_mentioned' => false, 'delta' => 0,
    'reason' => 'No new supported opinion change.', 'evidence' => 'Aela remembers it.',
]]);
same('committed', mindPoisoningEvaluateReflectionReply(
    $registration, $ack, $db, reflectionReplyRevalidator($registration, $stages),
    static function () use (&$zeroCalls, $zeroModel): string { $zeroCalls++; return $zeroModel(); }
), 'Confirmed zero decisions still commit dedupe entries for every source.');
$zeroLedger = $db->npcs[11]['plugin_extended_data']->mind_poisoning;
same(2, count($zeroLedger->events), 'A zero decision records all covered tuples.');
same([100, 101], array_map(static fn(object $entry): int => $entry->event_id, $zeroLedger->events), 'Every exact source event ID is recorded in order.');
same([], $zeroLedger->events[0]->judgments, 'A zero decision retains an empty earlier-line judgment list.');
same(0, $zeroLedger->events[1]->judgments[0]->delta, 'The final entry retains the confirmed zero judgment.');
$replayCalls = 0;
same('duplicate', mindPoisoningEvaluateReflectionReply(
    $registration, $ack, $db, reflectionReplyRevalidator($registration, $stages),
    static function () use (&$replayCalls): string { $replayCalls++; return ''; }
), 'A confirmed zero reply cannot be replayed through v2.');
$firstLineRegistration = array_diff_key($registration, ['lines' => true]);
$firstLineRegistration['event_id'] = 100;
$firstLineRegistration['utterance_id'] = 'utt_reply_0001';
$firstLineRegistration['speech_hash'] = hash('sha256', 'Lydia made a promise.');
$firstLineAck = ['_speech', 0, 10, json_encode([
    'speaker' => 'Aela', 'listener' => 'Dragonborn', 'speech' => 'Lydia made a promise.',
    'utterance_id' => 'utt_reply_0001',
], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)];
same('duplicate', mindPoisoningEvaluateReflection(
    $firstLineRegistration, $firstLineAck, $db, static fn(): bool => true,
    static function () use (&$replayCalls): string { $replayCalls++; return ''; }
), 'A confirmed zero reply line cannot be replayed through v1 either.');
same(0, $replayCalls, 'Confirmed zero replay is rejected before provider work.');

resetAckLoggingInteraction();
[$registration, $ack, $db] = reflectionReplyFixture(['Lydia broke a promise.', 'Aela still remembers.']);
$raceCalls = 0;
same('duplicate', mindPoisoningEvaluateReflectionReply(
    $registration,
    $ack,
    $db,
    reflectionReplyRevalidator($registration, $stages),
    static function () use (&$raceCalls, $db): string {
        $raceCalls++;
        $db->npcs[11]['plugin_extended_data']->mind_poisoning = (object)[
            'playthrough_id' => '1',
            'floor_event_id' => 0,
            'events' => [(object)['event_id' => 100, 'utterance_id' => 'utt_reply_0001', 'judgments' => []]],
        ];
        return reflectionReplyResponse('Lydia broke a promise.');
    }
), 'A member recorded between preflight and the lock prevents a reply-level transaction.');
same(1, $raceCalls, 'The transactional replay race is found after model work.');
same(10, $db->npcs[11]['extended_data']->relationships->Lydia->aff, 'A transaction-time member duplicate preserves affinity.');
same([], $db->history, 'A transaction-time member duplicate creates no snapshot.');
same(100, $db->npcs[11]['plugin_extended_data']->mind_poisoning->events[0]->event_id, 'The competing ledger entry remains intact.');

resetAckLoggingInteraction();
[$registration, $ack, $db] = reflectionReplyFixture(['Lydia kept her promise.', 'Aela remembers it.']);
$db->failCommit = true;
$records = [];
$observed = [];
$requestLog = captureRequestLog($records);
$requestLog->observe(static function (array $record, string $level) use (&$observed): void {
    if (($record['event'] ?? null) === 'request_finished') {
        $observed[] = $record + ['level' => $level];
    }
});
same('failed', mindPoisoningEvaluateReflectionReply(
    $registration,
    $ack,
    $db,
    reflectionReplyRevalidator($registration, $stages),
    static fn(): string => reflectionReplyResponse('Lydia kept her promise.'),
    $requestLog
), 'A false commit stays failed and unconfirmed.');
$summary = $observed[array_key_last($observed)] ?? [];
same('unconfirmed', $summary['commit_state'] ?? null, 'A false commit is diagnosed as unconfirmed.');
same(10, $db->npcs[11]['extended_data']->relationships->Lydia->aff, 'An unconfirmed commit does not claim an affinity update.');
same([], $db->history, 'An unconfirmed commit leaves no fixture snapshot.');
check(!property_exists($db->npcs[11]['plugin_extended_data'], 'mind_poisoning'), 'An unconfirmed commit leaves no fixture ledger.');

resetAckLoggingInteraction();
[$registration, $ack, $db] = reflectionReplyFixture(['Lydia made a promise.', 'Aela remembers it.']);
$stages = [];
$mutateFinalCheck = reflectionReplyRevalidator($registration, $stages, static function (array $_given, string $stage, int $call) use ($db): void {
    if ($stage === 'transaction' && $call === 4) {
        $db->events[100]['source_data'] = 'Aela: changed after staged write. (Talking to explicit_disable_rechat)';
    }
});
$calls = 0;
same('stale', mindPoisoningEvaluateReflectionReply(
    $registration, $ack, $db, $mutateFinalCheck,
    static function () use (&$calls): string { $calls++; return reflectionReplyResponse('Lydia made a promise.'); }
), 'Sources are checked after the final transaction callback and staged write.');
same(1, $calls, 'The staged-write mutation is detected after one provider call.');
same(10, $db->npcs[11]['extended_data']->relationships->Lydia->aff, 'A staged source mutation rolls back affinity.');
same([], $db->history, 'A staged source mutation rolls back its history snapshot.');
check(!property_exists($db->npcs[11]['plugin_extended_data'], 'mind_poisoning'), 'A staged source mutation rolls back every ledger entry.');

echo "reflection reply checks passed\n";
