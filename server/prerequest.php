<?php
declare(strict_types=1);

namespace ChimMindPoisoning;

use JsonException;
use Throwable;

require_once __DIR__ . '/influence.php';
require_once __DIR__ . '/model.php';
require_once __DIR__ . '/store.php';

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

function speechAckInteractionStatus(): string
{
    if (!function_exists('chimInteractionBegin') || !function_exists('chimInteractionState')) {
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
            return 'interaction-off';
        }
        if ($state['generation'] !== $requestGeneration) {
            return 'interaction-stale';
        }
        return $state['enabled'] ? 'ok' : 'interaction-off';
    } catch (Throwable) {
        return 'interaction-off';
    }
}

/**
 * Evaluate one exact _speech acknowledgement. The optional callable is a test seam;
 * production uses the configured requestJudgments adapter.
 */
function handleSpeechAck(array $gameRequest, StoreDb $store, ?callable $requestModel = null): string
{
    if (($gameRequest[0] ?? null) !== '_speech') {
        return 'ignored';
    }

    try {
        $interactionStatus = speechAckInteractionStatus();
        if ($interactionStatus !== 'ok') {
            return $interactionStatus;
        }
        if (!function_exists('chimIsGlobalLlmConnectorEnabled') || !\chimIsGlobalLlmConnectorEnabled('RELLLM_CONNECTOR')) {
            return 'disabled';
        }
        $connectorId = filter_var($GLOBALS['RELLLM_CONNECTOR'] ?? null, FILTER_VALIDATE_INT);
        if ($connectorId === false || $connectorId === null || $connectorId < 1) {
            return 'connector-invalid';
        }
        if (
            !function_exists('extractSpeakerNameFromChatEvent')
            || !function_exists('extractTalkTargetMetadata')
            || !function_exists('talkTargetsIncludeName')
        ) {
            return 'event-helpers-unavailable';
        }
        if (filter_var($GLOBALS['NEVER_CLEAR_RELATIONSHIP_DATA'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return 'restore-policy';
        }

        $raw = $gameRequest[3] ?? null;
        if (!is_string($raw) || strlen($raw) > 16384) {
            error_log('Mind Poisoning skipped: speech callback exceeds 16 KiB or is missing.');
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

        $profile = $store->activePlaythrough();
        if (!is_array($profile) || !is_string($profile['id'] ?? null) || $profile['id'] === '') {
            return 'stale';
        }
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
        $listenerExtended = $listener['extended_data'] ?? null;
        if (!$listenerExtended instanceof \stdClass) {
            return 'listener-invalid';
        }
        if (!empty($listenerExtended->relationships_locked) || (int)($listener['lock_profile'] ?? 0) !== 0) {
            return 'locked';
        }
        if (eventAlreadyProcessed($listener, $profile['id'], $source['event_id'], $utteranceId)) {
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
        $subjects = findSubjects($event, $identities, $playerName);
        if (count($subjects) > 8) {
            error_log('Mind Poisoning skipped: more than eight named subjects.');
            return 'too-many-subjects';
        }
        if ($subjects === []) {
            return 'no-subjects';
        }
        if (array_key_exists('player', $subjects)) {
            $relationships = $listenerExtended->relationships ?? new \stdClass();
            if (!$relationships instanceof \stdClass) {
                return 'listener-invalid';
            }
            try {
                playerRelationshipKey($relationships, $playerName);
            } catch (\RuntimeException) {
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

        $messages = buildMessages($event, promptNpcCopy($speaker), promptNpcCopy($listener), $subjects);
        $requestModel ??= __NAMESPACE__ . '\\requestJudgments';
        $response = $requestModel($messages);
        if (!is_string($response)) {
            return 'model-invalid';
        }
        $judgments = parseJudgments($response, $subjects, $event['text']);
        $interactionStatus = speechAckInteractionStatus();
        if ($interactionStatus !== 'ok') {
            return $interactionStatus;
        }
        return persistJudgments($event, $subjects, $judgments, $store);
    } catch (Throwable $error) {
        error_log('Mind Poisoning hook failed: ' . substr($error->getMessage(), 0, 180));
        return 'failed';
    }
}

if (isset($gameRequest) && is_array($gameRequest) && ($gameRequest[0] ?? null) === '_speech') {
    try {
        handleSpeechAck($gameRequest, new PostgresStoreDb());
    } catch (Throwable $error) {
        error_log('Mind Poisoning hook failed: ' . substr($error->getMessage(), 0, 180));
    }
}
