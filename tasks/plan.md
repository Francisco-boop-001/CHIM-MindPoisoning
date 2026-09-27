# Mind Poisoning Implementation Plan

> Execute with the user-selected gpt-6-luna/max subagents. Lead coordinates and reviews; lead writes no product code.

**Goal:** Package a real server plugin that evaluates acknowledged NPC speech and changes only the listener's CHIM affinity toward a named third party or Player.

**Architecture:** Three small runtime responsibilities: pure judgments, existing-server model/persistence adapters, and a thin acknowledgement hook. Schema4 package, no core edits or custom daemon.

**Tech stack:** PHP 8.2, existing HerikaServer/PostgreSQL APIs, Python standard library packaging.

**Spec:** `tasks/design.md`. Exact interfaces, acceptance criteria and ownership are in `tasks/influence-brief.md`, `tasks/runtime-brief.md`, `tasks/package-brief.md`.

## Global constraints

- All development writes stay in this project. Installed WSL and F: remain read-only.
- Existing server revision cf5030f15781637498be86debe26fcf102f5690d is a compatibility reference; no deployment pin advances.
- Model judgments use known identities, bounded integer delta -5..5 including zero, exact evidence, no arbitrary commands.
- Preserve unrelated fields, user locks, other plugins and save history. Never treat mocked DB/provider checks as runtime proof.
- No live provider calls, new dependencies or full live-server tests.

## Task 1 — pure influence module

Owner: influence. Files and exact signatures in influence-brief.

- [x] Read source normalization/relationship conventions and write meaningful failure checks.
- [x] Implement subject resolution, prompt and strict judgment validation.
- [x] Run focused PHP checks; lead reviewed complete module and independently ran `php tests/influence_test.php` successfully. Hook integration gate released.

## Task 2 — adapters, then integration

Owner: runtime. Store/model modules are independent of Task1 implementation; only their fixed input/output contracts are shared.

- [x] Trace callback, source event, connector globals and DB/history APIs.
- [x] Implement/test persistence and model adapters without final hook integration.
- [x] Model adapter separately accepted after file-ownership split: lead reviewed the complete adapter and actual connector factory, independently ran `php tests/model_test.php` successfully. Persistence remains pending.
- [x] Lead review adapter failure modes and test evidence; independent review caught eventlog rowid mismatch, corrected. Final native SQL shapes passed read-only PostgreSQL planning via `tasks/sql-check.sql`; this is not mutation/concurrency proof.
- [x] AFTER Task1 review, wire prerequest hook and run composed fixtures covering correct edge, invalid/stale/aborted/duplicate/zero/locked/failure cases.
- [x] Lead review every resulting diff and return defects to runtime owner. Final local gate passed; provider and PostgreSQL mutation/concurrency execution remain unverified.

## Task 3 — package

Owner: packaging. Build/test tooling can use small fixture payloads independently.

- [x] Implement deterministic schema4 archive build and verification.
- [x] Use real manager in explicitly isolated workspace roots; verify accept/tamper rejection without migrations/live state (fixture package; actual candidate gate remains).
- [x] AFTER runtime acceptance, finalize documentation and build actual candidate.
- [x] Lead inspect archive contents, hashes, docs and final evidence.

## Review focus

Exact listener/subject direction; source-line identity versus callback identity; stale save state; zero-decision history; concurrent/deduplicated writes; connector globals and timeout limitations; package byte identity.

## Final gate

- [x] Review all product files and resolve consequential findings with original owners.
- [x] Fresh focused checks on the final code and package; record evidence tier and remaining limits.
- [x] Complete todo review and hand off candidate/source with no deployment/pin change.
