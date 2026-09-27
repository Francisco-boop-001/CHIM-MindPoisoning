# Model adapter report

Status: **DONE** for the pure adapter contract.

## Changes

- Added `server/model.php` with `ChimMindPoisoning\requestJudgments(array $messages): string`.
- It accepts only `openrouterjson` and `openrouterjsoncached`, validates connector ID/row/model/URL/key, calls the existing instance-based `LLMConnector` and `fast_request`, and throws `RuntimeException` for configuration or response failures.
- The cached path seeds `setOldGlobals()` with a copy using driver `openrouterjson`, then copies that namespace into `CONNECTOR['openrouterjsoncached']`; the installed method has no cached-driver branch.
- The adapter sets `HTTP_TIMEOUT=12`, `MAX_TOKENS=1024`, and `FORCE_MAX_TOKENS=1024`, then restores connector, timeout, debug, prompt, and JSON-response globals in `finally`, including globals that were originally unset.
- Added `tests/model_test.php` with fixture implementations of the production instance API. It checks both drivers, string IDs from settings/database rows, unsupported and incomplete configuration, provider exceptions, empty/non-string responses, request limits, and global restoration.

## Verification

The first test run was red as intended: PHP failed the harness `require` because `server/model.php` did not exist yet. After implementation, this command passed:

```text
wsl.exe -d DwemerAI4Skyrim3 -- bash -lc "set -e; php -l /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/server/model.php; php -l /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/model_test.php; php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/model_test.php"
No syntax errors detected in /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/server/model.php
No syntax errors detected in /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/model_test.php
model adapter checks passed
```

The fixture class is defined before loading the adapter, so these checks make no database, HTTP, or provider calls. Installed-source inspection was read-only against server reference `cf5030f15781637498be86debe26fcf102f5690d`; it confirmed `LLMConnector` uses instance methods and preserves the database row ID as a string while `readOne()` casts its argument.

## Limits

`HTTP_TIMEOUT=12` is the existing connector's I/O timeout; this adapter does not enforce a hard wall-clock deadline or interrupt server work. It adds no retries or alternate-connector fallback. The OpenRouter driver can include its configured `fallback_models` in its request payload, so any provider-side model fallback remains provider-controlled. No live provider, database, or in-game behavior was verified.
