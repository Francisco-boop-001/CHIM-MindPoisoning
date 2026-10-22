<?php
declare(strict_types=1);

define('CHIM_MIND_POISONING_TEST_FIXTURES_ONLY', true);
require_once __DIR__ . '/runtime_test.php';
require_once __DIR__ . '/../server/intimacy.php';
require_once __DIR__ . '/../server/reflection.php';

if (!function_exists('chimGetGeneralSettingBool')) {
    function chimGetGeneralSettingBool(string $name, bool $default = false): bool
    {
        return $name === 'mind_poisoning_intimacy_enabled';
    }
}

// Mirror the pinned runtime fixture's two core source-parser seams for this standalone native test.
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
    $interactionReason = null;
    mpChildRequire(
        ChimMindPoisoning\speechAckInteractionStatus($interactionReason) === 'ok' && $interactionReason === 'ok',
        'reflection-fixture-interaction-gate-unavailable'
    );
    $pauseReason = null;
    mpChildRequire(
        ChimMindPoisoning\mindPoisoningPauseStatus($pauseReason) === 'enabled' && $pauseReason === null,
        'reflection-fixture-pause-gate-unavailable'
    );
    $checks += 2;
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

    mpChildExec($connection, "CREATE TABLE eventlog (
        rowid bigint PRIMARY KEY,
        type text NOT NULL,
        utterance_id text NOT NULL,
        delivery_state text NOT NULL,
        gamets numeric NOT NULL,
        data text NOT NULL,
        people text NOT NULL
    )");
    mpChildExec($connection, "CREATE TABLE core_npc_master (
        id bigint PRIMARY KEY,
        npc_name text NOT NULL,
        lock_profile integer NOT NULL DEFAULT 0,
        extended_data jsonb,
        plugin_extended_data jsonb,
        gamets_last_updated numeric,
        personality text,
        md5 text
    )");
    mpChildExec($connection, "CREATE TABLE core_npc_master_history (
        history_id bigserial PRIMARY KEY,
        npc_id bigint NOT NULL,
        npc_name text NOT NULL,
        lock_profile integer NOT NULL,
        extended_data jsonb,
        plugin_extended_data jsonb,
        gamets_last_updated numeric,
        personality text,
        md5 text,
        created timestamp NOT NULL
    )");
    mpChildExec($connection, "CREATE TABLE core_player (id text PRIMARY KEY, value text)");
    mpChildExec($connection, "INSERT INTO core_player (id, value) VALUES ('player_name', 'Dragonborn')");
    mpChildExec($connection, "INSERT INTO core_npc_master
        (id, npc_name, lock_profile, extended_data, plugin_extended_data, gamets_last_updated, personality, md5)
        VALUES
        (1, 'Aela', 0, '{\"relationships\":{\"Lydia\":{\"aff\":10,\"type\":\"neutral\"}}}'::jsonb, '{}'::jsonb, 0, '', ''),
        (2, 'Lydia', 0, '{\"relationships\":{}}'::jsonb, '{}'::jsonb, 0, '', ''),
        (3, 'Runa', 0, '{\"relationships\":{}}'::jsonb, '{}'::jsonb, 0, '', ''),
        (4, 'Nazeem', 0, '{\"relationships\":{}}'::jsonb, '{}'::jsonb, 0, '', '')");

    $longUtf8Source = 'Aela: ' . str_repeat('é', 2200);
    $oversizedPeople = '|Aela|' . str_repeat('x', 2048);
    mpChildExec($connection, "INSERT INTO eventlog
        (rowid, type, utterance_id, delivery_state, gamets, data, people)
        VALUES
        (10, 'chat', 'utt_history_multibyte', 'spoken', 1, $1, '|Aela|'),
        (11, 'chat', 'utt_history_people', 'spoken', 2, 'Aela: Lydia shared a plan. (Talking to Lydia)', $2),
        (12, 'chat', 'utt_history_valid', 'spoken', 3, 'Aela: Lydia brought me a letter. (Talking to Lydia)', '|Aela|Lydia|')",
        [$longUtf8Source, $oversizedPeople]);

    $store = new ChimMindPoisoning\PostgresStoreDb(null, $connection);
    $snapshotOwner = $store->npcById(1);
    mpChildRequire(is_array($snapshotOwner), 'snapshot-fixture-owner-missing');
    mpChildRequire($store->beginForListener(1), 'snapshot-fixture-smoke-lock-not-acquired');
    $snapshotSmoke = ['behavior' => 'exception', 'exception' => null, 'message' => null];
    try {
        $snapshotSmokeResult = $store->backupAndVerify(1, [
            'extended_data' => $snapshotOwner['extended_data'],
            'plugin_extended_data' => $snapshotOwner['plugin_extended_data'],
            'gamets_last_updated' => $snapshotOwner['gamets_last_updated'],
        ]);
        $snapshotSmoke['behavior'] = $snapshotSmokeResult ? 'returned-true' : 'returned-false';
    } catch (Throwable $error) {
        $snapshotSmoke['exception'] = substr($error::class, 0, 80);
        $message = preg_replace('/[^A-Za-z0-9_.:() -]/', '?', $error->getMessage());
        $snapshotSmoke['message'] = is_string($message) ? substr($message, 0, 160) : null;
    } finally {
        $store->rollback();
        $store->release();
    }
    mpChildRequire($snapshotSmoke['behavior'] === 'returned-true', 'snapshot-fixture-smoke-failed:' . json_encode($snapshotSmoke, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    $checks++;

    $history = $store->reflectionHistory('Aela', 100);
    mpChildRequire(array_column($history, 'event_id') === [12], 'reflection-history-byte-bounds-not-applied-before-limit');
    $checks++;

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
    mpChildExec($connection, "INSERT INTO nsfw_npc_data (npc_name, extended_data)
        VALUES ('Aela', '{\"is_child\":false,\"race\":\"Nord\"}'::jsonb),
               ('Lydia', '{\"is_child\":false,\"race\":\"Nord\"}'::jsonb)");
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

    $ownerBeforeReflection = $store->npcById(1);
    $targetBeforeReflection = $store->npcById(2);
    mpChildRequire(is_array($ownerBeforeReflection) && is_array($targetBeforeReflection), 'reflection-fixture-npcs-missing');
    mpChildRequire(
        ChimMindPoisoning\mindPoisoningIntimacyChildStatus($store, 'Aela', $ownerBeforeReflection) === false
            && ChimMindPoisoning\mindPoisoningIntimacyChildStatus($store, 'Lydia', $targetBeforeReflection) === false,
        'reflection-arrangement-preflight-did-not-allow-adult-pair'
    );

    $reflectionText = 'I trust Lydia again. I ended the arrangement with Lydia.';
    $reflectionSourceData = 'Aela: I trust Lydia again. I ended the arrangement with Lydia. (Talking to explicit_disable_rechat)';
    mpChildExec($connection, "INSERT INTO eventlog
        (rowid, type, utterance_id, delivery_state, gamets, data, people)
        VALUES (100, 'chat', 'utt_reflection_native_100', 'spoken', 10, $1, '|Aela|')",
        [$reflectionSourceData]);
    $reflectionEvent = [
        'event_id' => 100,
        'utterance_id' => 'utt_reflection_native_100',
        'speaker_kind' => 'npc',
        'speaker_id' => 1,
        'listener_id' => null,
        'speaker_name' => 'Aela',
        'opinion_owner_id' => 1,
        'source_kind' => 'reflection',
        'reflection_basis' => '',
        'speech_hash' => hash('sha256', $reflectionText),
        'text' => $reflectionText,
        'playthrough_id' => 'unprofiled',
        'player_name' => 'Dragonborn',
        'source_data' => $reflectionSourceData,
        'gamets' => 10.0,
    ];
    mpChildRequire(
        ChimMindPoisoning\sameEvent($reflectionEvent, $store->eventById(100, 'utt_reflection_native_100')),
        'reflection-fixture-source-not-current'
    );
    $basisHistory = null;
    $basisReason = null;
    $reflectionEvent['reflection_basis'] = ChimMindPoisoning\reflectionBasisForEvent(
        $ownerBeforeReflection,
        $store,
        $reflectionEvent,
        $basisHistory,
        $basisReason
    );
    mpChildRequire(is_string($reflectionEvent['reflection_basis']), 'reflection-fixture-basis-unavailable');
    $reflectionEvent['intimacy_expected_records'] = [
        'npc:2' => ChimMindPoisoning\mindPoisoningCaptureIntimacyRecord($ownerBeforeReflection, 'npc:2', 'unprofiled'),
    ];
    $reflectionSubjects = ['npc:2' => ['id' => 2, 'name' => 'Lydia']];
    $reflectionJudgments = [
        'npc:2' => [
            'delta' => 2,
            'reason' => 'Aela trusts Lydia after what happened.',
            'evidence' => 'I trust Lydia again.',
            'type_change' => ['from' => 'neutral', 'to' => 'suspicious'],
            'arrangement_change' => ['status' => 'none', 'evidence' => 'I ended the arrangement with Lydia.'],
        ],
    ];
    $registration = ['event_id' => 100, 'utterance_id' => 'utt_reflection_native_100'];
    $preflightIntimacyData = $store->intimacyChildDataByName('Aela');
    mpChildRequire(is_array($preflightIntimacyData) && ($preflightIntimacyData['is_child'] ?? null) === false, 'reflection-preflight-metadata-not-readable');

    mpChildExec($connection, "ALTER TABLE nsfw_npc_data ALTER COLUMN extended_data TYPE text USING extended_data::text");
    $reflectionRecords = [];
    $reflectionLog = new ChimMindPoisoning\RequestLog(static function (string $json, string $_level) use (&$reflectionRecords): void {
        $record = json_decode($json, true);
        if (is_array($record)) {
            $reflectionRecords[] = $record;
        }
    }, false);
    $reflectionLog->observe(static function (array $record) use (&$reflectionRecords): void {
        $reflectionRecords[] = $record;
    });
    $reflectionRevalidationStages = [];
    $reflectionStatus = ChimMindPoisoning\persistJudgments(
        $reflectionEvent,
        $reflectionSubjects,
        $reflectionJudgments,
        $store,
        $reflectionLog,
        static function (array $given, string $stage) use ($registration, &$reflectionRevalidationStages): bool {
            if (in_array($stage, ['transaction', 'precommit'], true) && count($reflectionRevalidationStages) < 4) {
                $reflectionRevalidationStages[] = $stage;
            }
            return $given === $registration;
        },
        $registration
    );
    mpChildRequire(in_array('transaction', $reflectionRevalidationStages, true), 'reflection-fixture-did-not-reach-transaction-revalidation');
    $reflectionFinished = array_values(array_filter($reflectionRecords, static fn(array $record): bool => ($record['event'] ?? null) === 'persistence_finished'))[0] ?? [];
    $reflectionFailureReason = $reflectionFinished['persistence_reason'] ?? null;
    $reflectionFailureStage = is_string($reflectionFailureReason)
        && preg_match('/\A([a-z0-9-]{1,64})-failed\z/D', $reflectionFailureReason, $stageMatch) === 1
        ? $stageMatch[1]
        : null;
    $reflectionFailureDiagnostic = json_encode([
        'status' => is_string($reflectionStatus) ? substr($reflectionStatus, 0, 32) : null,
        'persistence_reason' => is_string($reflectionFailureReason) ? substr($reflectionFailureReason, 0, 80) : null,
        'stage' => $reflectionFailureStage,
        'commit_state' => is_string($reflectionFinished['commit_state'] ?? null)
            ? substr($reflectionFinished['commit_state'], 0, 24)
            : null,
        'revalidation_stages' => $reflectionRevalidationStages,
    ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    $savedReflectionOwner = $store->npcById(1);
    $savedRelationship = $savedReflectionOwner['extended_data']->relationships->Lydia ?? null;
    $savedLedger = $savedReflectionOwner['plugin_extended_data']->mind_poisoning ?? null;
    mpChildRequire($reflectionStatus === 'committed', 'optional-child-query-aborted-mixed-reflection-write:' . $reflectionFailureDiagnostic);
    mpChildRequire(($savedRelationship->aff ?? null) === 12, 'optional-child-query-lost-affinity-write');
    mpChildRequire(($savedRelationship->type ?? null) === 'suspicious', 'optional-child-query-lost-relationship-type-write');
    mpChildRequire(is_object($savedLedger) && count($savedLedger->events ?? []) === 1, 'optional-child-query-lost-reflection-receipt');
    mpChildRequire(($savedLedger->arrangements ?? null) === null, 'unavailable-child-metadata-wrote-arrangement');
    mpChildRequire(($reflectionFinished['arrangement_changed_count'] ?? null) === 0, 'unavailable-child-metadata-counted-arrangement');
    mpChildRequire(($reflectionFinished['cleanup_failed'] ?? false) === false, 'optional-child-read-counted-as-cleanup-failure');
    $checks += 7;

    $cleanReflectionText = 'Runa brought a map to the camp.';
    $cleanReflectionSourceData = 'Aela: Runa brought a map to the camp. (Talking to explicit_disable_rechat)';
    mpChildExec($connection, "INSERT INTO eventlog
        (rowid, type, utterance_id, delivery_state, gamets, data, people)
        VALUES (101, 'chat', 'utt_reflection_native_101', 'spoken', 11, $1, '|Aela|')",
        [$cleanReflectionSourceData]);
    $cleanReflectionEvent = [
        'event_id' => 101,
        'utterance_id' => 'utt_reflection_native_101',
        'speaker_kind' => 'npc',
        'speaker_id' => 1,
        'listener_id' => null,
        'speaker_name' => 'Aela',
        'opinion_owner_id' => 1,
        'source_kind' => 'reflection',
        'reflection_basis' => '',
        'speech_hash' => hash('sha256', $cleanReflectionText),
        'text' => $cleanReflectionText,
        'playthrough_id' => 'unprofiled',
        'player_name' => 'Dragonborn',
        'source_data' => $cleanReflectionSourceData,
        'gamets' => 11.0,
    ];
    $ownerAfterReflection = $store->npcById(1);
    $cleanReflectionEvent['reflection_basis'] = ChimMindPoisoning\reflectionBasisForEvent(
        $ownerAfterReflection,
        $store,
        $cleanReflectionEvent,
        $basisHistory,
        $basisReason
    );
    $cleanSubjects = ChimMindPoisoning\findSubjects($cleanReflectionEvent, $store->npcIdentities(), 'Dragonborn');
    $cleanJudgments = [
        'npc:3' => [
            'delta' => 1,
            'reason' => 'Aela noticed Runa bringing a map.',
            'evidence' => 'Runa brought a map',
        ],
    ];
    $cleanRegistration = ['event_id' => 101, 'utterance_id' => 'utt_reflection_native_101'];
    $cleanRecords = [];
    $cleanLog = new ChimMindPoisoning\RequestLog(static function (string $json, string $_level) use (&$cleanRecords): void {
        $record = json_decode($json, true);
        if (is_array($record)) {
            $cleanRecords[] = $record;
        }
    }, false);
    $cleanStatus = ChimMindPoisoning\persistJudgments(
        $cleanReflectionEvent,
        $cleanSubjects,
        $cleanJudgments,
        $store,
        $cleanLog,
        static fn(array $given, string $_stage): bool => $given === $cleanRegistration,
        $cleanRegistration
    );
    $cleanFinished = array_values(array_filter($cleanRecords, static fn(array $record): bool => ($record['event'] ?? null) === 'persistence_finished'))[0] ?? [];
    mpChildRequire($cleanStatus === 'committed' && ($cleanFinished['persistence_outcome'] ?? null) === 'committed', 'later-clean-reflection-did-not-commit');
    mpChildRequire(($cleanFinished['cleanup_failed'] ?? false) === false, 'later-clean-reflection-inherited-cleanup-failure');
    $checks += 2;

    $observerDsn = 'host=' . $config['socket'] . ' dbname=' . $config['database'] . ' user=postgres port=' . $config['port']
        . ' sslmode=disable connect_timeout=3 application_name=mp-audit-fixes-child-observer';
    $observerConnection = null;
    try {
        $observerConnection = @pg_connect($observerDsn, PGSQL_CONNECT_FORCE_NEW);
        mpChildRequire($observerConnection instanceof PgSql\Connection, 'cleanup-observer-connect-failed');
        $observerIdentity = mpChildOne($observerConnection, "SELECT current_database() AS database_name,
            current_user AS user_name,
            current_setting('data_directory') AS data_directory,
            current_setting('unix_socket_directories') AS socket_directories,
            current_setting('port') AS port,
            current_setting('server_version_num') AS server_version_num,
            current_setting('mind_poisoning.disposable_marker', true) AS disposable_marker,
            inet_server_addr() IS NULL AS unix_only,
            (SELECT system_identifier::text FROM pg_control_system()) AS system_identifier");
        mpChildRequire(($observerIdentity['database_name'] ?? null) === $config['database'], 'cleanup-observer-database-identity-mismatch');
        mpChildRequire(($observerIdentity['user_name'] ?? null) === 'postgres', 'cleanup-observer-user-mismatch');
        mpChildRequire(($observerIdentity['data_directory'] ?? null) === $config['data'], 'cleanup-observer-data-directory-mismatch');
        mpChildRequire(($observerIdentity['socket_directories'] ?? null) === $config['socket'], 'cleanup-observer-socket-directory-mismatch');
        mpChildRequire(($observerIdentity['port'] ?? null) === $config['port'] && ($observerIdentity['unix_only'] ?? null) === 't', 'cleanup-observer-listener-mismatch');
        mpChildRequire(str_starts_with((string)($observerIdentity['server_version_num'] ?? ''), '15'), 'cleanup-observer-version-mismatch');
        mpChildRequire(($observerIdentity['disposable_marker'] ?? null) === $config['marker'], 'cleanup-observer-marker-mismatch');
        mpChildRequire(($observerIdentity['system_identifier'] ?? null) === $config['system_identifier'], 'cleanup-observer-system-identifier-mismatch');

        $cleanupRecords = [];
        $cleanupLog = new ChimMindPoisoning\RequestLog(static function (string $json, string $_level) use (&$cleanupRecords): void {
            $record = json_decode($json, true);
            if (is_array($record)) {
                $cleanupRecords[] = $record;
            }
        }, false);
        $store->setRequestLog($cleanupLog);
        mpChildRequire($store->beginForListener(1), 'cleanup-fixture-lock-not-acquired');
        mpChildRequire(@pg_query($connection, 'SELECT 1 / 0') === false, 'cleanup-fixture-did-not-abort-transaction');
        $store->release();
        mpChildRequire($store->cleanupFailed(), 'cleanup-failure-not-recorded');
        $lockBeforeRetry = mpChildOne($observerConnection, 'SELECT pg_try_advisory_lock($1) AS acquired', [1001000001]);
        mpChildRequire(($lockBeforeRetry['acquired'] ?? null) === 'f', 'failed-unlock-dropped-session-ownership');
        $store->rollback();
        $store->release();
        $lockAfterRetry = mpChildOne($observerConnection, 'SELECT pg_try_advisory_lock($1) AS acquired', [1001000001]);
        mpChildRequire(($lockAfterRetry['acquired'] ?? null) === 't', 'retry-did-not-release-advisory-lock');
        mpChildExec($observerConnection, 'SELECT pg_advisory_unlock($1)', [1001000001]);
        mpChildRequire($store->cleanupFailed(), 'successful-cleanup-retry-cleared-historical-diagnostic');
        $cleanupReported = array_values(array_filter($cleanupRecords, static fn(array $record): bool => ($record['event'] ?? null) === 'persistence_cleanup_failed'));
        mpChildRequire(count($cleanupReported) === 1 && ($cleanupReported[0]['cleanup_failed'] ?? null) === true, 'original-cleanup-failure-report-was-lost');

        $afterCleanupText = 'Nazeem dropped his ledger.';
        $afterCleanupSourceData = 'Aela: Nazeem dropped his ledger. (Talking to explicit_disable_rechat)';
        mpChildExec($connection, "INSERT INTO eventlog
            (rowid, type, utterance_id, delivery_state, gamets, data, people)
            VALUES (102, 'chat', 'utt_reflection_native_102', 'spoken', 12, $1, '|Aela|')",
            [$afterCleanupSourceData]);
        $afterCleanupEvent = [
            'event_id' => 102,
            'utterance_id' => 'utt_reflection_native_102',
            'speaker_kind' => 'npc',
            'speaker_id' => 1,
            'listener_id' => null,
            'speaker_name' => 'Aela',
            'opinion_owner_id' => 1,
            'source_kind' => 'reflection',
            'reflection_basis' => '',
            'speech_hash' => hash('sha256', $afterCleanupText),
            'text' => $afterCleanupText,
            'playthrough_id' => 'unprofiled',
            'player_name' => 'Dragonborn',
            'source_data' => $afterCleanupSourceData,
            'gamets' => 12.0,
        ];
        $ownerAfterCleanup = $store->npcById(1);
        $afterCleanupEvent['reflection_basis'] = ChimMindPoisoning\reflectionBasisForEvent(
            $ownerAfterCleanup,
            $store,
            $afterCleanupEvent,
            $basisHistory,
            $basisReason
        );
        $afterCleanupSubjects = ChimMindPoisoning\findSubjects($afterCleanupEvent, $store->npcIdentities(), 'Dragonborn');
        $afterCleanupJudgments = [
            'npc:4' => [
                'delta' => 1,
                'reason' => 'Aela noticed Nazeem drop a ledger.',
                'evidence' => 'Nazeem dropped his ledger',
            ],
        ];
        $afterCleanupRegistration = ['event_id' => 102, 'utterance_id' => 'utt_reflection_native_102'];
        $afterCleanupRecords = [];
        $afterCleanupLog = new ChimMindPoisoning\RequestLog(static function (string $json, string $_level) use (&$afterCleanupRecords): void {
            $record = json_decode($json, true);
            if (is_array($record)) {
                $afterCleanupRecords[] = $record;
            }
        }, false);
        $afterCleanupStatus = ChimMindPoisoning\persistJudgments(
            $afterCleanupEvent,
            $afterCleanupSubjects,
            $afterCleanupJudgments,
            $store,
            $afterCleanupLog,
            static fn(array $given, string $_stage): bool => $given === $afterCleanupRegistration,
            $afterCleanupRegistration
        );
        $afterCleanupFinished = array_values(array_filter($afterCleanupRecords, static fn(array $record): bool => ($record['event'] ?? null) === 'persistence_finished'))[0] ?? [];
        mpChildRequire($afterCleanupStatus === 'committed' && ($afterCleanupFinished['persistence_outcome'] ?? null) === 'committed', 'later-clean-reflection-inherited-old-cleanup-status');
        mpChildRequire(($afterCleanupFinished['cleanup_failed'] ?? false) === false, 'later-clean-reflection-request-inherited-old-cleanup-context');

        $laterRecords = [];
        $laterLog = new ChimMindPoisoning\RequestLog(static function (string $json, string $_level) use (&$laterRecords): void {
            $record = json_decode($json, true);
            if (is_array($record)) {
                $laterRecords[] = $record;
            }
        }, false);
        $laterStatus = ChimMindPoisoning\persistWrittenEvidenceJudgments(
            ['source_kind' => 'written_evidence'],
            [],
            [],
            $store,
            $laterLog
        );
        $laterFinished = array_values(array_filter($laterRecords, static fn(array $record): bool => ($record['event'] ?? null) === 'persistence_finished'))[0] ?? [];
        mpChildRequire($laterStatus === 'invalid' && ($laterFinished['persistence_outcome'] ?? null) === 'invalid', 'later-clean-wrapper-inherited-old-cleanup-failure');
        mpChildRequire(($laterFinished['cleanup_failed'] ?? false) === false, 'later-request-log-inherited-old-cleanup-failure');
        $checks += 20;
    } finally {
        if ($observerConnection instanceof PgSql\Connection) {
            @pg_close($observerConnection);
        }
    }

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
