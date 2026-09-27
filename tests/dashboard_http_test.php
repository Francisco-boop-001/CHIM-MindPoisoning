<?php
declare(strict_types=1);

function dashboardAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function dashboardRequest(string $url, string $method = 'GET', array $headers = []): array
{
    $context = stream_context_create(['http' => [
        'method' => $method,
        'header' => implode("\r\n", $headers),
        'ignore_errors' => true,
        'timeout' => 3,
    ]]);
    $body = file_get_contents($url, false, $context);
    $responseHeaders = $http_response_header ?? [];
    preg_match('/\s(\d{3})\s/', $responseHeaders[0] ?? '', $match);
    return [(int)($match[1] ?? 0), $responseHeaders, $body === false ? '' : $body];
}

function dashboardHeader(array $headers, string $name): ?string
{
    foreach ($headers as $header) {
        if (stripos($header, $name . ':') === 0) {
            return trim(substr($header, strlen($name) + 1));
        }
    }
    return null;
}

function dashboardStartServer(string $routerPath, string $root, string $remoteAddress, ?string $remoteUser): array
{
    $reserved = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
    if ($reserved === false) {
        throw new RuntimeException('Cannot reserve a local fixture port.');
    }
    $address = stream_socket_get_name($reserved, false);
    fclose($reserved);
    $port = (int)substr(strrchr((string)$address, ':'), 1);

    $environment = getenv();
    if (!is_array($environment)) {
        $environment = [];
    }
    $environment['MP_DASHBOARD_FIXTURE_ADDR'] = $remoteAddress;
    if ($remoteUser === null) {
        unset($environment['MP_DASHBOARD_FIXTURE_USER']);
    } else {
        $environment['MP_DASHBOARD_FIXTURE_USER'] = $remoteUser;
    }
    $environment['MP_DASHBOARD_FIXTURE_MARKER'] = $root . '/reader';

    $pipes = [];
    $process = proc_open(
        [PHP_BINARY, '-S', '127.0.0.1:' . $port, $routerPath],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        $root,
        $environment
    );
    if (!is_resource($process)) {
        throw new RuntimeException('Cannot start the local dashboard fixture server.');
    }
    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    $url = 'http://127.0.0.1:' . $port . '/dashboard.php';
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
        proc_terminate($process);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
        throw new RuntimeException('Local dashboard fixture server did not become ready.');
    }
    return [$process, $pipes, $url];
}

function dashboardStopServer($process, array $pipes): void
{
    proc_terminate($process);
    foreach ([1, 2] as $index) {
        if (isset($pipes[$index]) && is_resource($pipes[$index])) {
            fclose($pipes[$index]);
        }
    }
    proc_close($process);
}

function dashboardRemoveTree(string $path, string $tempRoot): void
{
    $resolvedPath = realpath($path);
    $resolvedTemp = realpath($tempRoot);
    if ($resolvedPath === false || $resolvedTemp === false
        || !str_starts_with($resolvedPath, $resolvedTemp . DIRECTORY_SEPARATOR)) {
        throw new RuntimeException('Refusing to remove a path outside this test fixture.');
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
if ($tempBase === false) {
    throw new RuntimeException('Temporary directory is unavailable.');
}
$root = $tempBase . DIRECTORY_SEPARATOR . 'mp-dashboard-' . bin2hex(random_bytes(8));
$pluginDir = $root . '/ext/mind_poisoning';
mkdir($pluginDir, 0777, true);
copy(__DIR__ . '/../server/dashboard.php', $pluginDir . '/dashboard.php');

file_put_contents($pluginDir . '/dashboard_data.php', <<<'PHP'
<?php
namespace ChimMindPoisoning;

$marker = getenv('MP_DASHBOARD_FIXTURE_MARKER');
if (is_string($marker)) file_put_contents($marker . '.included', "included\n", FILE_APPEND | LOCK_EX);

function dashboardFilters(array $query): array
{
    $tab = $query['tab'] ?? 'interactions';
    if (!is_string($tab) || !in_array($tab, ['interactions', 'diagnostics'], true)) {
        throw new \InvalidArgumentException('SECRET_FILTER_DETAIL');
    }
    $q = $query['q'] ?? '';
    if (!is_string($q) || strlen($q) > 120 || !preg_match('//u', $q)) {
        throw new \InvalidArgumentException('SECRET_FILTER_DETAIL');
    }
    return ['tab' => $tab, 'q' => $q, 'outcome' => '', 'level' => ''];
}

function dashboardLoad(string $serverRoot, array $filters): array
{
    $marker = getenv('MP_DASHBOARD_FIXTURE_MARKER');
    if (is_string($marker)) file_put_contents($marker . '.loaded', "loaded\n", FILE_APPEND | LOCK_EX);
    if (($filters['q'] ?? '') === 'fail') throw new \RuntimeException('SECRET_DATA_DETAIL');
    $logs = (($filters['q'] ?? '') === 'unavailable') ? 'unavailable' : 'available';
    return [
        'version' => 'fixture',
        'generated_at' => '2026-09-27T00:00:00Z',
        'notices' => [],
        'source' => ['logs' => $logs, 'database' => 'available', 'limited' => false],
        'interactions' => [],
        'records' => [[
            'event' => 'request_finished',
            'level' => 'info',
            'request_id' => 'req_0123456789abcdef',
            'utterance_id' => 'utt_abcdefgh',
            'outcome' => 'committed',
        ]],
    ];
}
PHP
);

file_put_contents($pluginDir . '/dashboard_view.php', <<<'PHP'
<?php
namespace ChimMindPoisoning;

function renderDashboard(array $model, array $filters): void
{
    $theme = htmlspecialchars((string)($filters['theme'] ?? 'day'), ENT_QUOTES, 'UTF-8');
    echo '<!doctype html><html><head><title>Mind Poisoning fixture</title></head>'
        . '<body data-theme="' . $theme . '"><main>Dashboard fixture</main></body></html>';
}
PHP
);

file_put_contents($root . '/router.php', <<<'PHP'
<?php
$address = getenv('MP_DASHBOARD_FIXTURE_ADDR');
if (is_string($address)) $_SERVER['REMOTE_ADDR'] = $address;
$user = getenv('MP_DASHBOARD_FIXTURE_USER');
if (is_string($user) && trim($user) !== '') $_SERVER['REMOTE_USER'] = $user;
else unset($_SERVER['REMOTE_USER']);
require __DIR__ . '/ext/mind_poisoning/dashboard.php';
PHP
);

try {
    [$deniedProcess, $deniedPipes, $deniedUrl] = dashboardStartServer($root . '/router.php', $root, '203.0.113.9', null);
    try {
        [$status, $headers, $body] = dashboardRequest($deniedUrl . '?tab=diagnostics', 'GET', [
            'X-Forwarded-For: 127.0.0.1',
            'Remote-User: spoofed-user',
        ]);
        dashboardAssert($status === 403, 'Remote request with spoofed identity headers was not denied.');
        dashboardAssert($body === "Forbidden.\n", 'Denied response was not fixed and generic.');
        dashboardAssert(!is_file($root . '/reader.included') && !is_file($root . '/reader.loaded'), 'Denied request loaded or called dashboard data code.');
    } finally {
        dashboardStopServer($deniedProcess, $deniedPipes);
    }

    [$localProcess, $localPipes, $localUrl] = dashboardStartServer($root . '/router.php', $root, '127.0.0.1', null);
    try {
        [$status, $headers, $body] = dashboardRequest($localUrl . '?tab=diagnostics', 'GET', [
            'X-Forwarded-For: 198.51.100.4',
        ]);
        dashboardAssert($status === 403, 'Loopback request carrying a forwarded-address header received the local exemption.');
        dashboardAssert(!is_file($root . '/reader.included') && !is_file($root . '/reader.loaded'), 'Forwarded loopback request loaded or called dashboard data code.');

        [$status, $headers, $body] = dashboardRequest($localUrl . '?tab=interactions');
        dashboardAssert($status === 200 && str_contains($body, 'Dashboard fixture'), 'Loopback request without REMOTE_USER was not allowed.');
        dashboardAssert(str_contains((string)dashboardHeader($headers, 'Cache-Control'), 'no-store'), 'Page omitted no-store cache control.');
        dashboardAssert(dashboardHeader($headers, 'X-Content-Type-Options') === 'nosniff', 'Page omitted nosniff.');
        $csp = (string)dashboardHeader($headers, 'Content-Security-Policy');
        dashboardAssert(str_contains($csp, "script-src 'none'"), 'Page CSP did not disable scripts.');
        dashboardAssert(str_contains($csp, "frame-ancestors 'self'"), 'Page CSP did not constrain framing.');
        dashboardAssert(str_contains($csp, "style-src 'self'") && str_contains($csp, "img-src 'self'"), 'Page CSP did not allow only same-origin style and artwork assets.');
        dashboardAssert(str_contains($body, 'data-theme="day"'), 'Theme did not default to day mode.');

        [$status, $headers, $body] = dashboardRequest($localUrl . '?tab=interactions&theme=night&q=preserve-me');
        dashboardAssert($status === 200 && str_contains($body, 'data-theme="night"'), 'Controller did not pass normalized night theme to the renderer.');

        $loadedBefore = is_file($root . '/reader.loaded') ? count(file($root . '/reader.loaded')) : 0;
        [$status, $headers, $body] = dashboardRequest($localUrl . '?tab=unknown');
        dashboardAssert($status === 400 && $body === "Invalid dashboard filters.\n", 'Malformed filter did not return a fixed 400 response.');
        dashboardAssert(!str_contains($body, 'SECRET_FILTER_DETAIL'), 'Filter exception detail leaked.');
        $loadedAfter = is_file($root . '/reader.loaded') ? count(file($root . '/reader.loaded')) : 0;
        dashboardAssert($loadedAfter === $loadedBefore, 'Invalid filters triggered a data load.');

        [$status, $headers, $body] = dashboardRequest($localUrl . '?tab=interactions&theme=nightmare');
        dashboardAssert($status === 400 && $body === "Invalid dashboard filters.\n", 'Unsupported theme did not return a fixed 400 response.');
        $themeLoadedAfter = is_file($root . '/reader.loaded') ? count(file($root . '/reader.loaded')) : 0;
        dashboardAssert($themeLoadedAfter === $loadedAfter, 'Unsupported theme triggered a data load.');

        [$status, $headers, $body] = dashboardRequest($localUrl . '?tab=diagnostics&download=2');
        dashboardAssert($status === 400 && !str_contains($body, 'SECRET'), 'Non-exact download flag was accepted or leaked details.');

        [$status, $headers, $body] = dashboardRequest($localUrl . '?tab=diagnostics&q=fail');
        dashboardAssert($status === 503 && $body === "Dashboard data is unavailable.\n", 'Unexpected data failure did not return a fixed unavailable response.');
        dashboardAssert(!str_contains($body, 'SECRET_DATA_DETAIL'), 'Data exception detail leaked.');

        [$status, $headers, $body] = dashboardRequest($localUrl . '?tab=diagnostics&download=1&q=unavailable');
        dashboardAssert($status === 503 && !str_contains($body, 'SECRET'), 'Unavailable log source produced a download.');
        dashboardAssert(dashboardHeader($headers, 'Content-Disposition') === null, 'Unavailable log source set attachment headers.');

        [$status, $headers, $body] = dashboardRequest($localUrl, 'POST');
        dashboardAssert($status === 405 && dashboardHeader($headers, 'Allow') === 'GET', 'Non-GET method was not rejected.');
    } finally {
        dashboardStopServer($localProcess, $localPipes);
    }

    [$authProcess, $authPipes, $authUrl] = dashboardStartServer($root . '/router.php', $root, '203.0.113.10', 'authenticated-fixture-user');
    try {
        [$status, $headers, $body] = dashboardRequest($authUrl . '?tab=diagnostics&download=1');
        dashboardAssert($status === 200, 'Server-authenticated remote request was not allowed.');
        dashboardAssert(str_starts_with((string)dashboardHeader($headers, 'Content-Type'), 'application/x-ndjson'), 'Download has an unexpected content type.');
        dashboardAssert(dashboardHeader($headers, 'Content-Disposition') === 'attachment; filename="mind-poisoning-diagnostics.jsonl"', 'Download filename was not fixed.');
        dashboardAssert(substr_count(trim($body), "\n") === 0, 'Expected one bounded JSONL record.');
        $record = json_decode(trim($body), true, 16, JSON_THROW_ON_ERROR);
        dashboardAssert(($record['event'] ?? null) === 'request_finished' && ($record['outcome'] ?? null) === 'committed', 'Download omitted the sanitized fixture record.');
        dashboardAssert(!str_contains($body, 'raw speech') && !str_contains($body, 'whole CHIM log') && !str_contains($body, 'SECRET'), 'Download included data outside sanitized records.');
    } finally {
        dashboardStopServer($authProcess, $authPipes);
    }

    echo "dashboard_http_test: ok\n";
} finally {
    dashboardRemoveTree($root, $tempBase);
}
