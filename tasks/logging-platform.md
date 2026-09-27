# CHIM logging platform evidence

Read-only source inspection of the installed HerikaServer at /var/www/html/HerikaServer on 2026-09-27. No server PHP, endpoint, database, provider, or configuration was executed or changed.

## Native sink and filtering

- lib/logger.php:4 sets Logger's default file to /var/www/html/HerikaServer/log/chim.log. ui/index.php:21 defines LOG_PATH as BASE_PATH/log; ui/api/chim_debugger_logs.php:42 exposes chim.log as the chim debugger log.
- lib/logger.php:111-129 provides Logger::trace, debug, info, warn, and error. Line 15 defaults the process-wide threshold to trace; lines 36-42 expose setLevel() for trace/debug/info/warn/error. No setLevel() call was found in installed first-party PHP outside the method/comment, but the logger itself can still filter below a configured threshold. Plugin code must use the existing methods and must not change global level.
- lib/logger.php:98-107 writes the default stream with error_log(..., 3, $logFile). Warning and error entries are also sent through ordinary PHP error_log(), so they are duplicated to the PHP/Apache error sink when the native logger is loaded. PHP Apache configuration has log_errors = On in /etc/php/8.2/apache2/php.ini:527; the explicit error_log directive is commented out at :595.
- Logger::setCustomLog() changes the static process-wide default destination (lib/logger.php:27-34,98-100). The only call found is service/manager.php:28, which redirects the manager to log/manager.log. A plugin should not call this API.

## Size handling and rotation

- Logger::deleteLogIfTooLarge() uses a 25 MiB threshold and truncates the file with /dev/null; it does not archive or rotate (lib/logger.php:179-185).
- ui/index.php:150-163 removes *.txt files and calls that helper for each *.log in LOG_PATH when the UI index path runs. service/manager.php:29 applies the same truncation to manager.log at manager startup.
- The installed OS rule /etc/logrotate.d/apache2 rotates /var/log/apache2/*.log daily, retaining 14 compressed rotations. It does not match CHIM's /var/www/html/HerikaServer/log/chim.log; no external rotation for that CHIM file was established by this inspection.

## Plugin diagnostic configuration and package impact

There is no per-plugin setting in the native logger. The plugin helper uses only the process environment variable MIND_POISONING_LOG_LEVEL=debug to opt into debug detail; unset or any other value stays at info. An operator would set that variable in the PHP worker's service environment and restart/reload that worker. The plugin does not edit host configuration or derive logging settings from request/callback input.

server/logging.php is added to the plugin package allowlist. Its RequestLog calls the native Logger only when already loaded, preserving the configured native threshold and warning/error duplication; otherwise it falls back to PHP error_log(). Native logger prefixes mean the whole CHIM log is not promised to be pure JSONL, though each structured JSON payload is a single escaped line.

The bounded field allowlist is:

- Context/IDs: event_id, utterance_id, playthrough_id, speaker_id, listener_id.
- Operational fields: stage, outcome, reason, model_outcome, persistence_outcome, persistence_reason, connector_id, payload_bytes, speech_bytes, subject_count, changed_count, elapsed_ms, model_ms, persistence_ms, subject, delta, committed, cleanup_failed, changes.
- changes contains at most eight objects with only a subject token (player or npc:<positive-id>), integer delta -5..5, and finite numeric before/after values. The prior affinity is preserved as observed, even if outside normal bounds; after is limited to -100..100.
- model_reason is optional, debug-only, and capped at 240 UTF-8 bytes. It is untrusted model rationale and may expose limited game/model text; it is not a speech/evidence field. No NPC names, raw speech, prompt, credentials, or arbitrary error messages are accepted.

IDs are validated as positive numeric values, except utterance_id, which must match utt_[A-Za-z0-9_-]{8,128}. connector_id accepts only the numeric configuration-row ID, never provider config or secrets.

## Verification

The focused local tests/logging_test.php checks filtering, correlation IDs, manifest version, UTC timestamps, the process-environment diagnostic opt-in, nested bounds, warning/error mapping, idempotent finish, a throwing sink, and fallback error_log() output. It uses an injected sink and a child PHP process; it does not connect to CHIM or invoke an installed endpoint.
