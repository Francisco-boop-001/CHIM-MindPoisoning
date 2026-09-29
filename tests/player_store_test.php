<?php
declare(strict_types=1);

require_once __DIR__ . '/runtime_test.php';

use ChimMindPoisoning\PostgresStoreDb;
use ChimMindPoisoning\RequestLog;
use function ChimMindPoisoning\normalizePlayerInputRow;
use function ChimMindPoisoning\persistJudgments;
use function ChimMindPoisoning\sameEvent;
use function ChimMindPoisoning\sameJsonValue;

final class PlayerInputQueryDb
{
    public array $rows = [];
    public array $queries = [];

    public function fetchOne(string $query, array $parameters = []): array
    {
        $this->queries[] = [$query, $parameters];
        if (str_contains($query, 'jsonb_agg')) {
            $matches = array_values(array_filter($this->rows, fn(array $row): bool => $this->matchesTuple($row, $parameters)));
            usort($matches, static fn(array $left, array $right): int => (int)$left['rowid'] <=> (int)$right['rowid']);
            $events = [];
            foreach (array_slice($matches, 0, 2) as $row) {
                $events[] = array_intersect_key($row, array_flip(['rowid', 'type', 'ts', 'gamets', 'data', 'localts', 'sess', 'people']));
            }
            return ['events' => json_encode($events, JSON_THROW_ON_ERROR)];
        }
        if (str_contains($query, 'FOR SHARE OF e') && count($parameters) === 2) {
            foreach ($this->rows as $row) {
                if ((int)($row['rowid'] ?? 0) !== (int)$parameters[0]
                    || 'input_' . (int)$row['rowid'] !== $parameters[1]
                    || !in_array($row['type'] ?? null, ['inputtext', 'inputtext_s', 'ginputtext', 'ginputtext_s'], true)
                    || ($row['sess'] ?? null) !== 'web') {
                    continue;
                }
                $row['source_count'] = (string)count(array_filter($this->rows, fn(array $candidate): bool => $this->sameTuple($row, $candidate)));
                return $row;
            }
        }
        return [];
    }

    public function fetchAll(string $query): array
    {
        return [];
    }

    private function matchesTuple(array $row, array $parameters): bool
    {
        if (count($parameters) !== 6) {
            return false;
        }
        foreach (['type', 'ts', 'gamets', 'data', 'localts', 'sess'] as $index => $field) {
            if ((string)($row[$field] ?? '') !== (string)$parameters[$index]) {
                return false;
            }
        }
        return true;
    }

    private function sameTuple(array $left, array $right): bool
    {
        foreach (['type', 'ts', 'gamets', 'data', 'localts', 'sess'] as $field) {
            if ((string)($left[$field] ?? '') !== (string)($right[$field] ?? '')) {
                return false;
            }
        }
        return true;
    }
}

final class NativeInputQueryDb
{
    public function __construct(private \PgSql\Connection $connection) {}

    public function fetchOne(string $query, array $parameters = []): array
    {
        $result = @pg_query_params($this->connection, $query, $parameters);
        check($result instanceof \PgSql\Result, 'The isolated PostgreSQL input query failed: ' . pg_last_error($this->connection));
        try {
            $row = pg_fetch_assoc($result);
            return is_array($row) ? $row : [];
        } finally {
            pg_free_result($result);
        }
    }

    public function fetchAll(string $query): array
    {
        return [];
    }
}

function playerSourceRow(int $rowid = 700, string $people = '|Lydia|Aela|'): array
{
    return [
        'rowid' => (string)$rowid,
        'type' => 'inputtext',
        'ts' => '1730000000000',
        'gamets' => '12',
        'data' => 'Dragonborn: Bruce helped Jarl Balgruuf. (mood: hopeful)',
        'localts' => 1730000001,
        'sess' => 'web',
        'people' => $people,
    ];
}

function playerStoreFixture(string $people = '|Lydia|Aela|'): array
{
    [, , , $db] = baseFixture();
    $row = playerSourceRow(700, $people);
    $db->events[700] = $row;
    $source = array_intersect_key($row, array_flip(['type', 'ts', 'gamets', 'data', 'localts', 'sess']));
    $normalized = $db->playerInputEvent($source);
    check(is_array($normalized), 'The unique web input source should normalize.');
    $event = array_merge($normalized, [
        'speaker_name' => 'Dragonborn',
        'player_name' => 'Dragonborn',
        'listener_id' => 22,
        'listener_name' => 'Lydia',
        'text' => 'Bruce helped Jarl Balgruuf.',
        'playthrough_id' => '1',
    ]);
    return [$event, ['npc:33' => ['name' => 'Jarl Balgruuf', 'id' => 33]], [
        'npc:33' => ['delta' => 4, 'reason' => 'A named claim may improve credibility.', 'evidence' => 'Bruce helped Jarl Balgruuf.'],
    ], $db, $row, $source];
}

$base = playerSourceRow();
$queryDb = new PlayerInputQueryDb();
$queryDb->rows = [$base];
$priorDb = $GLOBALS['db'] ?? null;
$GLOBALS['db'] = $queryDb;
$adapter = new PostgresStoreDb();
$source = array_intersect_key($base, array_flip(['type', 'ts', 'gamets', 'data', 'localts', 'sess']));
$normalized = $adapter->playerInputEvent($source);
check(is_array($normalized), 'A unique exact input tuple should resolve.');
check(
    $normalized['utterance_id'] === 'input_700'
        && $normalized['speaker_kind'] === 'player'
        && $normalized['speaker_id'] === null
        && $normalized['source_people'] === '|Lydia|Aela|',
    'Player input identity and routed audience metadata should normalize without inventing an emitted ACK.'
);
[$lookup, $parameters] = $queryDb->queries[0];
foreach (['type = $1', 'ts = $2', 'gamets = $3', 'data = $4', 'localts = $5', 'sess = $6', 'ORDER BY rowid LIMIT 2'] as $predicate) {
check(str_contains($lookup, $predicate), 'Input lookup must constrain every request-time tuple field and cap duplicate rows.');
}
check($parameters === ['inputtext', '1730000000000', '12', $base['data'], 1730000001, 'web'], 'Input lookup must preserve the exact source tuple parameter values.');
$revalidated = $adapter->eventById(700, 'input_700');
check(is_array($revalidated) && $revalidated['source_people'] === '|Lydia|Aela|', 'Transaction revalidation should recover the exact input row and audience.');
$revalidationQuery = $queryDb->queries[1][0];
check(str_contains($revalidationQuery, 'source_count') && str_contains($revalidationQuery, 'FOR SHARE OF e'), 'Transaction revalidation must lock the input row and reject duplicate source tuples.');

$queryDb->rows[] = playerSourceRow(701);
check($adapter->playerInputEvent($source) === null, 'Duplicate exact input rows must fail closed.');
check($adapter->eventById(700, 'input_700') === null, 'A duplicate tuple must fail transaction-time source revalidation.');
check($adapter->eventById(700, 'input_701') === null, 'Input correlation may not change the row identity.');
$nonWebSource = $source;
$nonWebSource['sess'] = 'game';
check($adapter->playerInputEvent($nonWebSource) === null, 'Non-web input rows must not enter the Player route.');
$unsupportedSource = $source;
$unsupportedSource['type'] = 'chat';
check($adapter->playerInputEvent($unsupportedSource) === null, 'Chat rows must not be downgraded into the Player route.');
if ($priorDb === null) {
    unset($GLOBALS['db']);
} else {
    $GLOBALS['db'] = $priorDb;
}

$oversizedNumber = normalizePlayerInputRow([
    'rowid' => '700', 'type' => 'inputtext', 'ts' => '1730000000000', 'gamets' => '12.5',
    'data' => $base['data'], 'localts' => 1730000001, 'sess' => 'web', 'people' => '|Lydia|', 'source_count' => '2',
]);
check($oversizedNumber === null, 'A source tuple that is no longer unique must not normalize.');

[$event, $subjects, $judgments, $db] = playerStoreFixture();
$records = [];
$log = new RequestLog(static function (string $json) use (&$records): void {
    $records[] = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
}, false);
$beforeSpeaker = serialize($db->npcs[11]);
$beforeSubject = serialize($db->npcs[33]);
same('committed', persistJudgments($event, $subjects, $judgments, $db, $log), 'A validated Player input should commit against its routed NPC listener.');
same(100, $db->npcs[22]['extended_data']->relationships->{'Jarl Balgruuf'}->aff, 'Player gossip should change only the listener-to-subject edge.');
same($beforeSpeaker, serialize($db->npcs[11]), 'The Player must not be looked up or written as a fake NPC speaker.');
same($beforeSubject, serialize($db->npcs[33]), 'The named subject row must remain unchanged.');
same(1, count($db->history), 'A committed Player judgment should keep the existing listener history snapshot.');
$finish = array_values(array_filter($records, static fn(array $record): bool => ($record['event'] ?? null) === 'persistence_finished'))[0] ?? [];
check(($finish['speaker_kind'] ?? null) === 'player' && !isset($finish['speaker_id']), 'Direct persistence logs must retain explicit Player provenance without an NPC ID.');

[$event, $subjects, $judgments, $db] = playerStoreFixture();
$db->events[700]['data'] .= ' changed';
$before = serialize($db->npcs[22]);
same('stale', persistJudgments($event, $subjects, $judgments, $db), 'Source-data drift before transaction revalidation must reject the event.');
same($before, serialize($db->npcs[22]), 'A stale source row must not change listener state.');

[$event, $subjects, $judgments, $db] = playerStoreFixture();
$db->playerName = 'Lydia';
$before = serialize($db->npcs[22]);
same('stale', persistJudgments($event, $subjects, $judgments, $db), 'A changed active Player identity must reject the event.');
same($before, serialize($db->npcs[22]), 'A Player identity mismatch must not change listener state.');

[$event, $subjects, $judgments, $db] = playerStoreFixture();
$event['speaker_id'] = 11;
$before = serialize($db->npcs[22]);
same('invalid', persistJudgments($event, $subjects, $judgments, $db), 'A Player event carrying an NPC ID must be invalid.');
same($before, serialize($db->npcs[22]), 'An invalid Player actor must not change listener state.');

[$event, $subjects, $judgments, $db] = playerStoreFixture();
$db->failSnapshot = true;
$before = serialize($db->npcs[22]);
same('failed', persistJudgments($event, $subjects, $judgments, $db), 'Snapshot failure must roll back a Player-origin relationship update.');
same($before, serialize($db->npcs[22]), 'Failed Player snapshots must restore the original listener state.');

[$event, $subjects, $judgments, $db] = playerStoreFixture();
$db->npcs[22]['extended_data']->relationships->Player = (object)['aff' => 3, 'type' => 'friend'];
$db->npcs[22]['extended_data']->relationships->Dragonborn = (object)['aff' => 7, 'type' => 'ally'];
$before = serialize($db->npcs[22]);
same('failed', persistJudgments($event, $subjects, $judgments, $db), 'Ambiguous Player relationship aliases must fail closed under the listener lock.');
same($before, serialize($db->npcs[22]), 'Ambiguous Player aliases must not mutate listener state.');

[$event, $subjects, $judgments, $db] = playerStoreFixture('|Aela|Farkas|');
$before = serialize($db->npcs[22]);
same('stale', persistJudgments($event, $subjects, $judgments, $db), 'The exact input audience must still include the routed listener under lock.');
same($before, serialize($db->npcs[22]), 'Input without the routed listener must not change listener state.');

$expected = $normalized + [
    'speaker_name' => 'Dragonborn', 'player_name' => 'Dragonborn', 'listener_id' => 22,
    'listener_name' => 'Lydia', 'text' => 'Bruce helped Jarl Balgruuf.', 'playthrough_id' => '1',
];
$current = $expected;
$expected['source_gamets'] = '9007199254740992';
$current['source_gamets'] = '9007199254740993';
$expected['gamets'] = (float)$expected['source_gamets'];
$current['gamets'] = (float)$current['source_gamets'];
check(!sameEvent($expected, $current), 'Adjacent bigint source clocks must not compare equal through float rounding.');

$socket = getenv('PLAYER_STORE_TEST_PG_SOCKET') ?: '';
if ($socket !== '') {
    check(
        preg_match('~\A/tmp/(mp-player-store-[A-Za-z0-9_-]+)/socket\z~D', $socket, $match) === 1,
        'Refusing a PostgreSQL socket outside the Player-store scratch namespace.'
    );
    check(function_exists('pg_connect') && defined('PGSQL_CONNECT_FORCE_NEW'), 'The PostgreSQL PHP extension is required.');
    check(!class_exists('sql', false), 'Run the isolated query fixture in a fresh PHP process.');
    $scratchRoot = '/tmp/' . $match[1];
    $connection = @pg_connect('host=' . $socket . ' port=5432 dbname=postgres user=postgres connect_timeout=3', PGSQL_CONNECT_FORCE_NEW);
    check($connection instanceof \PgSql\Connection, 'Could not connect to the isolated Player-store cluster.');
    $dataDirectoryResult = pg_query($connection, "SELECT current_setting('data_directory') AS path");
    check($dataDirectoryResult instanceof \PgSql\Result, 'Could not inspect the isolated data directory.');
    $dataDirectory = pg_fetch_assoc($dataDirectoryResult)['path'] ?? '';
    pg_free_result($dataDirectoryResult);
    check(realpath($dataDirectory) === realpath($scratchRoot . '/data'), 'Refusing a database outside the Player-store scratch cluster.');
    check(pg_host($connection) === $socket, 'Refusing a non-socket PostgreSQL connection.');
    $existingResult = pg_query($connection, "SELECT to_regclass('public.eventlog') AS eventlog");
    check($existingResult instanceof \PgSql\Result, 'Could not inspect the synthetic schema.');
    $existing = pg_fetch_assoc($existingResult)['eventlog'] ?? null;
    pg_free_result($existingResult);
    check($existing === null, 'The isolated cluster already contains an eventlog fixture.');
    check(pg_query($connection, 'CREATE TABLE public.eventlog (rowid bigint PRIMARY KEY, type text NOT NULL, ts bigint NOT NULL, gamets bigint NOT NULL, data text NOT NULL, localts integer NOT NULL, sess text NOT NULL, people text NOT NULL)') instanceof \PgSql\Result, 'Could not create the synthetic eventlog.');
    $largeClock = '9007199254740993';
    $insert = static function (int $rowid, string $gamets) use ($connection, $largeClock, $base): void {
        $result = pg_query_params(
            $connection,
            'INSERT INTO public.eventlog (rowid, type, ts, gamets, data, localts, sess, people) VALUES ($1, $2, $3, $4, $5, $6, $7, $8)',
            [$rowid, 'inputtext', $largeClock, $gamets, $base['data'], 1730000001, 'web', '|Lydia|Aela|']
        );
        check($result instanceof \PgSql\Result, 'Could not seed a synthetic Player input row.');
        pg_free_result($result);
    };
    $nativePriorDb = $GLOBALS['db'] ?? null;
    try {
        $insert(700, $largeClock);
        $GLOBALS['db'] = new NativeInputQueryDb($connection);
        $nativeAdapter = new PostgresStoreDb();
        $nativeSource = $source;
        $nativeSource['ts'] = $largeClock;
        $nativeSource['gamets'] = $largeClock;
        $nativeEvent = $nativeAdapter->playerInputEvent($nativeSource);
        check(
            is_array($nativeEvent)
                && $nativeEvent['source_ts'] === $largeClock
                && $nativeEvent['source_gamets'] === $largeClock,
            'PostgreSQL must preserve exact bigint source values beyond IEEE-754 precision.'
        );
        $nativeExpected = $nativeEvent + [
            'speaker_name' => 'Dragonborn', 'player_name' => 'Dragonborn', 'listener_id' => 22,
            'listener_name' => 'Lydia', 'text' => 'Bruce helped Jarl Balgruuf.', 'playthrough_id' => '1',
        ];
        $nativeCurrent = $nativeAdapter->eventById(700, 'input_700');
        check(is_array($nativeCurrent) && sameEvent($nativeExpected, $nativeCurrent), 'The exact synthetic input row should revalidate under the native query.');
        $insert(701, $largeClock);
        check($nativeAdapter->playerInputEvent($nativeSource) === null, 'PostgreSQL must reject duplicate exact input tuples.');
        check($nativeAdapter->eventById(700, 'input_700') === null, 'PostgreSQL must reject duplicate tuples on locked row revalidation.');
        check(pg_query($connection, 'DELETE FROM public.eventlog WHERE rowid = 701') instanceof \PgSql\Result, 'Could not remove the synthetic duplicate.');
        $update = pg_query($connection, 'UPDATE public.eventlog SET gamets = 9007199254740994 WHERE rowid = 700');
        check($update instanceof \PgSql\Result, 'Could not mutate the synthetic bigint for exactness verification.');
        pg_free_result($update);
        $changedCurrent = $nativeAdapter->eventById(700, 'input_700');
        check(is_array($changedCurrent) && !sameEvent($nativeExpected, $changedCurrent), 'A one-unit bigint drift must remain visible after PostgreSQL reads it.');
        echo "player store PostgreSQL query checks passed\n";
    } finally {
        if ($nativePriorDb === null) {
            unset($GLOBALS['db']);
        } else {
            $GLOBALS['db'] = $nativePriorDb;
        }
        pg_close($connection);
    }
} else {
    echo "player store PostgreSQL query checks skipped (isolated scratch socket not configured)\n";
}

echo "player store checks passed\n";
