<?php
declare(strict_types=1);

define('CHIM_MIND_POISONING_TEST_FIXTURES_ONLY', true);
require_once __DIR__ . '/runtime_test.php';
require_once __DIR__ . '/../server/reflection.php';

use function ChimMindPoisoning\mindPoisoningEvaluateReflection;

function reflectionFixture(array $options = []): array
{
    [, , , $db] = baseFixture();
    $actor = $db->npcs[11];
    $actor['extended_data']->relationships->Lydia = (object)['aff' => 10, 'type' => 'ally'];
    $actor['personality'] = 'A cautious companion';
    $db->npcs[11] = $actor;
    $speech = $options['speech'] ?? 'In my heart, Lydia earned my trust again.';
    $source = $options['source'] ?? 'Aela: I still think Lydia earned my trust. (Talking to explicit_disable_rechat)';
    $eventId = $options['event_id'] ?? 100;
    $utteranceId = $options['utterance_id'] ?? 'utt_0123456789abcdef';
    $deliveryState = $options['delivery_state'] ?? 'emitted';
    $db->events[$eventId] = [
        'event_id' => $eventId,
        'utterance_id' => $utteranceId,
        'gamets' => 10.0,
        'source_data' => $source,
        'delivery_state' => $deliveryState,
        'type' => 'chat',
    ];
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

function reflectionResponse(int $delta = 3): string
{
    return validModelResponse([[
        'subject' => 'npc:22',
        'subject_mentioned' => true,
        'delta' => $delta,
        'reason' => 'The actor recalls Lydia honoring a promise.',
        'evidence' => 'Lydia earned my trust again.',
    ]]);
}

resetAckLoggingInteraction();
[$registration, $ack, $db] = reflectionFixture();
$stages = [];
$modelCalls = 0;
$reflectionRecords = [];
$status = mindPoisoningEvaluateReflection(
    $registration,
    $ack,
    $db,
    static function (array $given, string $stage) use (&$stages, $registration): bool {
        $stages[] = $stage;
        return $given === $registration;
    },
    static function (array $messages) use (&$modelCalls): string {
        $modelCalls++;
        $payload = json_decode($messages[1]['content'], true, 512, JSON_THROW_ON_ERROR)['untrusted_data'];
        same('Lydia', $payload['candidates']['npc:22']['name'] ?? null, 'Solo model context should use the real subject catalog.');
        same('In my heart, Lydia earned my trust again.', $payload['current_reflection'] ?? null, 'The model must judge the exact ACK subtitle.');
        return reflectionResponse();
    },
    captureRequestLog($reflectionRecords)
);
same('committed', $status, 'A registered emitted line with its genuine ACK should commit.');
same(1, $modelCalls, 'One source ACK should make one provider request.');
check(in_array('pre_model', $stages, true) && in_array('transaction', $stages, true), 'Registration must be revalidated before model work and under transaction.');
same(2, count(array_filter($stages, static fn(string $stage): bool => $stage === 'transaction')), 'Registration must be checked after acquiring the actor lock and again before commit.');
same(13, $db->npcs[11]['extended_data']->relationships->Lydia->aff, 'A reflection may update only its actor-owned opinion edge.');
same(-50, $db->npcs[22]['extended_data']->relationships->Aela->aff, 'A reflection must not mutate another NPC.');
$reflectionSummary = lastRequestSummary($reflectionRecords);
same('reflection', $reflectionSummary['source_kind'] ?? null, 'Diagnostics must identify reflection provenance.');
same('11', $reflectionSummary['opinion_owner_id'] ?? null, 'Diagnostics must identify the actor who owns the opinion.');
check(!array_key_exists('listener_id', $reflectionSummary), 'Reflection diagnostics must not invent a listener identity.');
$reflectionLedger = $db->npcs[11]['plugin_extended_data']->mind_poisoning;
same('reflection', $reflectionLedger->events[0]->source_kind, 'The ledger must retain distinct reflection provenance.');
same(64, strlen($reflectionLedger->reflection_state->basis), 'The bounded actor evidence basis should be retained separately from rolling event history.');
same(['npc:22'], $reflectionLedger->reflection_state->subjects, 'The processed subject token should be recorded for the unchanged basis.');

same('duplicate', mindPoisoningEvaluateReflection($registration, $ack, $db, static fn(): bool => true, static function () use (&$modelCalls): string {
    $modelCalls++;
    return reflectionResponse();
}), 'An exact replay must be deduped.');
[$nextRegistration, $nextAck] = $registration === [] ? [[], []] : [$registration, $ack];
$nextRegistration['event_id'] = 101;
$nextRegistration['utterance_id'] = 'utt_abcdef0123456789';
$nextAck[3] = json_encode([
    'speaker' => 'Aela', 'listener' => 'Dragonborn', 'speech' => 'In my heart, Lydia earned my trust again.',
    'utterance_id' => $nextRegistration['utterance_id'],
], JSON_THROW_ON_ERROR);
$db->events[101] = [
    'event_id' => 101, 'utterance_id' => $nextRegistration['utterance_id'], 'gamets' => 11.0,
    'source_data' => 'Aela: I still think Lydia earned my trust. (Talking to explicit_disable_rechat)',
    'delivery_state' => 'spoken', 'type' => 'chat',
];
same('duplicate', mindPoisoningEvaluateReflection($nextRegistration, $nextAck, $db, static fn(): bool => true, static function () use (&$modelCalls): string {
    $modelCalls++;
    return reflectionResponse();
}), 'A new utterance over an unchanged profile/history basis must not compound.');
same(1, $modelCalls, 'Exact and unchanged-basis duplicates must both stop before provider work.');

$evictedLedger = $db->npcs[11]['plugin_extended_data']->mind_poisoning;
$evictedLedger->floor_event_id = 100;
$evictedLedger->events = [];
for ($eventId = 101; $eventId <= 228; $eventId++) {
    $evictedLedger->events[] = (object)['event_id' => $eventId, 'utterance_id' => 'utt_' . str_pad((string)$eventId, 16, '0', STR_PAD_LEFT), 'judgments' => []];
}
$db->npcs[11]['plugin_extended_data']->mind_poisoning = $evictedLedger;
$evictionRegistration = $registration;
$evictionRegistration['event_id'] = 300;
$evictionRegistration['utterance_id'] = 'utt_3000000000000000';
$evictionAck = $ack;
$evictionPayload = json_decode($evictionAck[3], true, 32, JSON_THROW_ON_ERROR);
$evictionPayload['utterance_id'] = $evictionRegistration['utterance_id'];
$evictionAck[3] = json_encode($evictionPayload, JSON_THROW_ON_ERROR);
$db->events[300] = [
    'event_id' => 300, 'utterance_id' => $evictionRegistration['utterance_id'], 'gamets' => 12.0,
    'source_data' => 'Aela: I still think Lydia earned my trust. (Talking to explicit_disable_rechat)',
    'delivery_state' => 'emitted', 'type' => 'chat',
];
same('duplicate', mindPoisoningEvaluateReflection($evictionRegistration, $evictionAck, $db, static fn(): bool => true, static function () use (&$modelCalls): string {
    $modelCalls++;
    return reflectionResponse();
}), 'The separate reflection basis must survive eviction from the 128-event rolling ledger.');
same(1, $modelCalls, 'Rolling-ledger eviction must not re-enable an unchanged reflection.');

resetAckLoggingInteraction();
[$registration, $ack, $db] = reflectionFixture();
$staleCalls = 0;
same('stale', mindPoisoningEvaluateReflection($registration, $ack, $db, static fn(): bool => false, static function () use (&$staleCalls): string {
    $staleCalls++;
    return reflectionResponse();
}), 'A changed or missing PCV registration must fail closed.');
same(0, $staleCalls, 'A stale registration must not reach the model.');
same([], $db->history, 'A stale registration must not create a snapshot.');

resetAckLoggingInteraction();
[$registration, $ack, $db] = reflectionFixture(['delivery_state' => 'aborted']);
$abortedCalls = 0;
same('event-unmatched', mindPoisoningEvaluateReflection($registration, $ack, $db, static fn(): bool => true, static function () use (&$abortedCalls): string {
    $abortedCalls++;
    return reflectionResponse();
}), 'Aborted speech must not be promoted to a reflection event.');
same(0, $abortedCalls, 'Aborted speech must stop before provider work.');

resetAckLoggingInteraction();
[$registration, $ack, $db] = reflectionFixture();
$badAck = $ack;
$badPayload = json_decode($badAck[3], true, 32, JSON_THROW_ON_ERROR);
$badPayload['speech'] = 'A different unrelated line.';
$badAck[3] = json_encode($badPayload, JSON_THROW_ON_ERROR);
$badAckCalls = 0;
same('event-mismatch', mindPoisoningEvaluateReflection($registration, $badAck, $db, static fn(): bool => true, static function () use (&$badAckCalls): string {
    $badAckCalls++;
    return reflectionResponse();
}), 'The PCV exact-emitted subtitle digest must match the genuine native ACK.');
same(0, $badAckCalls, 'An unrelated ACK must not reach the model.');

resetAckLoggingInteraction();
[$registration, $ack, $db] = reflectionFixture();
$db->busy = true;
$busyCalls = 0;
same('busy', mindPoisoningEvaluateReflection($registration, $ack, $db, static fn(): bool => true, static function () use (&$busyCalls): string {
    $busyCalls++;
    return reflectionResponse();
}), 'A contested actor lock must keep the reflection unapplied.');
same(1, $busyCalls, 'The provider must remain outside the actor database lock.');
same(10, $db->npcs[11]['extended_data']->relationships->Lydia->aff, 'A busy transaction must preserve the actor opinion.');
same([], $db->history, 'A busy transaction must not create a snapshot.');

resetAckLoggingInteraction();
[$registration, $ack, $db] = reflectionFixture();
$db->npcs[11]['extended_data']->relationships_locked = true;
$lockedCalls = 0;
same('locked', mindPoisoningEvaluateReflection($registration, $ack, $db, static fn(): bool => true, static function () use (&$lockedCalls): string {
    $lockedCalls++;
    return reflectionResponse();
}), 'A relationship lock must apply to the reflecting actor.');
same(0, $lockedCalls, 'A locked actor must stop before provider work.');

resetAckLoggingInteraction();
[$registration, $ack, $db] = reflectionFixture();
$zeroCalls = 0;
same('committed', mindPoisoningEvaluateReflection($registration, $ack, $db, static fn(): bool => true, static function (array $messages) use (&$zeroCalls): string {
    $zeroCalls++;
    return reflectionResponse(0);
}), 'An all-zero reflection should still persist the unchanged-basis dedupe token.');
same(10, $db->npcs[11]['extended_data']->relationships->Lydia->aff, 'An all-zero reflection must not alter affinity.');
$zeroNext = $registration;
$zeroNext['event_id'] = 102;
$zeroNext['utterance_id'] = 'utt_1020000000000000';
$zeroAck = $ack;
$zeroPayload = json_decode($zeroAck[3], true, 32, JSON_THROW_ON_ERROR);
$zeroPayload['utterance_id'] = $zeroNext['utterance_id'];
$zeroAck[3] = json_encode($zeroPayload, JSON_THROW_ON_ERROR);
$db->events[102] = [
    'event_id' => 102, 'utterance_id' => $zeroNext['utterance_id'], 'gamets' => 11.0,
    'source_data' => 'Aela: I still think Lydia earned my trust. (Talking to explicit_disable_rechat)',
    'delivery_state' => 'emitted', 'type' => 'chat',
];
same('duplicate', mindPoisoningEvaluateReflection($zeroNext, $zeroAck, $db, static fn(): bool => true, static function (array $messages) use (&$zeroCalls): string {
    $zeroCalls++;
    return reflectionResponse(0);
}), 'A zero decision must suppress another model call over the unchanged basis.');
same(1, $zeroCalls, 'The zero-decision basis must be durable.');

resetAckLoggingInteraction();
[$registration, $ack, $db] = reflectionFixture();
$basisEvent = [
    'event_id' => $registration['event_id'],
    'opinion_owner_id' => $registration['actor_id'],
    'player_name' => $db->playerName,
];
$basisHistory = null;
$basisReason = null;
$otherScopeBasis = ChimMindPoisoning\reflectionBasisForEvent($db->npcs[11], $db, $basisEvent, $basisHistory, $basisReason);
check(is_string($otherScopeBasis), 'The scope-reset test needs a stable evidence basis.');
$db->npcs[11]['plugin_extended_data']->mind_poisoning = (object)[
    'playthrough_id' => '2',
    'floor_event_id' => 0,
    'events' => [],
    'reflection_state' => (object)['basis' => $otherScopeBasis, 'subjects' => ['npc:33']],
];
same('committed', mindPoisoningEvaluateReflection($registration, $ack, $db, static fn(): bool => true, static fn(array $messages): string => reflectionResponse()), 'A new playthrough should start a fresh reflection ledger.');
$newScopeLedger = $db->npcs[11]['plugin_extended_data']->mind_poisoning;
same('1', $newScopeLedger->playthrough_id, 'The committed state should bind to the active playthrough.');
same(['npc:22'], $newScopeLedger->reflection_state->subjects, 'A prior scope subject must not leak into the new playthrough basis.');

resetAckLoggingInteraction();
[$registration, $ack, $db] = reflectionFixture();
$transactionCalls = 0;
$transactionModelCalls = 0;
same('stale', mindPoisoningEvaluateReflection($registration, $ack, $db, static function (array $given, string $stage) use (&$transactionCalls): bool {
    $transactionCalls++;
    return $stage !== 'transaction';
}, static function () use (&$transactionModelCalls): string {
    $transactionModelCalls++;
    return reflectionResponse();
}), 'A scope or registration change during model work must abort the actor transaction.');
same(1, $transactionModelCalls, 'The transaction recheck should happen only after the out-of-lock model call.');
same(3, $transactionCalls, 'A stale transaction should be checked twice before model work and once after acquiring the actor lock.');
same(10, $db->npcs[11]['extended_data']->relationships->Lydia->aff, 'A stale transaction must preserve the actor opinion.');
same([], $db->history, 'A stale transaction must not create a snapshot.');

resetAckLoggingInteraction();
[$registration, $ack, $db] = reflectionFixture();
$historyChangedCalls = 0;
same('stale', mindPoisoningEvaluateReflection($registration, $ack, $db, static fn(): bool => true, static function () use (&$historyChangedCalls, $db): string {
    $historyChangedCalls++;
    $db->reflectionRows[] = [
        'event_id' => 90, 'utterance_id' => 'utt_9090909090909090', 'delivery_state' => 'spoken',
        'people' => '|Aela|Lydia|', 'source_data' => 'Lydia: I kept my promise to Aela. (Talking to Aela)',
    ];
    return reflectionResponse();
}), 'A changed genuine-history basis during model work must not commit a stale judgment.');
same(1, $historyChangedCalls, 'The basis-stale case should make one provider call before its transaction recheck.');
same(10, $db->npcs[11]['extended_data']->relationships->Lydia->aff, 'A stale actor-history basis must preserve affinity.');
same([], $db->history, 'A stale actor-history basis must not create a snapshot.');

resetAckLoggingInteraction();
[$registration, $ack, $db] = reflectionFixture();
$failedRecords = [];
$privateText = json_decode($ack[3], true, 32, JSON_THROW_ON_ERROR)['speech'];
$privateHash = $registration['speech_hash'];
same('failed', mindPoisoningEvaluateReflection($registration, $ack, $db, static fn(): bool => true, static function (): string {
    throw new RuntimeException('provider fixture failure');
}, captureRequestLog($failedRecords)), 'Provider failures must remain failures.');
same([], $db->history, 'A provider failure must not create a snapshot.');
$encodedFailureLog = json_encode($failedRecords, JSON_THROW_ON_ERROR);
check(!str_contains($encodedFailureLog, $privateText) && !str_contains($encodedFailureLog, $privateHash), 'Logs must contain neither reflection dialogue nor the private speech digest.');

echo "reflection checks passed\n";
