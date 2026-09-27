# Store logging debug review

## Result

No consequential logging defect was reproduced, so `server/store.php` and `tests/store_logging_test.php` remain unchanged.

## Hypotheses checked

- **Post-commit cleanup versus terminal summary:** `persistJudgments()` updates `RequestLog` context after cleanup and emits its persistence summary from `finally` (`server/store.php:529-590`). `handleSpeechAck()` finishes the request after evaluation (`server/prerequest.php:149-162`); `RequestLog::finish()` merges the accumulated context into the terminal record (`server/logging.php:81-100`). A throwing cleanup after confirmed commit therefore retains committed values while marking persistence failed/cleanup failed; a native unlock failure keeps the confirmed commit and marks cleanup failed, which raises terminal severity (`server/logging.php:174-189`). Existing store fixtures cover post-commit release failure and the persistence summary, but not the complete hook terminal record. The call-path inspection found no conflicting overwrite.
- **Adapter reuse:** production constructs `PostgresStoreDb` in the `_speech` entry point and passes it to one `handleSpeechAck()` evaluation (`server/prerequest.php:436-439`, `server/prerequest.php:414`). The adapter's cleanup flag is not reset for reuse, but no production caller reuses that instance. Changing it for a hypothetical caller would be speculative.
- **Numeric summaries:** affinity summaries are collected during the transaction and included only after `commit()` returns confirmed success (`server/store.php:450-465`, `server/store.php:520-563`). `changed_count` compares actual before/after values. Logging preserves finite out-of-range prior values while requiring bounded finite `after` values; unrepresentable nonfinite details are omitted by the sanitizer (`server/logging.php:230-240`, `server/logging.php:256-275`). This may leave a count without a per-edge detail for malformed legacy data, but does not misstate whether a finite edge changed or weaken commit classification. No affinity rule was changed for this logging-only review.
- **Optional dedupe reason:** `eventAlreadyProcessed()` still returns its original boolean and defaults the by-reference reason to null (`server/store.php:169-198`). Existing callers without the optional argument remain in `tests/runtime_test.php`; the hook passes the reason explicitly (`server/prerequest.php:287`). No compatibility issue was found.

## Verification and limits

No tests were rerun because this review made no source or fixture changes. The latest focused `tests/store_logging_test.php` run reported by the runtime owner exited 0 with `runtime store checks passed` and `store logging checks passed`; its two persistence failure lines are injected negative fixtures. That fixture verifies in-memory persistence/logging only. Native PostgreSQL cleanup, actual CHIM logger delivery, and live provider/game behavior remain unverified; no live database or provider was called in this review.
