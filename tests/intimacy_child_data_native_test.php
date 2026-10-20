<?php
declare(strict_types=1);

require_once __DIR__ . '/../server/store.php';

function mpChildRequire(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function mpChildQuery(PgSql\Connection $connection, string $sql, array $parameters = []): PgSql\Result
{
    $result = $parameters === []
        ? @pg_query($connection, $sql)
        : @pg_query_params($connection, $sql, $parameters);
    mpChildRequire($result instanceof PgSql\Result, 'native-query-failed');
    return $result;
}

function mpChildOne(PgSql\Connection $connection, string $sql, array $parameters = []): array
{
    $result = mpChildQuery($connection, $sql, $parameters);
    try {
        $row = pg_fetch_assoc($result);
        mpChildRequire(is_array($row), 'native-row-missing');
        return $row;
    } finally {
        pg_free_result($result);
    }
}

function mpChildExec(PgSql\Connection $connection, string $sql, array $parameters = []): void
{
    $result = mpChildQuery($connection, $sql, $parameters);
    pg_free_result($result);
}

function mpChildConfig(): array
{
    mpChildRequire(PHP_SAPI === 'cli' && PHP_OS_FAMILY === 'Linux', 'linux-cli-required');
    mpChildRequire(extension_loaded('pgsql'), 'pgsql-extension-required');
    $values = [];
    foreach ([
        'data' => 'MP_WRITTEN_TEST_DATA_DIRECTORY',
        'socket' => 'MP_WRITTEN_TEST_SOCKET_DIRECTORY',
        'marker' => 'MP_WRITTEN_TEST_MARKER',
        'system_identifier' => 'MP_WRITTEN_TEST_SYSTEM_IDENTIFIER',
        'port' => 'MP_WRITTEN_TEST_PORT',
        'database' => 'MP_WRITTEN_TEST_DATABASE',
    ] as $key => $name) {
        $value = getenv($name);
        mpChildRequire(is_string($value) && $value !== '', 'fixture-environment-required');
        $values[$key] = $value;
    }
    mpChildRequire(preg_match('~\A(/tmp/(mp-audit-fixes-[A-Za-z0-9_-]{8,32})/postgres)/data\z~D', $values['data'], $matches) === 1, 'scratch-data-path-invalid');
    mpChildRequire($values['socket'] === $matches[1] . '/socket', 'scratch-socket-path-invalid');
    mpChildRequire($values['marker'] === $matches[2], 'scratch-marker-invalid');
    mpChildRequire(preg_match('/\A[0-9]{15,20}\z/D', $values['system_identifier']) === 1, 'scratch-system-identifier-invalid');
    mpChildRequire(preg_match('/\A5[0-9]{4}\z/D', $values['port']) === 1, 'scratch-port-invalid');
    mpChildRequire(preg_match('/\Ampaudit(?:_[a-z0-9_]{1,24})?\z/D', $values['database']) === 1, 'scratch-database-name-invalid');
    mpChildRequire(is_dir($values['data']) && !is_link($values['data']) && realpath($values['data']) === $values['data'], 'scratch-data-directory-invalid');
    mpChildRequire(is_dir($values['socket']) && !is_link($values['socket']) && realpath($values['socket']) === $values['socket'], 'scratch-socket-directory-invalid');
    foreach (getenv() as $name => $_value) {
        mpChildRequire(!str_starts_with(strtoupper((string)$name), 'PG'), 'postgres-environment-forbidden');
    }
    return $values;
}

$config = null;
$connection = null;
$schema = 'mp_audit_fixes_child_fixture';
$schemaCreated = false;
$failure = null;
$checks = 0;
try {
    $config = mpChildConfig();
    $dsn = 'host=' . $config['socket'] . ' dbname=' . $config['database'] . ' user=postgres port=' . $config['port']
        . ' sslmode=disable connect_timeout=3 application_name=mp-audit-fixes-child-native';
    $connection = @pg_connect($dsn, PGSQL_CONNECT_FORCE_NEW);
    mpChildRequire($connection instanceof PgSql\Connection, 'postgres-connect-failed');
    mpChildRequire(pg_connection_status($connection) === PGSQL_CONNECTION_OK, 'postgres-connection-unhealthy');
    $identity = mpChildOne($connection, "SELECT current_database() AS database_name,
        current_user AS user_name,
        current_setting('data_directory') AS data_directory,
        current_setting('unix_socket_directories') AS socket_directories,
        current_setting('port') AS port,
        current_setting('server_version_num') AS server_version_num,
        current_setting('mind_poisoning.disposable_marker', true) AS disposable_marker,
        inet_server_addr() IS NULL AS unix_only,
        (SELECT system_identifier::text FROM pg_control_system()) AS system_identifier");
    mpChildRequire(($identity['database_name'] ?? null) === $config['database'], 'database-identity-mismatch');
    mpChildRequire(($identity['user_name'] ?? null) === 'postgres', 'database-user-mismatch');
    mpChildRequire(($identity['data_directory'] ?? null) === $config['data'], 'database-data-directory-mismatch');
    mpChildRequire(($identity['socket_directories'] ?? null) === $config['socket'], 'database-socket-directory-mismatch');
    mpChildRequire(($identity['port'] ?? null) === $config['port'] && ($identity['unix_only'] ?? null) === 't', 'database-listener-mismatch');
    mpChildRequire(str_starts_with((string)($identity['server_version_num'] ?? ''), '15'), 'postgres-version-mismatch');
    mpChildRequire(($identity['disposable_marker'] ?? null) === $config['marker'], 'database-disposable-marker-mismatch');
    mpChildRequire(($identity['system_identifier'] ?? null) === $config['system_identifier'], 'database-system-identifier-mismatch');

    $schemaExists = mpChildOne($connection, 'SELECT EXISTS (SELECT 1 FROM pg_namespace WHERE nspname = $1) AS present', [$schema]);
    mpChildRequire(($schemaExists['present'] ?? null) === 'f', 'fixture-schema-already-exists');
    mpChildExec($connection, 'CREATE SCHEMA ' . $schema);
    $schemaCreated = true;
    mpChildExec($connection, "COMMENT ON SCHEMA {$schema} IS '" . $config['marker'] . "'");
    mpChildExec($connection, 'SET search_path TO ' . $schema . ', public');

    $store = new ChimMindPoisoning\PostgresStoreDb(null, $connection);
    mpChildRequire(method_exists($store, 'intimacyChildDataByName'), 'child-reader-method-missing');
    mpChildRequire($store->intimacyChildDataByName('Missing Table') === [], 'missing-optional-table-not-empty');
    $missingTable = mpChildOne($connection, "SELECT to_regclass('nsfw_npc_data') IS NULL AS missing");
    mpChildRequire(($missingTable['missing'] ?? null) === 't', 'reader-created-optional-table');
    $checks += 2;

    mpChildExec($connection, "CREATE TABLE nsfw_npc_data (
        npc_name varchar(255) PRIMARY KEY,
        extended_data jsonb DEFAULT '{}'::jsonb,
        updated_at timestamp DEFAULT CURRENT_TIMESTAMP
    )");
    mpChildExec($connection, "INSERT INTO nsfw_npc_data (npc_name, extended_data, updated_at)
        VALUES ('River_Guard', '{\"is_child\":true,\"race\":\"Nord\"}'::jsonb, TIMESTAMP '2000-01-01 00:00:00')");
    mpChildExec($connection, "INSERT INTO nsfw_npc_data (npc_name, extended_data) VALUES
        ('Large Unrelated', jsonb_build_object('is_child', false, 'race', 'Nord', 'unrelated', repeat('x', 8192))),
        ('Oversized Race', jsonb_build_object('is_child', false, 'race', repeat('r', 3000))),
        ('Non-object Facts', '[]'::jsonb)");
    $before = mpChildOne($connection, 'SELECT count(*)::int AS rows, min(updated_at)::text AS updated_at FROM nsfw_npc_data');
    $childData = $store->intimacyChildDataByName('River Guard');
    mpChildRequire($childData === ['is_child' => true, 'race' => 'Nord'], 'normalized-child-data-read-failed');
    mpChildRequire($store->intimacyChildDataByName('Absent NPC') === [], 'missing-npc-row-not-empty');
    mpChildRequire($store->intimacyChildDataByName('Large Unrelated') === ['is_child' => false, 'race' => 'Nord'], 'reader-returned-unrelated-metadata');
    mpChildRequire($store->intimacyChildDataByName('Oversized Race') === null, 'oversized-projection-not-unavailable');
    mpChildRequire($store->intimacyChildDataByName('Non-object Facts') === null, 'non-object-child-data-not-unavailable');
    $after = mpChildOne($connection, 'SELECT count(*)::int AS rows, min(updated_at)::text AS updated_at FROM nsfw_npc_data');
    mpChildRequire($after === $before, 'child-data-read-mutated-storage');
    $checks += 5;

    mpChildExec($connection, "INSERT INTO nsfw_npc_data (npc_name, extended_data) VALUES
        ('River Guard', '{\"is_child\":false,\"race\":\"Nord\"}'::jsonb)");
    mpChildRequire($store->intimacyChildDataByName('RIVER_GUARD') === null, 'ambiguous-normalized-name-not-unavailable');
    $checks++;

    echo json_encode(['suite' => 'intimacy-child-data-native', 'status' => 'passed', 'checks' => $checks], JSON_UNESCAPED_SLASHES) . "\n";
} catch (Throwable $error) {
    $failure = $error instanceof RuntimeException ? $error->getMessage() : 'fixture-exception';
} finally {
    if ($schemaCreated && $connection instanceof PgSql\Connection) {
        try {
            $owned = mpChildOne($connection, "SELECT pg_get_userbyid(nspowner) AS owner_name, obj_description(oid, 'pg_namespace') AS marker
                FROM pg_namespace WHERE nspname = $1", [$schema]);
            if (($owned['owner_name'] ?? null) === 'postgres' && ($owned['marker'] ?? null) === ($config['marker'] ?? null)) {
                mpChildExec($connection, 'DROP SCHEMA ' . $schema . ' CASCADE');
            } else {
                $failure ??= 'fixture-schema-ownership-lost';
            }
        } catch (Throwable) {
            $failure ??= 'fixture-schema-cleanup-failed';
        }
    }
    if ($connection instanceof PgSql\Connection) {
        @pg_close($connection);
    }
}

if (is_string($failure)) {
    fwrite(STDERR, 'FAIL: ' . $failure . "\n");
    exit(1);
}
