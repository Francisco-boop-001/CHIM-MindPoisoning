<?php
declare(strict_types=1);

require_once __DIR__ . '/../server/logging.php';

function dashboardIntegrationAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function dashboardIntegrationRequest(string $url): array
{
    $context = stream_context_create(['http' => [
        'method' => 'GET',
        'ignore_errors' => true,
        'timeout' => 3,
    ]]);
    $body = file_get_contents($url, false, $context);
    $headers = $http_response_header ?? [];
    preg_match('/\s(\d{3})\s/', $headers[0] ?? '', $match);
    return [(int)($match[1] ?? 0), $headers, $body === false ? '' : $body];
}

function dashboardIntegrationHeader(array $headers, string $name): ?string
{
    foreach ($headers as $header) {
        if (stripos($header, $name . ':') === 0) {
            return trim(substr($header, strlen($name) + 1));
        }
    }
    return null;
}

function dashboardIntegrationRemoveTree(string $path, string $tempBase): void
{
    $resolvedPath = realpath($path);
    $resolvedTempBase = realpath($tempBase);
    if ($resolvedPath === false || $resolvedTempBase === false
        || !str_starts_with($resolvedPath, $resolvedTempBase . DIRECTORY_SEPARATOR)
        || !str_contains(basename($resolvedPath), 'mp-dashboard-integration-')) {
        throw new RuntimeException('Refusing to remove a path outside this integration fixture.');
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($resolvedPath, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $entry) {
        if ($entry->isDir() && !$entry->isLink()) {
            rmdir($entry->getPathname());
        } else {
            unlink($entry->getPathname());
        }
    }
    rmdir($resolvedPath);
}

$tempBase = realpath(sys_get_temp_dir());
$sourceServer = realpath(__DIR__ . '/../server');
if ($tempBase === false || $sourceServer === false) {
    throw new RuntimeException('The local integration fixture paths are unavailable.');
}

$root = $tempBase . DIRECTORY_SEPARATOR . 'mp-dashboard-integration-' . bin2hex(random_bytes(8));
$pluginDir = $root . '/ext/mind_poisoning';
$process = null;
$pipes = [];
try {
    if (!mkdir($pluginDir, 0777, true) || !mkdir($root . '/log', 0777, true)) {
        throw new RuntimeException('Cannot create the isolated dashboard fixture.');
    }

    foreach ([
        'dashboard.php', 'dashboard_data.php', 'dashboard_view.php', 'dashboard.css',
        'dashboard-art.webp', 'store.php', 'influence.php', 'logging.php', 'manifest.json',
    ] as $file) {
        if (!copy($sourceServer . DIRECTORY_SEPARATOR . $file, $pluginDir . DIRECTORY_SEPARATOR . $file)) {
            throw new RuntimeException('Cannot copy a real dashboard module into the fixture.');
        }
    }

    // The reader must stop before any PostgreSQL connection because no core lib is installed here.
    dashboardIntegrationAssert(!is_file($root . '/lib/postgresql.class.php'), 'Fixture unexpectedly contains the CHIM SQL class.');

    $logLines = [];
    $nativeSink = static function (string $json, string $level) use (&$logLines): void {
        $logLines[] = '[2026-09-27 12:00:00] [' . $level . '] ' . $json;
    };
    $context = [
        'event_id' => 731,
        'utterance_id' => 'utt_fixture1234',
        'playthrough_id' => 42,
        'speaker_id' => 11,
        'listener_id' => 12,
    ];
    $change = ['subject' => 'npc:12', 'delta' => 2, 'before' => 31, 'after' => 33];
    $log = new \ChimMindPoisoning\RequestLog($nativeSink, false);
    $log->context($context);
    $log->event('persistence_finished', 'info', [
        'stage' => 'persistence',
        'persistence_outcome' => 'committed',
        'persistence_reason' => 'committed',
        'commit_state' => 'confirmed',
        'committed' => true,
        'changed_count' => 1,
        'changes' => [$change],
        'persistence_ms' => 4.5,
    ]);
    $log->finish('committed', 'committed', [
        'stage' => 'complete',
        'model_outcome' => 'valid',
        'persistence_outcome' => 'committed',
        'persistence_reason' => 'committed',
        'commit_state' => 'confirmed',
        'committed' => true,
        'changed_count' => 1,
        'changes' => [$change],
        'model_ms' => 12.5,
        'persistence_ms' => 4.5,
    ]);
    $targetRecord = json_decode(preg_replace('/\A\[[^\]]+\]\s+\[[^\]]+\]\s*/', '', $logLines[0]) ?: '', true, 32, JSON_THROW_ON_ERROR);
    $targetRequestId = $targetRecord['request_id'] ?? null;
    dashboardIntegrationAssert(is_string($targetRequestId), 'RequestLog did not emit a request ID.');

    $otherLog = new \ChimMindPoisoning\RequestLog($nativeSink, false);
    $otherLog->context([
        'event_id' => 732,
        'utterance_id' => 'utt_otherfixture123',
        'playthrough_id' => 42,
        'speaker_id' => 11,
        'listener_id' => 12,
    ]);
    $otherLog->finish('skipped', 'interaction_off', ['stage' => 'preflight']);
    $otherRecord = json_decode(preg_replace('/\A\[[^\]]+\]\s+\[[^\]]+\]\s*/', '', $logLines[count($logLines) - 1]) ?: '', true, 32, JSON_THROW_ON_ERROR);
    $otherRequestId = $otherRecord['request_id'] ?? null;
    dashboardIntegrationAssert(is_string($otherRequestId) && $otherRequestId !== $targetRequestId, 'Independent RequestLog fixture did not produce a distinct request ID.');

    $logLines[] = '[2026-09-27 12:00:00] [info] {"plugin":"core","text":"SECRET_CORE_TEXT_SHOULD_NOT_ESCAPE"}';
    if (file_put_contents($root . '/log/chim.log', implode("\n", $logLines) . "\n", LOCK_EX) === false) {
        throw new RuntimeException('Cannot write the isolated synthetic CHIM log.');
    }

    file_put_contents($root . '/router.php', <<<'PHP'
<?php
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($path === '/ext/mind_poisoning/dashboard.php') {
    require __DIR__ . '/ext/mind_poisoning/dashboard.php';
    return true;
}
return false;
PHP);

    $reserved = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
    if ($reserved === false) {
        throw new RuntimeException('Cannot reserve a loopback fixture port.');
    }
    $address = stream_socket_get_name($reserved, false);
    fclose($reserved);
    $port = (int)substr(strrchr((string)$address, ':'), 1);
    $pipes = [];
    $process = proc_open(
        [PHP_BINARY, '-S', '127.0.0.1:' . $port, $root . '/router.php'],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        $root
    );
    if (!is_resource($process)) {
        throw new RuntimeException('Cannot start the loopback dashboard fixture server.');
    }
    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);
    $baseUrl = 'http://127.0.0.1:' . $port;
    $ready = false;
    for ($attempt = 0; $attempt < 60; $attempt++) {
        $socket = @stream_socket_client('tcp://127.0.0.1:' . $port, $errorCode, $errorMessage, 0.1);
        if ($socket !== false) {
            fclose($socket);
            $ready = true;
            break;
        }
        usleep(50000);
    }
    if (!$ready) {
        throw new RuntimeException('Loopback dashboard fixture server did not become ready.');
    }

    $pageUrl = $baseUrl . '/ext/mind_poisoning/dashboard.php';
    [$status, $headers, $html] = dashboardIntegrationRequest($pageUrl . '?tab=interactions');
    dashboardIntegrationAssert($status === 200, 'Real dashboard controller failed to render the interaction page.');
    dashboardIntegrationAssert(str_contains($html, '<html lang="en" data-theme="day">'), 'Real renderer did not use the controller’s default day theme.');
    foreach ([
        'Showing log-only history', 'Current affinity is unavailable', 'Before 31', 'After 33',
        'Proposed', 'Applied', 'Current', 'Unverified history',
    ] as $expected) {
        dashboardIntegrationAssert(str_contains($html, $expected), 'Rendered interaction page omitted expected log-only evidence: ' . $expected);
    }
    dashboardIntegrationAssert(str_contains($html, '<div><dt>Current</dt><dd class="numeric">Unavailable</dd></div>'), 'Rendered interaction did not mark current affinity unavailable.');
    dashboardIntegrationAssert(str_contains($html, '<div><dt>Proposed</dt><dd class="numeric">+2</dd></div>'), 'Rendered interaction did not show the logged proposal.');
    dashboardIntegrationAssert(str_contains($html, '<div><dt>Applied</dt><dd class="numeric">+2</dd></div>'), 'Rendered interaction did not show the confirmed applied change.');
    dashboardIntegrationAssert(dashboardIntegrationHeader($headers, 'Content-Type') === 'text/html; charset=UTF-8', 'Interaction response has the wrong content type.');
    $csp = (string)dashboardIntegrationHeader($headers, 'Content-Security-Policy');
    dashboardIntegrationAssert(str_contains($csp, "style-src 'self'") && str_contains($csp, "img-src 'self'") && str_contains($csp, "script-src 'none'"), 'Page CSP did not retain self-only artwork/style and disabled scripts.');

    [$status, $headers, $css] = dashboardIntegrationRequest($baseUrl . '/ext/mind_poisoning/dashboard.css');
    dashboardIntegrationAssert($status === 200 && str_contains($css, '.dashboard-shell'), 'Real dashboard stylesheet route did not serve local CSS.');
    dashboardIntegrationAssert(str_starts_with((string)dashboardIntegrationHeader($headers, 'Content-Type'), 'text/css'), 'Stylesheet response has the wrong content type.');

    [$status, $headers, $art] = dashboardIntegrationRequest($baseUrl . '/ext/mind_poisoning/dashboard-art.webp');
    dashboardIntegrationAssert($status === 200 && str_starts_with($art, 'RIFF') && substr($art, 8, 4) === 'WEBP', 'Same-origin dashboard artwork was not served as WebP.');
    dashboardIntegrationAssert(str_starts_with((string)dashboardIntegrationHeader($headers, 'Content-Type'), 'image/webp'), 'Dashboard artwork response has the wrong content type.');

    $diagnosticQuery = '?tab=diagnostics&theme=day&q=' . rawurlencode($targetRequestId) . '&outcome=committed&level=info';
    [$status, , $diagnostics] = dashboardIntegrationRequest($pageUrl . $diagnosticQuery);
    dashboardIntegrationAssert($status === 200, 'Real dashboard controller failed to render diagnostics.');
    dashboardIntegrationAssert(str_contains($diagnostics, $targetRequestId), 'Diagnostics filter omitted the matching RequestLog ID.');
    dashboardIntegrationAssert(!str_contains($diagnostics, $otherRequestId), 'Diagnostics q filter included an unrelated RequestLog ID.');
    dashboardIntegrationAssert(!str_contains($diagnostics, 'SECRET_CORE_TEXT_SHOULD_NOT_ESCAPE'), 'Diagnostics exposed unrelated core log text.');
    dashboardIntegrationAssert(str_contains($diagnostics, '<input type="hidden" name="theme" value="day">'), 'Diagnostic filter form did not preserve the selected theme.');
    dashboardIntegrationAssert(preg_match('/<a href="([^"]+)"[^>]*>Night<\/a>/', $diagnostics, $nightLink) === 1, 'Real renderer omitted the explicit night-mode link.');
    $nightUrl = html_entity_decode($nightLink[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    parse_str((string)parse_url($nightUrl, PHP_URL_QUERY), $nightQuery);
    dashboardIntegrationAssert(($nightQuery['tab'] ?? null) === 'diagnostics'
        && ($nightQuery['theme'] ?? null) === 'night'
        && ($nightQuery['q'] ?? null) === $targetRequestId
        && ($nightQuery['outcome'] ?? null) === 'committed'
        && ($nightQuery['level'] ?? null) === 'info', 'Night-mode link did not preserve the active tab and filters.');

    [$status, $headers, $download] = dashboardIntegrationRequest($pageUrl . '?tab=diagnostics&theme=night&q=' . rawurlencode($targetRequestId) . '&outcome=committed&level=info&download=1');
    dashboardIntegrationAssert($status === 200, 'Filtered diagnostics download failed.');
    dashboardIntegrationAssert(dashboardIntegrationHeader($headers, 'Content-Type') === 'application/x-ndjson; charset=UTF-8', 'Diagnostics download has the wrong content type.');
    dashboardIntegrationAssert(str_contains((string)dashboardIntegrationHeader($headers, 'Content-Disposition'), 'mind-poisoning-diagnostics.jsonl'), 'Diagnostics download filename is not fixed.');
    dashboardIntegrationAssert(str_contains($download, $targetRequestId), 'Filtered JSONL omitted the matching RequestLog ID.');
    dashboardIntegrationAssert(!str_contains($download, $otherRequestId), 'Filtered JSONL included an unrelated RequestLog ID.');
    dashboardIntegrationAssert(!str_contains($download, 'SECRET_CORE_TEXT_SHOULD_NOT_ESCAPE'), 'Filtered JSONL exposed unrelated core log text.');
    dashboardIntegrationAssert(substr_count(trim($download), "\n") === 1, 'Filtered JSONL did not contain exactly the two matching request records.');

    [$status, $headers, $invalid] = dashboardIntegrationRequest($pageUrl . '?tab=not-a-tab');
    dashboardIntegrationAssert($status === 400, 'Invalid filter did not return HTTP 400.');
    dashboardIntegrationAssert($invalid === "Invalid dashboard filters.\n", 'Invalid filter response was not the fixed safe message.');
    dashboardIntegrationAssert(dashboardIntegrationHeader($headers, 'Content-Type') === 'text/plain; charset=UTF-8', 'Invalid filter response has the wrong content type.');

    echo "dashboard_integration_test: ok\n";
} finally {
    if (is_resource($process)) {
        proc_terminate($process);
        foreach ([1, 2] as $index) {
            if (isset($pipes[$index]) && is_resource($pipes[$index])) {
                fclose($pipes[$index]);
            }
        }
        proc_close($process);
    }
    if (is_dir($root)) {
        dashboardIntegrationRemoveTree($root, $tempBase);
    }
}
