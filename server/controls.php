<?php
declare(strict_types=1);

namespace ChimMindPoisoning;

use Throwable;

const MIND_POISONING_NPC_ACK_OVERHEARING_API_VERSION = 1;

function speechAckInteractionStatus(?string &$reason = null): string
{
    $reason = 'interaction_off';
    if (!function_exists('chimInteractionBegin') || !function_exists('chimInteractionState')) {
        $reason = 'interaction_helpers_unavailable';
        return 'interaction-off';
    }
    try {
        \chimInteractionBegin();
        $requestGeneration = $GLOBALS['chim_interaction_generation'] ?? null;
        $state = \chimInteractionState();
        if (
            !is_int($requestGeneration)
            || !is_array($state)
            || !is_bool($state['enabled'] ?? null)
            || !is_int($state['generation'] ?? null)
        ) {
            $reason = 'interaction_state_invalid';
            return 'interaction-off';
        }
        if ($state['generation'] !== $requestGeneration) {
            $reason = 'interaction_generation_stale';
            return 'interaction-stale';
        }
        if (!$state['enabled']) {
            return 'interaction-off';
        }
        $reason = 'ok';
        return 'ok';
    } catch (Throwable) {
        $reason = 'interaction_state_invalid';
        return 'interaction-off';
    }
}

/** Read the optional operator pause file without executing or caching it. */
function mindPoisoningPauseStatus(?string &$reason = null): string
{
    $reason = null;
    $invalid = static function () use (&$reason): string {
        $reason = 'pause_control_invalid';
        return 'control-invalid';
    };

    $enginePath = $GLOBALS['ENGINE_PATH'] ?? null;
    $driveAbsolute = is_string($enginePath)
        && preg_match('~\A[A-Za-z]:[\\\\/]~D', $enginePath) === 1;
    if (
        !is_string($enginePath)
        || $enginePath === ''
        || str_contains($enginePath, "\0")
        || (
            !str_starts_with($enginePath, '/')
            && !str_starts_with($enginePath, str_repeat(chr(92), 2))
            && !$driveAbsolute
        )
    ) {
        return $invalid();
    }

    $engineRoot = realpath($enginePath);
    if ($engineRoot === false || !is_dir($engineRoot) || !is_readable($engineRoot)) {
        return $invalid();
    }
    $mainPath = $engineRoot . DIRECTORY_SEPARATOR . 'main.php';
    if (!is_file($mainPath)) {
        return $invalid();
    }

    $dataPath = $engineRoot . DIRECTORY_SEPARATOR . 'data';
    clearstatcache(true, $dataPath);
    if (!file_exists($dataPath) && !is_link($dataPath)) {
        return 'enabled';
    }
    if (!is_dir($dataPath) || !is_readable($dataPath)) {
        return $invalid();
    }
    $dataRoot = realpath($dataPath);
    if ($dataRoot === false || !is_dir($dataRoot) || !is_readable($dataRoot)) {
        return $invalid();
    }

    $controlPath = $dataRoot . DIRECTORY_SEPARATOR . 'mind_poisoning.json';
    clearstatcache(true, $controlPath);
    if (!file_exists($controlPath) && !is_link($controlPath)) {
        return 'enabled';
    }
    if (is_link($controlPath) || !is_file($controlPath) || !is_readable($controlPath)) {
        return $invalid();
    }

    $handle = @fopen($controlPath, 'rb');
    if ($handle === false) {
        return $invalid();
    }
    try {
        $stat = fstat($handle);
        $contents = stream_get_contents($handle, 1025);
    } catch (Throwable) {
        $contents = false;
        $stat = false;
    } finally {
        fclose($handle);
    }
    if (
        !is_array($stat)
        || (($stat['mode'] ?? 0) & 0170000) !== 0100000
        || !is_string($contents)
        || strlen($contents) > 1024
    ) {
        return $invalid();
    }

    // json_decode collapses duplicate keys; accept only the documented one-boolean object.
    $controlMatch = [];
    if (preg_match('~\A[ \t\r\n]*\{[ \t\r\n]*"enabled"[ \t\r\n]*:[ \t\r\n]*(true|false)[ \t\r\n]*\}[ \t\r\n]*\z~', $contents, $controlMatch) !== 1) {
        return $invalid();
    }
    if ($controlMatch[1] === 'false') {
        $reason = 'plugin_paused';
        return 'plugin-paused';
    }
    return 'enabled';
}
