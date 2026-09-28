<?php
declare(strict_types=1);

use function ChimMindPoisoning\dashboardReadLogs;

require_once __DIR__ . '/../server/dashboard_data.php';

$server = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mp-dashboard-log-' . bin2hex(random_bytes(6));
if (!mkdir($server) || !mkdir($server . DIRECTORY_SEPARATOR . 'log')) {
    throw new RuntimeException('Could not create an isolated dashboard log fixture.');
}
$log = $server . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . 'chim.log';

try {
    file_put_contents($log, '');
    $empty = dashboardReadLogs($server);
    if ($empty !== ['available' => true, 'limited' => false, 'records' => []]) {
        throw new RuntimeException('An empty readable log was incorrectly reported as truncated.');
    }

    file_put_contents($log, '[unfinished entry');
    $partial = dashboardReadLogs($server);
    if ($partial !== ['available' => true, 'limited' => true, 'records' => []]) {
        throw new RuntimeException('An unfinished log line was not reported as truncated.');
    }
} finally {
    if (is_file($log)) {
        unlink($log);
    }
    rmdir($server . DIRECTORY_SEPARATOR . 'log');
    rmdir($server);
}

echo "dashboard empty-log checks passed\n";
