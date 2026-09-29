<?php
declare(strict_types=1);

namespace ChimMindPoisoning;

use JsonException;
use RuntimeException;
use Throwable;

require_once __DIR__ . '/logging.php';

interface StoreDb
{
    public function activePlaythrough(): ?array;
    public function acknowledgedEvent(string $utteranceId): ?array;
    public function playerInputEvent(array $source): ?array;
    public function eventById(int $eventId, string $utteranceId): ?array;
    public function npcIdentities(): array;
    public function npcById(int $npcId, bool $forUpdate = false): ?array;
    public function beginForListener(int $listenerId): bool;
    public function writeNpc(int $npcId, array $relationshipEdges, object $mindPoisoningData, float $gamets): bool;
    public function backupAndVerify(int $npcId, array $expected): bool;
    public function commit(): bool;
    public function rollback(): void;
    public function release(): void;
}

function assertPgSqlConnection(mixed $connection): void
{
    if (!class_exists(\PgSql\Connection::class) || !$connection instanceof \PgSql\Connection) {
        throw new RuntimeException('Mind Poisoning requires the inspected PgSql connection type.');
    }
}

function assertIdleTransactionStatus(int $status): void
{
    if ($status !== PGSQL_TRANSACTION_IDLE) {
        throw new RuntimeException('Mind Poisoning will not nest inside a caller transaction.');
    }
}

function decodeJsonObject(mixed $value, string $field): object
{
    if (is_object($value)) {
        return $value;
    }
    if ($value === null || $value === '') {
        return new \stdClass();
    }
    if (!is_string($value)) {
        throw new RuntimeException("Invalid stored {$field} value.");
    }
    try {
        $decoded = json_decode($value, false, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $error) {
        throw new RuntimeException("Invalid stored {$field} JSON.", 0, $error);
    }
    if (!is_object($decoded)) {
        throw new RuntimeException("Stored {$field} must be a JSON object.");
    }
    return $decoded;
}

function knownLedgerArray(mixed $value): ?array
{
    if ($value instanceof \stdClass) {
        $value = json_decode(json_encode($value, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }
    return is_array($value) ? $value : null;
}

function storedLedgerNamespace(object $pluginExtendedData): ?array
{
    if (!property_exists($pluginExtendedData, 'mind_poisoning')) {
        return [];
    }
    $ledger = knownLedgerArray($pluginExtendedData->mind_poisoning);
    if (
        !is_array($ledger)
        || !is_string($ledger['playthrough_id'] ?? null) || $ledger['playthrough_id'] === ''
        || !is_int($ledger['floor_event_id'] ?? null) || $ledger['floor_event_id'] < 0
        || !is_array($ledger['events'] ?? null) || !array_is_list($ledger['events'])
        || count($ledger['events']) > 128
    ) {
        return null;
    }
    foreach ($ledger['events'] as $entry) {
        if (
            !is_array($entry)
            || !is_int($entry['event_id'] ?? null) || $entry['event_id'] < 1
            || !is_string($entry['utterance_id'] ?? null) || $entry['utterance_id'] === ''
        ) {
            return null;
        }
    }
    return $ledger;
}

function jsonValueKey(mixed $value): string
{
    if ($value instanceof \stdClass) {
        $properties = get_object_vars($value);
        ksort($properties, SORT_STRING);
        $canonical = new \stdClass();
        foreach ($properties as $key => $item) {
            $canonical->{$key} = canonicalJsonValue($item);
        }
        return json_encode($canonical, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    return json_encode(canonicalJsonValue($value), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function canonicalJsonValue(mixed $value): mixed
{
    if ($value instanceof \stdClass) {
        $properties = get_object_vars($value);
        ksort($properties, SORT_STRING);
        $canonical = new \stdClass();
        foreach ($properties as $key => $item) {
            $canonical->{$key} = canonicalJsonValue($item);
        }
        return $canonical;
    }
    if (is_array($value)) {
        if (array_is_list($value)) {
            return array_map('ChimMindPoisoning\\canonicalJsonValue', $value);
        }
        ksort($value, SORT_STRING);
        $canonical = new \stdClass();
        foreach ($value as $key => $item) {
            $canonical->{(string)$key} = canonicalJsonValue($item);
        }
        return $canonical;
    }
    return $value;
}

function sameJsonValue(mixed $left, mixed $right): bool
{
    return jsonValueKey($left) === jsonValueKey($right);
}

function normalizeEventRow(array $row): ?array
{
    $eventId = filter_var($row['rowid'] ?? null, FILTER_VALIDATE_INT);
    $utteranceId = $row['utterance_id'] ?? null;
    $gamets = $row['gamets'] ?? null;
    $sourceData = $row['data'] ?? null;
    $deliveryState = $row['delivery_state'] ?? null;
    if (
        $eventId === false || $eventId < 1
        || ($row['type'] ?? null) !== 'chat'
        || !is_string($utteranceId)
        || !is_numeric($gamets) || !is_finite((float)$gamets)
        || !is_string($sourceData)
        || !in_array($deliveryState, ['emitted', 'spoken'], true)
    ) {
        return null;
    }

    return [
        'event_id' => (int)$eventId,
        'utterance_id' => $utteranceId,
        'gamets' => (float)$gamets,
        'source_data' => $sourceData,
        'delivery_state' => $deliveryState,
    ];
}

function playerInputTypes(): array
{
    return ['inputtext', 'inputtext_s', 'ginputtext', 'ginputtext_s'];
}

function normalizePlayerInputRow(array $row): ?array
{
    $eventId = filter_var($row['rowid'] ?? null, FILTER_VALIDATE_INT);
    $type = $row['type'] ?? null;
    $ts = $row['ts'] ?? null;
    $gamets = $row['gamets'] ?? null;
    $localts = filter_var($row['localts'] ?? null, FILTER_VALIDATE_INT);
    $data = $row['data'] ?? null;
    $sess = $row['sess'] ?? null;
    $people = $row['people'] ?? null;
    if (
        $eventId === false || $eventId < 1
        || !is_string($type) || !in_array($type, playerInputTypes(), true)
        || (!is_string($ts) && !is_int($ts)) || !is_numeric($ts) || !is_finite((float)$ts)
        || (!is_string($gamets) && !is_int($gamets) && !is_float($gamets))
        || !is_numeric($gamets) || !is_finite((float)$gamets)
        || $localts === false
        || !is_string($data)
        || $sess !== 'web'
        || !is_string($people) || trim($people) === ''
        || (array_key_exists('source_count', $row) && (string)$row['source_count'] !== '1')
    ) {
        return null;
    }

    return [
        'event_id' => (int)$eventId,
        'utterance_id' => 'input_' . (int)$eventId,
        'speaker_kind' => 'player',
        'speaker_id' => null,
        'source_kind' => 'player_input',
        'source_type' => $type,
        'source_ts' => (string)$ts,
        'source_gamets' => (string)$gamets,
        'gamets' => (float)$gamets,
        'source_data' => $data,
        'source_localts' => (int)$localts,
        'source_sess' => $sess,
        'source_people' => $people,
    ];
}

function playerInputSourceParameters(array $source): ?array
{
    $type = $source['type'] ?? null;
    $ts = $source['ts'] ?? null;
    $gamets = $source['gamets'] ?? null;
    $data = $source['data'] ?? null;
    $localts = $source['localts'] ?? null;
    $sess = $source['sess'] ?? null;
    if (
        !is_string($type) || !in_array($type, playerInputTypes(), true)
        || (!is_string($ts) && !is_int($ts)) || !is_numeric($ts) || !is_finite((float)$ts)
        || (!is_string($gamets) && !is_int($gamets))
        || !is_numeric($gamets) || !is_finite((float)$gamets)
        || !is_string($data)
        || !is_int($localts)
        || $sess !== 'web'
    ) {
        return null;
    }
    return [$type, $ts, $gamets, $data, $localts, $sess];
}

function eventAlreadyProcessed(array $npc, string $playthroughId, int $eventId, string $utteranceId, ?string &$reason = null): bool
{
    $reason = null;
    $plugins = $npc['plugin_extended_data'] ?? null;
    if (!$plugins instanceof \stdClass) {
        $reason = 'ledger-invalid';
        return true;
    }
    $ledger = storedLedgerNamespace($plugins);
    if ($ledger === null) {
        $reason = 'ledger-invalid';
        return true;
    }
    if ($ledger === [] || $ledger['playthrough_id'] !== $playthroughId) {
        return false;
    }
    $floor = $ledger['floor_event_id'];
    $entries = $ledger['events'];
    if ($eventId <= $floor) {
        $reason = 'ledger-floor';
        return true;
    }
    foreach ($entries as $entry) {
        if ($entry['event_id'] === $eventId || $entry['utterance_id'] === $utteranceId) {
            $reason = 'duplicate-event';
            return true;
        }
    }
    return false;
}

function validSubjectsAndJudgments(array $subjects, array $judgments, array $event): bool
{
    if (count($subjects) > 8 || count($subjects) !== count($judgments)) {
        return false;
    }
    foreach ($subjects as $token => $subject) {
        if (!is_string($token) || !is_array($subject) || !is_string($subject['name'] ?? null)) {
            return false;
        }
        if ($token === 'player') {
            if (
                ($event['speaker_kind'] ?? 'npc') === 'player'
                || $subject['name'] !== 'Player'
                || !array_key_exists('id', $subject)
                || $subject['id'] !== null
            ) {
                return false;
            }
        } elseif (
            preg_match('/\Anpc:([1-9][0-9]*)\z/', $token, $matches) !== 1
            || !is_int($subject['id'] ?? null)
            || (int)$matches[1] !== $subject['id']
            || $subject['id'] === ($event['speaker_id'] ?? null)
            || $subject['id'] === ($event['listener_id'] ?? null)
            || trim($subject['name']) === ''
        ) {
            return false;
        }

        $judgment = $judgments[$token] ?? null;
        if (
            !is_array($judgment)
            || !is_int($judgment['delta'] ?? null)
            || $judgment['delta'] < -5 || $judgment['delta'] > 5
            || !is_string($judgment['reason'] ?? null) || trim($judgment['reason']) === '' || strlen($judgment['reason']) > 600
            || !is_string($judgment['evidence'] ?? null) || trim($judgment['evidence']) === '' || strlen($judgment['evidence']) > 600
            || !is_string($event['text'] ?? null)
            || !str_contains($event['text'], $judgment['evidence'])
        ) {
            return false;
        }
    }
    foreach ($judgments as $token => $_judgment) {
        if (!is_string($token) || !array_key_exists($token, $subjects)) {
            return false;
        }
    }
    return true;
}

function sameActorName(mixed $left, mixed $right): bool
{
    return is_string($left) && is_string($right)
        && mb_strtolower(trim($left), 'UTF-8') === mb_strtolower(trim($right), 'UTF-8');
}

function playerInputIncludesListener(array $event, string $listenerName): bool
{
    $people = $event['source_people'] ?? null;
    if (!is_string($people) || trim($people) === '' || !function_exists('parsePeoplePipeList')) {
        return false;
    }
    try {
        $names = \parsePeoplePipeList($people);
    } catch (Throwable) {
        return false;
    }
    if (!is_array($names)) {
        return false;
    }
    $matches = 0;
    foreach ($names as $name) {
        if (sameActorName($name, $listenerName)) {
            $matches++;
        }
    }
    return $matches === 1;
}

function nextLedger(array $ledger, string $playthroughId, int $eventId, string $utteranceId, array $judgments): array
{
    if (($ledger['playthrough_id'] ?? null) !== $playthroughId) {
        $ledger = ['playthrough_id' => $playthroughId, 'floor_event_id' => 0, 'events' => []];
    }
    $floor = $ledger['floor_event_id'] ?? 0;
    $events = $ledger['events'] ?? [];
    if (!is_int($floor) || $floor < 0 || !is_array($events) || count($events) > 128) {
        throw new RuntimeException('Stored Mind Poisoning ledger is invalid.');
    }
    if ($eventId <= $floor) {
        throw new RuntimeException('The event is older than the bounded dedupe ledger floor.');
    }
    foreach ($events as $entry) {
        if (!is_array($entry) || !is_int($entry['event_id'] ?? null) || !is_string($entry['utterance_id'] ?? null)) {
            throw new RuntimeException('Stored Mind Poisoning ledger entry is invalid.');
        }
        if ($entry['event_id'] === $eventId || $entry['utterance_id'] === $utteranceId) {
            throw new RuntimeException('The event has already been recorded.');
        }
    }

    $details = [];
    foreach ($judgments as $token => $judgment) {
        $details[] = [
            'subject' => $token,
            'delta' => $judgment['delta'],
            'reason' => mb_strcut($judgment['reason'], 0, 120, 'UTF-8'),
            'evidence' => mb_strcut($judgment['evidence'], 0, 120, 'UTF-8'),
        ];
    }
    $events[] = ['event_id' => $eventId, 'utterance_id' => $utteranceId, 'judgments' => $details];
    usort($events, static fn(array $left, array $right): int => $left['event_id'] <=> $right['event_id']);
    if (count($events) > 128) {
        $removed = array_splice($events, 0, count($events) - 128);
        foreach ($removed as $entry) {
            $floor = max($floor, $entry['event_id']);
        }
    }
    return ['playthrough_id' => $playthroughId, 'floor_event_id' => $floor, 'events' => array_values($events)];
}

function playerRelationshipKey(object $relationships, string $playerName): ?string
{
    $aliases = ['player', 'the player', 'player character', 'the player character', 'dragonborn', 'the dragonborn', '#player_name#', '{player_name}'];
    if (trim($playerName) !== '') {
        $aliases[] = mb_strtolower(trim($playerName), 'UTF-8');
    }
    $aliases = array_fill_keys($aliases, true);
    $matches = [];
    foreach (get_object_vars($relationships) as $key => $_value) {
        if (is_string($key) && isset($aliases[mb_strtolower(trim($key), 'UTF-8')])) {
            $matches[] = $key;
        }
    }
    if (count($matches) > 1) {
        throw new RuntimeException('Multiple legacy Player relationship aliases are ambiguous.');
    }
    return $matches[0] ?? null;
}

function persistJudgments(array $event, array $subjects, array $judgments, StoreDb $store, ?RequestLog $requestLog = null): string
{
    $startedAt = hrtime(true);
    $committed = false;
    $commitAttempted = false;
    $cleanupRequired = false;
    $cleanupFailed = false;
    $cleanupError = null;
    $cleanupReason = '';
    $status = 'failed';
    $reason = 'persistence-failed';
    $stage = 'validation';
    $speakerKind = array_key_exists('speaker_kind', $event) ? $event['speaker_kind'] : 'npc';
    $playerSpeaker = $speakerKind === 'player';
    $edgeChanges = [];
    $done = static function (string $outcome, string $code) use (&$status, &$reason): string {
        $status = $outcome;
        $reason = $code;
        return $outcome;
    };

    $requestLog?->context([
        'event_id' => $event['event_id'] ?? null,
        'utterance_id' => $event['utterance_id'] ?? null,
        'playthrough_id' => $event['playthrough_id'] ?? null,
        'speaker_id' => $event['speaker_id'] ?? null,
        'speaker_kind' => $event['speaker_kind'] ?? 'npc',
        'listener_id' => $event['listener_id'] ?? null,
        'cleanup_failed' => false,
    ]);

    try {
        if (filter_var($GLOBALS['NEVER_CLEAR_RELATIONSHIP_DATA'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return $done('restore-policy', 'restore-policy');
        }
        if (
            !is_int($event['event_id'] ?? null) || $event['event_id'] < 1
            || !is_string($event['utterance_id'] ?? null) || $event['utterance_id'] === ''
            || !is_string($speakerKind) || !in_array($speakerKind, ['npc', 'player'], true)
            || ($playerSpeaker
                ? (
                    !array_key_exists('speaker_id', $event) || $event['speaker_id'] !== null
                    || !is_string($event['player_name'] ?? null) || trim($event['player_name']) === ''
                    || !is_string($event['speaker_name'] ?? null) || trim($event['speaker_name']) !== trim($event['player_name'])
                    || $event['utterance_id'] !== 'input_' . $event['event_id']
                    || ($event['source_kind'] ?? null) !== 'player_input'
                    || !is_string($event['source_type'] ?? null) || !in_array($event['source_type'], playerInputTypes(), true)
                    || !is_string($event['source_ts'] ?? null) || !is_numeric($event['source_ts']) || !is_finite((float)$event['source_ts'])
                    || !is_string($event['source_gamets'] ?? null) || !is_numeric($event['source_gamets']) || !is_finite((float)$event['source_gamets'])
                    || !is_int($event['source_localts'] ?? null)
                    || ($event['source_sess'] ?? null) !== 'web'
                    || !is_string($event['source_people'] ?? null) || trim($event['source_people']) === ''
                )
                : (
                    !is_int($event['speaker_id'] ?? null) || $event['speaker_id'] < 1
                    || ($event['source_kind'] ?? null) === 'player_input'
                ))
            || !is_int($event['listener_id'] ?? null) || $event['listener_id'] < 1
            || (!$playerSpeaker && $event['speaker_id'] === $event['listener_id'])
            || !is_string($event['speaker_name'] ?? null) || trim($event['speaker_name']) === ''
            || !is_string($event['listener_name'] ?? null) || trim($event['listener_name']) === ''
            || !is_string($event['text'] ?? null) || $event['text'] === ''
            || !is_string($event['playthrough_id'] ?? null) || $event['playthrough_id'] === ''
            || !is_numeric($event['gamets'] ?? null) || !is_finite((float)$event['gamets'])
            || !is_string($event['source_data'] ?? null)
            || !validSubjectsAndJudgments($subjects, $judgments, $event)
        ) {
            return $done('invalid', 'invalid-event');
        }

        $cleanupRequired = true;
        $stage = 'begin-listener';
        if (!$store->beginForListener($event['listener_id'])) {
            return $done('busy', 'listener-busy');
        }
        $stage = 'revalidate-event';
        $listener = $store->npcById($event['listener_id'], true);
        $active = $store->activePlaythrough();
        $currentEvent = $store->eventById($event['event_id'], $event['utterance_id']);
        if (
            !is_array($listener)
            || !is_array($active)
            || (string)($active['id'] ?? '') !== $event['playthrough_id']
            || !sameEvent($event, $currentEvent)
        ) {
            return $done('stale', 'event-stale');
        }
        if (
            ($playerSpeaker || array_key_exists('player', $subjects))
            && is_string($event['player_name'] ?? null)
            && trim($event['player_name']) !== ''
            && (!is_string($active['player_name'] ?? null) || !sameActorName($event['player_name'], $active['player_name']))
        ) {
            return $done('stale', 'player-identity-stale');
        }
        if (!sameActorName($listener['npc_name'] ?? null, $event['listener_name'])) {
            return $done('stale', 'listener-identity-stale');
        }
        $extendedData = $listener['extended_data'] ?? null;
        $pluginExtendedData = $listener['plugin_extended_data'] ?? null;
        if (!$extendedData instanceof \stdClass || !$pluginExtendedData instanceof \stdClass) {
            return $done('invalid', 'listener-state-invalid');
        }
        if (!empty($extendedData->relationships_locked) || (int)($listener['lock_profile'] ?? 0) !== 0) {
            return $done('locked', 'relationship-locked');
        }

        if ($playerSpeaker && !playerInputIncludesListener($currentEvent, $event['listener_name'])) {
            return $done('stale', 'input-audience-stale');
        }

        $stage = 'revalidate-actors';
        $identities = $store->npcIdentities();
        $actors = [$event['listener_id'] => $event['listener_name']];
        if (!$playerSpeaker) {
            $actors[$event['speaker_id']] = $event['speaker_name'];
        }
        foreach ($actors as $id => $name) {
            $matches = array_values(array_filter($identities, static fn(array $row): bool => sameActorName($row['npc_name'] ?? null, $name)));
            if (count($matches) !== 1 || (int)($matches[0]['id'] ?? 0) !== (int)$id) {
                return $done('stale', 'actor-catalog-stale');
            }
        }
        foreach ($subjects as $token => $subject) {
            if ($token === 'player') {
                continue;
            }
            $matches = array_values(array_filter($identities, static fn(array $row): bool => sameActorName($row['npc_name'] ?? null, $subject['name'])));
            if (count($matches) !== 1 || (int)($matches[0]['id'] ?? 0) !== $subject['id']) {
                return $done('stale', 'subject-catalog-stale');
            }
            $stage = 'revalidate-subject';
            $target = $store->npcById($subject['id']);
            if (!is_array($target) || !sameActorName($target['npc_name'] ?? null, $subject['name'])) {
                return $done('stale', 'subject-stale');
            }
        }
        if (!$playerSpeaker) {
            $stage = 'revalidate-speaker';
            $speaker = $store->npcById($event['speaker_id']);
            if (!is_array($speaker) || !sameActorName($speaker['npc_name'] ?? null, $event['speaker_name'])) {
                return $done('stale', 'speaker-stale');
            }
        }

        $stage = 'resolve-relationships';
        $relationships = $extendedData->relationships ?? new \stdClass();
        if (!$relationships instanceof \stdClass) {
            return $done('invalid', 'relationships-invalid');
        }
        if ($playerSpeaker) {
            try {
                playerRelationshipKey($relationships, (string)$event['player_name']);
            } catch (RuntimeException) {
                return $done('failed', 'player-alias-ambiguous');
            }
        }
        $updatedExtendedData = clone $extendedData;
        $updatedRelationships = clone $relationships;
        $edgeUpdates = [];
        foreach ($judgments as $token => $judgment) {
            if ($judgment['delta'] === 0) {
                continue;
            }
            $name = $subjects[$token]['name'];
            if ($token === 'player') {
                try {
                    $name = playerRelationshipKey($updatedRelationships, (string)($event['player_name'] ?? '')) ?? 'Player';
                } catch (RuntimeException) {
                    return $done('failed', 'player-alias-ambiguous');
                }
            }
            $prior = $updatedRelationships->{$name} ?? (object)['aff' => 0, 'type' => 'neutral'];
            if (!$prior instanceof \stdClass) {
                return $done('invalid', 'edge-invalid');
            }
            $updatedEdge = clone $prior;
            $affinity = $updatedEdge->aff ?? 0;
            if (!is_int($affinity) && !is_float($affinity) && !(is_string($affinity) && is_numeric($affinity))) {
                return $done('invalid', 'affinity-invalid');
            }
            $beforeAffinity = (float)$affinity;
            $nextAffinity = max(-100, min(100, (float)$affinity + $judgment['delta']));
            $updatedEdge->aff = floor($nextAffinity) === $nextAffinity ? (int)$nextAffinity : $nextAffinity;
            $updatedRelationships->{$name} = $updatedEdge;
            $edgeUpdates[$name] = $updatedEdge;
            $edgeChanges[] = [
                'subject' => $token,
                'delta' => $judgment['delta'],
                'before' => $beforeAffinity,
                'after' => $updatedEdge->aff,
            ];
        }
        if ($edgeUpdates !== []) {
            $updatedExtendedData->relationships = $updatedRelationships;
        }

        $stage = 'validate-ledger';
        $pluginNamespace = storedLedgerNamespace($pluginExtendedData);
        if ($pluginNamespace === null) {
            return $done('invalid', 'ledger-invalid');
        }
        if ($pluginNamespace !== [] && $pluginNamespace['playthrough_id'] === $event['playthrough_id']) {
            if ($event['event_id'] <= $pluginNamespace['floor_event_id']) {
                return $done('below-floor', 'ledger-floor');
            }
            foreach ($pluginNamespace['events'] as $entry) {
                if ($entry['event_id'] === $event['event_id'] || $entry['utterance_id'] === $event['utterance_id']) {
                    return $done('duplicate', 'duplicate-event');
                }
            }
        }
        $stage = 'update-ledger';
        $nextLedger = nextLedger($pluginNamespace, $event['playthrough_id'], $event['event_id'], $event['utterance_id'], $judgments);
        $updatedPluginExtendedData = clone $pluginExtendedData;
        $updatedPluginExtendedData->mind_poisoning = (object)$nextLedger;
        $stage = 'validate-timeline';
        $currentGamets = $listener['gamets_last_updated'] ?? 0;
        if ($currentGamets !== null && !is_numeric($currentGamets)) {
            return $done('invalid', 'timeline-invalid');
        }
        $snapshotGamets = max((float)($currentGamets ?? 0), (float)$event['gamets']);

        $stage = 'write-listener';
        if (!$store->writeNpc($event['listener_id'], $edgeUpdates, (object)$nextLedger, $snapshotGamets)) {
            return $done('failed', 'listener-write-failed');
        }
        $stage = 'verify-listener';
        $written = $store->npcById($event['listener_id']);
        if (
            !is_array($written)
            || !sameJsonValue($written['extended_data'] ?? null, $updatedExtendedData)
            || !sameJsonValue($written['plugin_extended_data'] ?? null, $updatedPluginExtendedData)
            || (float)($written['gamets_last_updated'] ?? -1) !== $snapshotGamets
        ) {
            return $done('failed', 'listener-verification-failed');
        }
        $expected = [
            'extended_data' => $updatedExtendedData,
            'plugin_extended_data' => $updatedPluginExtendedData,
            'gamets_last_updated' => $snapshotGamets,
        ];
        $stage = 'verify-snapshot';
        if (!$store->backupAndVerify($event['listener_id'], $expected)) {
            return $done('failed', 'snapshot-verification-failed');
        }
        $stage = 'commit';
        $commitAttempted = true;
        if (!$store->commit()) {
            return $done('failed', 'commit-failed');
        }
        $committed = true;
        return $done('committed', 'committed');
    } catch (Throwable $error) {
        return $done('failed', $stage . '-failed');
    } finally {
        if ($cleanupRequired) {
            try {
                if (!$committed) {
                    $cleanupStage = 'rollback';
                    $store->rollback();
                }
                $cleanupStage = 'release';
                $store->release();
            } catch (Throwable $error) {
                $cleanupFailed = true;
                $cleanupError = $error;
                $cleanupReason = $cleanupStage . '-failed';
                $status = 'failed';
                $reason = $cleanupReason;
            }
        }
        if ($store instanceof PostgresStoreDb && $store->cleanupFailed()) {
            $cleanupFailed = true;
        }

        $changes = $committed ? $edgeChanges : [];
        $changedCount = count(array_filter($changes, static fn(array $change): bool => (float)$change['before'] !== (float)$change['after']));
        if ($committed && $changedCount === 0 && $reason === 'committed') {
            $reason = 'zero-change';
        }
        $logFields = [
            'persistence_outcome' => $status,
            'persistence_reason' => $reason,
            'persistence_ms' => RequestLog::elapsedMs($startedAt),
            'commit_state' => $committed ? 'confirmed' : ($commitAttempted ? 'unconfirmed' : 'not_attempted'),
            'committed' => $committed,
            'changes' => $changes,
            'changed_count' => $changedCount,
        ];
        if ($cleanupFailed) {
            $logFields['cleanup_failed'] = true;
            $requestLog?->context(['cleanup_failed' => true]);
        }
        if ($requestLog !== null) {
            $requestLog->context($logFields);
            $level = $cleanupFailed || $status === 'failed'
                ? 'error'
                : ($status === 'invalid' ? 'warning' : 'info');
            $requestLog->event('persistence_finished', $level, $logFields);
            if ($cleanupError !== null) {
                $requestLog->event('persistence_cleanup_failed', 'error', [
                    'stage' => $cleanupReason === 'rollback-failed' ? 'rollback' : 'release',
                    'reason' => $cleanupReason,
                    'committed' => $committed,
                    'cleanup_failed' => true,
                ]);
            }
        } elseif ($status === 'failed') {
            try {
                @error_log('Mind Poisoning persistence failed at ' . $reason . '.');
            } catch (Throwable) {
            }
        }

        if ($cleanupError !== null) {
            throw $cleanupError;
        }
    }
}

function sameEvent(array $expected, ?array $current): bool
{
    if (!is_array($current)
        || (int)($current['event_id'] ?? 0) !== ($expected['event_id'] ?? null)
        || ($current['utterance_id'] ?? null) !== ($expected['utterance_id'] ?? null)
        || (float)($current['gamets'] ?? -1) !== (float)($expected['gamets'] ?? -2)
        || ($current['source_data'] ?? null) !== ($expected['source_data'] ?? null)) {
        return false;
    }

    if (($expected['speaker_kind'] ?? 'npc') === 'player') {
        foreach ([
            'speaker_kind', 'speaker_id', 'source_kind', 'source_type', 'source_ts',
            'source_localts', 'source_sess', 'source_people',
        ] as $field) {
            if (($current[$field] ?? null) !== ($expected[$field] ?? null)) {
                return false;
            }
        }
        return is_string($current['source_gamets'] ?? null)
            && $current['source_gamets'] === ($expected['source_gamets'] ?? null);
    }

    return ($current['speaker_kind'] ?? 'npc') === 'npc'
        && !array_key_exists('source_kind', $current)
        && in_array($current['delivery_state'] ?? null, ['emitted', 'spoken'], true);
}

final class PostgresStoreDb implements StoreDb
{
    private object $db;
    private ?RequestLog $requestLog;
    private mixed $connection = null;
    private bool $ownsTransaction = false;
    private bool $ownsAdvisoryLock = false;
    private bool $cleanupFailed = false;
    private int $advisoryKey = 0;

    public function __construct(?RequestLog $requestLog = null)
    {
        $db = $GLOBALS['db'] ?? null;
        if (!is_object($db)) {
            throw new RuntimeException('CHIM database helper is unavailable.');
        }
        $this->db = $db;
        $this->requestLog = $requestLog;
    }

    public function cleanupFailed(): bool
    {
        return $this->cleanupFailed;
    }

    public function activePlaythrough(): ?array
    {
        $rows = $this->rows(
            "SELECT profile.id,
                    (SELECT player.value FROM core_player player WHERE player.id = 'player_name' LIMIT 1) AS player_name
             FROM chim_meta.playthrough_profiles profile WHERE profile.is_active IS TRUE ORDER BY profile.id LIMIT 2"
        );
        if (count($rows) !== 1 || !is_numeric($rows[0]['id'] ?? null) || (int)$rows[0]['id'] < 1) {
            return null;
        }
        return ['id' => (string)(int)$rows[0]['id'], 'player_name' => trim((string)($rows[0]['player_name'] ?? ''))];
    }

    public function acknowledgedEvent(string $utteranceId): ?array
    {
        if (preg_match('/\Autt_[A-Za-z0-9_-]{8,128}\z/', $utteranceId) !== 1) {
            return null;
        }
        $row = $this->one(
            "SELECT COALESCE(jsonb_agg(to_jsonb(e) ORDER BY e.rowid), '[]'::jsonb) AS events
             FROM (SELECT rowid, type, utterance_id, delivery_state, gamets, data
                   FROM eventlog WHERE type = 'chat' AND utterance_id = $1 ORDER BY rowid LIMIT 2) e",
            [$utteranceId]
        );
        $events = json_decode((string)($row['events'] ?? '[]'), true);
        if (!is_array($events) || count($events) !== 1 || !is_array($events[0])) {
            return null;
        }
        return normalizeEventRow($events[0]);
    }

    public function playerInputEvent(array $source): ?array
    {
        $parameters = playerInputSourceParameters($source);
        if ($parameters === null) {
            return null;
        }
        $row = $this->one(
            "SELECT COALESCE(jsonb_agg(to_jsonb(e) ORDER BY e.rowid), '[]'::jsonb) AS events
             FROM (SELECT rowid, type, ts::text AS ts, gamets::text AS gamets, data, localts, sess, people
                   FROM eventlog
                   WHERE type = $1 AND ts = $2 AND gamets = $3 AND data = $4 AND localts = $5 AND sess = $6
                   ORDER BY rowid LIMIT 2) e",
            $parameters
        );
        try {
            $events = json_decode((string)($row['events'] ?? '[]'), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }
        if (!is_array($events) || count($events) !== 1 || !is_array($events[0])) {
            return null;
        }
        return normalizePlayerInputRow($events[0]);
    }

    public function eventById(int $eventId, string $utteranceId): ?array
    {
        if (str_starts_with($utteranceId, 'input_')) {
            if (preg_match('/\Ainput_([1-9][0-9]*)\z/', $utteranceId, $matches) !== 1 || (string)$eventId !== $matches[1]) {
                return null;
            }
            $row = $this->one(
                "SELECT e.rowid, e.type, e.ts::text AS ts, e.gamets::text AS gamets, e.data, e.localts, e.sess, e.people,
                        (SELECT count(*) FROM eventlog exact_event
                         WHERE exact_event.type = e.type AND exact_event.ts = e.ts
                           AND exact_event.gamets = e.gamets AND exact_event.data = e.data
                           AND exact_event.localts = e.localts AND exact_event.sess = e.sess) AS source_count
                 FROM eventlog e
                 WHERE e.rowid = $1 AND e.type IN ('inputtext', 'inputtext_s', 'ginputtext', 'ginputtext_s')
                   AND e.sess = 'web' AND ('input_' || e.rowid::text) = $2
                 FOR SHARE OF e",
                [$eventId, $utteranceId]
            );
            return $row ? normalizePlayerInputRow($row) : null;
        }

        $sql = "SELECT e.rowid, e.type, e.utterance_id, e.delivery_state, e.gamets, e.data
                FROM eventlog e
                WHERE e.rowid = $1 AND e.type = 'chat' AND e.utterance_id = $2
                  AND (SELECT count(*) FROM eventlog exact_event
                       WHERE exact_event.type = 'chat' AND exact_event.utterance_id = $2) = 1
                FOR SHARE OF e";
        $row = $this->one($sql, [$eventId, $utteranceId]);
        return $row ? normalizeEventRow($row) : null;
    }

    public function npcIdentities(): array
    {
        $rows = $this->rows(
            "SELECT id, npc_name FROM core_npc_master
             WHERE npc_name IS NOT NULL AND trim(npc_name) <> '' AND npc_name <> 'The Narrator'
             ORDER BY id"
        );
        return array_values(array_filter(array_map(static function (array $row): ?array {
            $id = filter_var($row['id'] ?? null, FILTER_VALIDATE_INT);
            $name = $row['npc_name'] ?? null;
            return $id !== false && $id > 0 && is_string($name) && trim($name) !== ''
                ? ['id' => (int)$id, 'npc_name' => $name]
                : null;
        }, $rows)));
    }

    public function npcById(int $npcId, bool $forUpdate = false): ?array
    {
        $sql = 'SELECT * FROM core_npc_master WHERE id = $1' . ($forUpdate ? ' FOR UPDATE' : '');
        $row = $this->one($sql, [$npcId]);
        if (!$row) {
            return null;
        }
        foreach (['extended_data', 'plugin_extended_data'] as $column) {
            $row[$column] = decodeJsonObject($row[$column] ?? null, $column);
        }
        if (isset($row['gamets_last_updated']) && $row['gamets_last_updated'] !== '') {
            if (!is_numeric($row['gamets_last_updated'])) {
                throw new RuntimeException('Invalid listener timeline value.');
            }
            $row['gamets_last_updated'] = (float)$row['gamets_last_updated'];
        }
        return $row;
    }

    public function beginForListener(int $listenerId): bool
    {
        $this->captureConnection();
        assertIdleTransactionStatus(pg_transaction_status($this->connection));
        $this->advisoryKey = 1001000000 + $listenerId;
        $result = @pg_query_params($this->connection, 'SELECT pg_try_advisory_lock($1) AS acquired', [$this->advisoryKey]);
        $this->assertConnectionIdentity();
        if ($result === false) {
            throw new RuntimeException('Could not acquire the relationship advisory lock.');
        }
        $row = pg_fetch_assoc($result);
        if (($row['acquired'] ?? null) !== 't') {
            return false;
        }
        $this->ownsAdvisoryLock = true;
        assertIdleTransactionStatus(pg_transaction_status($this->connection));
        if (@pg_query($this->connection, 'BEGIN') === false) {
            $this->assertConnectionIdentity();
            throw new RuntimeException('Could not begin the listener transaction.');
        }
        $this->ownsTransaction = true;
        $this->assertTransactionConnection();
        return true;
    }

    public function writeNpc(int $npcId, array $relationshipEdges, object $mindPoisoningData, float $gamets): bool
    {
        $params = [$npcId];
        $extendedExpression = 'extended_data';
        if ($relationshipEdges !== []) {
            $extendedExpression = "jsonb_set(COALESCE(extended_data, '{}'::jsonb), '{relationships}', COALESCE(extended_data->'relationships', '{}'::jsonb), true)";
        }
        foreach ($relationshipEdges as $name => $edge) {
            if (!is_string($name) || !$edge instanceof \stdClass) {
                throw new RuntimeException('Invalid relationship edge update.');
            }
            $params[] = $name;
            $nameIndex = count($params);
            $params[] = json_encode($edge, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $edgeIndex = count($params);
            $extendedExpression = "jsonb_set(COALESCE({$extendedExpression}, '{}'::jsonb), ARRAY['relationships', \${$nameIndex}::text], \${$edgeIndex}::jsonb, true)";
        }
        $params[] = json_encode($mindPoisoningData, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $pluginIndex = count($params);
        $params[] = $gamets;
        $gametsIndex = count($params);
        $sql = "UPDATE core_npc_master
                SET extended_data = {$extendedExpression},
                    plugin_extended_data = jsonb_set(COALESCE(plugin_extended_data, '{}'::jsonb), ARRAY['mind_poisoning']::text[], \${$pluginIndex}::jsonb, true),
                    gamets_last_updated = \${$gametsIndex}
                WHERE id = $1 RETURNING id";
        $result = $this->nativeQuery($sql, $params);
        return pg_fetch_assoc($result) !== false;
    }

    public function backupAndVerify(int $npcId, array $expected): bool
    {
        // The CHIM helper calls sql::re_connect() before INSERT. Native I/O keeps this inside our transaction.
        $row = $this->nativeOne('SELECT * FROM core_npc_master WHERE id = $1', [$npcId]);
        if (!$row) {
            return false;
        }
        $extendedData = decodeJsonObject($row['extended_data'] ?? null, 'extended_data');
        $extendedData->_chim_history_source = 'relationship';
        $row['extended_data'] = json_encode($extendedData, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        unset($row['id']);
        $row['npc_id'] = $npcId;
        $row['created'] = date('Y-m-d H:i:s');

        $columns = array_keys($row);
        foreach ($columns as $column) {
            if (!is_string($column) || preg_match('/\A[A-Za-z_][A-Za-z0-9_]*\z/', $column) !== 1) {
                throw new RuntimeException('Unexpected NPC history column name.');
            }
        }
        $slots = [];
        foreach ($columns as $index => $_column) {
            $slots[] = '$' . ($index + 1);
        }
        $insert = 'INSERT INTO core_npc_master_history (' . implode(', ', $columns) . ') VALUES ('
            . implode(', ', $slots) . ') RETURNING history_id';
        $result = $this->nativeQuery($insert, array_values($row));
        $inserted = pg_fetch_assoc($result);
        $historyId = filter_var($inserted['history_id'] ?? null, FILTER_VALIDATE_INT);
        if ($historyId === false || $historyId < 1) {
            return false;
        }

        $expectedExtended = clone $expected['extended_data'];
        $expectedExtended->_chim_history_source = 'relationship';
        $expectedExtendedJson = json_encode($expectedExtended, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $expectedPluginsJson = json_encode($expected['plugin_extended_data'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $verification = $this->nativeOne(
            'SELECT npc_id,
                    (extended_data = $2::jsonb) AS extended_matches,
                    (plugin_extended_data = $3::jsonb) AS plugins_match,
                    (gamets_last_updated = $4::numeric) AS gamets_match
             FROM core_npc_master_history WHERE history_id = $1 AND npc_id = $5',
            [(int)$historyId, $expectedExtendedJson, $expectedPluginsJson, $expected['gamets_last_updated'], $npcId]
        );
        return is_array($verification)
            && (int)($verification['npc_id'] ?? 0) === $npcId
            && ($verification['extended_matches'] ?? null) === 't'
            && ($verification['plugins_match'] ?? null) === 't'
            && ($verification['gamets_match'] ?? null) === 't';
    }

    public function commit(): bool
    {
        if (!$this->ownsTransaction) {
            return false;
        }
        $result = @pg_query($this->connection, 'COMMIT');
        $this->assertConnectionIdentity();
        if ($result === false) {
            throw new RuntimeException('PostgreSQL rejected the listener transaction commit.');
        }
        $this->ownsTransaction = false;
        if (pg_transaction_status($this->connection) !== PGSQL_TRANSACTION_IDLE) {
            throw new RuntimeException('PostgreSQL did not return to idle after commit.');
        }
        return true;
    }

    public function rollback(): void
    {
        if (!$this->ownsTransaction) {
            return;
        }
        if (!$this->connection instanceof \PgSql\Connection
            || pg_connection_status($this->connection) !== PGSQL_CONNECTION_OK) {
            $this->reportCleanupFailure('rollback', 'rollback-connection-unavailable');
        } elseif (pg_transaction_status($this->connection) !== PGSQL_TRANSACTION_IDLE
            && @pg_query($this->connection, 'ROLLBACK') === false) {
            $this->reportCleanupFailure('rollback', 'rollback-query-failed');
        }
        $this->ownsTransaction = false;
    }

    public function release(): void
    {
        if (!$this->ownsAdvisoryLock) {
            return;
        }
        if (!$this->connection instanceof \PgSql\Connection
            || pg_connection_status($this->connection) !== PGSQL_CONNECTION_OK) {
            $this->reportCleanupFailure('release', 'advisory-unlock-connection-unavailable');
        } else {
            $result = @pg_query_params($this->connection, 'SELECT pg_advisory_unlock($1) AS released', [$this->advisoryKey]);
            if ($result === false) {
                $this->reportCleanupFailure('release', 'advisory-unlock-query-failed');
            } else {
                $row = pg_fetch_assoc($result);
                if (($row['released'] ?? null) !== 't') {
                    $this->reportCleanupFailure('release', 'advisory-unlock-not-confirmed');
                }
            }
        }
        $this->ownsAdvisoryLock = false;
    }

    private function reportCleanupFailure(string $stage, string $reason): void
    {
        $this->cleanupFailed = true;
        if ($this->requestLog !== null) {
            $this->requestLog->context(['cleanup_failed' => true]);
            $this->requestLog->event('persistence_cleanup_failed', 'error', [
                'stage' => $stage,
                'reason' => $reason,
                'cleanup_failed' => true,
            ]);
            return;
        }
        try {
            @error_log('Mind Poisoning cleanup failed at ' . $reason . '.');
        } catch (Throwable) {
        }
    }

    private function rows(string $query, array $params = []): array
    {
        if ($this->ownsTransaction) {
            $result = $this->nativeQuery($query, $params);
            $rows = [];
            while ($row = pg_fetch_assoc($result)) {
                $rows[] = $row;
            }
            return $rows;
        }
        if ($params) {
            $row = $this->db->fetchOne($query, $params);
            return is_array($row) && $row !== [] ? [$row] : [];
        }
        $rows = $this->db->fetchAll($query);
        return is_array($rows) ? $rows : [];
    }

    private function one(string $query, array $params = []): array
    {
        if ($this->ownsTransaction) {
            return $this->nativeOne($query, $params) ?? [];
        }
        $row = $this->db->fetchOne($query, $params);
        return is_array($row) ? $row : [];
    }

    private function nativeOne(string $query, array $params): ?array
    {
        $result = $this->nativeQuery($query, $params);
        $row = pg_fetch_assoc($result);
        return is_array($row) ? $row : null;
    }

    private function nativeQuery(string $query, array $params)
    {
        $this->assertTransactionConnection();
        $result = $params
            ? @pg_query_params($this->connection, $query, $params)
            : @pg_query($this->connection, $query);
        $this->assertConnectionIdentity();
        if ($result === false) {
            throw new RuntimeException('Native PostgreSQL statement failed: ' . substr(pg_last_error($this->connection), 0, 180));
        }
        return $result;
    }

    private function captureConnection(): void
    {
        if (!class_exists(\sql::class)) {
            throw new RuntimeException('CHIM sql helper is unavailable.');
        }
        $property = new \ReflectionProperty(\sql::class, 'link');
        $property->setAccessible(true);
        $connection = $property->getValue();
        assertPgSqlConnection($connection);
        if (pg_connection_status($connection) !== PGSQL_CONNECTION_OK) {
            throw new RuntimeException('CHIM PostgreSQL connection is not healthy.');
        }
        $this->connection = $connection;
    }

    private function assertConnectionIdentity(): void
    {
        if (!class_exists(\sql::class)) {
            throw new RuntimeException('CHIM sql helper disappeared during persistence.');
        }
        $property = new \ReflectionProperty(\sql::class, 'link');
        $property->setAccessible(true);
        $current = $property->getValue();
        if ($current !== $this->connection) {
            throw new RuntimeException('CHIM PostgreSQL connection changed during persistence.');
        }
        assertPgSqlConnection($this->connection);
        if (pg_connection_status($this->connection) !== PGSQL_CONNECTION_OK) {
            throw new RuntimeException('CHIM PostgreSQL connection failed during persistence.');
        }
    }

    private function assertTransactionConnection(): void
    {
        if (!$this->ownsTransaction) {
            throw new RuntimeException('No Mind Poisoning transaction is active.');
        }
        $this->assertConnectionIdentity();
        $status = pg_transaction_status($this->connection);
        if ($status !== PGSQL_TRANSACTION_INTRANS && $status !== PGSQL_TRANSACTION_INERROR) {
            throw new RuntimeException('Mind Poisoning lost its transaction boundary.');
        }
    }
}
