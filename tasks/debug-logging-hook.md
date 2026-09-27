# Logging hook debug review

## Scope and hypotheses

This review covered the ACK include path in `server/prerequest.php`, its `RequestLog` lifecycle, and the focused runtime fixture. No user failure scenario was supplied. The frozen `dist/logging-check/mind_poisoning.tar.gz` baseline and all shared product files were left untouched.

- **Include scope:** A top-level variable collision or missing `$gameRequest` could misroute an ACK. The prior read-only loader trace in `tasks/debug-bootstrap.md` confirms the core imports global `$gameRequest` before including the hook; the existing child sentinel confirms a caller `$store` survives. No new collision was found in the prefixed bootstrap variables.
- **Bootstrap classification:** Logger-load failure uses a fixed fallback error; dependency-load and store-constructor failures use distinct stable codes when a logger exists. The existing child fixture checks a safely correlated store-constructor failure. No classification defect was reproduced.
- **Logging-mode correlation:** Bootstrap validates the bounded utterance ID and places it in logger context before hook gates. Context fields are retained in both modes; only debug events such as `ack_started` are filtered. Source inspection and prior helper/bootstrap fixtures found no mode-dependent summary-ID loss. A separate paired production-bootstrap run under both modes was not repeated.
- **Cleanup exception summary:** The store emits its persistence event after cleanup, records commit state and cleanup failure, then propagates a cleanup exception. The hook catches it and finishes the same logger context. Existing store tests covered this directly, but not through the hook; this was the remaining concrete composition risk.
- **Logging exceptions:** The helper contains sink exceptions, and prior helper plus hook fixtures covered throwing sinks. No additional sink code was changed.

## Focused reproduction

Added one failure injection to `MemoryStoreDb::release()` and one composed ACK case in `tests/runtime_test.php`. The fixture commits affinity and history, then throws during release. The hook returns its existing `failed` status; the terminal request summary retains `persistence_outcome=failed`, `persistence_reason=release-failed`, `commit_state=confirmed`, `committed=true`, `cleanup_failed=true`, and error severity. The fixture also verifies the affinity and history remain committed. This confirms the summary does not imply rollback after a post-commit cleanup failure; no product-code fix was justified.

WSL checks run for this focused change:

- `wsl.exe -d DwemerAI4Skyrim3 -- php -l /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/runtime_test.php` — exit 0, no syntax errors.
- `wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/runtime_test.php` — exit 0, `runtime store checks passed`. The two emitted persistence messages are the existing injected snapshot-verification and Player-alias failure fixtures.

There was no failing hook reproduction: the new composed cleanup case passed on its first run. No full helper/store suite was rerun for this debug check.

## Limits

The cleanup case uses an in-memory adapter and an injected release exception; it does not execute a PostgreSQL advisory unlock or prove live transaction behavior. The installed HerikaServer/mod, native logger, provider, endpoints, and database were not accessed or changed. No edits were made to `server/prerequest.php`, `server/store.php`, `server/logging.php`, package artifacts, release pins, or publication state.
