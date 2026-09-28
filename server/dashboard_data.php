<?php
declare(strict_types=1);

namespace ChimMindPoisoning;

use DateTimeImmutable;
use DateTimeZone;
use JsonException;
use ReflectionClass;
use RuntimeException;
use Throwable;

require_once __DIR__ . '/store.php';
require_once __DIR__ . '/influence.php';

const DASHBOARD_LOG_BYTES = 2097152;
const DASHBOARD_LOG_RECORDS = 1000;
const DASHBOARD_NPC_ROWS = 1000;
const DASHBOARD_LEDGER_ROWS = 100;
const DASHBOARD_LEDGER_BYTES = 65536;
const DASHBOARD_INTERACTIONS = 200;

function dashboardFilters(array $query): array
{
    $read = static function (string $key, string $default) use ($query): string {
        if (!array_key_exists($key, $query)) {
            return $default;
        }
        if (!is_string($query[$key])) {
            throw new \InvalidArgumentException('Invalid dashboard filter.');
        }
        return $query[$key];
    };

    $tab = $read('tab', 'interactions');
    $q = $read('q', '');
    $outcome = $read('outcome', '');
    $level = $read('level', '');
    if (!in_array($tab, ['interactions', 'diagnostics'], true)) {
        throw new \InvalidArgumentException('Invalid dashboard filter.');
    }
    if (
        strlen($q) > 480
        || preg_match('//u', $q) !== 1
        || preg_match('/[\x00-\x1F\x7F]/', $q) === 1
        || preg_match_all('/./us', $q, $unused) === false
        || count($unused[0]) > 120
        || !in_array($outcome, ['', 'committed', 'zero-change', 'unconfirmed', 'skipped', 'rejected', 'failed'], true)
        || !in_array($level, ['', 'debug', 'info', 'warning', 'error'], true)
    ) {
        throw new \InvalidArgumentException('Invalid dashboard filter.');
    }

    return ['tab' => $tab, 'q' => $q, 'outcome' => $outcome, 'level' => $level];
}

function dashboardLoad(string $serverRoot, array $filters): array
{
    $filters = dashboardFilters($filters);
    $manifest = json_decode((string)@file_get_contents(__DIR__ . '/manifest.json'), true);
    $version = is_array($manifest) && is_string($manifest['version'] ?? null) ? $manifest['version'] : 'unknown';
    $model = [
        'version' => $version,
        'generated_at' => gmdate('Y-m-d\TH:i:s\Z'),
        'notices' => [],
        'source' => ['logs' => 'unavailable', 'database' => 'unavailable', 'limited' => false],
        'interactions' => [],
        'records' => [],
    ];

    $logs = dashboardReadLogs($serverRoot);
    $model['source']['logs'] = $logs['available'] ? 'available' : 'unavailable';
    $model['source']['limited'] = $logs['limited'];
    if (!$logs['available']) {
        $model['notices'][] = 'The plugin log could not be read.';
    }
    $model['notices'][] = 'CHIM logging is best-effort; suppressed or fallback log writes may be missing.';

    $database = null;
    $interactionLimited = false;
    try {
        $database = dashboardReadDatabase($serverRoot);
        $model['source']['database'] = 'available';
        if ($database['limited']) {
            $model['source']['limited'] = true;
            $model['notices'][] = 'Some catalog, ledger, or relationship data exceeded the dashboard read limits.';
        }
        if ($database['active_playthrough'] === null) {
            $model['notices'][] = 'A single active playthrough could not be identified.';
        }
        if ($database['invalid_ledgers']) {
            $model['source']['limited'] = true;
            $model['notices'][] = 'Some saved interaction ledgers were invalid and were omitted.';
        }
        $model['interactions'] = dashboardBuildInteractions($database, $logs['records'], $interactionLimited);
    } catch (Throwable) {
        $model['notices'][] = 'The database source could not be read; retained log records are shown without active-profile verification.';
        $model['interactions'] = dashboardLogInteractions($logs['records'], null, false, $interactionLimited);
    }

    if (count($model['interactions']) > DASHBOARD_INTERACTIONS || $interactionLimited) {
        $model['interactions'] = array_slice($model['interactions'], 0, DASHBOARD_INTERACTIONS);
        $model['source']['limited'] = true;
        $model['notices'][] = 'Only the most recent 200 interactions are shown.';
    }
    $model['interactions'] = array_values(array_filter(
        $model['interactions'],
        static fn(array $interaction): bool => dashboardInteractionMatches($interaction, $filters)
    ));

    if ($logs['limited']) {
        $model['notices'][] = 'The plugin log tail or record list was capped; older records may be missing.';
    }
    $model['records'] = array_values(array_filter(
        $logs['records'],
        static fn(array $record): bool => dashboardRecordMatches($record, $filters)
    ));

    return $model;
}

/** @return array{available:bool,limited:bool,records:array} */
function dashboardReadLogs(string $serverRoot): array
{
    $root = realpath($serverRoot);
    if ($root === false) {
        return ['available' => false, 'limited' => false, 'records' => []];
    }
    $path = realpath($root . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . 'chim.log');
    if (
        $path === false
        || !str_starts_with($path, rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)
        || !is_file($path) || !is_readable($path)
    ) {
        return ['available' => false, 'limited' => false, 'records' => []];
    }

    $handle = @fopen($path, 'rb');
    if ($handle === false) {
        return ['available' => false, 'limited' => false, 'records' => []];
    }
    try {
        $stat = fstat($handle);
        if (!is_array($stat) || !is_int($stat['size'] ?? null) || $stat['size'] < 0) {
            return ['available' => false, 'limited' => false, 'records' => []];
        }
        $offset = max(0, $stat['size'] - DASHBOARD_LOG_BYTES);
        if (fseek($handle, $offset, SEEK_SET) !== 0) {
            return ['available' => false, 'limited' => false, 'records' => []];
        }
        $tail = '';
        while (strlen($tail) < DASHBOARD_LOG_BYTES && !feof($handle)) {
            $chunk = fread($handle, min(65536, DASHBOARD_LOG_BYTES - strlen($tail)));
            if ($chunk === false) {
                return ['available' => false, 'limited' => false, 'records' => []];
            }
            if ($chunk === '') {
                break;
            }
            $tail .= $chunk;
        }
    } finally {
        fclose($handle);
    }

    $limited = $offset > 0;
    $lines = explode("\n", $tail);
    if ($offset > 0) {
        array_shift($lines); // The first tail line may start in the middle of a shared log entry.
    }
    if ($tail !== '' && !str_ends_with($tail, "\n")) {
        array_pop($lines); // Ignore a log line that was still being written.
        $limited = true;
    }

    $records = [];
    foreach ($lines as $line) {
        $record = dashboardParseLogLine(rtrim($line, "\r"));
        if ($record !== null) {
            $records[] = $record;
        }
    }
    if (count($records) > DASHBOARD_LOG_RECORDS) {
        $records = array_slice($records, -DASHBOARD_LOG_RECORDS);
        $limited = true;
    }

    return ['available' => true, 'limited' => $limited, 'records' => array_reverse($records)];
}

function dashboardParseLogLine(string $line): ?array
{
    if (preg_match('/\A(?:\[[^\]\r\n]{1,64}\]\s*)?\[[^\]\r\n]{1,24}\]\s*(\{.*\})\z/D', $line, $matches) !== 1) {
        return null;
    }
    try {
        $record = json_decode($matches[1], false, 32, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        return null;
    }
    if (!$record instanceof \stdClass || ($record->plugin ?? null) !== 'mind_poisoning' || ($record->schema_version ?? null) !== 1) {
        return null;
    }

    $version = $record->version ?? null;
    $timestamp = dashboardTimestamp($record->timestamp ?? null);
    $requestId = $record->request_id ?? null;
    $level = $record->level ?? null;
    $event = $record->event ?? null;
    if (
        !is_string($version) || preg_match('/\A[0-9]+\.[0-9]+\.[0-9]+\z/D', $version) !== 1
        || $timestamp === null
        || !is_string($requestId) || preg_match('/\A(?:[a-f0-9]{24}|fallback-[1-9][0-9]*)\z/D', $requestId) !== 1
        || !in_array($level, ['debug', 'info', 'warning', 'error'], true)
        || !is_string($event) || preg_match('/\A[a-z][a-z0-9_]{0,47}\z/D', $event) !== 1
    ) {
        return null;
    }

    $clean = [
        'schema_version' => 1,
        'plugin' => 'mind_poisoning',
        'version' => $version,
        'timestamp' => $timestamp,
        'request_id' => $requestId,
        'level' => $level,
        'event' => $event,
    ];
    foreach (['event_id', 'playthrough_id', 'speaker_id', 'listener_id', 'connector_id'] as $field) {
        $id = dashboardId($record->{$field} ?? null);
        if ($id !== null) {
            $clean[$field] = $id;
        }
    }
    $utteranceId = $record->utterance_id ?? null;
    if (is_string($utteranceId) && preg_match('/\Autt_[A-Za-z0-9_-]{8,128}\z/D', $utteranceId) === 1) {
        $clean['utterance_id'] = $utteranceId;
    }
    foreach (['stage', 'outcome', 'reason', 'model_outcome', 'persistence_outcome', 'persistence_reason'] as $field) {
        $value = $record->{$field} ?? null;
        if (is_string($value) && preg_match('/\A[a-z][a-z0-9_.-]{0,63}\z/D', $value) === 1) {
            $clean[$field] = $value;
        }
    }
    $commitState = $record->commit_state ?? null;
    if (in_array($commitState, ['confirmed', 'unconfirmed', 'not_attempted'], true)) {
        $clean['commit_state'] = $commitState;
    }
    foreach (['elapsed_ms', 'model_ms', 'persistence_ms'] as $field) {
        $value = dashboardFiniteNumber($record->{$field} ?? null, 0, 86400000);
        if ($value !== null) {
            $clean[$field] = $value;
        }
    }
    foreach (['payload_bytes', 'subject_count', 'speech_bytes', 'changed_count'] as $field) {
        $value = $record->{$field} ?? null;
        $max = $field === 'subject_count' || $field === 'changed_count' ? 8 : 2147483647;
        if (is_int($value) && $value >= 0 && $value <= $max) {
            $clean[$field] = $value;
        }
    }
    if (is_int($record->delta ?? null) && $record->delta >= -5 && $record->delta <= 5) {
        $clean['delta'] = $record->delta;
    }
    $subject = dashboardSubject($record->subject ?? null);
    if ($subject !== null) {
        $clean['subject'] = $subject;
    }
    foreach (['committed', 'cleanup_failed'] as $field) {
        if (is_bool($record->{$field} ?? null)) {
            $clean[$field] = $record->{$field};
        }
    }
    $changes = dashboardLogChanges($record->changes ?? null);
    if ($changes !== null) {
        $clean['changes'] = $changes;
    }

    return $clean;
}

function dashboardTimestamp(mixed $value): ?string
{
    if (!is_string($value) || preg_match('/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z\z/D', $value) !== 1) {
        return null;
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', $value, new DateTimeZone('UTC'));
    $errors = DateTimeImmutable::getLastErrors();
    return $date !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
        ? $value
        : null;
}

function dashboardId(mixed $value): ?string
{
    if (is_int($value)) {
        $value = (string)$value;
    }
    return is_string($value) && preg_match('/\A[1-9][0-9]{0,18}\z/D', $value) === 1 ? $value : null;
}

function dashboardSubject(mixed $value): ?string
{
    if ($value === 'player') {
        return 'player';
    }
    return is_string($value) && preg_match('/\Anpc:[1-9][0-9]{0,18}\z/D', $value) === 1 ? $value : null;
}

function dashboardFiniteNumber(mixed $value, float $min, float $max): int|float|null
{
    if ((!is_int($value) && !is_float($value)) || !is_finite((float)$value) || $value < $min || $value > $max) {
        return null;
    }
    return $value;
}

function dashboardLogChanges(mixed $changes): ?array
{
    if (!is_array($changes) || !array_is_list($changes)) {
        return null;
    }
    $clean = [];
    foreach (array_slice($changes, 0, 8) as $change) {
        if (!$change instanceof \stdClass) {
            continue;
        }
        $subject = dashboardSubject($change->subject ?? null);
        $delta = $change->delta ?? null;
        $before = dashboardFiniteNumber($change->before ?? null, -PHP_FLOAT_MAX, PHP_FLOAT_MAX);
        $after = dashboardFiniteNumber($change->after ?? null, -100, 100);
        if ($subject !== null && is_int($delta) && $delta >= -5 && $delta <= 5 && $before !== null && $after !== null) {
            $clean[] = ['subject' => $subject, 'delta' => $delta, 'before' => $before, 'after' => $after];
        }
    }
    return $clean;
}

/** @return array{active_playthrough:?string,player_name:string,rows:array,limited:bool,invalid_ledgers:bool} */
function dashboardReadDatabase(string $serverRoot): array
{
    $root = realpath($serverRoot);
    if ($root === false || !function_exists('pg_connect') || !defined('PGSQL_CONNECT_FORCE_NEW')) {
        throw new RuntimeException('Database source unavailable.');
    }
    $classPath = realpath($root . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'postgresql.class.php');
    if ($classPath === false || !str_starts_with($classPath, rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)) {
        throw new RuntimeException('Database source unavailable.');
    }
    if (!class_exists('sql', false)) {
        require_once $classPath;
    }
    if (!class_exists('sql', false)) {
        throw new RuntimeException('Database source unavailable.');
    }
    $reflection = new ReflectionClass('sql');
    if ($reflection->getFileName() === false || realpath($reflection->getFileName()) !== $classPath || !$reflection->hasProperty('connString')) {
        throw new RuntimeException('Database source unavailable.');
    }
    $property = $reflection->getProperty('connString');
    $defaults = $reflection->getDefaultProperties();
    $dsn = $defaults['connString'] ?? null;
    if (!$property->isPrivate() || $property->isStatic() || !is_string($dsn) || $dsn === '' || strlen($dsn) > 4096 || str_contains($dsn, "\0")) {
        throw new RuntimeException('Database source unavailable.');
    }

    $connection = @pg_connect($dsn . ' connect_timeout=5', PGSQL_CONNECT_FORCE_NEW);
    if (!$connection instanceof \PgSql\Connection) {
        throw new RuntimeException('Database source unavailable.');
    }
    $inTransaction = false;
    try {
        $begin = @pg_query($connection, 'BEGIN ISOLATION LEVEL REPEATABLE READ READ ONLY');
        if ($begin === false) {
            throw new RuntimeException('Database source unavailable.');
        }
        pg_free_result($begin);
        $inTransaction = true;
        $timeout = @pg_query($connection, "SET LOCAL statement_timeout = '3000ms'");
        if ($timeout === false) {
            throw new RuntimeException('Database source unavailable.');
        }
        pg_free_result($timeout);
        $profiles = dashboardPgRows(
            $connection,
            "SELECT profile.id::text AS id,
                    (SELECT player.value FROM public.core_player player WHERE player.id = 'player_name' LIMIT 1) AS player_name
             FROM chim_meta.playthrough_profiles profile WHERE profile.is_active IS TRUE ORDER BY profile.id LIMIT 2"
        );
        $activeId = count($profiles) === 1 ? dashboardId($profiles[0]['id'] ?? null) : null;
        $playerName = count($profiles) === 1 ? dashboardLabel($profiles[0]['player_name'] ?? null, '') : '';
        $identityRows = dashboardPgRows(
            $connection,
            'SELECT id::text AS id, CASE WHEN char_length(npc_name) <= 160 THEN npc_name ELSE NULL END AS npc_name, (npc_name IS NOT NULL AND char_length(npc_name) > 160) AS name_limited FROM public.core_npc_master ORDER BY id LIMIT $1',
            [DASHBOARD_NPC_ROWS + 1]
        );
        $limited = count($identityRows) > DASHBOARD_NPC_ROWS;
        if ($limited) {
            $identityRows = array_slice($identityRows, 0, DASHBOARD_NPC_ROWS);
        }
        $identities = [];
        foreach ($identityRows as $identity) {
            $id = dashboardId($identity['id'] ?? null);
            if ($id === null) {
                continue;
            }
            $name = dashboardLabel($identity['npc_name'] ?? null, '');
            if ($name !== '') {
                $identities[$id] = $name;
            }
            $limited = $limited || ($identity['name_limited'] ?? null) === 't';
        }

        $rows = [];
        if ($activeId !== null) {
            $rows = dashboardPgRows(
                $connection,
                "SELECT id::text AS id,
                        CASE WHEN octet_length((extended_data->'relationships')::text) <= 16384 THEN (extended_data->'relationships')::text END AS relationships,
                        CASE WHEN octet_length((plugin_extended_data->'mind_poisoning')::text) <= $2 THEN (plugin_extended_data->'mind_poisoning')::text END AS mind_poisoning,
                        (octet_length((extended_data->'relationships')::text) > 16384) AS relationships_limited,
                        (octet_length((plugin_extended_data->'mind_poisoning')::text) > $2) AS ledger_limited,
                        (
                            jsonb_typeof(plugin_extended_data) IS NOT NULL
                            AND (
                                jsonb_typeof(plugin_extended_data) <> 'object'
                                OR (
                                    plugin_extended_data ? 'mind_poisoning'
                                    AND (
                                        jsonb_typeof(plugin_extended_data->'mind_poisoning') <> 'object'
                                        OR NULLIF(plugin_extended_data->'mind_poisoning'->>'playthrough_id', '') IS NULL
                                    )
                                )
                            )
                        ) AS namespace_invalid
                 FROM public.core_npc_master
                 WHERE jsonb_typeof(plugin_extended_data) IS NOT NULL
                   AND (
                       jsonb_typeof(plugin_extended_data) <> 'object'
                       OR (
                           plugin_extended_data ? 'mind_poisoning'
                           AND (
                               jsonb_typeof(plugin_extended_data->'mind_poisoning') <> 'object'
                               OR NULLIF(plugin_extended_data->'mind_poisoning'->>'playthrough_id', '') IS NULL
                               OR plugin_extended_data->'mind_poisoning'->>'playthrough_id' = $1
                           )
                       )
                   )
                 ORDER BY id LIMIT $3",
                [$activeId, DASHBOARD_LEDGER_BYTES, DASHBOARD_LEDGER_ROWS + 1]
            );
        }
        $limited = $limited || count($rows) > DASHBOARD_LEDGER_ROWS;
        if (count($rows) > DASHBOARD_LEDGER_ROWS) {
            $rows = array_slice($rows, 0, DASHBOARD_LEDGER_ROWS);
        }
        $invalidLedgers = false;
        foreach ($rows as &$row) {
            $row['id'] = dashboardId($row['id'] ?? null);
            $row['npc_name'] = $identities[$row['id'] ?? ''] ?? '';
            $relationships = dashboardJsonObject($row['relationships'] ?? null);
            if (($row['relationships_limited'] ?? null) === 't') {
                $relationships = null;
            }
            $row['extended_data'] = (object)['relationships' => $relationships];
            $row['plugin_extended_data'] = new \stdClass();
            $plugin = $row['plugin_extended_data'];
            $limited = $limited
                || ($row['relationships_limited'] ?? null) === 't'
                || ($row['ledger_limited'] ?? null) === 't';
            if (($row['namespace_invalid'] ?? null) === 't') {
                $invalidLedgers = true;
            }
            if (is_string($row['mind_poisoning'] ?? null)) {
                $ledgerNamespace = dashboardJsonObject($row['mind_poisoning']);
                $plugin->mind_poisoning = $ledgerNamespace;
                if ($ledgerNamespace === null || storedLedgerNamespace($plugin) === null) {
                    $invalidLedgers = true;
                }
            }
        }
        unset($row);

        return [
            'active_playthrough' => $activeId,
            'player_name' => $playerName,
            'identities' => $identities,
            'rows' => $rows,
            'limited' => $limited,
            'invalid_ledgers' => $invalidLedgers,
        ];
    } finally {
        if ($inTransaction) {
            @pg_query($connection, 'ROLLBACK');
        }
        @pg_close($connection);
    }
}

function dashboardPgRows(\PgSql\Connection $connection, string $sql, array $params = []): array
{
    $result = $params === [] ? @pg_query($connection, $sql) : @pg_query_params($connection, $sql, $params);
    if ($result === false) {
        throw new RuntimeException('Database source unavailable.');
    }
    try {
        $rows = [];
        while (($row = pg_fetch_assoc($result)) !== false) {
            $rows[] = $row;
        }
        return $rows;
    } finally {
        pg_free_result($result);
    }
}

function dashboardJsonObject(mixed $value): ?\stdClass
{
    if ($value === null || $value === '') {
        return new \stdClass();
    }
    if (!is_string($value)) {
        return null;
    }
    try {
        $decoded = json_decode($value, false, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        return null;
    }
    return $decoded instanceof \stdClass ? $decoded : null;
}

function dashboardLabel(mixed $value, string $fallback): string
{
    if (!is_string($value) || preg_match('//u', $value) !== 1) {
        return $fallback;
    }
    $value = trim($value);
    while (strlen($value) > 160) {
        $value = substr($value, 0, -1);
        while ($value !== '' && preg_match('//u', $value) !== 1) {
            $value = substr($value, 0, -1);
        }
    }
    return $value === '' ? $fallback : $value;
}

function dashboardBuildInteractions(array $database, array $records, bool &$limited): array
{
    $limited = false;
    $rows = $database['rows'] ?? [];
    $activeId = $database['active_playthrough'] ?? null;
    if (!is_array($rows) || !is_string($activeId)) {
        return dashboardLogInteractions($records, null, true, $limited);
    }
    $names = is_array($database['identities'] ?? null) ? $database['identities'] : [];
    $nameCounts = [];
    $listeners = [];
    foreach ($rows as $row) {
        if (is_array($row) && is_string($row['id'] ?? null)) {
            $listeners[$row['id']] = $row;
        }
    }
    foreach ($names as $name) {
        if (is_string($name)) {
            $nameCounts[$name] = ($nameCounts[$name] ?? 0) + 1;
        }
    }

    $recordIndex = dashboardIndexRecords($records);
    $candidates = [];
    $ledgerKeys = [];
    foreach ($rows as $row) {
        if (!is_array($row) || !is_string($row['id'] ?? null)) {
            continue;
        }
        $plugins = $row['plugin_extended_data'] ?? null;
        if (!$plugins instanceof \stdClass) {
            continue;
        }
        $ledger = storedLedgerNamespace($plugins);
        if ($ledger === null || $ledger === [] || $ledger['playthrough_id'] !== $activeId) {
            continue;
        }
        foreach ($ledger['events'] as $entry) {
            $eventId = $entry['event_id'] ?? null;
            $utteranceId = $entry['utterance_id'] ?? null;
            $judgments = $entry['judgments'] ?? null;
            if (
                !is_int($eventId) || $eventId < 1
                || !is_string($utteranceId) || preg_match('/\Autt_[A-Za-z0-9_-]{8,128}\z/D', $utteranceId) !== 1
                || !is_array($judgments) || !array_is_list($judgments) || count($judgments) > 8
            ) {
                continue;
            }
            $key = dashboardInteractionKey($activeId, (string)$eventId, $utteranceId, $row['id']);
            $ledgerKeys[$key] = true;
            dashboardKeepRecent($candidates, [
                'kind' => 'ledger', 'key' => $key, 'event_id' => (string)$eventId,
                'utterance_id' => $utteranceId, 'playthrough_id' => $activeId,
                'listener_id' => $row['id'], 'entry' => $entry, 'row' => $row,
                'attribution' => 'active',
            ], $limited);
        }
    }

    foreach ($records as $record) {
        if (!is_array($record) || ($record['event'] ?? null) !== 'request_finished') {
            continue;
        }
        $outcome = $record['outcome'] ?? null;
        if (!in_array($outcome, ['skipped', 'rejected', 'failed'], true)) {
            continue;
        }
        $playthroughId = dashboardId($record['playthrough_id'] ?? null);
        if ($playthroughId !== null && $playthroughId !== $activeId) {
            continue;
        }
        $eventId = dashboardId($record['event_id'] ?? null);
        $utteranceId = $record['utterance_id'] ?? null;
        $listenerId = dashboardId($record['listener_id'] ?? null);
        if ($playthroughId !== null && $eventId !== null && is_string($utteranceId) && $listenerId !== null) {
            $key = dashboardInteractionKey($playthroughId, $eventId, $utteranceId, $listenerId);
            if (isset($ledgerKeys[$key])) {
                continue;
            }
        } else {
            $key = null;
        }
        dashboardKeepRecent($candidates, [
            'kind' => 'log', 'key' => $key, 'event_id' => $eventId,
            'utterance_id' => is_string($utteranceId) ? $utteranceId : null,
            'playthrough_id' => $playthroughId, 'listener_id' => $listenerId,
            'record' => $record, 'attribution' => $playthroughId === null ? 'unattributed' : 'active',
        ], $limited);
    }

    usort($candidates, 'ChimMindPoisoning\\dashboardCandidateOrder');
    if (count($candidates) > DASHBOARD_INTERACTIONS) {
        $limited = true;
    }
    $interactions = [];
    foreach (array_slice($candidates, 0, DASHBOARD_INTERACTIONS) as $candidate) {
        if ($candidate['kind'] === 'log') {
            $interactions[] = dashboardLogInteraction(
                $candidate['record'], $candidate['playthrough_id'], $candidate['attribution'],
                $names, $listeners, $recordIndex, true, (string)($database['player_name'] ?? '')
            );
        } else {
            $interactions[] = dashboardLedgerInteraction($candidate, $recordIndex, $names, $listeners, $nameCounts, $database['player_name'] ?? '');
        }
    }
    return $interactions;
}

function dashboardInteractionKey(string $playthroughId, string $eventId, string $utteranceId, string $listenerId): string
{
    return implode("\x1f", [$playthroughId, $eventId, $utteranceId, $listenerId]);
}

function dashboardIndexRecords(array $records): array
{
    $index = [];
    foreach ($records as $record) {
        if (!is_array($record)) {
            continue;
        }
        $playthroughId = dashboardId($record['playthrough_id'] ?? null);
        $eventId = dashboardId($record['event_id'] ?? null);
        $utteranceId = $record['utterance_id'] ?? null;
        $listenerId = dashboardId($record['listener_id'] ?? null);
        $event = $record['event'] ?? null;
        if ($playthroughId === null || $eventId === null || !is_string($utteranceId) || $listenerId === null || !is_string($event)) {
            continue;
        }
        $key = dashboardInteractionKey($playthroughId, $eventId, $utteranceId, $listenerId);
        $index[$key][$event][] = $record;
    }
    return $index;
}

function dashboardKeepRecent(array &$candidates, array $candidate, bool &$limited): void
{
    $candidates[] = $candidate;
    if (count($candidates) > DASHBOARD_INTERACTIONS * 2) {
        usort($candidates, 'ChimMindPoisoning\\dashboardCandidateOrder');
        $candidates = array_slice($candidates, 0, DASHBOARD_INTERACTIONS);
        $limited = true;
    }
}

function dashboardCandidateOrder(array $left, array $right): int
{
    $leftId = $left['event_id'] ?? null;
    $rightId = $right['event_id'] ?? null;
    if (is_string($leftId) && is_string($rightId)) {
        $length = strlen($rightId) <=> strlen($leftId);
        if ($length !== 0) {
            return $length;
        }
        $order = strcmp($rightId, $leftId);
        if ($order !== 0) {
            return $order;
        }
    } elseif (is_string($leftId) !== is_string($rightId)) {
        return is_string($leftId) ? -1 : 1;
    }
    return strcmp((string)($right['record']['timestamp'] ?? ''), (string)($left['record']['timestamp'] ?? ''));
}

function dashboardLedgerInteraction(array $candidate, array $recordIndex, array $names, array $listeners, array $nameCounts, string $playerName): array
{
    $group = $recordIndex[$candidate['key']] ?? [];
    [$persistence, $commitState] = dashboardPersistenceForGroup($group);
    $request = dashboardRequestForGroup($group, $persistence);
    $entry = $candidate['entry'];
    $row = $candidate['row'];
    $changes = [];
    $allZero = true;
    foreach ($entry['judgments'] as $judgment) {
        if (!is_array($judgment)) {
            continue;
        }
        $subject = dashboardSubject($judgment['subject'] ?? null);
        $delta = $judgment['delta'] ?? null;
        if ($subject === null || !is_int($delta) || $delta < -5 || $delta > 5) {
            continue;
        }
        $allZero = $allZero && $delta === 0;
        $subjectId = $subject === 'player' ? null : substr($subject, 4);
        $label = $subject === 'player'
            ? dashboardLabel($playerName, 'Player')
            : ($names[$subjectId] ?? 'NPC #' . $subjectId);
        $loggedChange = $commitState === 'confirmed' ? dashboardMatchedChange($persistence, $subject, $delta) : null;
        $before = $loggedChange['before'] ?? null;
        $after = $loggedChange['after'] ?? null;
        $confirmed = $loggedChange !== null && $before !== null && $after !== null;
        $listener = $listeners[$candidate['listener_id']] ?? $row;
        $current = $subject !== 'player' && !isset($names[$subjectId])
            ? ['value' => null, 'state' => 'unavailable']
            : dashboardCurrent($listener, $subject, $label, $playerName, $nameCounts);
        $changes[] = [
            'subject' => $subject,
            'label' => $label,
            'proposed' => $delta,
            'before' => $confirmed ? $before : null,
            'after' => $confirmed ? $after : null,
            'applied' => $delta === 0 ? 0 : ($confirmed ? $after - $before : null),
            'current' => $current['value'],
            'current_state' => $current['state'],
        ];
    }
    $outcome = is_string($request['outcome'] ?? null) ? $request['outcome'] : 'committed';
    if ($commitState === 'unconfirmed') {
        $outcome = 'unconfirmed';
    } elseif ($commitState === 'confirmed' && ($request['outcome'] ?? null) !== 'failed' && ($persistence['changed_count'] ?? null) === 0) {
        $outcome = 'zero-change';
    } elseif ($request === null && $commitState === 'none' && $allZero) {
        $outcome = 'zero-change';
    }

    $speakerId = dashboardId($request['speaker_id'] ?? $persistence['speaker_id'] ?? null);
    return [
        'request_id' => $request['request_id'] ?? $persistence['request_id'] ?? null,
        'event_id' => $candidate['event_id'],
        'utterance_id' => $candidate['utterance_id'],
        'playthrough_id' => $candidate['playthrough_id'],
        'attribution' => 'active',
        'timestamp' => $request['timestamp'] ?? $persistence['timestamp'] ?? null,
        'speaker' => $speakerId === null ? null : ($names[$speakerId] ?? 'NPC #' . $speakerId),
        'listener' => $names[$candidate['listener_id']] ?? 'NPC #' . $candidate['listener_id'],
        'outcome' => $outcome,
        'reason' => $request['reason'] ?? $persistence['persistence_reason'] ?? 'ledger_recorded',
        'model_ms' => $request['model_ms'] ?? null,
        'persistence_ms' => $request['persistence_ms'] ?? $persistence['persistence_ms'] ?? null,
        'changes' => $changes,
    ];
}

function dashboardPersistenceForGroup(array $group): array
{
    $confirmed = [];
    $unconfirmed = [];
    foreach ($group['persistence_finished'] ?? [] as $record) {
        if (($record['commit_state'] ?? null) === 'confirmed' && ($record['committed'] ?? null) === true) {
            $confirmed[] = $record;
        } elseif (($record['commit_state'] ?? null) === 'unconfirmed') {
            $unconfirmed[] = $record;
        }
    }
    if (count($confirmed) === 1) {
        return [$confirmed[0], 'confirmed'];
    }
    if ($confirmed !== []) {
        return [null, 'ambiguous'];
    }
    if (count($unconfirmed) === 1) {
        return [$unconfirmed[0], 'unconfirmed'];
    }
    return [null, $unconfirmed === [] ? 'none' : 'ambiguous'];
}

function dashboardRequestForGroup(array $group, ?array $persistence): ?array
{
    $requests = $group['request_finished'] ?? [];
    if ($persistence !== null) {
        $requests = array_values(array_filter(
            $requests,
            static fn(array $record): bool => ($record['request_id'] ?? null) === ($persistence['request_id'] ?? null)
        ));
    } else {
        $requests = array_values(array_filter($requests, static fn(array $record): bool => ($record['outcome'] ?? null) === 'committed'));
    }
    return count($requests) === 1 ? $requests[0] : null;
}

function dashboardMatchedChange(?array $persistence, string $subject, int $delta): ?array
{
    if (!is_array($persistence) || !is_array($persistence['changes'] ?? null)) {
        return null;
    }
    $matches = array_values(array_filter(
        $persistence['changes'],
        static fn(array $change): bool => ($change['subject'] ?? null) === $subject && ($change['delta'] ?? null) === $delta
    ));
    return count($matches) === 1 ? $matches[0] : null;
}

function dashboardCurrent(array $listener, string $subject, string $subjectName, string $playerName, array $nameCounts): array
{
    $extended = $listener['extended_data'] ?? null;
    if (!$extended instanceof \stdClass) {
        return ['value' => null, 'state' => 'invalid'];
    }
    $relationships = property_exists($extended, 'relationships') ? $extended->relationships : new \stdClass();
    if (!$relationships instanceof \stdClass) {
        return ['value' => null, 'state' => 'invalid'];
    }
    if ($subject === 'player') {
        try {
            $key = playerRelationshipKey($relationships, $playerName);
        } catch (Throwable) {
            return ['value' => null, 'state' => 'ambiguous'];
        }
    } else {
        if (($nameCounts[$subjectName] ?? 0) > 1) {
            return ['value' => null, 'state' => 'ambiguous'];
        }
        $key = $subjectName;
        if (!property_exists($relationships, $key)) {
            return ['value' => null, 'state' => 'unset_default_zero'];
        }
    }
    if ($key === null) {
        return ['value' => null, 'state' => 'unset_default_zero'];
    }
    $edge = $relationships->{$key} ?? null;
    if (!$edge instanceof \stdClass) {
        return ['value' => null, 'state' => 'invalid'];
    }
    $affinity = $edge->aff ?? 0;
    if (!is_int($affinity) && !is_float($affinity) && !(is_string($affinity) && is_numeric($affinity))) {
        return ['value' => null, 'state' => 'invalid'];
    }
    $value = (float)$affinity;
    if (!is_finite($value)) {
        return ['value' => null, 'state' => 'invalid'];
    }
    return ['value' => floor($value) === $value ? (int)$value : $value, 'state' => 'set'];
}

function dashboardLogInteractions(array $records, ?string $activeId, bool $databaseAvailable, bool &$limited): array
{
    $limited = false;
    $index = dashboardIndexRecords($records);
    $interactions = [];
    foreach ($records as $record) {
        if (!is_array($record) || ($record['event'] ?? null) !== 'request_finished') {
            continue;
        }
        $playthroughId = dashboardId($record['playthrough_id'] ?? null);
        $outcome = $record['outcome'] ?? null;
        if ($databaseAvailable) {
            if ($activeId !== null && $playthroughId !== null && $playthroughId !== $activeId) {
                continue;
            }
            if (!in_array($outcome, ['skipped', 'rejected', 'failed'], true)) {
                continue;
            }
        }
        $attribution = $databaseAvailable
            ? ($playthroughId === null ? 'unattributed' : ($activeId === null ? 'unverified' : 'active'))
            : ($playthroughId === null ? 'unattributed' : 'unverified');
        $interactions[] = dashboardLogInteraction($record, $playthroughId, $attribution, [], [], $index, $databaseAvailable, '');
    }
    usort($interactions, 'ChimMindPoisoning\\dashboardCandidateOrder');
    if (count($interactions) > DASHBOARD_INTERACTIONS) {
        $limited = true;
    }
    return array_slice($interactions, 0, DASHBOARD_INTERACTIONS);
}

function dashboardLogInteraction(array $record, ?string $playthroughId, string $attribution, array $names, array $listeners, array $recordIndex, bool $databaseAvailable, string $playerName): array
{
    $speakerId = dashboardId($record['speaker_id'] ?? null);
    $listenerId = dashboardId($record['listener_id'] ?? null);
    $eventId = dashboardId($record['event_id'] ?? null);
    $utteranceId = is_string($record['utterance_id'] ?? null) ? $record['utterance_id'] : null;
    $key = $playthroughId !== null && $eventId !== null && $utteranceId !== null && $listenerId !== null
        ? dashboardInteractionKey($playthroughId, $eventId, $utteranceId, $listenerId)
        : null;
    $group = $key === null ? [] : ($recordIndex[$key] ?? []);
    $group = array_map(
        static fn(array $items): array => array_values(array_filter(
            $items,
            static fn(array $item): bool => ($item['request_id'] ?? null) === ($record['request_id'] ?? null)
        )),
        $group
    );
    [$persistence, $commitState] = dashboardPersistenceForGroup($group);
    if ($persistence !== null && ($persistence['request_id'] ?? null) !== ($record['request_id'] ?? null)) {
        $persistence = null;
        $commitState = 'none';
    }
    $outcome = is_string($record['outcome'] ?? null) ? $record['outcome'] : 'unknown';
    if ($commitState === 'unconfirmed') {
        $outcome = 'unconfirmed';
    } elseif ($commitState === 'confirmed' && $outcome === 'committed' && ($persistence['changed_count'] ?? null) === 0) {
        $outcome = 'zero-change';
    }
    $changes = [];
    $nameCounts = [];
    foreach ($names as $name) {
        if (is_string($name)) {
            $nameCounts[$name] = ($nameCounts[$name] ?? 0) + 1;
        }
    }
    if ($commitState === 'confirmed' && is_array($persistence['changes'] ?? null)) {
        foreach ($persistence['changes'] as $change) {
            $subject = $change['subject'] ?? null;
            if (!is_string($subject)) {
                continue;
            }
            $subjectId = $subject === 'player' ? null : substr($subject, 4);
            $label = $subject === 'player' ? dashboardLabel($playerName, 'Player') : ($names[$subjectId] ?? 'NPC #' . $subjectId);
            $subjectKnown = $subject === 'player' ? $playerName !== '' : isset($names[$subjectId]);
            $current = $databaseAvailable && isset($listeners[$listenerId]) && $subjectKnown
                ? dashboardCurrent($listeners[$listenerId], $subject, $label, $playerName, $nameCounts)
                : ['value' => null, 'state' => 'unavailable'];
            $changes[] = [
                'subject' => $subject,
                'label' => $label,
                'proposed' => $change['delta'],
                'before' => $change['before'],
                'after' => $change['after'],
                'applied' => $change['after'] - $change['before'],
                'current' => $current['value'],
                'current_state' => $current['state'],
            ];
        }
    }
    return [
        'request_id' => $record['request_id'] ?? null,
        'event_id' => $eventId,
        'utterance_id' => $utteranceId,
        'playthrough_id' => $playthroughId,
        'attribution' => $attribution,
        'timestamp' => $record['timestamp'] ?? null,
        'speaker' => $speakerId === null ? null : ($names[$speakerId] ?? 'NPC #' . $speakerId),
        'listener' => $listenerId === null ? null : ($names[$listenerId] ?? 'NPC #' . $listenerId),
        'outcome' => $outcome,
        'reason' => $record['reason'] ?? $persistence['persistence_reason'] ?? null,
        'model_ms' => $record['model_ms'] ?? null,
        'persistence_ms' => $record['persistence_ms'] ?? $persistence['persistence_ms'] ?? null,
        'changes' => $changes,
    ];
}

function dashboardRecordMatches(array $record, array $filters): bool
{
    if ($filters['level'] !== '' && ($record['level'] ?? null) !== $filters['level']) {
        return false;
    }
    $recordOutcome = dashboardNormalizedOutcome($record);
    if ($filters['outcome'] !== '' && $recordOutcome !== $filters['outcome']) {
        return false;
    }
    return dashboardContains($record, $filters['q']);
}

function dashboardNormalizedOutcome(array $record): ?string
{
    if (($record['commit_state'] ?? null) === 'unconfirmed') {
        return 'unconfirmed';
    }
    if (
        ($record['commit_state'] ?? null) === 'confirmed'
        && ($record['committed'] ?? null) === true
        && ($record['changed_count'] ?? null) === 0
        && ($record['outcome'] ?? $record['persistence_outcome'] ?? null) === 'committed'
    ) {
        return 'zero-change';
    }
    if (($record['persistence_reason'] ?? null) === 'zero-change' || ($record['reason'] ?? null) === 'zero-change') {
        return 'zero-change';
    }
    $outcome = $record['outcome'] ?? $record['model_outcome'] ?? $record['persistence_outcome'] ?? null;
    return is_string($outcome) ? $outcome : null;
}

function dashboardInteractionMatches(array $interaction, array $filters): bool
{
    if ($filters['outcome'] !== '' && ($interaction['outcome'] ?? null) !== $filters['outcome']) {
        return false;
    }
    return dashboardContains($interaction, $filters['q']);
}

function dashboardContains(array $value, string $query): bool
{
    if ($query === '') {
        return true;
    }
    $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($encoded)) {
        return false;
    }
    return mb_stripos($encoded, $query, 0, 'UTF-8') !== false;
}
