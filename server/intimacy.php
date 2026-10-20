<?php
declare(strict_types=1);

namespace ChimMindPoisoning;

use InvalidArgumentException;
use RuntimeException;
use Throwable;

const MIND_POISONING_INTIMACY_API_VERSION = 1;
const MIND_POISONING_INTIMACY_MAX_TARGETS = 64;

require_once __DIR__ . '/store.php';

function validIntimacySubject(mixed $subject): bool
{
    return is_string($subject)
        && ($subject === 'player' || preg_match('/\Anpc:[1-9][0-9]*\z/D', $subject) === 1);
}

function mindPoisoningIntimacyAckIdentity(array $event): string
{
    $fields = [
        $event['event_id'] ?? null,
        $event['utterance_id'] ?? null,
        $event['playthrough_id'] ?? null,
        $event['speaker_id'] ?? null,
        $event['listener_id'] ?? null,
        $event['target_subject'] ?? null,
        $event['speech_hash'] ?? null,
        $event['interaction_generation'] ?? null,
    ];
    if (
        !is_int($fields[0]) || $fields[0] < 1
        || !is_string($fields[1]) || preg_match('/\Autt_[A-Za-z0-9_-]{8,128}\z/D', $fields[1]) !== 1
        || !is_string($fields[2]) || trim($fields[2]) === ''
        || !is_int($fields[3]) || $fields[3] < 1
        || (!is_int($fields[4]) && $fields[4] !== null) || (is_int($fields[4]) && $fields[4] < 1)
        || !validIntimacySubject($fields[5])
        || !is_string($fields[6]) || preg_match('/\A[a-f0-9]{64}\z/D', $fields[6]) !== 1
        || !is_int($fields[7]) || $fields[7] < 0
        || (($fields[5] === 'player') !== ($fields[4] === null))
    ) {
        throw new InvalidArgumentException('Invalid intimacy ACK identity.');
    }
    return hash('sha256', json_encode($fields, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function validIntimacyAckEnvelope(array $event): bool
{
    if (
        ($event['source_kind'] ?? null) !== 'intimacy'
        || ($event['speaker_kind'] ?? null) !== 'npc'
        || !is_int($event['speaker_id'] ?? null) || $event['speaker_id'] < 1
        || ($event['opinion_owner_id'] ?? null) !== $event['speaker_id']
        || !is_string($event['speaker_name'] ?? null) || trim($event['speaker_name']) === ''
        || !is_string($event['listener_name'] ?? null) || trim($event['listener_name']) === ''
        || ($event['listener_role'] ?? null) !== 'addressed'
        || !is_string($event['addressed_listener_name'] ?? null)
        || $event['addressed_listener_name'] !== $event['listener_name']
        || !validIntimacySubject($event['target_subject'] ?? null)
        || !is_int($event['interaction_generation'] ?? null) || $event['interaction_generation'] < 0
        || !is_string($event['text'] ?? null) || $event['text'] === '' || preg_match('//u', $event['text']) !== 1
        || !is_string($event['speech_hash'] ?? null)
        || !hash_equals(hash('sha256', $event['text']), $event['speech_hash'])
        || !is_string($event['playthrough_id'] ?? null) || trim($event['playthrough_id']) === ''
        || !is_string($event['source_data'] ?? null)
        || (!is_int($event['gamets'] ?? null) && !is_float($event['gamets']) && !(is_string($event['gamets'] ?? null) && is_numeric($event['gamets'])))
        || !is_finite((float)$event['gamets']) || (float)$event['gamets'] < 0
        || !array_key_exists('intimacy_expected_record', $event)
        || (!is_array($event['intimacy_expected_record']) && $event['intimacy_expected_record'] !== null)
        || (is_array($event['intimacy_expected_record'])
            && validIntimacyArrangementMap([$event['target_subject'] => $event['intimacy_expected_record']]) === null)
        || !is_string($event['ack_identity'] ?? null)
    ) {
        return false;
    }
    $target = $event['target_subject'];
    if ($target === 'player') {
        if (
            ($event['listener_id'] ?? null) !== null
            || ($event['addressed_listener_id'] ?? null) !== null
            || !is_string($event['player_name'] ?? null) || trim($event['player_name']) === ''
        ) {
            return false;
        }
    } else {
        $targetId = (int)substr($target, 4);
        if (
            ($event['listener_id'] ?? null) !== $targetId
            || ($event['addressed_listener_id'] ?? null) !== $targetId
            || $targetId === $event['speaker_id']
            || !is_string($event['player_name'] ?? null) || trim($event['player_name']) === ''
        ) {
            return false;
        }
    }
    if (!validIntimacyChange($event['intimacy_change'] ?? null, $event['text'])) {
        return false;
    }
    try {
        return hash_equals(mindPoisoningIntimacyAckIdentity($event), $event['ack_identity']);
    } catch (Throwable) {
        return false;
    }
}

function validIntimacyChange(mixed $change, string $text): bool
{
    if ($change === null) {
        return true;
    }
    $keys = is_array($change) ? array_keys($change) : [];
    sort($keys, SORT_STRING);
    return is_array($change)
        && $keys === ['evidence', 'status']
        && in_array($change['status'] ?? null, ['casual', 'romantic', 'none'], true)
        && is_string($change['evidence'] ?? null)
        && $change['evidence'] !== ''
        && strlen($change['evidence']) <= 120
        && preg_match('//u', $change['evidence']) === 1
        && str_contains($text, $change['evidence']);
}

function validIntimacyExpectedRecords(mixed $records, array $subjects): bool
{
    if (!is_array($records) || count($records) > 8) {
        return false;
    }
    foreach ($records as $target => $record) {
        if (
            !is_string($target) || !validIntimacySubject($target)
            || !array_key_exists($target, $subjects)
            || (!is_array($record) && $record !== null)
            || (is_array($record) && validIntimacyArrangementMap([$target => $record]) === null)
        ) {
            return false;
        }
    }
    return true;
}

function mindPoisoningIntimacyRecognitionEnabled(): bool
{
    if (!function_exists('chimGetGeneralSettingBool')) {
        return false;
    }
    try {
        return \chimGetGeneralSettingBool('mind_poisoning_intimacy_enabled', false) === true;
    } catch (Throwable) {
        return false;
    }
}

/** Mirror SHARMAT's server-side hard child signals for the arrangement lane. */
function mindPoisoningIntimacyIsKnownChild(
    string $actorName,
    ?array $boundNpcRow = null,
    ?array $trustedChildData = null
): bool {
    static $names = [
        'babette', 'erith', 'blaise', 'clinton lylvieve', 'lucia', 'mila valentia',
        'francois beaufort', 'samuel', 'aeta', 'agni', 'britte', 'dagny', 'dorthe',
        'eirid', 'fjotra', 'helgi', "helgi's ghost", 'hrefna', 'minette vinius',
        'rin', 'runa fair-shield', 'sissel', 'sofie', 'svari', 'assur',
        'aventus aretino', 'bottar', 'frodnar', 'frothar', 'gralnach',
        'grimvar cruel-sea', 'haming', 'hroar', 'joric', 'knud', 'lars battle-born',
        'little pelagius', 'nelkir', 'skuli', 'smaref ice-blade', 'sond', 'virkmund',
        'adara', 'braith', 'alesan', 'kayd', 'lavinia', 'clinton',
    ];
    if (in_array(strtolower(trim($actorName)), $names, true)) {
        return true;
    }

    $extendedData = $boundNpcRow['extended_data'] ?? null;
    $pluginData = $boundNpcRow['plugin_extended_data'] ?? null;
    $pluginData = is_array($pluginData) ? $pluginData : (is_object($pluginData) ? get_object_vars($pluginData) : []);
    $sharmatData = $pluginData['sharmat'] ?? null;
    $facts = [
        $boundNpcRow ?? [],
        is_array($extendedData) ? $extendedData : (is_object($extendedData) ? get_object_vars($extendedData) : []),
        is_array($sharmatData) ? $sharmatData : (is_object($sharmatData) ? get_object_vars($sharmatData) : []),
        $trustedChildData ?? [],
    ];
    foreach ($facts as $fact) {
        if (!empty($fact['is_child'])) {
            return true;
        }
        $race = $fact['race'] ?? null;
        if (is_string($race) && str_contains(strtolower(trim($race)), 'child')) {
            return true;
        }
    }
    return false;
}

/** Returns null when the optional read-only child metadata lookup is unavailable. */
function mindPoisoningIntimacyChildStatus(
    StoreDb $store,
    string $actorName,
    ?array $boundNpcRow = null,
    bool $readStoredChildData = true
): ?bool {
    if (mindPoisoningIntimacyIsKnownChild($actorName, $boundNpcRow)) {
        return true;
    }
    if (!$readStoredChildData || !method_exists($store, 'intimacyChildDataByName')) {
        return false;
    }
    try {
        $childData = $store->intimacyChildDataByName($actorName);
    } catch (Throwable) {
        return null;
    }
    if ($childData === null) {
        return null;
    }
    return mindPoisoningIntimacyIsKnownChild($actorName, $boundNpcRow, $childData);
}

function intimacyDirectSourceCurrent(array $event, array $source, array $active, array $identities): bool
{
    if (
        !validIntimacyAckEnvelope($event)
        || !is_array($source)
        || (int)($source['event_id'] ?? 0) !== $event['event_id']
        || ($source['utterance_id'] ?? null) !== $event['utterance_id']
        || ($source['source_data'] ?? null) !== $event['source_data']
        || !in_array($source['delivery_state'] ?? null, ['emitted', 'spoken'], true)
        || (array_key_exists('speaker_kind', $source) && $source['speaker_kind'] !== 'npc')
        || (string)($active['id'] ?? '') !== $event['playthrough_id']
    ) {
        return false;
    }
    $speakerMatches = array_values(array_filter($identities, static fn(array $row): bool => sameActorName($row['npc_name'] ?? null, $event['speaker_name'])));
    if (count($speakerMatches) !== 1 || (int)($speakerMatches[0]['id'] ?? 0) !== $event['speaker_id']) {
        return false;
    }
    $parts = chatSourceParts($source['source_data']);
    $target = is_array($parts) ? ($parts['target'] ?? null) : null;
    $targets = is_array($target) ? ($target['targets'] ?? null) : null;
    if (
        !is_array($parts) || !sameActorName($parts['speaker'] ?? null, $event['speaker_name'])
        || ($parts['text'] ?? null) !== $event['text']
        || !is_array($target) || empty($target['hasExplicitTarget']) || !empty($target['isBroadcast'])
        || !is_array($targets) || count($targets) !== 1 || !is_string($targets[0] ?? null)
    ) {
        return false;
    }
    if ($event['target_subject'] === 'player') {
        $playerName = $active['player_name'] ?? null;
        return is_string($playerName) && trim($playerName) !== ''
            && sameActorName($event['player_name'], $playerName)
            && intimacyDirectTargetMatches($targets[0], trim($playerName), $active, $identities);
    }
    $targetId = (int)substr($event['target_subject'], 4);
    $targetMatches = array_values(array_filter($identities, static fn(array $row): bool => sameActorName($row['npc_name'] ?? null, $event['addressed_listener_name'])));
    return count($targetMatches) === 1
        && (int)($targetMatches[0]['id'] ?? 0) === $targetId
        && sameActorName($targets[0], $event['addressed_listener_name']);
}

function mindPoisoningCaptureIntimacyRecord(array $ownerRow, string $targetSubject, string $playthroughId): ?array
{
    $ownerId = filter_var($ownerRow['id'] ?? null, FILTER_VALIDATE_INT);
    if ($ownerId === false || $ownerId < 1 || !validIntimacySubject($targetSubject) || $playthroughId === '') {
        throw new RuntimeException('Invalid intimacy record capture.');
    }
    $plugins = $ownerRow['plugin_extended_data'] ?? null;
    if (!$plugins instanceof \stdClass) {
        throw new RuntimeException('Stored intimacy namespace is invalid.');
    }
    $ledger = storedLedgerNamespace($plugins);
    if ($ledger === null) {
        throw new RuntimeException('Stored intimacy namespace is invalid.');
    }
    if ($ledger === []) {
        return null;
    }
    if ($ledger['playthrough_id'] !== $playthroughId) {
        return null;
    }
    $arrangements = $ledger['arrangements'] ?? [];
    if (!is_array($arrangements)) {
        throw new RuntimeException('Stored intimacy map is invalid.');
    }
    if (!array_key_exists($targetSubject, $arrangements)) {
        return null;
    }
    $record = $arrangements[$targetSubject];
    if (
        !is_array($record)
        || ($record['owner_id'] ?? null) !== $ownerId
        || ($record['target'] ?? null) !== $targetSubject
        || ($record['playthrough_id'] ?? null) !== $playthroughId
    ) {
        throw new RuntimeException('Stored intimacy identity is invalid.');
    }
    return $record;
}

function mindPoisoningTransitionIntimacyArrangement(
    array $arrangements,
    string $targetSubject,
    ?array $change,
    array $provenance
): array {
    $validated = validIntimacyArrangementMap($arrangements);
    $keys = array_keys($provenance);
    sort($keys, SORT_STRING);
    if (
        $validated === null || !validIntimacySubject($targetSubject)
        || $keys !== ['basis', 'gamets', 'owner_id', 'playthrough_id', 'sources']
        || !is_int($provenance['owner_id'] ?? null) || $provenance['owner_id'] < 1
        || !in_array($provenance['basis'] ?? null, ['direct_npc_dialogue', 'registered_reflection'], true)
        || !is_string($provenance['playthrough_id'] ?? null) || trim($provenance['playthrough_id']) === ''
        || (!is_int($provenance['gamets'] ?? null) && !is_float($provenance['gamets'] ?? null))
        || !is_finite((float)$provenance['gamets']) || $provenance['gamets'] < 0
        || !validStoredIntimacySources($provenance['sources'] ?? null)
        || (($provenance['basis'] === 'direct_npc_dialogue') && count($provenance['sources']) !== 1)
    ) {
        throw new InvalidArgumentException('Invalid intimacy transition.');
    }
    if ($change !== null) {
        $changeKeys = array_keys($change);
        sort($changeKeys, SORT_STRING);
        if (
            $changeKeys !== ['evidence', 'status']
            || !in_array($change['status'] ?? null, ['casual', 'romantic', 'none'], true)
            || !is_string($change['evidence'] ?? null) || $change['evidence'] === ''
            || strlen($change['evidence']) > 120 || preg_match('//u', $change['evidence']) !== 1
        ) {
            throw new InvalidArgumentException('Invalid intimacy change.');
        }
    }

    $current = $validated[$targetSubject] ?? null;
    if ($current !== null && (
        $current['owner_id'] !== $provenance['owner_id']
        || $current['target'] !== $targetSubject
        || $current['playthrough_id'] !== $provenance['playthrough_id']
    )) {
        throw new InvalidArgumentException('Stored intimacy identity does not match.');
    }
    if ($change === null) {
        return ['arrangements' => $validated, 'outcome' => 'no_claim', 'before' => $current['kind'] ?? null, 'after' => $current['kind'] ?? null];
    }
    $latestSource = $provenance['sources'][array_key_last($provenance['sources'])]['event_id'];
    $currentSources = $current['sources'] ?? [];
    $currentSource = $currentSources === [] ? 0 : $currentSources[array_key_last($currentSources)]['event_id'];
    if ($current !== null && $latestSource <= $currentSource) {
        return ['arrangements' => $validated, 'outcome' => 'older', 'before' => $current['kind'], 'after' => $current['kind']];
    }
    if ($current === null && count($validated) >= MIND_POISONING_INTIMACY_MAX_TARGETS) {
        return ['arrangements' => $validated, 'outcome' => 'capacity', 'before' => null, 'after' => null];
    }

    $kind = $change['status'];
    $before = $current['kind'] ?? null;
    $record = [
        'owner_id' => $provenance['owner_id'],
        'target' => $targetSubject,
        'playthrough_id' => $provenance['playthrough_id'],
        'kind' => $kind,
        'basis' => $provenance['basis'],
        'sources' => $provenance['sources'],
        'gamets' => (float)$provenance['gamets'],
    ];
    if ($kind === 'none' && in_array($before, ['casual', 'romantic'], true)) {
        $record['prior_kind'] = $before;
        $outcome = 'ended';
    } elseif ($kind === 'none' && $before === 'none' && in_array($current['prior_kind'] ?? null, ['casual', 'romantic'], true)) {
        $record['prior_kind'] = $current['prior_kind'];
        $outcome = 'reaffirmed';
    } elseif ($current !== null && $before === $kind) {
        $outcome = 'reaffirmed';
    } elseif ($kind === 'none') {
        $outcome = 'none';
    } else {
        $outcome = 'applied';
    }
    $validated[$targetSubject] = $record;
    ksort($validated, SORT_STRING);
    return ['arrangements' => $validated, 'outcome' => $outcome, 'before' => $before, 'after' => $kind];
}

function mindPoisoningReadIntimacyArrangement(int $ownerNpcId, string $targetSubject, ?StoreDb $store = null): array
{
    if ($ownerNpcId < 1 || !validIntimacySubject($targetSubject)) {
        return ['state' => 'unavailable', 'kind' => null, 'owner_id' => max(0, $ownerNpcId), 'target' => $targetSubject, 'playthrough_id' => null, 'basis' => null, 'reason' => 'invalid_target', 'sources' => []];
    }
    try {
        $store ??= new PostgresStoreDb();
        $active = $store->activePlaythrough();
        if (!is_array($active) || !is_string($active['id'] ?? null) || trim($active['id']) === '') {
            return intimacyUnavailable($ownerNpcId, $targetSubject, null, 'scope_unavailable');
        }
        $identities = $store->npcIdentities();
        $owner = $store->npcById($ownerNpcId);
        return mindPoisoningReadIntimacyFromRow($ownerNpcId, $targetSubject, $owner, $active, $identities, $store);
    } catch (Throwable) {
        return intimacyUnavailable($ownerNpcId, $targetSubject, null, 'read_unavailable');
    }
}

function mindPoisoningReadIntimacyFromRow(
    int $ownerNpcId,
    string $targetSubject,
    mixed $owner,
    array $active,
    array $identities,
    StoreDb $store,
    ?array &$sourceRowCache = null
): array {
    $scope = $active['id'];
    $base = ['owner_id' => $ownerNpcId, 'target' => $targetSubject, 'playthrough_id' => $scope];
    if (
        !is_array($owner) || (int)($owner['id'] ?? 0) !== $ownerNpcId
        || !is_string($owner['npc_name'] ?? null) || trim($owner['npc_name']) === ''
        || !intimacyUniqueNpcIdentity($identities, $ownerNpcId, $owner['npc_name'])
    ) {
        return intimacyUnavailable($ownerNpcId, $targetSubject, $scope, 'owner_unavailable');
    }
    $targetName = null;
    if ($targetSubject === 'player') {
        $targetName = is_string($active['player_name'] ?? null) ? trim($active['player_name']) : '';
        if ($targetName === '') {
            return intimacyUnavailable($ownerNpcId, $targetSubject, $scope, 'target_unavailable');
        }
    } else {
        $targetId = (int)substr($targetSubject, 4);
        $target = $store->npcById($targetId);
        if (
            !is_array($target) || (int)($target['id'] ?? 0) !== $targetId
            || !is_string($target['npc_name'] ?? null) || trim($target['npc_name']) === ''
            || !intimacyUniqueNpcIdentity($identities, $targetId, $target['npc_name'])
        ) {
            return intimacyUnavailable($ownerNpcId, $targetSubject, $scope, 'target_unavailable');
        }
        $targetName = $target['npc_name'];
    }

    $plugins = $owner['plugin_extended_data'] ?? null;
    if (!$plugins instanceof \stdClass) {
        return intimacyUnavailable($ownerNpcId, $targetSubject, $scope, 'state_unavailable');
    }
    $ledger = storedLedgerNamespace($plugins);
    if ($ledger === null) {
        return intimacyUnavailable($ownerNpcId, $targetSubject, $scope, 'state_invalid');
    }
    if ($ledger === []) {
        return ['state' => 'none', 'kind' => null] + $base + ['basis' => null, 'reason' => 'arrangement_absent', 'sources' => []];
    }
    if ($ledger['playthrough_id'] !== $scope) {
        return intimacyUnavailable($ownerNpcId, $targetSubject, $scope, 'scope_mismatch');
    }
    $arrangements = $ledger['arrangements'] ?? [];
    $record = $arrangements[$targetSubject] ?? null;
    if ($record === null) {
        return ['state' => 'none', 'kind' => null] + $base + ['basis' => null, 'reason' => 'arrangement_absent', 'sources' => []];
    }
    $ownerChildStatus = mindPoisoningIntimacyChildStatus($store, $owner['npc_name'], $owner);
    $targetChildStatus = mindPoisoningIntimacyChildStatus(
        $store,
        (string)$targetName,
        $targetSubject === 'player' ? null : $target,
        $targetSubject !== 'player'
    );
    if ($ownerChildStatus !== false || $targetChildStatus !== false) {
        return intimacyUnavailable($ownerNpcId, $targetSubject, $scope,
            $ownerChildStatus === true || $targetChildStatus === true ? 'known_child' : 'child_data_unavailable');
    }
    if (
        $record['owner_id'] !== $ownerNpcId || $record['target'] !== $targetSubject
        || $record['playthrough_id'] !== $scope
        || !intimacySourcesCurrent($record, $owner['npc_name'], (string)$targetName, $active, $identities, $store, $sourceRowCache)
    ) {
        return intimacyUnavailable($ownerNpcId, $targetSubject, $scope, 'source_unavailable');
    }
    $state = match ($record['kind']) {
        'casual', 'romantic' => 'active',
        'none' => array_key_exists('prior_kind', $record) ? 'ended' : 'none',
        default => 'unavailable',
    };
    return [
        'state' => $state,
        'kind' => $state === 'active' ? $record['kind'] : null,
    ] + $base + [
        'basis' => $record['basis'],
        'reason' => match ($state) {
            'active' => 'arrangement_active',
            'ended' => 'arrangement_ended',
            'none' => 'arrangement_none',
            default => 'state_invalid',
        },
        'sources' => array_map(static fn(array $source): array => [
            'event_id' => $source['event_id'],
            'utterance_id' => $source['utterance_id'],
            'speech_hash' => $source['speech_hash'],
        ], $record['sources']),
    ];
}

function intimacyUniqueNpcIdentity(array $identities, int $npcId, string $name): bool
{
    $idMatches = 0;
    $nameMatches = 0;
    foreach ($identities as $identity) {
        if (!is_array($identity) || !is_int($identity['id'] ?? null) || !is_string($identity['npc_name'] ?? null)) {
            return false;
        }
        if ($identity['id'] === $npcId && sameActorName($identity['npc_name'], $name)) {
            $idMatches++;
        }
        if (sameActorName($identity['npc_name'], $name)) {
            $nameMatches++;
        }
    }
    return $idMatches === 1 && $nameMatches === 1;
}

function intimacySourcesCurrent(
    array $record,
    string $ownerName,
    string $targetName,
    array $active,
    array $identities,
    StoreDb $store,
    ?array &$sourceRowCache = null
): bool
{
    $lastGamets = null;
    foreach ($record['sources'] as $source) {
        $sourceKey = $source['event_id'] . ':' . $source['utterance_id'];
        if ($sourceRowCache !== null && array_key_exists($sourceKey, $sourceRowCache)) {
            $event = $sourceRowCache[$sourceKey];
        } else {
            $event = $store->eventById($source['event_id'], $source['utterance_id']);
            if ($sourceRowCache !== null) {
                $sourceRowCache[$sourceKey] = $event;
            }
        }
        if (
            !is_array($event) || (int)($event['event_id'] ?? 0) !== $source['event_id']
            || ($event['utterance_id'] ?? null) !== $source['utterance_id']
            || !is_string($event['source_data'] ?? null)
            || !in_array($event['delivery_state'] ?? null, ['emitted', 'spoken'], true)
            || (array_key_exists('speaker_kind', $event) && $event['speaker_kind'] !== 'npc')
        ) {
            return false;
        }
        $parts = $record['basis'] === 'registered_reflection'
            ? reflectionSourceParts($event['source_data'])
            : chatSourceParts($event['source_data']);
        if (
            !is_array($parts) || !sameActorName($parts['speaker'] ?? null, $ownerName)
            || !hash_equals($source['speech_hash'], hash('sha256', (string)($parts['text'] ?? '')))
        ) {
            return false;
        }
        if ($record['basis'] === 'direct_npc_dialogue') {
            $target = $parts['target'] ?? null;
            $targets = is_array($target) ? ($target['targets'] ?? null) : null;
            if (
                !is_array($target) || empty($target['hasExplicitTarget']) || !empty($target['isBroadcast'])
                || !is_array($targets) || count($targets) !== 1 || !is_string($targets[0] ?? null)
                || !intimacyDirectTargetMatches($targets[0], $targetName, $active, $identities)
            ) {
                return false;
            }
        }
        $gamets = $event['gamets'] ?? null;
        if (!is_int($gamets) && !is_float($gamets) && !(is_string($gamets) && is_numeric($gamets))) {
            return false;
        }
        $lastGamets = (float)$gamets;
    }
    return is_float($lastGamets) && $lastGamets === (float)$record['gamets'];
}

function intimacyDirectTargetMatches(string $sourceTarget, string $targetName, array $active, array $identities): bool
{
    $sourceTarget = trim($sourceTarget);
    if ($targetName !== trim((string)($active['player_name'] ?? ''))) {
        return sameActorName($sourceTarget, $targetName);
    }
    if (sameActorName($sourceTarget, 'Player')) {
        return true;
    }
    if (!function_exists(__NAMESPACE__ . '\\isPlayerName') || !isPlayerName($sourceTarget, $targetName)) {
        return false;
    }
    foreach ($identities as $identity) {
        if (!is_array($identity) || !is_string($identity['npc_name'] ?? null)) {
            return false;
        }
        if (sameActorName($identity['npc_name'], $sourceTarget)) {
            return false;
        }
    }
    return true;
}

function intimacyUnavailable(int $ownerId, string $target, ?string $scope, string $reason): array
{
    return ['state' => 'unavailable', 'kind' => null, 'owner_id' => $ownerId, 'target' => $target,
        'playthrough_id' => $scope, 'basis' => null, 'reason' => $reason, 'sources' => []];
}

function mindPoisoningIntimacyContext(string $actorName, ?StoreDb $store = null): string
{
    if (
        !mindPoisoningIntimacyRecognitionEnabled()
        || !function_exists(__NAMESPACE__ . '\\mindPoisoningPauseStatus')
    ) {
        return '';
    }
    $pauseReason = null;
    if (mindPoisoningPauseStatus($pauseReason) !== 'enabled') {
        return '';
    }
    try {
        $store ??= new PostgresStoreDb();
        $active = $store->activePlaythrough();
        $identities = $store->npcIdentities();
        if (!is_array($active) || !is_string($active['id'] ?? null)) {
            return '';
        }
        $owners = array_values(array_filter($identities, static fn(array $row): bool => sameActorName($row['npc_name'] ?? null, $actorName)));
        if (count($owners) !== 1 || !is_int($owners[0]['id'] ?? null)) {
            return '';
        }
        $ownerId = $owners[0]['id'];
        $owner = $store->npcById($ownerId);
        $plugins = is_array($owner) ? ($owner['plugin_extended_data'] ?? null) : null;
        $ledger = $plugins instanceof \stdClass ? storedLedgerNamespace($plugins) : null;
        if ($ledger === null || $ledger === [] || $ledger['playthrough_id'] !== $active['id']) {
            return '';
        }
        $arrangements = $ledger['arrangements'] ?? [];
        if ($arrangements === []) {
            return '';
        }
        uasort($arrangements, static fn(array $left, array $right): int =>
            ($right['sources'][array_key_last($right['sources'])]['event_id'] ?? 0)
            <=> ($left['sources'][array_key_last($left['sources'])]['event_id'] ?? 0));
        $lines = [];
        $sourceRowCache = [];
        foreach (array_slice($arrangements, 0, 8, true) as $target => $_record) {
            $projection = mindPoisoningReadIntimacyFromRow($ownerId, (string)$target, $owner, $active, $identities, $store, $sourceRowCache);
            if (!in_array($projection['state'] ?? null, ['active', 'ended', 'none'], true)) {
                continue;
            }
            $targetName = (string)$target;
            if ($target === 'player') {
                $targetName = (string)($active['player_name'] ?? 'Player');
            } else {
                $id = (int)substr((string)$target, 4);
                $targetRow = $store->npcById($id);
                $targetName = is_array($targetRow) && is_string($targetRow['npc_name'] ?? null)
                    ? $targetRow['npc_name']
                    : ('NPC #' . $id);
            }
            $quotedName = json_encode($targetName, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            $basis = ($projection['basis'] ?? null) === 'registered_reflection' ? 'private reflection' : 'self-report';
            $description = match ($projection['state']) {
                'active' => 'reports a ' . $projection['kind'] . ' intimacy arrangement with ' . $quotedName,
                'ended' => 'reports that a ' . ($_record['prior_kind'] ?? 'previous') . ' intimacy arrangement with ' . $quotedName . ' ended',
                default => 'reports no current intimacy arrangement with ' . $quotedName,
            };
            $lines[] = '- ' . $basis . ': ' . $description . '.';
        }
        if ($lines === []) {
            return '';
        }
        return "Private, actor-owned intimacy reports (not current willingness):\n"
            . implode("\n", $lines)
            . "\nThese reports do not establish reciprocity or exclusivity.";
    } catch (Throwable) {
        return '';
    }
}
