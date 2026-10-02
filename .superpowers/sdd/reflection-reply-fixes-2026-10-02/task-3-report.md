# Task 3 report: reflection diagnostics and consumer documentation

## Scope and result

Completed Task 3 on `fix/reflection-reply-v2`. The shared evaluator now attempts the normal `RequestLog` path when the optional logger is omitted, while preserving a caller-supplied logger and observer. Focused tests cover terminal delivery, fixed severity, sink-failure containment, and the pinned v1 consumer path. The v1 API and domain return statuses are unchanged. Documentation identifies v2 as a source-only, unreleased capability, separate from the published v0.1.14 package.

Changed files:

- `server/reflection.php`
- `tests/reflection_diagnostics_test.php`
- `docs/integration-api.md`
- `server/README.md`
- `server/AGENTS.md`
- this report

`server/logging.php`, PCV code, package/version metadata, and release files were not changed. Lead-owned task files remain unstaged and outside this commit.

## Trace and implementation

Both public reflection entry points call the shared `runReflectionEvaluation()` runner. Before this change, the optional nullable `RequestLog` was used only through null-safe calls, so an omitted logger left an invoked evaluation without its terminal `request_finished` record. The runner now constructs `RequestLog` only when the argument is null, catches construction exceptions, and continues with the evaluator result if construction fails. A supplied logger is retained; no second logger is created. Existing `RequestLog` already contains exceptions from its default sink, injected sink, and observer, so no logging implementation change was needed.

Terminal outcome classification is unchanged except for two fixed mismatch reasons. When the domain result is `event-mismatch` and the reason is exactly `reflection-ack-mismatch` or `reflection-source-mismatch`, the terminal outcome is `rejected`, which the existing `RequestLog` severity mapping emits as `warning`. The evaluator still returns its established `event-mismatch` status. This makes malformed/mismatched ACK and source failures warnings while keeping ordinary unforwarded ACKs outside MP and preserving generic skips as informational. Invalid registration and reply caps retain their existing warning behavior; non-final ACK, replay, locked actor, and no-subject cases remain `skipped`/`info`; invalid model responses remain warnings; provider/internal and persistence failures remain errors. Unconfirmed commits remain explicit errors. No global hook logging or additional per-ACK records were added.

PCV revision `c46d650` was inspected read-only. Its caller checks `MIND_POISONING_REFLECTION_API_VERSION === 1` and invokes only `mindPoisoningEvaluateReflection()`. Its importer accepts the corresponding terminal mapping: `rejected` plus `warning` becomes the PCV `rejected` / `evaluation_rejected` result; `skipped` plus `info` remains `skipped` / `evaluation_skipped`; unconfirmed commits require `error`. PCV handles attributable exits before it calls MP. An unregistered or ordinary ACK that never reaches MP must not generate an MP record. The development PCV caller therefore needs a separate adoption change to use v2; existing callers remain v1-compatible.

The docs state the exact v2 signature and registration tuple keys, callback checkpoints and immutable complete-list obligation, final native ACK rule, parsed-source-body digest contract, joined text limits, all-ID ledger/floor replay behavior, diagnostic severity and redaction, and the boundary between fixture evidence and runtime proof. They explicitly distinguish v1's ACK-to-registration digest comparison from v2's parsed source-body digest comparison. V2 remains unreleased/source-only; published v0.1.14 and the inspected PCV caller remain v1.

## Red-green and verification

Runtime: PHP 8.2.29 CLI under WSL (`DwemerAI4Skyrim3`). The omitted-logger regression was first run before the runner change and failed as expected (exit 1):

```text
wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning-reflection-v2/tests/reflection_diagnostics_test.php
PHP Fatal error: Uncaught RuntimeException: Each invoked reflection call must emit exactly one terminal record.
expected: 1
actual: 0 in /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning-reflection-v2/tests/runtime_test.php:290
```

After the change, the focused diagnostics and compatibility checks passed (each exit 0). `2>$null` captured the test result while suppressing the expected isolated JSON log output from existing fixtures:

```text
wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning-reflection-v2/tests/reflection_diagnostics_test.php 2>$null
reflection diagnostics checks passed

wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning-reflection-v2/tests/reflection_test.php 2>$null
reflection checks passed

wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning-reflection-v2/tests/reflection_reply_test.php 2>$null
reflection reply checks passed

wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning-reflection-v2/tests/reflection_observer_test.php 2>$null
reflection observer/importer: 5 cases passed (committed delta, zero change, provider failure, unconfirmed commit, warning skip)
```

PHP syntax checks passed (exit 0):

```text
wsl.exe -d DwemerAI4Skyrim3 -- php -l /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning-reflection-v2/server/reflection.php 2>$null
No syntax errors detected in /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning-reflection-v2/server/reflection.php

wsl.exe -d DwemerAI4Skyrim3 -- php -l /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning-reflection-v2/tests/reflection_diagnostics_test.php 2>$null
No syntax errors detected in /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning-reflection-v2/tests/reflection_diagnostics_test.php
```

`git diff --check` passed for the changed tracked documentation/source files; final staged whitespace checking is recorded after staging the new test and this report.

## Limits

The focused fixture captures the default global `Logger` sink and checks that omitted v1 and v2 logger paths produce one terminal record. It also verifies supplied observer delivery without a default duplicate, warning versus informational classifications, redaction, and that a throwing supplied sink cannot alter the committed evaluator result or relationship history. Constructor-failure handling was verified by source inspection only; no factory seam was added to simulate a `RequestLog` constructor failure.

All execution used isolated in-memory fixtures. These checks do not establish live provider behavior, PostgreSQL durability, installed extension ordering, companion grouping or subtitle/event-body correspondence, or audible playback. PCV v2 adoption remains future caller work. No push, publish, install, or live data operation was performed.
