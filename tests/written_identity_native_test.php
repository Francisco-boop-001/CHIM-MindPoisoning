<?php
declare(strict_types=1);

namespace {
    function mpW1Require(bool $condition, string $code): void
    {
        if (!$condition) {
            throw new RuntimeException($code);
        }
    }

    function mpW1Query(PgSql\Connection $connection, string $sql, array $parameters = []): PgSql\Result
    {
        $result = $parameters === []
            ? @pg_query($connection, $sql)
            : @pg_query_params($connection, $sql, $parameters);
        mpW1Require($result instanceof PgSql\Result, 'native-query-failed');
        return $result;
    }

    function mpW1One(PgSql\Connection $connection, string $sql, array $parameters = []): array
    {
        $result = mpW1Query($connection, $sql, $parameters);
        try {
            $row = pg_fetch_assoc($result);
            mpW1Require(is_array($row), 'native-row-missing');
            return $row;
        } finally {
            pg_free_result($result);
        }
    }

    function mpW1Exec(PgSql\Connection $connection, string $sql, array $parameters = []): void
    {
        $result = mpW1Query($connection, $sql, $parameters);
        pg_free_result($result);
    }

    function mpW1Uuid(int $suffix): string
    {
        return sprintf('00000000-0000-4000-8000-%012x', $suffix);
    }

    function mpW1Config(): array
    {
        mpW1Require(PHP_SAPI === 'cli' && PHP_OS_FAMILY === 'Linux', 'linux-cli-required');
        mpW1Require(extension_loaded('pgsql') && extension_loaded('mbstring'), 'required-extension-missing');
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
            mpW1Require(is_string($value) && $value !== '', 'fixture-environment-required');
            $values[$key] = $value;
        }
        mpW1Require(preg_match('~\A(/tmp/(mp-audit-fixes-[A-Za-z0-9_-]{8,32})/postgres)/data\z~D', $values['data'], $matches) === 1, 'scratch-data-path-invalid');
        mpW1Require($values['socket'] === $matches[1] . '/socket', 'scratch-socket-path-invalid');
        mpW1Require($values['marker'] === $matches[2], 'scratch-marker-invalid');
        mpW1Require(preg_match('/\A[0-9]{15,20}\z/D', $values['system_identifier']) === 1, 'scratch-system-identifier-invalid');
        mpW1Require(preg_match('/\A5[0-9]{4}\z/D', $values['port']) === 1, 'scratch-port-invalid');
        mpW1Require(preg_match('/\Ampaudit(?:_[a-z0-9_]{1,24})?\z/D', $values['database']) === 1, 'scratch-database-name-invalid');
        mpW1Require(is_dir($values['data']) && !is_link($values['data']) && realpath($values['data']) === $values['data'], 'scratch-data-directory-invalid');
        mpW1Require(is_dir($values['socket']) && !is_link($values['socket']) && realpath($values['socket']) === $values['socket'], 'scratch-socket-directory-invalid');
        return $values;
    }

    function mpW1Envelope(string $documentId, string $receiptId, string $operationId, int $recipientId = 2001): array
    {
        return [
            'schema_version' => 1,
            'document_id' => $documentId,
            'playthrough_id' => '1',
            'recipient' => ['kind' => 'npc', 'id' => $recipientId, 'name' => $recipientId === 2001 ? 'Lydia' : 'Mara'],
            'claimed_sender' => ['kind' => 'npc', 'id' => 2100, 'name' => 'Aela'],
            'body' => 'A short, ordinary note.',
            'receipt' => [
                'receipt_id' => $receiptId,
                'operation_id' => $operationId,
                'status' => 'confirmed',
                'exposure' => 'context',
            ],
            'attachments' => [],
        ];
    }

    function mpW1Insert(PgSql\Connection $connection, int $rowId, string $data): void
    {
        mpW1Exec($connection, "INSERT INTO eventlog
            (rowid, type, sess, ts, gamets, localts, people, data)
            VALUES ($1, 'info', 'written_evidence_v1', $2, 10, $3, '|Lydia|', $4)",
            [$rowId, 2000000 + $rowId, 3000000 + $rowId, $data]);
    }

    function mpW1EncodedEnvelope(array $payload): string
    {
        $encoded = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        mpW1Require(is_string($encoded), 'envelope-encode-failed');
        return $encoded;
    }
}

namespace ChimMindPoisoning {
    function hrtime(bool $asNumber = false): int|array
    {
        $values = $GLOBALS['mpW1IdentityClockValues'] ?? null;
        if (is_array($values) && $values !== []) {
            $value = array_shift($values);
            $GLOBALS['mpW1IdentityClockValues'] = $values;
            $GLOBALS['mpW1IdentityClockLast'] = $value;
        } else {
            $value = $GLOBALS['mpW1IdentityClockLast'] ?? null;
        }
        if (is_int($value)) {
            return $asNumber ? $value : [intdiv($value, 1_000_000_000), $value % 1_000_000_000];
        }
        return \hrtime($asNumber);
    }

    require_once __DIR__ . '/../server/written_evidence.php';

    $config = null;
    $schema = 'mp_audit_fixes_w1_fixture';
    $connection = null;
    $schemaCreated = false;
    $checks = 0;
    $failure = null;

    try {
        $config = \mpW1Config();
        foreach (getenv() as $name => $_value) {
            \mpW1Require(!str_starts_with(strtoupper((string)$name), 'PG'), 'postgres-environment-forbidden');
        }
        $dsn = 'host=' . $config['socket'] . ' dbname=' . $config['database'] . ' user=postgres port=' . $config['port'] . ' sslmode=disable connect_timeout=3 application_name=mp-audit-fixes-w1-native';
        $connection = @pg_connect($dsn, PGSQL_CONNECT_FORCE_NEW);
        \mpW1Require($connection instanceof \PgSql\Connection, 'postgres-connect-failed');
        \mpW1Require(pg_connection_status($connection) === PGSQL_CONNECTION_OK, 'postgres-connection-unhealthy');

        $identity = \mpW1One($connection, "SELECT current_database() AS database_name,
            current_user AS user_name,
            current_setting('data_directory') AS data_directory,
            current_setting('unix_socket_directories') AS socket_directories,
            current_setting('port') AS port,
            current_setting('server_version_num') AS server_version_num,
            current_setting('mind_poisoning.disposable_marker', true) AS disposable_marker,
            inet_server_addr() IS NULL AS unix_only,
            (SELECT system_identifier::text FROM pg_control_system()) AS system_identifier");
        \mpW1Require(($identity['database_name'] ?? null) === $config['database'], 'database-identity-mismatch');
        \mpW1Require(($identity['user_name'] ?? null) === 'postgres', 'database-user-mismatch');
        \mpW1Require(($identity['data_directory'] ?? null) === $config['data'], 'database-data-directory-mismatch');
        \mpW1Require(($identity['socket_directories'] ?? null) === $config['socket'], 'database-socket-directory-mismatch');
        \mpW1Require(($identity['port'] ?? null) === $config['port'] && ($identity['unix_only'] ?? null) === 't', 'database-listener-mismatch');
        \mpW1Require(str_starts_with((string)($identity['server_version_num'] ?? ''), '15'), 'postgres-version-mismatch');
        \mpW1Require(($identity['disposable_marker'] ?? null) === $config['marker'], 'database-disposable-marker-mismatch');
        \mpW1Require(($identity['system_identifier'] ?? null) === $config['system_identifier'], 'database-system-identifier-mismatch');

        $schemaExists = \mpW1One($connection, 'SELECT EXISTS (SELECT 1 FROM pg_namespace WHERE nspname = $1) AS present', [$schema]);
        \mpW1Require(($schemaExists['present'] ?? null) === 'f', 'fixture-schema-already-exists');
        \mpW1Exec($connection, 'CREATE SCHEMA ' . $schema);
        $schemaCreated = true;
        \mpW1Exec($connection, "COMMENT ON SCHEMA {$schema} IS '" . $config['marker'] . "'");
        \mpW1Exec($connection, "CREATE TABLE {$schema}.eventlog (
            rowid bigint PRIMARY KEY,
            type character varying(128) NOT NULL,
            data text NOT NULL,
            sess text NOT NULL,
            gamets bigint NOT NULL,
            localts bigint NOT NULL,
            ts bigint NOT NULL,
            people text NOT NULL
        )");
        \mpW1Exec($connection, 'SET search_path TO ' . $schema . ', public');

        $store = new PostgresStoreDb(null, $connection);
        $malformed = '{"not-json":';
        \mpW1Insert($connection, 101, $malformed);
        $nulPayload = \mpW1Envelope(\mpW1Uuid(1), \mpW1Uuid(2), \mpW1Uuid(3));
        $nulPayload['body'] = "An unrelated invalid carrier with \0 in its body.";
        $nulJson = \mpW1EncodedEnvelope($nulPayload);
        \mpW1Require(str_contains($nulJson, '\\u0000'), 'nul-carrier-not-escaped');
        \mpW1Insert($connection, 102, $nulJson);
        $checks++;

        for ($i = 1; $i <= 260; $i++) {
            $payload = \mpW1Envelope(\mpW1Uuid(1000 + $i), \mpW1Uuid(2000 + $i), \mpW1Uuid(3000 + $i));
            \mpW1Insert($connection, 102 + $i, \mpW1EncodedEnvelope($payload));
        }
        $checks++;

        $targetDocument = \mpW1Uuid(1);
        $targetReceipt = \mpW1Uuid(2);
        $targetOperation = \mpW1Uuid(3);
        $escapedPayload = \mpW1Envelope($targetDocument, $targetReceipt, $targetOperation);
        $escapedJson = \mpW1EncodedEnvelope($escapedPayload);
        $escapedDocument = str_replace('1', '\\u0031', $targetDocument);
        $escapedJson = str_replace('"document_id":"' . $targetDocument . '"', '"document_id":"' . $escapedDocument . '"', $escapedJson);
        \mpW1Require(str_contains($escapedJson, '\\u0031'), 'valid-json-escape-not-present');
        \mpW1Insert($connection, 400, $escapedJson);

        $wrongRecipient = \mpW1Envelope($targetDocument, \mpW1Uuid(4), \mpW1Uuid(5), 2002);
        \mpW1Insert($connection, 401, \mpW1EncodedEnvelope($wrongRecipient));
        $receiptReuse = \mpW1Envelope(\mpW1Uuid(6), $targetReceipt, \mpW1Uuid(7), 2002);
        \mpW1Insert($connection, 402, \mpW1EncodedEnvelope($receiptReuse));
        $operationReuse = \mpW1Envelope(\mpW1Uuid(8), \mpW1Uuid(9), $targetOperation, 2002);
        \mpW1Insert($connection, 403, \mpW1EncodedEnvelope($operationReuse));
        $checks++;

        $documentMatches = $store->writtenEvidenceIdentityEvents(
            $targetDocument,
            '1',
            2001,
            $targetReceipt,
            $targetOperation,
            100,
            403
        );
        \mpW1Require(is_array($documentMatches) && array_column($documentMatches, 'event_id') === [400, 401], 'unrelated-corruption-blocked-or-document-conflict-missing');
        \mpW1Require(($documentMatches[0]['recipient_matches'] ?? false) === true
            && ($documentMatches[1]['recipient_matches'] ?? true) === false, 'recipient-collision-not-classified');
        $checks += 2;

        $receiptMatches = $store->writtenEvidenceIdentityEvents(
            \mpW1Uuid(10),
            '1',
            2001,
            $targetReceipt,
            \mpW1Uuid(11),
            100,
            403
        );
        \mpW1Require(is_array($receiptMatches) && array_column($receiptMatches, 'event_id') === [400, 402], 'receipt-reuse-not-detected');
        $operationMatches = $store->writtenEvidenceIdentityEvents(
            \mpW1Uuid(12),
            '1',
            2001,
            \mpW1Uuid(13),
            $targetOperation,
            100,
            403
        );
        \mpW1Require(is_array($operationMatches) && array_column($operationMatches, 'event_id') === [400, 403], 'operation-reuse-not-detected');
        $checks += 2;

        $beforeTarget = $store->writtenEvidenceIdentityEvents(
            $targetDocument,
            '1',
            2001,
            $targetReceipt,
            $targetOperation,
            100,
            399
        );
        \mpW1Require($beforeTarget === [], 'through-bound-includes-future-source');
        $afterFirst = $store->writtenEvidenceIdentityEvents(
            $targetDocument,
            '1',
            2001,
            $targetReceipt,
            $targetOperation,
            400,
            403
        );
        \mpW1Require(is_array($afterFirst) && array_column($afterFirst, 'event_id') === [401, 402], 'exclusive-after-bound-or-page-order-failed');
        $checks += 2;

        $budgetPayload = \mpW1Envelope(\mpW1Uuid(9000), \mpW1Uuid(9001), \mpW1Uuid(9002));
        \mpW1Exec($connection, "INSERT INTO eventlog
            (rowid, type, sess, ts, gamets, localts, people, data)
            SELECT row_id, 'info', 'written_evidence_v1', 2000000 + row_id, 10, 3000000 + row_id, '|Lydia|', $3
            FROM generate_series($1::bigint, $2::bigint) AS series(row_id)",
            [500, 4596, \mpW1EncodedEnvelope($budgetPayload)]);
        $lateMatch = \mpW1Envelope(\mpW1Uuid(9200), \mpW1Uuid(9201), \mpW1Uuid(9202));
        \mpW1Insert($connection, 4597, \mpW1EncodedEnvelope($lateMatch));
        $matchesAfterFormerRowCap = $store->writtenEvidenceIdentityEvents(
            \mpW1Uuid(9200),
            '1',
            2001,
            \mpW1Uuid(9201),
            \mpW1Uuid(9202),
            499,
            4597
        );
        \mpW1Require(is_array($matchesAfterFormerRowCap) && array_column($matchesAfterFormerRowCap, 'event_id') === [4597], 'valid-match-after-4096-sources-unavailable');
        $checks++;

        $GLOBALS['mpW1IdentityClockValues'] = [1_000_000_000, 1_000_000_000, 4_000_000_001];
        $GLOBALS['mpW1IdentityClockLast'] = null;
        $expiredDeadline = $store->writtenEvidenceIdentityEvents(
            \mpW1Uuid(9300),
            '1',
            2001,
            \mpW1Uuid(9301),
            \mpW1Uuid(9302),
            399,
            403
        );
        $GLOBALS['mpW1IdentityClockValues'] = null;
        $GLOBALS['mpW1IdentityClockLast'] = null;
        \mpW1Require($expiredDeadline === null, 'expired-total-scan-budget-reported-clean');
        $checks++;

        echo json_encode(['suite' => 'written-identity-native-w1', 'status' => 'passed', 'checks' => $checks], JSON_UNESCAPED_SLASHES) . "\n";
    } catch (\Throwable $error) {
        $failure = $error instanceof \RuntimeException ? $error->getMessage() : 'fixture-exception';
    } finally {
        if ($schemaCreated && $connection instanceof \PgSql\Connection) {
            try {
                $owned = \mpW1One($connection, "SELECT pg_get_userbyid(nspowner) AS owner_name, obj_description(oid, 'pg_namespace') AS marker
                    FROM pg_namespace WHERE nspname = $1", [$schema]);
                if (($owned['owner_name'] ?? null) === 'postgres' && ($owned['marker'] ?? null) === ($config['marker'] ?? null)) {
                    \mpW1Exec($connection, 'DROP SCHEMA ' . $schema . ' CASCADE');
                } else {
                    $failure ??= 'fixture-schema-ownership-lost';
                }
            } catch (\Throwable) {
                $failure ??= 'fixture-schema-cleanup-failed';
            }
        }
        if ($connection instanceof \PgSql\Connection) {
            @pg_close($connection);
        }
    }

    if (is_string($failure)) {
        fwrite(STDERR, 'FAIL: ' . $failure . "\n");
        exit(1);
    }
}
