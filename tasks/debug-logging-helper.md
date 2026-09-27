# Logging helper blind-spot review

## Scope

Inspected `server/logging.php`, its production call sites, `tests/logging_test.php`, and `tasks/logging-platform.md`. Read the installed `/var/www/html/HerikaServer/lib/logger.php` source in WSL only; did not execute CHIM code or touch installed files, configuration, endpoints, databases, or providers. One isolated PHP command loaded only the project helper and serialized in-memory records.

## Findings

- **Reserved-record spoofing — no defect found.** `event()` creates `schema_version`, `plugin`, `version`, `timestamp`, `request_id`, `level`, and `event`, then merges only `sanitizeFields()` output ([logging.php:53](/K:/ActorwrightExchange/projects/CHIM-MindPoisoning/server/logging.php:53), [logging.php:74](/K:/ActorwrightExchange/projects/CHIM-MindPoisoning/server/logging.php:74)). The allowlist has no keys for those reserved names. The local reproduction attempted overrides through both `context()` and event fields; the emitted header stayed canonical and the overrides were absent.

- **Inherited debug rationale — reproducible helper behavior, no current production path.** With diagnostic mode enabled, `context()` accepts bounded `model_reason`; a later debug event inherits it because event serialization merges saved context ([logging.php:42](/K:/ActorwrightExchange/projects/CHIM-MindPoisoning/server/logging.php:42), [logging.php:74](/K:/ActorwrightExchange/projects/CHIM-MindPoisoning/server/logging.php:74), [logging.php:302](/K:/ActorwrightExchange/projects/CHIM-MindPoisoning/server/logging.php:302)). The reproduction emitted `model_reason: context-sentinel` on a later event. The counterargument is confirmed by the call-site search: current production code supplies rationale only directly to `judgment_proposal` ([prerequest.php:401](/K:/ActorwrightExchange/projects/CHIM-MindPoisoning/server/prerequest.php:401)); no production `context()` call supplies `model_reason`. `event()` does not save event fields back into context. I made no code/test change for this latent API misuse with no current production caller.

- **Injected-sink reentrancy — bounded to the testing seam.** `finish()` guards reentrant finish calls with `finishing`/`finished` ([logging.php:81](/K:/ActorwrightExchange/projects/CHIM-MindPoisoning/server/logging.php:81)); `event()` has no general reentrant-event guard. A sink that recursively calls `event()` could recurse, but production constructs `RequestLog` without a sink ([prerequest.php:17](/K:/ActorwrightExchange/projects/CHIM-MindPoisoning/server/prerequest.php:17)); no request or callback data selects a callable. No production fix is justified.

- **Native Logger API and filtering — source-compatible; filtering is intentional.** Installed CHIM defines public static `debug`, `info`, `warn`, and `error` with an optional log-file parameter (`/var/www/html/HerikaServer/lib/logger.php:111-128`); the helper’s one-argument calls and `warning`→`warn` map match those methods. CHIM's `setLevel()` changes a process-global minimum (`:36-42`); `shouldLog()` compares levels and `log()` returns before writing below the threshold (`:61-78`). Thus `debug` and `info` can be silently suppressed at a `warn` threshold. The plugin does not change that level or retry through another sink when native Logger is loaded. The project stub verifies method dispatch and level mapping, but actual CHIM methods were not executed; active deployment level and sink health remain unknown.

- **Request lifetime — source trace only.** `prerequest.php` creates one `RequestLog` for `_speech` while initializing the variable to null for other events ([prerequest.php:9-17](/K:/ActorwrightExchange/projects/CHIM-MindPoisoning/server/prerequest.php:9)). It passes the instance through the handler/store and calls `finish()` with the handler result ([prerequest.php:161](/K:/ActorwrightExchange/projects/CHIM-MindPoisoning/server/prerequest.php:161)); bootstrap failures also finish it ([prerequest.php:54](/K:/ActorwrightExchange/projects/CHIM-MindPoisoning/server/prerequest.php:54), [prerequest.php:442](/K:/ActorwrightExchange/projects/CHIM-MindPoisoning/server/prerequest.php:442)). The object has no static request state. Existing focused tests cover one summary and idempotent finish; no real loader/endpoint lifecycle was executed.

## Reproduction and disposition

The isolated WSL PHP reproduction exited 0. Its core setup was:

```php
$log->context(['model_reason' => 'context-sentinel', 'request_id' => 'forged', 'event' => 'forged']);
$log->event('later_debug', 'debug', ['level' => 'error', 'timestamp' => 'forged']);
```

The record contained the prior `model_reason` sentinel, but retained generated `request_id`/`timestamp`, `plugin: mind_poisoning`, `version: 0.1.1`, `level: debug`, and `event: later_debug`. This confirmed the latent context behavior without contacting CHIM. The reserved-field check and the read-only installed-source inspection found no production defect. No edits were made to `server/logging.php` or `tests/logging_test.php`; no additional test suite was run because the existing focused coverage was already available and no production-path failure was found.
