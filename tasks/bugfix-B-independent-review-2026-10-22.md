# MP bugfix B independent review — 2026-10-22

## Scope

Read-only review of the current `server/store.php` and `server/restore_policy.php` changes, cleanup-failure-generation callers, optional child-metadata savepoint handling, native fixture setup, and retained B stage outputs. No product/test files were changed and no commands or tests were run by this reviewer.

## Findings

No blocking source defect identified in the reviewed changes.

- `intimacyChildDataByName()` creates a savepoint only inside a store-owned transaction. On optional-read failure, its recovery path rolls back to and releases that savepoint; if recovery fails, it records a cleanup failure and throws so the caller cannot proceed as if the transaction were healthy. `assertTransactionConnection()` admits `INERROR` so the rollback command can run after a failed statement.
- `rollback()` preserves transaction ownership on connection/query/status failure and clears it only after PostgreSQL reports idle. `release()` preserves advisory-lock ownership unless `pg_advisory_unlock()` confirms `true`. `releaseRestorePolicyBarrier()` likewise clears ownership only after the semaphore release confirms success. `beginForListener()` refuses reuse while a transaction or advisory lock remains owned; restore-policy callers also refuse a second barrier acquisition while the prior barrier remains owned.
- The sticky `cleanupFailed()` value remains historical evidence. Production callers now compare the operation-local generation in `persistJudgments()`, `persistWrittenEvidenceJudgments()`, and restore-policy baselining, so a previous cleanup failure does not mark a later clean operation as failed. `rg` found no other production caller of `cleanupFailed()`.
- The native fixture now supplies the parser/runtime seams required to reach the intended reflection transaction path. It asserts transaction revalidation before interpreting the mixed-write result. Stage 4 is the valid no-savepoint RED (`write-listener-failed`); stage 7 is the valid cleanup RED (`retry-did-not-release-advisory-lock`). Stages 1–3 and 5–6 are correctly described in B’s report as fixture/harness failures, not product evidence.
- Retained stage 8 output at `K:\ActorwrightExchange\artifacts\mp-bugfix-20261022\B\child-native-stage8.log` reports 41 checks passed, exit 0. Its recorded `store.php` hash matches the current file; its native-fixture hash also matches. The recorded `restore_policy.php` hash differs from the current file, but the only current post-run change is formatting of the cleanup-generation condition: transforming that condition in memory to its prior one-line form reproduces the exact recorded stage-8 hash. No source file was changed during this comparison.

## Limits

Stage 8 is lead-run native evidence, not a run performed by this reviewer. B’s report still lists the written-identity native check and integrated Linux suites as pending. The restore-policy semaphore failure path was reviewed statically; stage 8’s child fixture proves the optional-read and PostgreSQL lock-retry paths, not a live `SemaphoreManager` release failure/retry.
