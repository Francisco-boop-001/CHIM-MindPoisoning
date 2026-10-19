<?php
declare(strict_types=1);

namespace {
    final class Logger
    {
        public static array $records = [];

        public static function debug(string $message): void { self::$records[] = $message; }
        public static function info(string $message): void { self::$records[] = $message; }
        public static function warn(string $message): void { self::$records[] = $message; }
        public static function error(string $message): void { self::$records[] = $message; }
    }
}

namespace ChimMindPoisoning {
    require_once __DIR__ . '/../server/logging.php';

    final class PostgresStoreDb
    {
        public static int $constructed = 0;

        public function __construct(?RequestLog $requestLog = null, mixed $trustedPgConnection = null)
        {
            self::$constructed++;
        }
    }

    function handlePlayerInput(...$args): void
    {
        $GLOBALS['written_hook_order'][] = 'player_input';
        if (!empty($GLOBALS['written_hook_throw_player'])) {
            throw new \RuntimeException('synthetic ordinary MP failure');
        }
    }

    function drainWrittenEvidenceHints($store = null, $requestModel = null): array
    {
        $GLOBALS['written_hook_order'][] = 'drain';
        $GLOBALS['written_hook_drain_calls'][] = ['store' => $store];
        return [];
    }
}

namespace {
    use ChimMindPoisoning\PostgresStoreDb;
    use ChimMindPoisoning\RequestLog;

    function writtenHookCheck(bool $condition, string $message): void
    {
        if (!$condition) {
            $GLOBALS['written_hook_failures'][] = $message;
        }
    }

    function runWrittenPostrequest(array $request): void
    {
        $gameRequest = $request;
        include __DIR__ . '/../server/postrequest.php';
    }

    $globalsSource = file_get_contents(__DIR__ . '/../server/globals.php');
    $packageSource = file_get_contents(__DIR__ . '/../scripts/package.py');
    $GLOBALS['written_hook_failures'] = [];
    writtenHookCheck(
        is_string($globalsSource) && str_contains($globalsSource, "require_once __DIR__ . '/written_evidence.php';"),
        'globals.php must expose the versioned written-evidence API before companion hooks run.'
    );
    writtenHookCheck(
        is_string($packageSource) && str_contains($packageSource, '"written_evidence.php"'),
        'The package source allowlist must ship the written-evidence API file.'
    );

    $GLOBALS['written_hook_order'] = [];
    $GLOBALS['written_hook_drain_calls'] = [];
    $GLOBALS['MIND_POISONING_WRITTEN_EVIDENCE_HINTS'] = [101];
    runWrittenPostrequest(['funcret', 'native-result']);
    writtenHookCheck(
        $GLOBALS['written_hook_order'] === ['drain'] && count($GLOBALS['written_hook_drain_calls']) === 1,
        'A non-Player request must drain its request-local written hint before the old early return.'
    );
    writtenHookCheck(
        count($GLOBALS['written_hook_drain_calls']) === 1
            && $GLOBALS['written_hook_drain_calls'][0]['store'] === null,
        'The hook must use the existing native StoreDb bootstrap, never a request-supplied connection.'
    );

    $GLOBALS['written_hook_order'] = [];
    $GLOBALS['written_hook_drain_calls'] = [];
    runWrittenPostrequest(['inputtext', 'ordinary player message']);
    writtenHookCheck(
        $GLOBALS['written_hook_order'] === ['player_input', 'drain'],
        'Player input must finish ordinary MP handling before the one written-hint drain.'
    );

    $GLOBALS['written_hook_order'] = [];
    $GLOBALS['written_hook_drain_calls'] = [];
    $GLOBALS['written_hook_throw_player'] = true;
    runWrittenPostrequest(['inputtext', 'ordinary player message']);
    unset($GLOBALS['written_hook_throw_player']);
    writtenHookCheck(
        $GLOBALS['written_hook_order'] === ['player_input', 'drain'],
        'A caught ordinary MP failure must not skip the independent written-hint drain.'
    );

    $GLOBALS['written_hook_order'] = [];
    $GLOBALS['written_hook_drain_calls'] = [];
    runWrittenPostrequest(['INIT']);
    writtenHookCheck(
        $GLOBALS['written_hook_order'] === [] && $GLOBALS['written_hook_drain_calls'] === [],
        'INIT must never drain written evidence across a scope reset.'
    );

    $records = [];
    $log = new RequestLog(static function (string $json) use (&$records): void {
        $records[] = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }, true);
    $log->context([
        'source_kind' => 'written_evidence',
        'event_id' => 444,
        'playthrough_id' => '7',
        'opinion_owner_id' => 22,
        'speaker_kind' => 'npc',
        'speaker_id' => 99,
        'listener_id' => 22,
        'utterance_id' => 'utt_forged_written_source',
        'source_type' => 'written_evidence',
        'source_sess' => 'written_evidence_v1',
        'source_digest' => str_repeat('a', 64),
        'source_data' => 'private written body must not be logged',
        'body' => 'private written body must not be logged',
        'api_key' => 'credential must not be logged',
        'model_reason' => 'private explanation may quote the body',
    ]);
    $log->event('written_evidence_started', 'info');
    $log->event('written_evidence_detail', 'debug', ['model_reason' => 'private explanation may quote the body']);
    $log->finish('committed', 'written_evidence_committed', [
        'commit_state' => 'confirmed',
        'committed' => true,
        'source_digest' => str_repeat('b', 64),
        'source_data' => 'private written body must not be logged',
        'api_key' => 'credential must not be logged',
    ]);
    $serializedRecords = json_encode($records, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    writtenHookCheck(
        ($records[0]['source_kind'] ?? null) === 'written_evidence'
            && ($records[0]['event_id'] ?? null) === '444'
            && ($records[0]['playthrough_id'] ?? null) === '7'
            && ($records[0]['opinion_owner_id'] ?? null) === '22',
        'Written logs should keep only their closed source kind and fixed numeric IDs.'
    );
    writtenHookCheck(
        !str_contains($serializedRecords, 'private written body')
            && !str_contains($serializedRecords, 'credential must not be logged')
            && !str_contains($serializedRecords, 'source_digest')
            && !str_contains($serializedRecords, 'source_data')
            && !str_contains($serializedRecords, 'speaker_id')
            && !str_contains($serializedRecords, 'speaker_kind')
            && !str_contains($serializedRecords, 'listener_id')
            && !str_contains($serializedRecords, 'utterance_id')
            && !str_contains($serializedRecords, 'source_type')
            && !str_contains($serializedRecords, 'source_sess')
            && !str_contains($serializedRecords, 'model_reason'),
        'Written source logs must not expose body, digest, credentials, source provenance, speaker, or utterance data.'
    );

    if ($GLOBALS['written_hook_failures'] !== []) {
        foreach ($GLOBALS['written_hook_failures'] as $failure) {
            fwrite(STDERR, "FAIL: {$failure}\n");
        }
        exit(1);
    }
    echo "written hook/privacy tests passed\n";
}
