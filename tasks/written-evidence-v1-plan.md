# SDD ledger — plan: tasks/written-evidence-v1-plan.md

Base: f21a00abbb393c03a8faf226c0707281776d175d, unpublished feature/written-evidence-v1. Frozen producer fixture194da266679a0e21c823659a59279b46dcca2eeed2df13f371bbd0bde4a5c623; source design is CHIM-Correspondence-dev/docs/superpowers/specs/2026-10-19-correspondence-written-evidence-design.md. No prior written implementation exists in this branch; prior intimacy release is complete and must not be redispatched.

| Tasks sharing interface | Producer / consumer | Preflight result |
| --- | --- | --- |
| Source/store ↔ evaluator/API | Canonical written row+collision query+locked revalidation / recipient-owned source-kind event | Interface needs frozen signatures before concurrent edits. Optional capability preserves existing StoreDb test implementations. |
| API ↔ request bootstrap/diagnostics | eventID-only queued hint/status and actual opinion outcome / bounded processing hook and truthful logs/history | Existing MP is synchronous; no existing durable worker. Must not call a synchronous provider from supposedly read-only status/enqueue. |
| Store ↔ old speech/reflection/intimacy | Optional written receipts/source-kind branch / existing atomic persistence and128event floor | Preserve ordinary APIs and separate future-source receipt identity; no fabricated ACK. |
| All tasks ↔ external coordinator | Versioned public exports and native fixture seam / source-only integration before coordinator's exclusive clone checks | Owner runs standalone synthetic checks only; coordinator controls all native DB/service use. |

Scope self-consistency: no version/package build required for owner; package source allowlist must include new runtime files for later coordinator staging. Product changes remain only in isolated MP branch. Separate source files/tests avoid concurrent edits; shared interfaces frozen before implementation. Independent reviewers are read-only; implementation owners reused for fixes.
## Frozen implementation ownership
- [x] Read producer fixture, actual callers, MP store/restore boundaries, sibling protocol and independent preflights.
- [x] Freeze public API and optional store/explicit-connection seam in artifacts/chim-correspondence-implementation-20261019/mp-owner-handoff.md.
- [x] Storage owner: source/collision reads, no-ACK recipient transaction and separate non-evicting receipts; isolated red/green checks and native fixture for coordinator only.
- [x] API owner: strict source adapter, candidate/prompt/parser, credential-free reads and one-per-drain hints; isolated red/green checks.
- [x] Integration owner: global/postrequest exports, bounded drain, sanitized logging, truthful UI and package allowlist; targeted checks.
- [x] Lead: inspect all diffs/callers/failure modes, return defects, run focused affected gates and produce hash-pinned handoff.

Decisions: written receipt outcomes committed|zero_change; no fictional speech ID or sender-presence claim; no intimacy mutation. Producer re-enqueue fairness is required beyond the three-visible-letter window and belongs to the correspondence owner. No durable queue claim. Preserve existing live/source branches and release metadata; no provider, WSL, DB or service commands by MP owners.

## Red evidence (before product changes)
- Integration owner: tests/written_hook_test.php exits1 with eight focused missing API/caller/package/privacy assertions; INIT no-drain assertion already passes.
- Coordinator-owned native cross fixture: artifacts/chim-correspondence-implementation-20261019/E/mp-cross-public-red.txt reports RED_mp_public_written_entry_not_ready_no_fixture_writes. This is coordinator evidence; MP owners did not execute or enter the clone/database. No DDL/data writes in that red check.

Carrier correction: physical source type is info with exact sess written_evidence_v1; logical written_evidence remains unchanged. Capability adds source_event_type=info. This supersedes initial physical custom-type wording: coordinator verified generic NPC/Narrator/BGL history excludes info but admits unknown custom types. Source queries/API fixtures must reject ordinary info and former custom-type rows. Strict JSON fixture unchanged; no source redesign or restart.

## Final review
Owner scope is complete and frozen, unpublished. Lead inspected all changed runtime/export paths and raw verification, returned defects to owners, independently ran the production evaluator fixture (93 checks), syntax gates and exact24-file runtime export check. Final named-argument correction also passes93 with the owner; independent review found no concrete blocker. API/store hashes and exact commands/results/limitations are in artifacts/chim-correspondence-implementation-20261019/mp-owner-handoff.md and mp-owner-source-inventory.json. Public release/version/tag/pin stays0.1.26; no archive was built under that unchanged identity. Coordinator first native cross result is a prior snapshot and must be rebound to the final inventory; final native/package gates remain coordinator-owned. No MP owner WSL/database/provider/service/game/live action. New source remains in this isolated working branch for coordinator staging.

## Final coordinator integration update — 2026-10-19 (supersedes pending final native/package gate)
The final coordinator receipts have been read and checked. mp-cross-final-native.txt is PASS: real producer/store, exact info/written_evidence_v1 source, public MP native adapter/evaluator, actual recipient affinity/types/third-party receipt/history, and replay/coexisting namespaces. Model callback calls1; provider calls0; native gameplay NOT RUN.

candidate-consumers-final.json is PASS: all three exact development candidates installed by the unchanged official manager. MP's normal public hint drain processed the source and committed recipient opinion/history while preserving the real S&S issue and same-recipient namespaces; replay was unchanged. MP development candidate is 0.1.26-correspondence.dev.1, package SHA25613767c2c3fa59781e3d9bbc78fd874f597e5e85c9b97f699ab48049761a4c30e. This is an unpublished disposable-install candidate, not a public version/pin change.

final-payload-verification.json is PASS: all24 MP owner runtime files unchanged, packaged payload equal, copied manifest override only. Protected clone file equality, gaming metadata equality and provider_calls0 are recorded. Metadata equality is not a complete game-file content hash audit. The coordinator performed the native/service work; this MP task only read the retained receipts and updated its documentation.

The earlier native snapshot limitation is now resolved by final-byte native/package evidence. Remaining limits still apply: deterministic model/physical-receipt boundaries, no real-provider judgment or Skyrim delivery/playback/gameplay proof, and no new-source native commit-acknowledgment-loss durability proof. Conditional retries, head-of-line blocking and trusted unique publication remain documented. No programming, installation, service operation, release, tag or promotion was performed by this update. Public pin remains0.1.26.
