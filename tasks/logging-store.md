# Store observability

## Changes

- `server/store.php` accepts an optional `RequestLog` in `persistJudgments()` and `PostgresStoreDb`. Persistence keeps its existing string statuses and emits a sanitized `persistence_finished` event after cleanup. Rejection and failure reasons are stable codes; no exception messages, names, speech, or evidence are logged.
- Affinity summaries contain bounded subject tokens, proposed delta, and numeric before/after values only when `commit()` returned verified success. `changed_count` counts actual numeric differences; a clamped proposal may be recorded with equal values and count zero. Zero judgments have no edge values.
- `commit_state` distinguishes `not_attempted`, `unconfirmed`, and `confirmed`. `committed` remains true only for confirmed commits. A lost commit acknowledgement or failed post-commit check is unconfirmed, not proof of rollback; its planned affinity values are omitted.
- Native rollback and advisory unlock failures emit separate cleanup diagnostics. `eventAlreadyProcessed()` retains its boolean result and optionally reports `ledger-invalid`, `ledger-floor`, or `duplicate-event` for precise hook logging.
- `tests/store_logging_test.php` reuses the runtime fixture and checks timing of emission, outcomes/reasons, zero and clamped deltas, unconfirmed commit, snapshot failure, cleanup exceptions, throwing sinks, and ledger rejection reasons.

## Verification

Red before the persistence event was implemented:

```text
wsl.exe -d DwemerAI4Skyrim3 --exec php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/store_logging_test.php
runtime store checks passed
PHP Fatal error: ... Persistence summary must emit after release cleanup.
expected: true
actual: false
```

Red before adding the optional ledger reason:

```text
wsl.exe -d DwemerAI4Skyrim3 --exec php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/store_logging_test.php
runtime store checks passed
PHP Fatal error: ... Malformed ledger preflight should expose its rejection reason.
expected: 'ledger-invalid'
actual: NULL
```

Final focused run, reported by the runtime owner:

```text
wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/store_logging_test.php
Mind Poisoning persistence failed at snapshot-verification-failed.
Mind Poisoning persistence failed at player-alias-ambiguous.
runtime store checks passed
store logging checks passed
```

Both failure log lines are expected negative fixtures. No live endpoint, model provider, or database was called.

## Limits

PostgreSQL rollback and advisory-unlock failure branches were source-reviewed only; the fixture does not exercise native PostgreSQL cleanup. If `COMMIT` may have reached PostgreSQL but the client loses acknowledgement or a subsequent connection-state check fails, the log reports `commit_state=unconfirmed`, `committed=false`, and no affinity values. It makes no rollback claim. Real database, provider, and in-game behavior remain unverified here.
