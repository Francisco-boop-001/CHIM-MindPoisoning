<?php
declare(strict_types=1);

namespace ChimMindPoisoning;

require_once __DIR__ . '/../server/dashboard_data.php';

function d02Assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new \RuntimeException($message);
    }
}

function d02Query(\PgSql\Connection $connection, string $query, array $params = []): array
{
    $result = $params === [] ? pg_query($connection, $query) : pg_query_params($connection, $query, $params);
    d02Assert($result instanceof \PgSql\Result, 'Synthetic fixture SQL failed.');
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

function d02InsertLedger(\PgSql\Connection $connection, int $id, string $playthrough, string $eventsJson, bool $raw = false): void
{
    $ledgerJson = $raw
        ? $eventsJson
        : json_encode([
            'mind_poisoning' => [
                'playthrough_id' => $playthrough,
                'floor_event_id' => 0,
                'events' => json_decode($eventsJson, true, 512, JSON_THROW_ON_ERROR),
            ],
        ], JSON_THROW_ON_ERROR);
    $result = pg_query_params(
        $connection,
        'INSERT INTO public.core_npc_master (id, npc_name, extended_data, plugin_extended_data) VALUES ($1, $2, $3::jsonb, $4::jsonb)',
        [$id, 'NPC ' . $id, '{"relationships":{}}', $ledgerJson]
    );
    d02Assert($result instanceof \PgSql\Result, 'Could not seed a synthetic ledger row.');
    pg_free_result($result);
}

$socket = getenv('D02_TEST_PG_SOCKET') ?: '';
if ($socket === '') {
    echo "dashboard_recency_test: skipped (isolated PostgreSQL socket not configured)\n";
    return;
}
d02Assert(
    preg_match('~\A/tmp/(mp-d02-recency-[A-Za-z0-9_-]+)/socket\z~D', $socket, $match) === 1,
    'Refusing a PostgreSQL socket outside the D02 scratch path.'
);
d02Assert(function_exists('pg_connect') && defined('PGSQL_CONNECT_FORCE_NEW'), 'The PostgreSQL PHP extension is required.');
d02Assert(!class_exists('sql', false), 'Run this focused fixture in a fresh PHP process.');

$scratchRoot = '/tmp/' . $match[1];
$connection = @pg_connect('host=' . $socket . ' port=5432 dbname=postgres user=postgres connect_timeout=3', PGSQL_CONNECT_FORCE_NEW);
d02Assert($connection instanceof \PgSql\Connection, 'Could not connect to the isolated fixture server.');
$dataDirectory = d02Query($connection, "SELECT current_setting('data_directory') AS path")[0]['path'] ?? '';
d02Assert(realpath($dataDirectory) === realpath($scratchRoot . '/data'), 'Refusing a PostgreSQL data directory outside the D02 scratch cluster.');
d02Assert(pg_host($connection) === $socket, 'Refusing a non-socket PostgreSQL connection.');
$existing = d02Query($connection, "SELECT to_regclass('chim_meta.playthrough_profiles') AS profile, to_regclass('public.core_npc_master') AS npc")[0] ?? [];
d02Assert(($existing['profile'] ?? null) === null && ($existing['npc'] ?? null) === null, 'The isolated fixture cluster already contains project tables.');

$sourceRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mp-d02-recency-source-' . getmypid();
$libraryPath = $sourceRoot . DIRECTORY_SEPARATOR . 'lib';
d02Assert(mkdir($libraryPath, 0700, true), 'Could not create the synthetic server shim directory.');
$shim = "<?php class sql { private \$connString = " . var_export(
    'host=' . $socket . ' port=5432 dbname=postgres user=postgres connect_timeout=3',
    true
) . "; }\n";
d02Assert(file_put_contents($libraryPath . DIRECTORY_SEPARATOR . 'postgresql.class.php', $shim) !== false, 'Could not write the synthetic connection shim.');

try {
    d02Query($connection, 'CREATE SCHEMA chim_meta');
    d02Query($connection, 'CREATE TABLE chim_meta.playthrough_profiles (id bigint PRIMARY KEY, is_active boolean NOT NULL)');
    d02Query($connection, 'CREATE TABLE public.core_player (id text PRIMARY KEY, value text)');
    d02Query($connection, 'CREATE TABLE public.core_npc_master (id bigint PRIMARY KEY, npc_name text, extended_data jsonb, plugin_extended_data jsonb)');
    d02Query($connection, "INSERT INTO chim_meta.playthrough_profiles (id, is_active) VALUES (1, true), (2, false)");
    d02Query($connection, "INSERT INTO public.core_player (id, value) VALUES ('player_name', 'Test Player')");
    d02Query(
        $connection,
        "INSERT INTO public.core_npc_master (id, npc_name, extended_data, plugin_extended_data)
         SELECT id, 'Catalog ' || id, '{}'::jsonb, '{}'::jsonb
         FROM generate_series(300, 1299) AS ids(id)"
    );

    for ($eventId = 1; $eventId <= 105; $eventId++) {
        $entry = [[
            'event_id' => $eventId,
            'utterance_id' => 'utt_' . str_pad((string)$eventId, 8, '0', STR_PAD_LEFT),
            'judgments' => [],
        ]];
        d02InsertLedger($connection, 100 + $eventId - 1, '1', json_encode($entry, JSON_THROW_ON_ERROR));
    }
    foreach ([2 => 20000, 10 => 20000, 2000 => 30000] as $id => $eventId) {
        $entry = [[
            'event_id' => $eventId,
            'utterance_id' => 'utt_' . str_pad((string)$eventId, 8, '0', STR_PAD_LEFT),
            'judgments' => [],
        ]];
        d02InsertLedger($connection, $id, '1', json_encode($entry, JSON_THROW_ON_ERROR));
    }
    $decoy = [[
        'event_id' => 999999,
        'utterance_id' => 'utt_decoy000',
        'judgments' => [],
    ]];
    d02InsertLedger($connection, 2500, '2', json_encode($decoy, JSON_THROW_ON_ERROR));

    $selected = dashboardReadDatabase($sourceRoot);
    $selectedIds = array_column($selected['rows'], 'id');
    d02Assert(count($selectedIds) === 100 && $selected['limited'], 'The >100 ledger cap was not surfaced.');
    d02Assert(array_slice($selectedIds, 0, 3) === ['2000', '2', '10'], 'Recent high-ID selection or numeric tie ordering failed.');
    d02Assert(in_array('2000', $selectedIds, true), 'The newest high-ID listener was lost to the row cap.');
    d02Assert(!in_array('100', $selectedIds, true), 'The oldest listener was not removed by recency selection.');
    d02Assert(!in_array('2500', $selectedIds, true), 'A newer event from an inactive playthrough influenced the selection.');
    d02Assert(!array_key_exists('2000', $selected['identities']), 'The identity cap fixture unexpectedly included the high-ID listener.');
    $highIdRow = $selected['rows'][0];
    d02Assert($highIdRow['id'] === '2000' && $highIdRow['npc_name'] === '', 'The high-ID listener did not retain its honest fallback identity.');

    d02Query($connection, "DELETE FROM public.core_npc_master WHERE plugin_extended_data ? 'mind_poisoning'");
    d02InsertLedger($connection, 10, '1', json_encode([['event_id' => 7, 'utterance_id' => 'utt_valid0007', 'judgments' => []]], JSON_THROW_ON_ERROR));
    d02InsertLedger($connection, 2, '1', json_encode([['event_id' => 3, 'utterance_id' => 'utt_valid0003', 'judgments' => []]], JSON_THROW_ON_ERROR));
    d02InsertLedger($connection, 3, '1', json_encode([['event_id' => '999999', 'utterance_id' => 'utt_stringid', 'judgments' => []]], JSON_THROW_ON_ERROR));
    d02InsertLedger($connection, 4, '1', '{"mind_poisoning":{"playthrough_id":"1","floor_event_id":0,"events":[{"event_id":9223372036854775808,"utterance_id":"utt_overflow"}]}}', true);
    $tooMany = [];
    for ($eventId = 1; $eventId <= 129; $eventId++) {
        $tooMany[] = ['event_id' => 100000 + $eventId, 'utterance_id' => 'utt_' . str_pad((string)$eventId, 8, '0', STR_PAD_LEFT), 'judgments' => []];
    }
    d02InsertLedger($connection, 5, '1', json_encode($tooMany, JSON_THROW_ON_ERROR));
    d02InsertLedger($connection, 6, '1', json_encode([['event_id' => 10000, 'judgments' => []]], JSON_THROW_ON_ERROR));
    d02InsertLedger($connection, 7, '1', '[]');

    $malformed = dashboardReadDatabase($sourceRoot);
    d02Assert(array_column($malformed['rows'], 'id') === ['10', '2', '3', '4', '5', '6', '7'], 'Invalid, oversized, and empty ledgers did not sort after valid activity with stable numeric ties.');
    d02Assert($malformed['invalid_ledgers'], 'Malformed fixture ledgers were not reported.');
    echo "dashboard_recency_test: actual PostgreSQL selection passed (recent row cap, active profile, numeric ties, invalid event IDs, oversized event array, identity cap)\n";
} finally {
    pg_close($connection);
    @unlink($libraryPath . DIRECTORY_SEPARATOR . 'postgresql.class.php');
    @rmdir($libraryPath);
    @rmdir($sourceRoot);
}
