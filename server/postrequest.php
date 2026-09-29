<?php
declare(strict_types=1);

namespace ChimMindPoisoning;

if (!isset($gameRequest) || !is_array($gameRequest)
    || !in_array($gameRequest[0] ?? null, ['inputtext', 'inputtext_s', 'ginputtext', 'ginputtext_s'], true)) {
    return;
}

$chimMindPoisoningPostRequestLog = null;
try {
    @require_once __DIR__ . '/logging.php';
    if (!class_exists(RequestLog::class, false)) {
        throw new \RuntimeException('Request logger is unavailable.');
    }
    $chimMindPoisoningPostRequestLog = new RequestLog();
    $chimMindPoisoningPostRequestLog->context(['speaker_kind' => 'player']);
    $chimMindPoisoningPostRequestStore = new PostgresStoreDb($chimMindPoisoningPostRequestLog);
    handlePlayerInput(
        $gameRequest,
        $GLOBALS['eventlogInsert'] ?? null,
        $chimMindPoisoningPostRequestStore,
        null,
        $chimMindPoisoningPostRequestLog
    );
} catch (\Throwable) {
    if ($chimMindPoisoningPostRequestLog instanceof RequestLog) {
        $chimMindPoisoningPostRequestLog->finish('failed', 'runtime_bootstrap_failed', [
            'stage' => 'bootstrap',
            'speaker_kind' => 'player',
        ]);
    } else {
        @error_log('Mind Poisoning player input failed: runtime_bootstrap_failed');
    }
}
