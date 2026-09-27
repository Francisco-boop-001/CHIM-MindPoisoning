<?php
declare(strict_types=1);

use function ChimMindPoisoning\dashboardFilters;
use function ChimMindPoisoning\dashboardLoad;
use function ChimMindPoisoning\renderDashboard;

(static function (): void {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
    header('X-Frame-Options: SAMEORIGIN');
    header("Content-Security-Policy: default-src 'none'; style-src 'self'; script-src 'none'; img-src 'self'; form-action 'self'; base-uri 'none'; object-src 'none'; frame-ancestors 'self'");

    $sendFixed = static function (int $status, string $contentType, string $body, array $headers = []): void {
        http_response_code($status);
        header('Content-Type: ' . $contentType);
        foreach ($headers as $header) {
            header($header);
        }
        echo $body;
    };

    $remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    $remoteUser = $_SERVER['REMOTE_USER'] ?? '';
    $hasForwardedAddress = array_key_exists('HTTP_FORWARDED', $_SERVER)
        || array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)
        || array_key_exists('HTTP_X_REAL_IP', $_SERVER);
    $isLoopback = is_string($remoteAddress)
        && in_array($remoteAddress, ['127.0.0.1', '::1'], true)
        && !$hasForwardedAddress;
    $hasAuthenticatedUser = is_string($remoteUser) && trim($remoteUser) !== '';
    if (!$isLoopback && !$hasAuthenticatedUser) {
        $sendFixed(403, 'text/plain; charset=UTF-8', "Forbidden.\n");
        return;
    }

    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
        $sendFixed(405, 'text/plain; charset=UTF-8', "Method not allowed.\n", ['Allow: GET']);
        return;
    }

    try {
        require_once __DIR__ . '/dashboard_data.php';
        require_once __DIR__ . '/dashboard_view.php';
    } catch (Throwable $error) {
        $sendFixed(503, 'text/plain; charset=UTF-8', "Dashboard is unavailable.\n");
        return;
    }

    try {
        $filters = dashboardFilters($_GET);
        $theme = $_GET['theme'] ?? 'day';
        if (!is_string($theme) || !in_array($theme, ['day', 'night'], true)) {
            throw new InvalidArgumentException('Invalid dashboard theme.');
        }
        $filters['theme'] = $theme;
    } catch (InvalidArgumentException $error) {
        $sendFixed(400, 'text/plain; charset=UTF-8', "Invalid dashboard filters.\n");
        return;
    } catch (Throwable $error) {
        $sendFixed(500, 'text/plain; charset=UTF-8', "Dashboard request failed.\n");
        return;
    }

    $hasDownload = array_key_exists('download', $_GET);
    if ($hasDownload && ($_GET['download'] !== '1' || ($filters['tab'] ?? null) !== 'diagnostics')) {
        $sendFixed(400, 'text/plain; charset=UTF-8', "Invalid dashboard download request.\n");
        return;
    }

    try {
        $model = dashboardLoad(dirname(__DIR__, 2), $filters);
    } catch (Throwable $error) {
        $sendFixed(503, 'text/plain; charset=UTF-8', "Dashboard data is unavailable.\n");
        return;
    }

    if ($hasDownload) {
        if (!is_array($model)
            || !is_array($model['source'] ?? null)
            || ($model['source']['logs'] ?? null) !== 'available'
            || !is_array($model['records'] ?? null)
            || count($model['records']) > 1000) {
            $sendFixed(503, 'text/plain; charset=UTF-8', "Diagnostic records are unavailable.\n");
            return;
        }

        try {
            $jsonl = '';
            foreach ($model['records'] as $record) {
                if (!is_array($record)) {
                    throw new UnexpectedValueException('Invalid diagnostic record.');
                }
                $line = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                $jsonl .= $line . "\n";
            }
        } catch (Throwable $error) {
            $sendFixed(503, 'text/plain; charset=UTF-8', "Diagnostic records are unavailable.\n");
            return;
        }

        http_response_code(200);
        header('Content-Type: application/x-ndjson; charset=UTF-8');
        header('Content-Disposition: attachment; filename="mind-poisoning-diagnostics.jsonl"');
        echo $jsonl;
        return;
    }

    if (!is_array($model) || !is_array($filters)) {
        $sendFixed(503, 'text/plain; charset=UTF-8', "Dashboard data is unavailable.\n");
        return;
    }

    ob_start();
    try {
        renderDashboard($model, $filters);
        $html = ob_get_clean();
    } catch (Throwable $error) {
        ob_end_clean();
        $sendFixed(500, 'text/plain; charset=UTF-8', "Dashboard rendering failed.\n");
        return;
    }

    if (!is_string($html)) {
        $sendFixed(500, 'text/plain; charset=UTF-8', "Dashboard rendering failed.\n");
        return;
    }
    http_response_code(200);
    header('Content-Type: text/html; charset=UTF-8');
    echo $html;
})();
