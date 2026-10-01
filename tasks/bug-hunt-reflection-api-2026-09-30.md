# Bug hunt after the reflection diagnostics API

## Scope

Reviewed MP working tree on HEAD `f0784bcb1462d2c8d9ad06614c52a999c3731c37`, with the existing uncommitted observer/correlation API preserved. Three existing gpt-6-luna Max owners audited separate flows using Ponytail FULL. Lead reviewed code and verification and wrote task evidence only. Private Conversation, installed CHIM, modlist, release assets and metadata were not changed by this task.

## Confirmed defects and fixes

### Invalid stored affinity was silently repaired

`persistJudgments()` accepted an existing affinity of 250 and clamped a proposed -5 change to 100: an actual -150 mutation. This violated the legal affinity range and conservative delta contract. The three-line guard in `server/store.php` now rejects non-finite or out-of-range prior affinity under the existing lock/transaction, returning `invalid` with `affinity-invalid`, before writes or COMMIT.

Red: the new isolated test expected `invalid` but received `committed`. Green: the parameterized `tests/store_logging_test.php` regression covers 250, -250 and numeric-string overflow `1e309`; each leaves serialized listener state unchanged, reports `not_attempted`, and records no changes. The existing legal 100/+5 boundary remains a confirmed commit with no actual affinity change.

Counterargument: the pre-fix MP record retained proposed delta -5 and the endpoints 250/100; disappearance from MP logging was not proven. PCV rejects an out-of-range before value by source inspection, but no pre-fix importer execution was captured. The confirmed defect is the silent state repair, independently of diagnostic consequences.

### Callback warnings escaped failure containment

PHP `E_USER_WARNING` diagnostics are not Throwable exceptions. Custom sink/observer warnings escaped into displayed output and stderr, despite the existing catch blocks. `server/logging.php` now suppresses PHP diagnostics only around those two callback invocations, matching the existing native Logger calls, and retains exception handling.

Red: `tests/logging_test.php` failed with `warnings from custom sink and observer callbacks must not escape into output or stderr`. Green: a child process with `display_errors=1` produces only `warning child returned status=committed sink=2 observer=2`, with empty stderr. Both callbacks still receive the records and the returned status is unchanged.

Counterargument: these are trusted PHP callbacks. Deliberate echo/write operations and independently installed error handlers are not sandboxed by this API. The change contains PHP's default warning output; it does not claim arbitrary callback isolation.

### Invalid reflection persistence finished as an informational skip

The affinity rejection exposed a terminal diagnostic mismatch: `persistence_finished` was warning-level with `affinity-invalid`, but `request_finished` mapped returned status `invalid` to an informational skip. The reflection wrapper now maps that status to diagnostic `rejected`, preserving the returned `invalid` status and specific persistence fields.

Red: the genuine reflection fixture expected warning severity but received info. Green: the final record is rejected/warning, the invalid affinity remains 250, and no snapshot is saved. This changes diagnostic classification only, not the registry, actor matching, replay or authorization rules.

### Dashboard accepted illegal current affinity as normal

The read-only current-value helper returned `state=set` for finite affinities outside the legal range. `dashboardCurrent()` now classifies those values as invalid instead of presenting them as supported current affinities. It does not normalize or write the stored value.

Red: the isolated helper fixture failed for an out-of-range affinity. Green: `tests/dashboard_current_bounds_test.php` preserves legal -100/+100 values and marks 250, -250 and numeric overflow invalid. This fixture calls only the pure presenter helper, not the database reader or installed page.

## Audited hypotheses without a justified change

- Missing reflection sentinel: rejected by the exact source-target parser and independently checked by PCV.
- Event/actor identity or history drift: revalidated under the transaction; stale history has fixture coverage without snapshots.
- Replayed ACK or unchanged evidence reaching the provider again: existing exact/basis dedupe checks stop it.
- Opinion-only changes outside the reflection evidence basis: intentional dedupe semantics; persistence uses the latest locked edge. No contract-grounded failure justified changing the basis.
- Observer reentrancy, malformed IDs, raw rationale forwarding, or consumer tuple mismatch: existing guards and sanitized projection remained compatible in the reviewed flows.

The dashboard audit found no further justified change to attribution, escaping, filters, exports or authentication. Reflection records without listeners remain solo reflections; ownerless records stay unattributed and mismatched owner/speaker IDs are rejected. These conclusions are source/fixture evidence, not deployed authentication proof.

## Focused commands and evidence

```powershell
wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/store_logging_test.php
wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/logging_test.php
wsl.exe -d DwemerAI4Skyrim3 -- php -d display_errors=1 /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/logging_test.php warning-child
wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/reflection_test.php
wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/reflection_observer_test.php
wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/dashboard_current_bounds_test.php
```

Owners observed passing results after the fixes. After reviewing the final diffs, the lead independently ran the five affected regression gates; all exited 0:

```text
logging checks passed
store logging checks passed
reflection checks passed
reflection observer/importer: 5 cases passed (committed delta, zero change, provider failure, unconfirmed commit, warning skip)
dashboard current-affinity bounds checks passed
```

The lead also ran `php -l` on the four affected server files and five relevant fixture files; every file reported no syntax errors. The scoped `git diff --check` passed. Model, influence, ordinary request-hook, access-gate, package and manifest source did not change. Current HEAD remains the inspected commit.

## Changed files and review

Product edits from this hunt are confined to `server/store.php`, `server/logging.php`, `server/reflection.php`, and `server/dashboard_data.php`. Regressions are in `tests/store_logging_test.php`, `tests/logging_test.php`, `tests/reflection_test.php`, and new `tests/dashboard_current_bounds_test.php`. Plan/review evidence is in `tasks/todo.md` and this report. Earlier API/integration documentation and test changes remain intact.

Lead accepted the four fixes after reviewing all changed call paths and outputs. Invalid-state validation runs at the shared locked persistence boundary for NPC, Player and reflection paths; callback warning suppression is restricted to diagnostic delivery; reflection status returns remain intact; the dashboard change is read-only. Unrelated contributor work was preserved. No additional defect was established in the examined scopes, and no optional broad suite was run.

## Limits and pin

Checks use in-memory stores, stubbed provider responses and temporary files. They do not prove installed-game behavior, provider reliability or live PostgreSQL durability/concurrency. Compatibility and published version remain unchanged at PRE-ALPHA 0.1.12. No commit, installation or publication was requested in this bug hunt.
