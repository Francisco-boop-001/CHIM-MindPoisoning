<?php
declare(strict_types=1);

namespace ChimMindPoisoning;

use JsonException;
use Throwable;

require_once __DIR__ . '/controls.php';
require_once __DIR__ . '/influence.php';
require_once __DIR__ . '/model.php';
require_once __DIR__ . '/store.php';

/**
 * Evaluate one registered solo-reflection ACK. The revalidation callback must
 * read the server-only PCV registration and confirm the exact active scope.
 */
function mindPoisoningEvaluateReflection(
    array $registration,
    array $gameRequest,
    StoreDb $store,
    callable $revalidate,
    ?callable $requestModel = null,
    ?RequestLog $requestLog = null
): string {
    $logFields = ['stage' => 'preflight', 'model_outcome' => 'not_called'];
    $requestLog?->context([
        'source_kind' => 'reflection',
        'payload_bytes' => is_string($gameRequest[3] ?? null) ? strlen($gameRequest[3]) : 0,
    ]);
    $requestLog?->event('reflection_started', 'debug', ['stage' => 'preflight']);

    try {
        $status = evaluateReflection($registration, $gameRequest, $store, $revalidate, $requestModel, $requestLog, $logFields);
    } catch (Throwable) {
        $status = 'failed';
        $logFields['reason'] ??= 'reflection-failed';
    }

    $outcome = $status === 'committed' ? 'committed' : ($status === 'failed' ? 'failed' : ($status === 'model-invalid' ? 'rejected' : 'skipped'));
    $reason = is_string($logFields['reason'] ?? null) ? $logFields['reason'] : $status;
    $requestLog?->finish($outcome, $reason, $logFields);
    return $status;
}

function reflectionRegistrationValid(array $registration): bool
{
    $keys = ['event_id', 'utterance_id', 'actor_id', 'actor_name', 'playthrough_id', 'config_id', 'rechat_target_hint', 'speech_hash'];
    $actualKeys = array_keys($registration);
    sort($actualKeys, SORT_STRING);
    sort($keys, SORT_STRING);
    if ($actualKeys !== $keys) {
        return false;
    }
    return is_int($registration['event_id']) && $registration['event_id'] > 0
        && is_string($registration['utterance_id']) && preg_match('/\Autt_[A-Za-z0-9_-]{8,128}\z/D', $registration['utterance_id']) === 1
        && is_int($registration['actor_id']) && $registration['actor_id'] > 0
        && is_string($registration['actor_name']) && trim($registration['actor_name']) !== ''
        && strlen($registration['actor_name']) <= 256 && preg_match('//u', $registration['actor_name']) === 1
        && is_string($registration['playthrough_id']) && $registration['playthrough_id'] !== ''
        && strlen($registration['playthrough_id']) <= 64
        && is_string($registration['config_id'])
        && preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\z/iD', $registration['config_id']) === 1
        && $registration['rechat_target_hint'] === 'explicit_disable_rechat'
        && is_string($registration['speech_hash']) && preg_match('/\A[a-f0-9]{64}\z/D', $registration['speech_hash']) === 1;
}

function reflectionAckPayload(array $gameRequest): ?array
{
    $raw = $gameRequest[3] ?? null;
    if (($gameRequest[0] ?? null) !== '_speech' || !is_string($raw) || strlen($raw) > 16384 || preg_match('//u', $raw) !== 1) {
        return null;
    }
    try {
        $payload = json_decode($raw, false, 32, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        return null;
    }
    if (!$payload instanceof \stdClass) {
        return null;
    }
    $fields = get_object_vars($payload);
    foreach (['speaker', 'listener', 'speech', 'utterance_id'] as $field) {
        if (!is_string($fields[$field] ?? null)) {
            return null;
        }
    }
    if (
        strlen($fields['speaker']) > 256 || strlen($fields['listener']) > 256 || strlen($fields['speech']) > 12000
        || trim($fields['speaker']) === '' || trim($fields['listener']) === '' || trim($fields['speech']) === ''
        || preg_match('//u', $fields['speaker']) !== 1 || preg_match('//u', $fields['listener']) !== 1 || preg_match('//u', $fields['speech']) !== 1
    ) {
        return null;
    }
    $fields['speaker'] = trim($fields['speaker']);
    $fields['listener'] = trim($fields['listener']);
    $fields['speech'] = trim($fields['speech']);
    $fields['utterance_id'] = trim($fields['utterance_id']);
    return preg_match('/\Autt_[A-Za-z0-9_-]{8,128}\z/D', $fields['utterance_id']) === 1 ? $fields : null;
}

function reflectionPlayerTransport(string $listener, string $playerName): bool
{
    foreach ([$playerName, 'Player', 'the Player', 'Dragonborn', 'the Dragonborn'] as $candidate) {
        if (trim($candidate) !== '' && sameActorName($listener, $candidate)) {
            return true;
        }
    }
    return false;
}

/** @param array<string, mixed> $logFields */
function evaluateReflection(
    array $registration,
    array $gameRequest,
    StoreDb $store,
    callable $revalidate,
    ?callable $requestModel,
    ?RequestLog $requestLog,
    array &$logFields
): string {
    $fail = static function (string $status, string $reason) use (&$logFields): string {
        $logFields['reason'] = $reason;
        return $status;
    };
    if (!reflectionRegistrationValid($registration) || ($gameRequest[0] ?? null) !== '_speech') {
        return $fail('invalid-payload', 'reflection-registration-invalid');
    }
    $requestLog?->context([
        'event_id' => $registration['event_id'],
        'utterance_id' => $registration['utterance_id'],
        'playthrough_id' => $registration['playthrough_id'],
        'speaker_id' => $registration['actor_id'],
        'speaker_kind' => 'npc',
        'opinion_owner_id' => $registration['actor_id'],
    ]);
    $interactionReason = null;
    if (speechAckInteractionStatus($interactionReason) !== 'ok') {
        return $fail('interaction-off', $interactionReason ?? 'interaction_off');
    }
    $pauseReason = null;
    if (mindPoisoningPauseStatus($pauseReason) !== 'enabled') {
        return $fail('paused', $pauseReason ?? 'plugin_paused');
    }
    if (filter_var($GLOBALS['NEVER_CLEAR_RELATIONSHIP_DATA'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
        return $fail('restore-policy', 'restore-policy');
    }
    if (!function_exists('chimIsGlobalLlmConnectorEnabled') || !\chimIsGlobalLlmConnectorEnabled('RELLLM_CONNECTOR')) {
        return $fail('disabled', 'connector-disabled');
    }
    $connectorId = positiveConnectorId($GLOBALS['RELLLM_CONNECTOR'] ?? null);
    if ($connectorId === null) {
        return $fail('connector-invalid', 'connector-invalid');
    }
    $profile = $store->activePlaythrough();
    $playerName = is_string($profile['player_name'] ?? null) ? trim($profile['player_name']) : '';
    if (
        !is_array($profile) || !is_string($profile['id'] ?? null) || $profile['id'] !== $registration['playthrough_id']
        || $playerName === '' || strlen($playerName) > 256 || preg_match('//u', $playerName) !== 1
    ) {
        return $fail('stale', 'reflection-scope-stale');
    }
    $ack = reflectionAckPayload($gameRequest);
    if (
        !is_array($ack)
        || $ack['utterance_id'] !== $registration['utterance_id']
        || !sameActorName($ack['speaker'], $registration['actor_name'])
        || reflectionPlayerTransport($ack['speaker'], $playerName)
        || !reflectionPlayerTransport($ack['listener'], $playerName)
        || !hash_equals($registration['speech_hash'], hash('sha256', $ack['speech']))
    ) {
        return $fail('event-mismatch', 'reflection-ack-mismatch');
    }
    $source = $store->acknowledgedEvent($registration['utterance_id']);
    if (
        !is_array($source) || ($source['event_id'] ?? null) !== $registration['event_id']
        || ($source['utterance_id'] ?? null) !== $registration['utterance_id']
        || !in_array($source['delivery_state'] ?? null, ['emitted', 'spoken'], true)
        || !is_string($source['source_data'] ?? null)
    ) {
        return $fail('event-unmatched', 'reflection-source-unmatched');
    }
    $parts = reflectionSourceParts($source['source_data']);
    if (!is_array($parts) || !sameActorName($parts['speaker'], $registration['actor_name'])) {
        return $fail('event-mismatch', 'reflection-source-mismatch');
    }
    try {
        if (!$revalidate($registration, 'pre_model')) {
            return $fail('stale', 'reflection-registration-stale');
        }
    } catch (Throwable) {
        return $fail('stale', 'reflection-registration-stale');
    }

    $identities = $store->npcIdentities();
    $listenerNpcMatches = array_values(array_filter($identities, static fn(array $row): bool => sameActorName($row['npc_name'] ?? null, $ack['listener'])));
    if ($listenerNpcMatches !== []) {
        return $fail('event-mismatch', 'reflection-listener-ambiguous');
    }
    $matches = array_values(array_filter($identities, static fn(array $row): bool => sameActorName($row['npc_name'] ?? null, $registration['actor_name'])));
    if (count($matches) !== 1 || (int)($matches[0]['id'] ?? 0) !== $registration['actor_id']) {
        return $fail('actor-unmatched', 'reflection-actor-unmatched');
    }
    $actor = $store->npcById($registration['actor_id']);
    if (!is_array($actor) || !sameActorName($actor['npc_name'] ?? null, $registration['actor_name'])) {
        return $fail('actor-stale', 'reflection-actor-stale');
    }
    $extendedData = $actor['extended_data'] ?? null;
    $pluginData = $actor['plugin_extended_data'] ?? null;
    if (!$extendedData instanceof \stdClass || !$pluginData instanceof \stdClass) {
        return $fail('actor-invalid', 'reflection-actor-state-invalid');
    }
    if (!empty($extendedData->relationships_locked) || (int)($actor['lock_profile'] ?? 0) !== 0) {
        return $fail('locked', 'relationship-locked');
    }

    $event = [
        'utterance_id' => $registration['utterance_id'],
        'event_id' => $registration['event_id'],
        'speaker_kind' => 'npc',
        'speaker_id' => $registration['actor_id'],
        'opinion_owner_id' => $registration['actor_id'],
        'listener_id' => null,
        'speaker_name' => $actor['npc_name'],
        'text' => $ack['speech'],
        'gamets' => $source['gamets'],
        'playthrough_id' => $registration['playthrough_id'],
        'player_name' => $playerName,
        'source_data' => $source['source_data'],
        'source_kind' => 'reflection',
        'speech_hash' => $registration['speech_hash'],
    ];
    $dedupeReason = null;
    if (eventAlreadyProcessed($actor, $event['playthrough_id'], $event['event_id'], $event['utterance_id'], $dedupeReason)) {
        return $fail('duplicate', $dedupeReason ?? 'duplicate-event');
    }
    $ledger = storedLedgerNamespace($pluginData);
    if ($ledger === null) {
        return $fail('invalid', 'ledger-invalid');
    }

    $subjects = findSubjects($event, $identities, $playerName);
    if (count($subjects) > 8) {
        return $fail('too-many-subjects', 'reflection-too-many-subjects');
    }
    if ($subjects === []) {
        return $fail('no-subjects', 'reflection-no-subjects');
    }
    if (array_key_exists('player', $subjects)) {
        $relationships = $extendedData->relationships ?? new \stdClass();
        if (!$relationships instanceof \stdClass) {
            return $fail('actor-invalid', 'relationships-invalid');
        }
        try {
            playerRelationshipKey($relationships, $playerName);
        } catch (\RuntimeException) {
            return $fail('actor-invalid', 'player-alias-ambiguous');
        }
    }
    foreach ($subjects as $token => $subject) {
        if ($token === 'player') {
            continue;
        }
        $target = $store->npcById($subject['id']);
        $targetMatches = array_values(array_filter($identities, static fn(array $row): bool => sameActorName($row['npc_name'] ?? null, $subject['name'])));
        if (!is_array($target) || !sameActorName($target['npc_name'] ?? null, $subject['name']) || count($targetMatches) !== 1 || (int)$targetMatches[0]['id'] !== $subject['id']) {
            return $fail('subject-stale', 'reflection-subject-stale');
        }
    }

    $history = null;
    $basisReason = null;
    $basis = reflectionBasisForEvent($actor, $store, $event, $history, $basisReason);
    if (!is_string($basis) || !is_array($history)) {
        return $fail('stale', $basisReason ?? 'reflection-basis-unavailable');
    }
    $pending = [];
    foreach ($subjects as $token => $subject) {
        if (!reflectionBasisProcessed($actor, $event['playthrough_id'], $basis, $token)) {
            $pending[$token] = $subject;
        }
    }
    if ($pending === []) {
        return $fail('duplicate', 'reflection-basis-duplicate');
    }
    $storedState = $ledger !== [] && $ledger['playthrough_id'] === $event['playthrough_id'] ? ($ledger['reflection_state'] ?? null) : null;
    $existingCount = is_array($storedState) && ($storedState['basis'] ?? null) === $basis ? count($storedState['subjects']) : 0;
    $newCount = count(array_diff(array_keys($pending), is_array($storedState) && ($storedState['basis'] ?? null) === $basis ? $storedState['subjects'] : []));
    if ($existingCount + $newCount > 32) {
        return $fail('duplicate', 'reflection-subject-cap');
    }
    $subjects = $pending;

    $interactionReason = null;
    if (speechAckInteractionStatus($interactionReason) !== 'ok') {
        return $fail('interaction-stale', $interactionReason ?? 'interaction-stale');
    }
    $pauseReason = null;
    if (mindPoisoningPauseStatus($pauseReason) !== 'enabled') {
        return $fail('paused', $pauseReason ?? 'plugin-paused');
    }
    try {
        if (!$revalidate($registration, 'pre_model')) {
            return $fail('stale', 'reflection-registration-stale');
        }
    } catch (Throwable) {
        return $fail('stale', 'reflection-registration-stale');
    }

    $requestModel ??= __NAMESPACE__ . '\\requestJudgments';
    $actorPrompt = $actor;
    $actorPrompt['extended_data'] = json_decode(json_encode($extendedData, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), true, 512, JSON_THROW_ON_ERROR);
    $messages = buildReflectionMessages($event + ['reflection_basis' => $basis], $actorPrompt, $subjects, $history);
    $requestLog?->context(['connector_id' => $connectorId, 'subject_count' => count($subjects)]);
    $requestLog?->event('reflection_model_started', 'debug', ['stage' => 'model', 'connector_id' => $connectorId, 'subject_count' => count($subjects)]);
    $started = hrtime(true);
    try {
        $response = $requestModel($messages);
    } catch (Throwable $error) {
        $reason = $error instanceof ModelRequestFailure ? $error->reasonCode : 'model_request_failed';
        $logFields['model_outcome'] = 'failed';
        $logFields['reason'] = $reason;
        $logFields['model_ms'] = RequestLog::elapsedMs($started);
        $requestLog?->event('reflection_model_finished', 'error', ['stage' => 'model', 'model_outcome' => 'failed', 'reason' => $reason, 'model_ms' => $logFields['model_ms']]);
        return 'failed';
    }
    $logFields['model_ms'] = RequestLog::elapsedMs($started);
    if (!is_string($response)) {
        $logFields['model_outcome'] = 'invalid';
        $requestLog?->event('reflection_model_finished', 'warning', ['stage' => 'model', 'model_outcome' => 'invalid', 'reason' => 'model_response_invalid_type', 'model_ms' => $logFields['model_ms']]);
        return $fail('model-invalid', 'model_response_invalid_type');
    }
    try {
        $judgments = parseJudgments($response, $subjects, $event['text']);
    } catch (JudgmentValidationFailure $error) {
        $logFields['model_outcome'] = 'invalid';
        $logFields['reason'] = $error->reasonCode;
        $requestLog?->event('reflection_model_finished', 'warning', ['stage' => 'model', 'model_outcome' => 'invalid', 'reason' => $error->reasonCode, 'model_ms' => $logFields['model_ms']]);
        return $fail('model-invalid', $error->reasonCode);
    }
    $logFields['model_outcome'] = 'valid';
    $requestLog?->event('reflection_model_finished', 'info', ['stage' => 'model', 'model_outcome' => 'valid', 'model_ms' => $logFields['model_ms']]);
    $logFields['stage'] = 'persistence';
    $status = persistJudgments($event + ['reflection_basis' => $basis], $subjects, $judgments, $store, $requestLog, $revalidate, $registration);
    $logFields['reason'] = $status;
    return $status;
}
