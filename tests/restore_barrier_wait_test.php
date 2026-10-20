<?php
declare(strict_types=1);

namespace {
    function restoreBarrierAssert(bool $condition, string $reason): void
    {
        if (!$condition) {
            throw new RuntimeException($reason);
        }
    }

    restoreBarrierAssert(PHP_SAPI === 'cli' && PHP_OS_FAMILY === 'Linux', 'linux-cli-required');
    restoreBarrierAssert(extension_loaded('sysvsem'), 'sysvsem-required');
    restoreBarrierAssert(function_exists('pcntl_fork') && function_exists('pcntl_waitpid'), 'pcntl-required');

    $ipcNamespace = @readlink('/proc/self/ns/ipc');
    $initIpcNamespace = @readlink('/proc/1/ns/ipc');
    restoreBarrierAssert(
        is_string($ipcNamespace)
        && is_string($initIpcNamespace)
        && $ipcNamespace !== $initIpcNamespace,
        'isolated-ipc-namespace-required'
    );

    $semaphorePath = __DIR__ . '/fixtures/semaphore_manager.0ec590b42.php';
    restoreBarrierAssert(
        hash_file('sha256', $semaphorePath) === 'f64d4134d7eeeb5c32908371a445ec98bbb7a5d5eed8c71bb9af83c4ec0761b3',
        'pinned-semaphore-source-hash-mismatch'
    );

    if (!class_exists('Logger', false)) {
        class Logger
        {
            public static function info(string $message): void {}
            public static function warn(string $message): void {}
        }
    }

    require_once $semaphorePath;
    require_once __DIR__ . '/../server/store.php';

    $store = (new ReflectionClass(\ChimMindPoisoning\PostgresStoreDb::class))->newInstanceWithoutConstructor();
    restoreBarrierAssert(\SemaphoreManager::wait('MAIN', 1, 1003), 'fixture-main-lock-acquire-failed');

    $releaseChild = pcntl_fork();
    restoreBarrierAssert($releaseChild !== -1, 'fixture-release-child-fork-failed');
    if ($releaseChild === 0) {
        usleep(2_000_000);
        exit(\SemaphoreManager::release('MAIN') ? 0 : 21);
    }

    $started = microtime(true);
    $acquiredAfterRelease = $store->acquireRestorePolicyBarrier();
    $releaseElapsed = microtime(true) - $started;
    $childStatus = 0;
    $waitedChild = pcntl_waitpid($releaseChild, $childStatus);
    restoreBarrierAssert($waitedChild === $releaseChild, 'fixture-release-child-wait-failed');
    restoreBarrierAssert(pcntl_wifexited($childStatus) && pcntl_wexitstatus($childStatus) === 0, 'fixture-release-child-failed');
    restoreBarrierAssert($acquiredAfterRelease, 'barrier-did-not-wait-for-released-main');
    restoreBarrierAssert($releaseElapsed >= 1.8 && $releaseElapsed < 4.8, 'released-main-wait-outside-bound');

    $store->releaseRestorePolicyBarrier();
    restoreBarrierAssert(\SemaphoreManager::wait('MAIN', 1, 1003), 'fixture-expiry-lock-acquire-failed');
    $started = microtime(true);
    $acquiredWhileHeld = $store->acquireRestorePolicyBarrier();
    $expiryElapsed = microtime(true) - $started;
    restoreBarrierAssert(!$acquiredWhileHeld, 'barrier-acquired-held-main');
    restoreBarrierAssert($expiryElapsed >= 2.5 && $expiryElapsed < 5.0, 'held-main-wait-outside-bound');
    restoreBarrierAssert(\SemaphoreManager::release('MAIN'), 'fixture-expiry-lock-release-failed');

    echo "restore barrier wait test passed (released and expired MAIN waits)\n";
}
