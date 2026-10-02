# Task 2 implementation report: full-reply reflection

## Scope and result

Implemented on `fix/reflection-reply-v2`, based on reviewed commit `5761de4ec22011104f96aeb4eb266f75829559b7`. The v1 capability remains version 1 with its original public function signature and registration shape. Added the opt-in reply capability at version 2. No PCV, metadata, package, install, provider, database, push, or publication changes were made.

Changed files:

- `server/reflection.php`
- `server/store.php`
- `tests/reflection_reply_test.php`
- `tests/runtime_test.php` (in-memory commit-failure fixture flag)
- `.superpowers/sdd/reflection-reply-fixes-2026-10-02/task-2-report.md` (this report)

## Implementation

Both public evaluators now use one internal request runner with an explicit full-reply flag; their public arguments and defaults remain the same. V2 accepts only the strict top-level registration plus an ordered list of 1–8 exact event/utterance/hash tuples. It verifies every tuple against existing `acknowledgedEvent()` rows and `reflectionSourceParts()`, requires the actor and sole explicit sentinel, and joins trimmed source bodies with one ASCII space. The final matching ACK alone can trigger evaluation. The inclusive text limits are 2,000 UTF-8 code points and 8,000 bytes; overflow is rejected with a fixed reason and is never truncated. A list above eight lines uses `reflection-reply-too-many-lines`; joined text overflow uses `reflection-reply-too-large`.

The callback receives the complete v2 registration at both existing `pre_model` checkpoints and both transaction checkpoints. After each callback, the evaluator re-reads every captured source; under the actor lock and after the final transaction callback it checks them again before commit. An emitted-to-spoken transition remains acceptable; a missing, aborted, changed, or mismatched source fails closed.

Replay prevention reuses the existing ledger, floor, and transaction path. Every covered event and utterance is checked before provider work and under the lock. One atomic relationship write/history snapshot records each tuple in the existing ledger format; every member is marked `source_kind=reflection`, earlier entries have empty judgments, and the final entry carries the reply decision. Confirmed zero decisions still record all tuples. No `StoreDb` method, table, or runtime file was added.

## Red-green and verification evidence

Before the implementation, the exact focused command failed at the missing capability assertion (exit 1):

```text
wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning-reflection-v2/tests/reflection_reply_test.php
PHP Fatal error: Uncaught RuntimeException: The separate full-reply capability must advertise version 2.
```

After implementation and final review fixes, the same command passed (exit 0):

```text
reflection reply checks passed
```

The focused fixture covers first-line-only subject matching, final and early ACK behavior, malformed/different-actor/wrong-body ACK rejection, aborted/missing/foreign/malformed/hash-mismatched sources, reversed and duplicate members, a callback rejection of a mixed but individually valid source list, the over-eight and text-cap reasons through `RequestLog` (including final event/utterance correlation), 2,000 four-byte characters at the inclusive boundary, a reply crossing the existing floor, v1/v2 overlap and zero-decision replay, duplicate races under the lock, mutations at the second `pre_model` callback and after model/staged-write callbacks, and false-commit preservation with `unconfirmed` diagnostics.

Final compatibility runs under PHP 8.2.29, each exit 0:

```text
wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning-reflection-v2/tests/reflection_test.php
reflection checks passed

wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning-reflection-v2/tests/reflection_observer_test.php
reflection observer/importer: 5 cases passed (committed delta, zero change, provider failure, unconfirmed commit, warning skip)
```

PHP syntax checks all passed:

```text
wsl.exe -d DwemerAI4Skyrim3 -- php -l /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning-reflection-v2/server/reflection.php
No syntax errors detected in /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning-reflection-v2/server/reflection.php
wsl.exe -d DwemerAI4Skyrim3 -- php -l /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning-reflection-v2/server/store.php
No syntax errors detected in /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning-reflection-v2/server/store.php
wsl.exe -d DwemerAI4Skyrim3 -- php -l /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning-reflection-v2/tests/reflection_reply_test.php
No syntax errors detected in /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning-reflection-v2/tests/reflection_reply_test.php
wsl.exe -d DwemerAI4Skyrim3 -- php -l /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning-reflection-v2/tests/runtime_test.php
No syntax errors detected in /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning-reflection-v2/tests/runtime_test.php
```

`git diff --check` passed. The working tree also contains pre-existing lead-owned changes in `tasks/lessons.md`, `tasks/todo.md`, `tasks/reflection-reply-fixes-2026-10-02.md`, and `tasks/reflection-reply-v2-contract-2026-10-02.md`; they were preserved and excluded from this task's commit.

## Trust boundary and limits

The event rows and numeric ordering do not identify reply membership. The companion's revalidation callback must compare the exact complete ordered list captured immutably from one server request at every checkpoint, including rejecting a list mixed across replies. The current inspected PCV route registers one output line; it does not yet provide this v2 list. Mind Poisoning therefore does not claim independent grouping proof. A final ACK and `emitted` rows establish a line-attempt sequence, not proof that every earlier line was heard or played.

All verification used in-memory stores and isolated fixtures. No live model provider, PostgreSQL database, installed CHIM instance, or game/audio path was exercised. The implementation is not evidence of v2 PCV integration or deployed behavior.

## Named ordinary-route regression check

Ran the existing runtime suite on branch `fix/reflection-reply-v2` at HEAD `cb534aa3e7bacb735c27de15deba1148f16b7475`, after the shared persistence and covered-event ledger changes. Exact command:

```text
wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning-reflection-v2/tests/runtime_test.php
```

Exit code: `0`. Captured output, with stdout and stderr unsuppressed:

```text
Mind Poisoning persistence failed at snapshot-verification-failed.
Mind Poisoning persistence failed at player-alias-ambiguous.
runtime store checks passed
```

The first diagnostic is the suite's deliberate snapshot-verification failure case; `tests/runtime_test.php` asserts the failed status and exact `snapshot-verification-failed` persistence reason. The second comes from the existing multiple-Player-alias case, which creates both `Dragonborn` and `Player` relationship edges, expects `persistJudgments()` to fail, and verifies rollback (`tests/runtime_test.php:1019-1024`; `server/store.php:837-848`). It is not emitted by the separate NPC-catalog alias-race fixture, which returns `stale` before persistence. Both captured diagnostics are expected failure-path output. No unexpected PHP warning, notice, or fatal error appeared. This run covers the ordinary runtime store routes and alias guards with isolated fixtures only; it does not use a live provider or database.
