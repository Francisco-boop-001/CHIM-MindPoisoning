# Optional reflection diagnostics API

## Scope and source

Reviewed from MP HEAD `f0784bcb1462d2c8d9ad06614c52a999c3731c37` (manifest 0.1.12). Existing embedded Private Conversation edits, submission files and artwork evidence were preserved. No version, package, release, installation or compatibility pin changed.

Three gpt-6-luna Max owners handled logger delivery, reflection correlation, and importer verification/documentation. The lead reviewed the changed call paths and diffs and wrote only task evidence.

Private Conversation was read-only. Its standalone logger changed concurrently during this task; the final integration check uses the standalone source rather than assuming the embedded copy is identical. Observed SHA-256:

- `CHIM-PrivateConversation/server/log.php`: `834ae19254a25dee6f06f74d88a8d6bfa39831cd7fc3d4d6004efb588cc091d8`
- `CHIM-PrivateConversation/server/reflection.php`: `7f33436258d1641dd7788fe854e830b2675231f442529479e20b86848645c3c2`

The reflection source changed from `447eecab...` during verification to establish PCV request correlation before evaluating. The lead inspected that change; it does not alter the observer attachment used by this fixture. These hashes describe the read-only compatibility evidence, not this task's edits.

## Reviewed behavior

- `RequestLog::observe(?callable): void` retains the constructor and normal sink. Delivery is attempted before notifying the observer. Each receiver's exceptions are contained; recursive observer logging reaches the normal sink without recursively notifying the observer. Passing null detaches it.
- Observer records are already sanitized. The projection additionally removes opt-in debug `model_reason`; normal debug sink behavior remains unchanged. Dialogue, names, prompts, credentials, speech hashes and claim tokens are not added to the observer contract.
- Configuration UUID enters reflection context only after the full registration validates. Its exact value is preserved. MP accepts the existing generic UUID shape; PCV independently requires its generated lowercase v4 UUIDs.
- Unknown commit state remains uncertain. `unconfirmed` and cleanup failure use error severity, and confirmed zero changes remain legitimate commits. Malformed registration is a warning-level diagnostic rejection without changing its returned status.
- Store writes, authorization, source matching, revalidation, replay prevention and registry shape are unchanged. Specific persistence reasons remain in persistence records and final MP context. PCV's aggregate final summary may use a broad reason; its separate persistence record retains the specific cause.
- No PCV load/path dependency or inferred pair-scene correlation was introduced.

## Red-to-green and focused verification

Commands below ran with workspace fixtures using PHP in WSL, without calling installed CHIM, PostgreSQL or a model provider:

```powershell
wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/logging_test.php
wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/store_logging_test.php
wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/reflection_test.php
wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/reflection_observer_test.php
wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-PrivateConversation/tests/log_check.php
```

All exited 0 with their passing-check messages. The updated logger fixture against committed HEAD in `/tmp/mp-logging-red-20260930-01` exited 1 at the new configuration-ID assertion. The updated reflection fixture first failed at the absent configuration UUID, then exposed the malformed-registration info/warning mismatch. Both passed after the scoped fixes.

PHP syntax checks passed for `server/logging.php`, `server/reflection.php`, `tests/logging_test.php`, `tests/reflection_test.php`, and `tests/reflection_observer_test.php`. Existing isolated store checks cover commit failure, rollback/release failure, retained confirmed writes after cleanup failure, and throwing logging sinks. Reflection checks cover trusted tuple retention, invalid registration without correlation, legitimate zero change, exact replay/stale requests without repeated provider work, warning-level invalid pause control, and persistence failure details.

The real evaluator → observer → standalone PCV importer fixture passed five isolated cases: changed commit, confirmed zero change, provider failure, unconfirmed commit and warning-level skip. It checks exact configuration/event/utterance/request correlation, imported changes and timings, and absence of private speech/hash/provider text. The decorator returns false from COMMIT to exercise uncertain reporting; this is not a simulation of PostgreSQL durability.

The lead reviewed every task-owned diff, including callback ordering, sanitization, failed delivery, registration timing, unchanged authorization/store call paths and the real importer boundary. A fixture load-order defect was returned to its owner and repaired before execution passed. Strengthened assertions then passed for the complete imported change tuple, `zero-change`, `model_request_failed`, `commit-failed`, both terminal timing fields, and the uncertain final summary's error severity.

Final cross-plugin output: `reflection observer/importer: 5 cases passed (committed delta, zero change, provider failure, unconfirmed commit, warning skip)`. Verified fixture SHA-256: `764b563cbf2cd0faa195025b80f2e4333296271053a0d85f0ded0145d15e10c1`.

Review complete: the optional reflection diagnostic bridge is compatible with the inspected PCV importer. Other plugins may attach an observer without loading PCV; they must satisfy the existing provenance-bound evaluator contract. All task changes remain uncommitted working-tree changes. Published 0.1.12 and its release/compatibility pins are unchanged.

## Evidence limits

These are isolated executions with in-memory stores and stubbed model responses. They do not prove installed-game integration, provider reliability, PostgreSQL durability/concurrency, playback, or latency in a live session. A callback is request-local diagnostic delivery, not a durable event bus. The existing reflection evaluator remains provenance-bound; it does not authorize arbitrary external relationship writes or provide verified pair-scene attribution.
