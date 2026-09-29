<?php
declare(strict_types=1);

namespace ChimMindPoisoning;

use JsonException;
use Throwable;

$chimMindPoisoningIsSpeech = isset($gameRequest) && is_array($gameRequest) && ($gameRequest[0] ?? null) === '_speech';
$chimMindPoisoningRequestLog = null;
try {
    @require_once __DIR__ . '/logging.php';
    if (!class_exists(RequestLog::class, false)) {
        throw new \RuntimeException('Request logger is unavailable.');
    }
    if ($chimMindPoisoningIsSpeech) {
        $chimMindPoisoningRequestLog = new RequestLog();
        $chimMindPoisoningBootstrapPayload = $gameRequest[3] ?? null;
        $chimMindPoisoningBootstrapContext = [
            'payload_bytes' => is_string($chimMindPoisoningBootstrapPayload) ? strlen($chimMindPoisoningBootstrapPayload) : 0,
        ];
        if (is_string($chimMindPoisoningBootstrapPayload) && strlen($chimMindPoisoningBootstrapPayload) <= 16384) {
            try {
                $chimMindPoisoningBootstrapDecoded = json_decode($chimMindPoisoningBootstrapPayload, true, 32, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                $chimMindPoisoningBootstrapDecoded = null;
            }
            $chimMindPoisoningBootstrapUtteranceId = is_array($chimMindPoisoningBootstrapDecoded)
                ? ($chimMindPoisoningBootstrapDecoded['utterance_id'] ?? null)
                : null;
            if (
                is_string($chimMindPoisoningBootstrapUtteranceId)
                && preg_match('/\Autt_[A-Za-z0-9_-]{8,128}\z/D', $chimMindPoisoningBootstrapUtteranceId) === 1
            ) {
                $chimMindPoisoningBootstrapContext['utterance_id'] = $chimMindPoisoningBootstrapUtteranceId;
            }
        }
        $chimMindPoisoningRequestLog->context($chimMindPoisoningBootstrapContext);
    }
} catch (Throwable) {
    if ($chimMindPoisoningIsSpeech) {
        @error_log('Mind Poisoning ACK failed: logging_bootstrap_failed');
        return;
    }
    $chimMindPoisoningRequestLog = null;
}

try {
    @require_once __DIR__ . '/influence.php';
    @require_once __DIR__ . '/model.php';
    @require_once __DIR__ . '/store.php';
} catch (Throwable) {
    if ($chimMindPoisoningRequestLog instanceof RequestLog) {
        $chimMindPoisoningRequestLog->finish('failed', 'dependency_bootstrap_failed', ['stage' => 'bootstrap']);
        return;
    }
    throw new \RuntimeException('Mind Poisoning dependency bootstrap failed.');
}

function uniqueNpcNamed(array $identities, string $name): ?array
{
    $matches = array_values(array_filter(
        $identities,
        static fn(array $row): bool => sameActorName($row['npc_name'] ?? null, $name)
    ));
    return count($matches) === 1 ? $matches[0] : null;
}

function promptNpcCopy(array $npc): array
{
    $copy = $npc;
    $extendedData = $npc['extended_data'] ?? new \stdClass();
    if (!$extendedData instanceof \stdClass) {
        throw new \RuntimeException('NPC relationship context is unavailable.');
    }
    $copy['extended_data'] = json_decode(
        json_encode($extendedData, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        true,
        512,
        JSON_THROW_ON_ERROR
    );
    return $copy;
}

function isPlayerName(string $name, string $playerName): bool
{
    foreach (['Player', 'the Player', 'Player Character', 'the Player Character', 'Dragonborn', 'the Dragonborn', $playerName] as $alias) {
        if ($alias !== '' && sameActorName($name, $alias)) {
            return true;
        }
    }
    return false;
}

function validatedPlayerInputInsert(array $gameRequest, array $insert): ?array
{
    $type = $insert['type'] ?? null;
    $types = playerInputTypes();
    $ts = $insert['ts'] ?? null;
    $gamets = $insert['gamets'] ?? null;
    $data = $insert['data'] ?? null;
    if (
        !is_string($type) || !in_array($type, $types, true) || ($gameRequest[0] ?? null) !== $type
        || (!is_string($ts) && !is_int($ts)) || !is_numeric($ts) || (string)$ts !== (string)($gameRequest[1] ?? '')
        || (!is_string($gamets) && !is_int($gamets)) || !is_numeric($gamets) || (string)$gamets !== (string)($gameRequest[2] ?? '')
        || !is_string($data) || $data !== ($gameRequest[3] ?? null)
        || !is_int($insert['localts'] ?? null) || $insert['localts'] < 1
        || ($insert['sess'] ?? null) !== 'web'
        || !is_string($insert['people'] ?? null) || trim($insert['people']) === ''
    ) {
        return null;
    }

    return [
        'type' => $type,
        'ts' => $ts,
        'gamets' => $gamets,
        'data' => $data,
        'localts' => $insert['localts'],
        'sess' => 'web',
    ];
}

function speechAckInteractionStatus(?string &$reason = null): string
{
    $reason = 'interaction_off';
    if (!function_exists('chimInteractionBegin') || !function_exists('chimInteractionState')) {
        $reason = 'interaction_helpers_unavailable';
        return 'interaction-off';
    }
    try {
        \chimInteractionBegin();
        $requestGeneration = $GLOBALS['chim_interaction_generation'] ?? null;
        $state = \chimInteractionState();
        if (
            !is_int($requestGeneration)
            || !is_array($state)
            || !is_bool($state['enabled'] ?? null)
            || !is_int($state['generation'] ?? null)
        ) {
            $reason = 'interaction_state_invalid';
            return 'interaction-off';
        }
        if ($state['generation'] !== $requestGeneration) {
            $reason = 'interaction_generation_stale';
            return 'interaction-stale';
        }
        if (!$state['enabled']) {
            return 'interaction-off';
        }
        $reason = 'ok';
        return 'ok';
    } catch (Throwable) {
        $reason = 'interaction_state_invalid';
        return 'interaction-off';
    }
}

/**
 * Evaluate one exact _speech acknowledgement. The optional callable is a test seam;
 * production uses the configured requestJudgments adapter.
 */
function handleSpeechAck(
    array $gameRequest,
    StoreDb $store,
    ?callable $requestModel = null,
    ?RequestLog $requestLog = null
): string
{
    if (($gameRequest[0] ?? null) !== '_speech') {
        return 'ignored';
    }

    $logFields = ['stage' => 'preflight', 'model_outcome' => 'not_called'];
    $requestLog?->context(['payload_bytes' => is_string($gameRequest[3] ?? null) ? strlen($gameRequest[3]) : 0]);
    $requestLog?->event('ack_started', 'debug', ['stage' => 'preflight']);

    $status = evaluateInfluenceRequest($gameRequest, $store, $requestModel, $requestLog, $logFields);
    return finishInfluenceRequest($status, $logFields, $requestLog);
}

/** @param array<string, mixed> $logFields */
function finishInfluenceRequest(string $status, array $logFields, ?RequestLog $requestLog): string
{
    $outcome = 'skipped';
    $reason = $logFields['reason'] ?? $status;
    if ($status === 'committed') {
        $outcome = 'committed';
        $reason = 'committed';
    } elseif ($status === 'failed') {
        $outcome = 'failed';
        $reason = $logFields['reason'] ?? 'evaluation_failed';
    } elseif ($status === 'model-invalid') {
        $outcome = 'rejected';
    }
    $requestLog?->finish($outcome, $reason, $logFields);
    return $status;
}

/**
 * Evaluate a single postrequest Player input event. $sourceInsert is the exact
 * main.php eventlogInsert snapshot, retained in $GLOBALS until plugin postrequest.
 */
function handlePlayerInput(
    array $gameRequest,
    mixed $sourceInsert,
    StoreDb $store,
    ?callable $requestModel = null,
    ?RequestLog $requestLog = null
): string
{
    if (!in_array($gameRequest[0] ?? null, playerInputTypes(), true)) {
        return 'ignored';
    }

    $logFields = ['stage' => 'preflight', 'model_outcome' => 'not_called'];
    $requestLog?->context(['speaker_kind' => 'player']);
    $requestLog?->context(['payload_bytes' => is_string($gameRequest[3] ?? null) ? strlen($gameRequest[3]) : 0]);
    $requestLog?->event('player_input_started', 'debug', ['stage' => 'preflight']);
    $status = evaluateInfluenceRequest($gameRequest, $store, $requestModel, $requestLog, $logFields, is_array($sourceInsert) ? $sourceInsert : []);
    return finishInfluenceRequest($status, $logFields, $requestLog);
}

/** @param array<string, mixed> $logFields */
function evaluateInfluenceRequest(
    array $gameRequest,
    StoreDb $store,
    ?callable $requestModel,
    ?RequestLog $requestLog,
    array &$logFields,
    ?array $playerInputInsert = null
): string
{
    try {
        $interactionReason = null;
        $interactionStatus = speechAckInteractionStatus($interactionReason);
        if ($interactionStatus !== 'ok') {
            $logFields['reason'] = $interactionReason;
            return $interactionStatus;
        }
        if (!function_exists('chimIsGlobalLlmConnectorEnabled') || !\chimIsGlobalLlmConnectorEnabled('RELLLM_CONNECTOR')) {
            return 'disabled';
        }
        $connectorId = filter_var($GLOBALS['RELLLM_CONNECTOR'] ?? null, FILTER_VALIDATE_INT);
        if ($connectorId === false || $connectorId === null || $connectorId < 1) {
            return 'connector-invalid';
        }
        $logFields['connector_id'] = $connectorId;
        $requestLog?->context(['connector_id' => $connectorId]);
        if ($playerInputInsert === null) {
            if (
                !function_exists('extractSpeakerNameFromChatEvent')
                || !function_exists('extractTalkTargetMetadata')
                || !function_exists('talkTargetsIncludeName')
            ) {
                return 'event-helpers-unavailable';
            }
        } elseif (
            !function_exists('chimDecodePlayerRoutingSnapshotField')
            || !function_exists('parsePeoplePipeList')
        ) {
            return 'routing-helpers-unavailable';
        }
        if (filter_var($GLOBALS['NEVER_CLEAR_RELATIONSHIP_DATA'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return 'restore-policy';
        }

        if ($playerInputInsert !== null) {
            $logFields['stage'] = 'correlation';
            $profile = $store->activePlaythrough();
            if (!is_array($profile) || !is_string($profile['id'] ?? null) || $profile['id'] === '') {
                return 'stale';
            }
            $requestLog?->context(['playthrough_id' => $profile['id']]);
            $playerName = is_string($profile['player_name'] ?? null) ? trim($profile['player_name']) : '';
            if ($playerName === '') {
                return 'player-identity-unavailable';
            }
            $playerInputInsert = validatedPlayerInputInsert($gameRequest, $playerInputInsert);
            if ($playerInputInsert === null) {
                return 'event-unmatched';
            }

            $source = $store->playerInputEvent($playerInputInsert);
            if (!is_array($source)) {
                return 'event-unmatched';
            }
            $requestLog?->context([
                'speaker_kind' => 'player',
                'event_id' => $source['event_id'],
                'utterance_id' => $source['utterance_id'],
                'playthrough_id' => $profile['id'],
            ]);

            $routeField = is_string($gameRequest[4] ?? null) ? $gameRequest[4] : '';
            $route = \chimDecodePlayerRoutingSnapshotField($routeField);
            $targetMode = is_array($route) ? ($route['target_mode'] ?? null) : null;
            $listenerInput = is_array($route) ? ($route['listener'] ?? null) : null;
            if (
                !in_array($targetMode, ['direct', 'automatic'], true)
                || !is_string($listenerInput) || trim($listenerInput) === ''
                || isPlayerName($listenerInput, $playerName)
            ) {
                return 'listener-unmatched';
            }
            $listenerInput = trim($listenerInput);
            $sourcePeople = $source['source_people'] ?? null;
            if (!is_string($sourcePeople) || trim($sourcePeople) === '') {
                return 'listener-unmatched';
            }
            $people = \parsePeoplePipeList($sourcePeople);
            $audienceMatches = array_values(array_filter(
                $people,
                static fn(mixed $name): bool => is_string($name) && sameActorName($name, $listenerInput)
            ));
            if (count($audienceMatches) !== 1) {
                return 'listener-unmatched';
            }

            $playerTtsSource = $GLOBALS['PLAYER_TTS_SOURCE_TEXT'] ?? null;
            $sourceData = $source['source_data'] ?? null;
            if (is_string($playerTtsSource)) {
                $playerTtsSource = rtrim($playerTtsSource);
            }
            if (
                !is_string($playerName) || trim($playerName) === ''
                || !is_string($playerTtsSource) || $playerTtsSource === ''
                || strlen($playerTtsSource) > 12000 || preg_match('//u', $playerTtsSource) !== 1
                || !is_string($sourceData) || strlen($sourceData) > 16384
                || !str_starts_with($sourceData, $playerTtsSource)
            ) {
                return 'player-text-invalid';
            }
            $speakerColon = strpos($playerTtsSource, ':');
            if ($speakerColon === false || !sameActorName(trim(substr($playerTtsSource, 0, $speakerColon)), $playerName)) {
                return 'player-identity-mismatch';
            }
            $text = trim(substr($playerTtsSource, $speakerColon + 1));
            if ($text === '' || preg_match('//u', $text) !== 1) {
                return 'player-text-invalid';
            }
            $text = str_replace(["\r", "\n", "|"], ' ', $text);
            $text = preg_replace('/\s+/', ' ', $text);
            if (!is_string($text) || $text === '') {
                return 'player-text-invalid';
            }
            $logFields['speech_bytes'] = strlen($text);

            $identities = $store->npcIdentities();
            $listenerIdentity = uniqueNpcNamed($identities, $listenerInput);
            if (!is_array($listenerIdentity)) {
                return 'actor-unmatched';
            }
            $listener = $store->npcById($listenerIdentity['id']);
            if (!is_array($listener) || !sameActorName($listener['npc_name'] ?? null, $listenerInput)) {
                return 'actor-stale';
            }
            $requestLog?->context(['listener_id' => $listenerIdentity['id']]);
            $listenerExtended = $listener['extended_data'] ?? null;
            if (!$listenerExtended instanceof \stdClass) {
                $logFields['reason'] = 'listener_extended_data_invalid';
                return 'listener-invalid';
            }
            if (!empty($listenerExtended->relationships_locked) || (int)($listener['lock_profile'] ?? 0) !== 0) {
                return 'locked';
            }
            $relationships = $listenerExtended->relationships ?? new \stdClass();
            if (!$relationships instanceof \stdClass) {
                $logFields['reason'] = 'listener_relationships_invalid';
                return 'listener-invalid';
            }
            try {
                playerRelationshipKey($relationships, $playerName);
            } catch (\RuntimeException) {
                $logFields['reason'] = 'player_alias_ambiguous';
                return 'listener-invalid';
            }

            $dedupeReason = null;
            if (eventAlreadyProcessed($listener, $profile['id'], $source['event_id'], $source['utterance_id'], $dedupeReason)) {
                $logFields['reason'] = $dedupeReason ?? 'duplicate';
                return 'duplicate';
            }

            $speaker = [];
            $event = [
                'utterance_id' => $source['utterance_id'],
                'speaker_kind' => 'player',
                'speaker_id' => null,
                'listener_id' => $listenerIdentity['id'],
                'speaker_name' => $playerName,
                'listener_name' => $listener['npc_name'],
                'text' => $text,
                'gamets' => $source['gamets'],
                'event_id' => $source['event_id'],
                'playthrough_id' => $profile['id'],
                'player_name' => $playerName,
                'source_data' => $sourceData,
                'source_kind' => $source['source_kind'],
                'source_type' => $source['source_type'],
                'source_ts' => $source['source_ts'],
                'source_gamets' => $source['source_gamets'],
                'source_localts' => $source['source_localts'],
                'source_sess' => $source['source_sess'],
                'source_people' => $source['source_people'],
            ];
        } else {
            $raw = $gameRequest[3] ?? null;
            if (!is_string($raw) || strlen($raw) > 16384) {
                return 'oversized';
            }
            try {
                $payload = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                return 'invalid-payload';
            }
            if (!is_array($payload) || array_is_list($payload)) {
                return 'invalid-payload';
            }
            $speakerInput = $payload['speaker'] ?? null;
            $listenerInput = $payload['listener'] ?? null;
            $speech = $payload['speech'] ?? null;
            $utteranceId = $payload['utterance_id'] ?? null;
            if (
                !is_string($speakerInput) || !is_string($listenerInput) || !is_string($speech) || !is_string($utteranceId)
                || strlen($speakerInput) > 256 || strlen($listenerInput) > 256 || strlen($speech) > 12000
                || trim($speakerInput) === '' || trim($listenerInput) === '' || trim($speech) === ''
                || preg_match('//u', $speakerInput) !== 1 || preg_match('//u', $listenerInput) !== 1 || preg_match('//u', $speech) !== 1
                || preg_match('/\Autt_[A-Za-z0-9_-]{8,128}\z/', $utteranceId) !== 1
            ) {
                return 'invalid-payload';
            }
            $speakerInput = trim($speakerInput);
            $listenerInput = trim($listenerInput);
            $speech = trim($speech);
            $logFields['speech_bytes'] = strlen($speech);
            $requestLog?->context(['utterance_id' => $utteranceId]);
            $logFields['stage'] = 'correlation';

            $profile = $store->activePlaythrough();
            if (!is_array($profile) || !is_string($profile['id'] ?? null) || $profile['id'] === '') {
                return 'stale';
            }
            $requestLog?->context(['playthrough_id' => $profile['id']]);
            $playerName = is_string($profile['player_name'] ?? null) ? trim($profile['player_name']) : '';
            if (isPlayerName($speakerInput, $playerName) || isPlayerName($listenerInput, $playerName)) {
                return 'player-actor';
            }

            $source = $store->acknowledgedEvent($utteranceId);
            $sourceData = is_array($source) ? ($source['source_data'] ?? null) : null;
            if (!is_string($sourceData) || strlen($sourceData) > 16384) {
                return 'event-unmatched';
            }
            $sourceSpeaker = \extractSpeakerNameFromChatEvent($sourceData);
            $target = \extractTalkTargetMetadata($sourceData);
            if (
                !is_string($sourceSpeaker) || !sameActorName($sourceSpeaker, $speakerInput)
                || !is_array($target) || empty($target['hasExplicitTarget']) || !empty($target['isBroadcast'])
                || !\talkTargetsIncludeName($target['targets'] ?? [], $listenerInput)
            ) {
                return 'event-mismatch';
            }
            $requestLog?->context(['event_id' => $source['event_id']]);

            $identities = $store->npcIdentities();
            $speakerIdentity = uniqueNpcNamed($identities, $speakerInput);
            $listenerIdentity = uniqueNpcNamed($identities, $listenerInput);
            if (!is_array($speakerIdentity) || !is_array($listenerIdentity) || $speakerIdentity['id'] === $listenerIdentity['id']) {
                return 'actor-unmatched';
            }
            $speaker = $store->npcById($speakerIdentity['id']);
            $listener = $store->npcById($listenerIdentity['id']);
            if (
                !is_array($speaker) || !is_array($listener)
                || !sameActorName($speaker['npc_name'] ?? null, $speakerInput)
                || !sameActorName($listener['npc_name'] ?? null, $listenerInput)
            ) {
                return 'actor-stale';
            }
            $requestLog?->context([
                'speaker_id' => $speakerIdentity['id'],
                'listener_id' => $listenerIdentity['id'],
            ]);
            $listenerExtended = $listener['extended_data'] ?? null;
            if (!$listenerExtended instanceof \stdClass) {
                $logFields['reason'] = 'listener_extended_data_invalid';
                return 'listener-invalid';
            }
            if (!empty($listenerExtended->relationships_locked) || (int)($listener['lock_profile'] ?? 0) !== 0) {
                return 'locked';
            }
            $dedupeReason = null;
            if (eventAlreadyProcessed($listener, $profile['id'], $source['event_id'], $utteranceId, $dedupeReason)) {
                $logFields['reason'] = $dedupeReason ?? 'duplicate';
                return 'duplicate';
            }

            $event = [
                'utterance_id' => $utteranceId,
                'speaker_id' => $speakerIdentity['id'],
                'listener_id' => $listenerIdentity['id'],
                'speaker_name' => $speaker['npc_name'],
                'listener_name' => $listener['npc_name'],
                'text' => $speech,
                'gamets' => $source['gamets'],
                'event_id' => $source['event_id'],
                'playthrough_id' => $profile['id'],
                'player_name' => $playerName,
                'source_data' => $sourceData,
            ];
        }
        $subjects = findSubjects($event, $identities, $playerName);
        $logFields['subject_count'] = count($subjects);
        if (count($subjects) > 8) {
            return 'too-many-subjects';
        }
        if ($subjects === []) {
            return 'no-subjects';
        }
        if (array_key_exists('player', $subjects)) {
            $relationships = $listenerExtended->relationships ?? new \stdClass();
            if (!$relationships instanceof \stdClass) {
                $logFields['reason'] = 'listener_relationships_invalid';
                return 'listener-invalid';
            }
            try {
                playerRelationshipKey($relationships, $playerName);
            } catch (\RuntimeException) {
                $logFields['reason'] = 'player_alias_ambiguous';
                return 'listener-invalid';
            }
        }
        foreach ($subjects as $subject) {
            if ($subject['id'] === null) {
                continue;
            }
            $row = $store->npcById($subject['id']);
            if (!is_array($row) || !sameActorName($row['npc_name'] ?? null, $subject['name'])) {
                return 'subject-stale';
            }
        }

        $speakerPrompt = ($event['speaker_kind'] ?? 'npc') === 'player' ? [] : promptNpcCopy($speaker);
        $messages = buildMessages($event, $speakerPrompt, promptNpcCopy($listener), $subjects);
        $requestModel ??= __NAMESPACE__ . '\\requestJudgments';
        $requestLog?->context(['connector_id' => $connectorId, 'subject_count' => count($subjects)]);
        $eligibleEvent = ($event['speaker_kind'] ?? 'npc') === 'player' ? 'player_input_eligible' : 'ack_eligible';
        $requestLog?->event($eligibleEvent, 'debug', [
            'stage' => 'model',
            'connector_id' => $connectorId,
            'subject_count' => count($subjects),
        ]);
        $logFields['stage'] = 'model';
        $requestLog?->event('model_started', 'debug', [
            'stage' => 'model',
            'connector_id' => $connectorId,
            'subject_count' => count($subjects),
        ]);
        $modelStarted = hrtime(true);
        try {
            $response = $requestModel($messages);
        } catch (Throwable $error) {
            $logFields['model_ms'] = RequestLog::elapsedMs($modelStarted);
            $logFields['model_outcome'] = 'failed';
            $failureReason = $error instanceof ModelRequestFailure ? $error->reasonCode : 'model_request_failed';
            $logFields['reason'] = $failureReason;
            $requestLog?->event('model_finished', 'error', [
                'stage' => 'model',
                'model_outcome' => 'failed',
                'reason' => $failureReason,
                'model_ms' => $logFields['model_ms'],
            ]);
            return 'failed';
        }
        $logFields['model_ms'] = RequestLog::elapsedMs($modelStarted);
        if (!is_string($response)) {
            $logFields['model_outcome'] = 'invalid';
            $logFields['reason'] = 'model_response_invalid';
            $requestLog?->event('model_finished', 'warning', [
                'stage' => 'model',
                'model_outcome' => 'invalid',
                'reason' => 'model_response_invalid',
                'model_ms' => $logFields['model_ms'],
            ]);
            return 'model-invalid';
        }
        try {
            $judgments = parseJudgments($response, $subjects, $event['text']);
        } catch (Throwable $error) {
            $validationReason = $error instanceof JudgmentValidationFailure
                ? $error->reasonCode
                : 'judgment_validation_failed';
            $logFields['model_outcome'] = 'invalid';
            $logFields['reason'] = $validationReason;
            $requestLog?->event('model_finished', 'warning', [
                'stage' => 'model_validation',
                'model_outcome' => 'invalid',
                'reason' => $validationReason,
                'model_ms' => $logFields['model_ms'],
            ]);
            return 'failed';
        }
        $logFields['model_outcome'] = 'validated';
        $requestLog?->event('model_finished', 'info', [
            'stage' => 'model_validation',
            'model_outcome' => 'validated',
            'model_ms' => $logFields['model_ms'],
        ]);
        foreach ($judgments as $subject => $judgment) {
            $requestLog?->event('judgment_proposal', 'debug', [
                'stage' => 'model_validation',
                'subject' => $subject,
                'delta' => $judgment['delta'],
                'model_reason' => $judgment['reason'],
            ]);
        }
        $interactionReason = null;
        $interactionStatus = speechAckInteractionStatus($interactionReason);
        if ($interactionStatus !== 'ok') {
            $logFields['stage'] = 'post_model_gate';
            $logFields['reason'] = $interactionReason;
            return $interactionStatus;
        }
        $logFields['stage'] = 'persistence';
        $persistenceStarted = hrtime(true);
        try {
            $status = persistJudgments($event, $subjects, $judgments, $store, $requestLog);
        } finally {
            $logFields['persistence_ms'] = RequestLog::elapsedMs($persistenceStarted);
        }
        $logFields['persistence_outcome'] = $status;
        if ($status === 'failed' && !isset($logFields['reason'])) {
            $logFields['reason'] = 'persistence_failed';
        }
        return $status;
    } catch (Throwable) {
        if (!isset($logFields['reason'])) {
            $logFields['reason'] = ($logFields['stage'] ?? null) === 'persistence'
                ? 'persistence_failed'
                : 'evaluation_failed';
        }
        if (($logFields['stage'] ?? null) === 'persistence') {
            $logFields['persistence_outcome'] = 'failed';
        }
        return 'failed';
    }
}

if (isset($gameRequest) && is_array($gameRequest) && ($gameRequest[0] ?? null) === '_speech') {
    try {
        $chimMindPoisoningStore = new PostgresStoreDb($chimMindPoisoningRequestLog);
        handleSpeechAck($gameRequest, $chimMindPoisoningStore, null, $chimMindPoisoningRequestLog);
    } catch (Throwable) {
        if ($chimMindPoisoningRequestLog instanceof RequestLog) {
            $chimMindPoisoningRequestLog->finish('failed', 'runtime_bootstrap_failed', ['stage' => 'bootstrap']);
        } else {
            @error_log('Mind Poisoning ACK failed: runtime_bootstrap_failed');
        }
    }
}
