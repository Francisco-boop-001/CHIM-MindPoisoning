<?php
declare(strict_types=1);

namespace {
    final class sql
    {
        private static ?\PgSql\Connection $link = null;

        public static function attach(\PgSql\Connection $connection): void
        {
            self::$link = $connection;
        }
    }
}

namespace ChimMindPoisoning {
    require_once __DIR__ . '/../server/logging.php';
    require_once __DIR__ . '/../server/store.php';

    function timeoutCheck(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new \RuntimeException($message);
        }
    }

    function timeoutRows(\PgSql\Connection $connection, string $sql, array $params = []): array
    {
        $result = $params === [] ? @pg_query($connection, $sql) : @pg_query_params($connection, $sql, $params);
        timeoutCheck($result instanceof \PgSql\Result, 'Isolated PostgreSQL statement failed.');
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

    function timeoutMs(\PgSql\Connection $connection): array
    {
        $row = timeoutRows(
            $connection,
            "SELECT round(extract(epoch FROM current_setting('lock_timeout')::interval) * 1000)::int AS lock_ms,
                    round(extract(epoch FROM current_setting('statement_timeout')::interval) * 1000)::int AS statement_ms"
        )[0] ?? [];
        return [(int)($row['lock_ms'] ?? -1), (int)($row['statement_ms'] ?? -1)];
    }

    function timeoutJson(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    function timeoutSetNpcState(\PgSql\Connection $connection, array $extendedData, array $pluginExtendedData, float $gamets): void
    {
        timeoutRows(
            $connection,
            'UPDATE public.core_npc_master SET extended_data = $1::jsonb, plugin_extended_data = $2::jsonb, gamets_last_updated = $3::numeric WHERE id = 1',
            [timeoutJson($extendedData), timeoutJson($pluginExtendedData), (string)$gamets]
        );
    }

    function timeoutNpcStateMatches(\PgSql\Connection $connection, array $extendedData, array $pluginExtendedData, float $gamets): bool
    {
        $row = timeoutRows(
            $connection,
            "SELECT extended_data = $1::jsonb AS extended_data_matches,
                    plugin_extended_data = $2::jsonb AS plugin_extended_data_matches,
                    gamets_last_updated = $3::numeric AS gamets_matches
             FROM public.core_npc_master WHERE id = 1",
            [timeoutJson($extendedData), timeoutJson($pluginExtendedData), (string)$gamets]
        )[0] ?? [];
        return ($row['extended_data_matches'] ?? null) === 't'
            && ($row['plugin_extended_data_matches'] ?? null) === 't'
            && ($row['gamets_matches'] ?? null) === 't';
    }

    function timeoutRelationshipMapIsObject(\PgSql\Connection $connection): bool
    {
        $row = timeoutRows(
            $connection,
            "SELECT jsonb_typeof(extended_data->'relationships') AS map_type FROM public.core_npc_master WHERE id = 1"
        )[0] ?? [];
        return ($row['map_type'] ?? null) === 'object';
    }

    function setSessionTimeouts(\PgSql\Connection $connection, string $lock, string $statement): void
    {
        timeoutRows($connection, "SELECT set_config('lock_timeout', $1, false), set_config('statement_timeout', $2, false)", [$lock, $statement]);
    }

    function runTimeoutScenario(\PgSql\Connection $connection, PostgresStoreDb $store, int $listenerId, string $lock, string $statement, array $expected, array $restored, bool $commit): void
    {
        setSessionTimeouts($connection, $lock, $statement);
        try {
            timeoutCheck($store->beginForListener($listenerId), 'Could not acquire the isolated listener transaction.');
            timeoutCheck(timeoutMs($connection) === $expected, 'Transaction-local timeout settings were incorrect.');
            if ($commit) {
                timeoutCheck($store->commit(), 'The isolated transaction did not commit.');
            }
        } finally {
            $store->rollback();
            $store->release();
        }
        timeoutCheck(timeoutMs($connection) === $restored, 'Transaction timeout settings leaked into the session.');
    }

    $socket = getenv('MP_STORE_TIMEOUT_TEST_PG_SOCKET') ?: '';
    if ($socket === '') {
        echo "store_timeout_test: skipped (isolated PostgreSQL socket not configured)\n";
        return;
    }
    timeoutCheck(
        preg_match('~\A/tmp/(mp-store-timeout-[A-Za-z0-9_-]+)/socket\z~D', $socket, $match) === 1,
        'Refusing a PostgreSQL socket outside the store-timeout scratch namespace.'
    );
    timeoutCheck(function_exists('pg_connect') && defined('PGSQL_CONNECT_FORCE_NEW'), 'The PostgreSQL PHP extension is required.');
    $scratchRoot = '/tmp/' . $match[1];
    $connectionA = @pg_connect('host=' . $socket . ' port=5432 dbname=postgres user=postgres connect_timeout=3', PGSQL_CONNECT_FORCE_NEW);
    timeoutCheck($connectionA instanceof \PgSql\Connection, 'Could not connect to the isolated PostgreSQL cluster.');
    $connectionB = @pg_connect('host=' . $socket . ' port=5432 dbname=postgres user=postgres connect_timeout=3', PGSQL_CONNECT_FORCE_NEW);
    timeoutCheck($connectionB instanceof \PgSql\Connection, 'Could not open the second isolated PostgreSQL connection.');
    $dataDirectory = timeoutRows($connectionA, 'SELECT current_setting(\'data_directory\') AS path')[0]['path'] ?? '';
    timeoutCheck(realpath($dataDirectory) === realpath($scratchRoot . '/data'), 'Refusing a PostgreSQL data directory outside the scratch cluster.');
    timeoutCheck(pg_host($connectionA) === $socket && pg_host($connectionB) === $socket, 'Refusing a non-socket PostgreSQL connection.');
    $existing = timeoutRows($connectionA, "SELECT to_regclass('public.core_npc_master') AS npc, to_regclass('public.core_npc_master_history') AS history")[0] ?? [];
    timeoutCheck(($existing['npc'] ?? null) === null && ($existing['history'] ?? null) === null, 'The scratch cluster already contains persistence fixture tables.');

    $tableCreated = false;
    try {
        timeoutRows($connectionA, 'CREATE TABLE public.core_npc_master (id bigint PRIMARY KEY, npc_name text, extended_data jsonb, plugin_extended_data jsonb, gamets_last_updated numeric)');
        $tableCreated = true;
        timeoutRows($connectionA, 'CREATE TABLE public.core_npc_master_history (history_id bigserial PRIMARY KEY, npc_id bigint NOT NULL)');
        timeoutRows($connectionA, "INSERT INTO public.core_npc_master VALUES (1, 'Listener', '{\"relationships\":{}}', '{\"mind_poisoning\":{}}', 0)");
        \sql::attach($connectionA);
        $GLOBALS['db'] = new \stdClass();
        $store = new PostgresStoreDb();

        runTimeoutScenario($connectionA, $store, 41, '0', '0', [1000, 3000], [0, 0], true);
        runTimeoutScenario($connectionA, $store, 42, '250ms', '700ms', [250, 700], [250, 700], false);
        runTimeoutScenario($connectionA, $store, 43, '5s', '8s', [1000, 3000], [5000, 8000], true);

        setSessionTimeouts($connectionA, '0', '0');
        $statementTimedOut = false;
        $statementStarted = hrtime(true);
        try {
            timeoutCheck($store->beginForListener(45), 'Could not acquire the statement-timeout transaction.');
            $statementResult = @pg_query($connectionA, 'SELECT pg_sleep(4)');
            $statementElapsedMs = (hrtime(true) - $statementStarted) / 1_000_000;
            $statementTimedOut = $statementResult === false && $statementElapsedMs >= 2500 && $statementElapsedMs < 4500
                && pg_transaction_status($connectionA) === PGSQL_TRANSACTION_INERROR;
        } finally {
            $store->rollback();
            $store->release();
        }
        timeoutCheck($statementTimedOut, 'The transaction statement timeout did not cancel a long-running statement near its bound.');
        timeoutCheck(timeoutMs($connectionA) === [0, 0], 'Statement timeout settings leaked after rollback.');

        timeoutRows($connectionB, 'BEGIN');
        timeoutRows($connectionB, 'SELECT id FROM public.core_npc_master WHERE id = 1 FOR UPDATE');
        $startedAt = hrtime(true);
        $records = [];
        $requestLog = new RequestLog(static function (string $json) use (&$records): void {
            $records[] = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        }, false);
        $event = [
            'event_id' => 700,
            'utterance_id' => 'utt_timeout123',
            'speaker_id' => 11,
            'listener_id' => 1,
            'speaker_name' => 'Speaker',
            'listener_name' => 'Listener',
            'text' => 'I trust the subject.',
            'gamets' => 10.0,
            'playthrough_id' => 'unprofiled',
            'source_data' => 'Speaker: I trust the subject. (Talking to Listener)',
        ];
        $subjects = ['npc:2' => ['name' => 'Subject', 'id' => 2]];
        $judgments = ['npc:2' => ['delta' => 2, 'reason' => 'Trust', 'evidence' => 'I trust the subject.']];
        $elapsedMs = 0.0;
        $status = '';
        try {
            $startedAt = hrtime(true);
            $status = persistJudgments($event, $subjects, $judgments, $store, $requestLog);
            $elapsedMs = (hrtime(true) - $startedAt) / 1_000_000;
        } finally {
            timeoutRows($connectionB, 'ROLLBACK');
        }
        timeoutCheck($status === 'failed', 'The timed-out persistence request did not fail closed.');
        timeoutCheck($elapsedMs >= 700 && $elapsedMs < 4500, 'The production persistence path did not finish near the row-lock bound.');
        timeoutCheck(pg_transaction_status($connectionA) === PGSQL_TRANSACTION_IDLE, 'The persistence finally block left a transaction open.');
        timeoutCheck(timeoutMs($connectionA) === [0, 0], 'The failed persistence transaction leaked local timeout settings.');
        $persistence = array_values(array_filter($records, static fn(array $row): bool => ($row['event'] ?? null) === 'persistence_finished'))[0] ?? [];
        timeoutCheck(
            ($persistence['persistence_outcome'] ?? null) === 'failed'
                && ($persistence['persistence_reason'] ?? null) === 'revalidate-event-failed'
                && ($persistence['commit_state'] ?? null) === 'not_attempted'
                && ($persistence['committed'] ?? null) === false,
            'The production persistence failure was not logged as an uncommitted stage failure.'
        );

        $unchanged = timeoutRows(
            $connectionA,
            'SELECT extended_data::text, plugin_extended_data::text, gamets_last_updated::text FROM public.core_npc_master WHERE id = 1'
        )[0] ?? [];
        timeoutCheck(
            $unchanged === ['extended_data' => '{"relationships": {}}', 'plugin_extended_data' => '{"mind_poisoning": {}}', 'gamets_last_updated' => '0']
                && (int)(timeoutRows($connectionA, 'SELECT count(*) AS count FROM public.core_npc_master_history')[0]['count'] ?? -1) === 0,
            'The timed-out transaction changed listener data or history.'
        );
        $lock = timeoutRows($connectionB, 'SELECT pg_try_advisory_lock($1) AS acquired', [1001000001])[0]['acquired'] ?? null;
        timeoutCheck($lock === 't', 'The timed-out transaction did not release its listener advisory lock.');
        timeoutRows($connectionB, 'SELECT pg_advisory_unlock($1)', [1001000001]);

        $basePluginData = [
            'retained_namespace' => ['keep' => true],
            'mind_poisoning' => ['playthrough_id' => 'before', 'floor_event_id' => 0, 'events' => []],
        ];
        $npcEmptyBefore = ['relationships' => [], 'metadata' => ['preserve' => 'empty-npc']];
        $npcEmptyAfter = [
            'relationships' => ['Guard' => ['aff' => 0, 'type' => 'neutral']],
            'metadata' => ['preserve' => 'empty-npc'],
        ];
        $npcEmptyPluginAfter = [
            'retained_namespace' => ['keep' => true],
            'mind_poisoning' => ['playthrough_id' => 'npc-empty', 'floor_event_id' => 0, 'events' => []],
        ];
        timeoutSetNpcState($connectionA, $npcEmptyBefore, $basePluginData, 0.0);
        try {
            timeoutCheck($store->beginForListener(1), 'Could not begin the empty-NPC writer transaction.');
            timeoutCheck(
                $store->writeNpc(1, ['Guard' => (object)['aff' => 0, 'type' => 'neutral']], (object)$npcEmptyPluginAfter['mind_poisoning'], 12.0),
                'The writer rejected an exact empty NPC relationship map.'
            );
            timeoutCheck(timeoutNpcStateMatches($connectionA, $npcEmptyAfter, $npcEmptyPluginAfter, 12.0), 'The empty NPC map did not become an object with its neutral edge and preserved metadata.');
            timeoutCheck(timeoutRelationshipMapIsObject($connectionA), 'The empty NPC map remained array-shaped inside the transaction.');
            timeoutCheck($store->commit(), 'The empty-NPC writer transaction did not commit.');
        } finally {
            $store->rollback();
            $store->release();
        }
        timeoutCheck(timeoutNpcStateMatches($connectionB, $npcEmptyAfter, $npcEmptyPluginAfter, 12.0), 'The committed empty NPC relationship update was not visible to the second connection.');
        timeoutCheck(timeoutRelationshipMapIsObject($connectionB), 'The committed empty NPC relationship map was not object-shaped.');

        $playerEmptyBefore = ['relationships' => [], 'metadata' => ['preserve' => 'empty-player']];
        $playerEmptyAfter = [
            'relationships' => ['Player' => ['aff' => 0, 'type' => 'neutral']],
            'metadata' => ['preserve' => 'empty-player'],
        ];
        $playerEmptyPluginAfter = [
            'retained_namespace' => ['keep' => true],
            'mind_poisoning' => ['playthrough_id' => 'player-empty', 'floor_event_id' => 0, 'events' => []],
        ];
        timeoutSetNpcState($connectionA, $playerEmptyBefore, $basePluginData, 0.0);
        try {
            timeoutCheck($store->beginForListener(1), 'Could not begin the empty-Player writer transaction.');
            timeoutCheck(
                $store->writeNpc(1, ['Player' => (object)['aff' => 0, 'type' => 'neutral']], (object)$playerEmptyPluginAfter['mind_poisoning'], 13.0),
                'The writer rejected an exact empty Player relationship map.'
            );
            timeoutCheck(timeoutNpcStateMatches($connectionA, $playerEmptyAfter, $playerEmptyPluginAfter, 13.0), 'The empty Player map did not become an object with a neutral Player edge and preserved metadata.');
            timeoutCheck(timeoutRelationshipMapIsObject($connectionA), 'The empty Player map remained array-shaped inside the transaction.');
            timeoutCheck($store->commit(), 'The empty-Player writer transaction did not commit.');
        } finally {
            $store->rollback();
            $store->release();
        }
        timeoutCheck(timeoutNpcStateMatches($connectionB, $playerEmptyAfter, $playerEmptyPluginAfter, 13.0), 'The committed empty Player relationship update was not visible to the second connection.');

        $aliasBefore = [
            'relationships' => [
                'Player' => ['aff' => 1, 'type' => 'neutral'],
                'Hawke' => ['aff' => 50, 'type' => 'romantic', 'custom_info' => 'favor owed', 'extra' => ['preserve' => 'selected']],
                'Guard' => ['aff' => 8, 'type' => 'friendly', 'extra' => ['preserve' => true]],
            ],
            'metadata' => ['preserve' => 'alias-write'],
        ];
        $aliasAfter = [
            'relationships' => [
                'Player' => ['aff' => 47, 'type' => 'romantic', 'custom_info' => 'favor owed', 'extra' => ['preserve' => 'selected']],
                'Guard' => ['aff' => 8, 'type' => 'friendly', 'extra' => ['preserve' => true]],
            ],
            'metadata' => ['preserve' => 'alias-write'],
        ];
        $aliasBeforePlugin = [
            'retained_namespace' => ['keep' => true],
            'mind_poisoning' => ['playthrough_id' => 'before', 'floor_event_id' => 0, 'events' => []],
        ];
        $aliasAfterPlugin = [
            'retained_namespace' => ['keep' => true],
            'mind_poisoning' => ['playthrough_id' => 'alias-after', 'floor_event_id' => 701, 'events' => []],
        ];
        timeoutSetNpcState($connectionA, $aliasBefore, $aliasBeforePlugin, 19.0);
        try {
            timeoutCheck($store->beginForListener(1), 'Could not begin the Player-alias writer transaction.');
            timeoutCheck(
                $store->writeNpc(
                    1,
                    ['Player' => (object)[
                        'aff' => 47,
                        'type' => 'romantic',
                        'custom_info' => 'favor owed',
                        'extra' => (object)['preserve' => 'selected'],
                    ]],
                    (object)$aliasAfterPlugin['mind_poisoning'],
                    20.0,
                    ['Hawke']
                ),
                'The writer rejected a parameterized Player-alias removal and canonical upsert.'
            );
            timeoutCheck(timeoutNpcStateMatches($connectionA, $aliasAfter, $aliasAfterPlugin, 20.0), 'The Player/Hawke update did not remove Hawke and upsert Player47 while preserving unrelated data.');
            timeoutCheck(timeoutNpcStateMatches($connectionB, $aliasBefore, $aliasBeforePlugin, 19.0), 'The second connection observed an uncommitted Player-alias update.');
            timeoutCheck($store->commit(), 'The Player-alias writer transaction did not commit.');
        } finally {
            $store->rollback();
            $store->release();
        }
        timeoutCheck(timeoutNpcStateMatches($connectionB, $aliasAfter, $aliasAfterPlugin, 20.0), 'The committed Player-alias update was not visible to the second connection.');

        $invalidMaps = [
            ['relationships' => [['aff' => 99, 'type' => 'neutral']], 'metadata' => ['preserve' => 'array-refusal']],
            ['relationships' => null, 'metadata' => ['preserve' => 'null-refusal']],
        ];
        foreach ($invalidMaps as $index => $invalidExtendedData) {
            $invalidPluginData = [
                'retained_namespace' => ['keep' => true],
                'mind_poisoning' => ['playthrough_id' => 'invalid-before', 'floor_event_id' => 0, 'events' => []],
            ];
            timeoutSetNpcState($connectionA, $invalidExtendedData, $invalidPluginData, 21.0);
            try {
                timeoutCheck($store->beginForListener(1), 'Could not begin a malformed-map writer transaction.');
                timeoutCheck(
                    !$store->writeNpc(1, ['Guard' => (object)['aff' => 1, 'type' => 'neutral']], (object)['playthrough_id' => 'invalid-after', 'events' => []], 22.0),
                    'The writer accepted a nonempty-array or null relationship map.'
                );
                timeoutCheck(timeoutNpcStateMatches($connectionA, $invalidExtendedData, $invalidPluginData, 21.0), 'A rejected malformed relationship map changed listener data.');
            } finally {
                $store->rollback();
                $store->release();
            }
            timeoutCheck(timeoutNpcStateMatches($connectionB, $invalidExtendedData, $invalidPluginData, 21.0), 'A rejected malformed relationship map became visible after rollback.');
        }

        $rollbackBefore = [
            'relationships' => [
                'Player' => ['aff' => 1, 'type' => 'neutral'],
                'Hawke' => ['aff' => 50, 'type' => 'romantic'],
                'Guard' => ['aff' => 8, 'type' => 'friendly', 'extra' => ['preserve' => true]],
            ],
            'metadata' => ['preserve' => 'rollback'],
        ];
        $rollbackBeforePlugin = [
            'retained_namespace' => ['keep' => true],
            'mind_poisoning' => ['playthrough_id' => 'rollback-before', 'floor_event_id' => 0, 'events' => []],
        ];
        $rollbackAfter = [
            'relationships' => [
                'Player' => ['aff' => 47, 'type' => 'romantic'],
                'Guard' => ['aff' => 8, 'type' => 'friendly', 'extra' => ['preserve' => true]],
            ],
            'metadata' => ['preserve' => 'rollback'],
        ];
        $rollbackAfterPlugin = [
            'retained_namespace' => ['keep' => true],
            'mind_poisoning' => ['playthrough_id' => 'rollback-after', 'floor_event_id' => 702, 'events' => []],
        ];
        timeoutSetNpcState($connectionA, $rollbackBefore, $rollbackBeforePlugin, 30.0);
        try {
            timeoutCheck($store->beginForListener(1), 'Could not begin the writer rollback transaction.');
            timeoutCheck(
                $store->writeNpc(
                    1,
                    ['Player' => (object)['aff' => 47, 'type' => 'romantic']],
                    (object)$rollbackAfterPlugin['mind_poisoning'],
                    31.0,
                    ['Hawke']
                ),
                'The writer failed before the rollback check.'
            );
            timeoutCheck(timeoutNpcStateMatches($connectionA, $rollbackAfter, $rollbackAfterPlugin, 31.0), 'The rollback transaction did not stage all writer changes.');
            timeoutCheck(timeoutNpcStateMatches($connectionB, $rollbackBefore, $rollbackBeforePlugin, 30.0), 'The second connection observed uncommitted writer changes before rollback.');
            timeoutCheck((int)(timeoutRows($connectionA, 'SELECT count(*) AS count FROM public.core_npc_master_history')[0]['count'] ?? -1) === 0, 'A direct writer call unexpectedly created a history snapshot.');
        } finally {
            $store->rollback();
            $store->release();
        }
        timeoutCheck(timeoutNpcStateMatches($connectionA, $rollbackBefore, $rollbackBeforePlugin, 30.0), 'Rollback did not restore the canonical edge, alias, ledger, and timeline.');
        timeoutCheck(timeoutNpcStateMatches($connectionB, $rollbackBefore, $rollbackBeforePlugin, 30.0), 'The second connection saw writer data after rollback.');
        timeoutCheck((int)(timeoutRows($connectionB, 'SELECT count(*) AS count FROM public.core_npc_master_history')[0]['count'] ?? -1) === 0, 'Rollback left a history snapshot behind.');

        echo "store_timeout_test: timeout bounds, direct writer normalization/removal, commit visibility, rollback, malformed-map refusal, setting restoration, and lock release passed\n";
    } finally {
        if ($tableCreated) {
            @pg_query($connectionA, 'DROP TABLE IF EXISTS public.core_npc_master_history, public.core_npc_master');
        }
        pg_close($connectionA);
        pg_close($connectionB);
    }
}
