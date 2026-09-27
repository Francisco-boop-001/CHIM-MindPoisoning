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

- [ ] Read source normalization/relationship conventions and write meaningful failure checks.
- [ ] Implement subject resolution, prompt and strict judgment validation.
- [ ] Run focused PHP checks; lead review complete affected inputs and output contract.

## Task 2 — adapters, then integration

Owner: runtime. Store/model modules are independent of Task1 implementation; only their fixed input/output contracts are shared.

- [ ] Trace callback, source event, connector globals and DB/history APIs.
- [ ] Implement/test persistence and model adapters without final hook integration.
- [ ] Lead review adapter failure modes and test evidence.
- [ ] AFTER Task1 review, wire prerequest hook and run composed fixtures covering correct edge, invalid/stale/aborted/duplicate/zero/locked/failure cases.
- [ ] Lead review every resulting diff and return defects to runtime owner.

## Task 3 — package

Owner: packaging. Build/test tooling can use small fixture payloads independently.

- [ ] Implement deterministic schema4 archive build and verification.
- [ ] Use real manager in explicitly isolated workspace roots; verify accept/tamper rejection without migrations/live state.
- [ ] AFTER runtime acceptance, finalize documentation and build actual candidate.
- [ ] Lead inspect archive contents, hashes, docs and final evidence.

## Review focus

Exact listener/subject direction; source-line identity versus callback identity; stale save state; zero-decision history; concurrent/deduplicated writes; connector globals and timeout limitations; package byte identity.

## Final gate

- [ ] Review all product files and resolve consequential findings with original owners.
- [ ] Fresh focused checks on the final code and package; record evidence tier and remaining limits.
- [ ] Complete todo review and hand off candidate/source with no deployment/pin change.
