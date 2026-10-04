# Relationship compatibility implementation — 2026-10-04

Baseline: a83da0a4d5e342e79aecc411afbbeff96363b7d1. Scope: empty relationships arrays and CHIM-canonical Player aliases in evaluation, persistence and dashboard. No sibling edits, release/version/package/pin change, commit, push, publication or installation. Preserve the unrelated PCV plan, critique, user images, submission drafts and recovery records.

## Tasks / exclusive ownership

1. Core owner: server/store.php, server/influence.php and any justified shared relationship helper. Trace all callers; publish a minimal helper/write contract before changes. Accept only an empty array as an empty map; keep malformed values refused. Reuse the pinned CHIM normalization API for Player aliases, preserve metadata/unrelated edges, restore PLAYER_NAME in finally. Use the same canonical prior in model context. Re-read under owner FOR UPDATE; remove aliases atomically with affinity, dedupe and history. Do not rewrite unrelated extended_data or normalize on unrelated NPC-only changes.
2. Adapter owner: server/prerequest.php, server/reflection.php, server/dashboard_data.php; relevant current-source paragraphs in docs/development.md and docs/mind-poisoning.md. Consume the agreed shared contract. Validate relationship map on all eligible paths before a provider call, including NPC-only reflection/ACK; preserve existing lock, scope, subject/catalog and dedupe gates. Preflight normalization is read-only. Dashboard uses the same canonical Player edge and empty-map semantics.
3. Test/evidence owner: tests/*.php changes and new targeted tests, plus this task's verification report. First reproduce current [] rejection and alias refusal using existing fixtures, capturing red output before product edits. Then prove the four handoff cases, canonical model/dashboard consistency, metadata preservation, Player global restoration, zero change, concurrent alias insertion/reordering under the lock, replay and rollback. Review old alias-refusal assertions and replace only obsolete expectations; retain genuine catalog identity ambiguity tests. Cover all affected paths meaningfully with existing seams. Use real pinned core helper source for canonicalization tests, not an invented weighting mock. Own and coordinate any isolated PostgreSQL fixture/check; no other agent enters WSL.

## Interface/dependency scan

| Producer / consumer | Shared boundary | Ruling |
| --- | --- | --- |
| Core / adapters | Map validation and canonical Player read | Core publishes concrete helper contract before either edits dependent call sites. |
| Core / tests | StoreDb write semantics, core normalizer availability | Preserve interfaces where possible; disclose any necessary change before test owner adapts fixtures. |
| Adapters / tests | Existing public request and dashboard functions | Write red tests first against existing entry points; integrate after helper contract is confirmed. |
| Each task / itself | Ownership and evidence | No overlapping edits; owners report exact paths, commands and outputs. Tests are the only test editor. |

Ruling: use native task files as the SDD ledger/briefs on Windows rather than invoking Bash scripts through an unspecified WSL distro. Reuse owners for fixes. Lead writes no product code and reviews every diff and verification transcript. Existing supplied implementation directions remain evidence; this new user request authorizes the bounded repairs.

## Verification/environment

Native portable PHP is retained at dist/hub-pcv15-2026-10-04/php-native/php.exe. No gaming distro invocation. Initial Windows wsl --list --verbose shows gaming and test distros Stopped. Only test owner may use DwemerAI4Skyrim3-test after the documented safety/exclusive guard and Windows gaming-VHD timestamp snapshot. Isolated PostgreSQL must be its own temporary cluster/socket (or existing guarded disposable fixture pattern), not CHIM's player database. Do not start CHIM, Apache, providers, workers or /etc/start_env. Stop optional testing once the behavior is evidenced; syntax is not runtime proof.

## Acceptance

- [] plus NPC subject commits one neutral edge and an object-shaped map; [] plus Player subject writes Player.
- Player affinity50 romantic + actual-name affinity1 neutral + delta-3 selects core's prior50, writes Player47, removes alias and preserves metadata/unrelated edges.
- [1] is refused before any model call across affected routes.
- Prompt, save and dashboard agree on canonical Player state. Revalidation uses locked current state, zero change and replay retain truthful outcomes; rollback does not leave partial affinity/alias/ledger/history changes.
- Changed syntax and existing suites remain green; actual SQL fixture proves jsonb writes and alias removal. Record exact limitations.
## Verification environment ruling

Portable PHP initially lacked enabled mbstring/pgsql. Enabling both passed the first failing baseline assertion, but existing runtime fixtures then failed at Windows symlink creation. These are environment limitations, not evidence for a product change. To avoid adding a dynamic test-extraction harness, the lead authorized checks owner alone to enter the explicitly named stopped test clone for PHP 8.2 and a guarded scratch PostgreSQL cluster; no CHIM services/bootstrap/provider/player database. Fresh Windows preflight: all WSL distros Stopped; gaming VHD D:/DwemerAI4Skyrim3/ext4.vhdx length180175765504, LastWriteTimeUtc2026-10-04T14:34:55.0918612Z. Refuse occupied/running environments; stop only this task's test clone in finally and compare gaming metadata afterward.
## RED gate

Checks owner captured actual persistJudgments failures in test-clone PHP8.2.29: empty NPC map=invalid, empty Player map=invalid, duplicate Player keys=failed; each expected committed. Exit255. Clone terminated afterward, all distros stopped; Windows gaming VHD size/time match the recorded baseline. Core and adapter implementation owners are released from the RED gate. Shared API: storedRelationshipMap(mixed):?array; canonicalRelationshipMap(array,?string):?array; optional writeNpc fifth argument relationshipKeysToRemove=[].

## Lead review findings returned to owners

- Adapter review round1: remove optional-function probing/repeated exception wrappers for mandatory statically required pure helpers; restore canonical Player credibility gate for Player-origin requests even when Player is excluded as subject. Owner reports both corrected; green evidence and final diff review pending.
- Shared-helper review: explicit empty playerName must not inherit an unrelated global; canonicalization must keep stored Player-edge array rejection separate from trusted prompt-copy arrays. Restore Player-speaker validation under the locked persistence read. Mandatory canonical read failure must not silently become zero affinity in model context.
- Dashboard review: normalized Player edge is a core array; old stdClass-only downstream read would mark a valid Player edge invalid. Owner must handle the canonical result explicitly without broadly allowing malformed stored NPC edge arrays.
- Remove the old playerRelationshipKey policy only if no callers remain after this change; no general cleanup or external API change is authorized.
## Lead source review and syntax gate

Reviewed the five changed product files against the frozen contracts. All eligible raw-map readers now use the strict object/exact-empty-array gate; Player-origin credibility and Player-subject reads canonicalize through the actual core helper. Persistence repeats the read under the owner row lock, derives removed alias keys from the canonical map, and performs bound-key deletion/upserts in the same UPDATE as the ledger/timeline, before snapshot verification and commit. NPC-only and zero-delta judgments leave aliases untouched. Dashboard converts only the canonical Player result to its object edge representation. Explicit stored null is consistently rejected; missing maps still default empty.

Native PHP 8.5.11 (-n -l) passed for server/influence.php, server/store.php, server/prerequest.php, server/reflection.php, server/dashboard_data.php. Scoped git diff --check -- server passed. These are syntax/whitespace gates only; behavioral and PostgreSQL verification remain pending. Core and adapter owners are cross-reviewing each other's product files read-only, while the separate checks owner completes isolated regression evidence.

Independent adapter review found no blocking scoped defect. It confirmed the raw opinion-owner gate precedes provider calls for Player/direct ACK/overheard/reflection paths and canonical Player priors/dashboard reads agree. A raw NPC-speaker malformed-map-to-neutral prompt fallback was compared against the baseline and confirmed pre-existing, outside these opinion-owner repairs; no unrelated speaker policy was added. Independent core review found no concrete defect in locked revalidation, bound-key single-UPDATE mutation or rollback ordering. Both documentation paragraphs now explicitly distinguish published v0.1.17 from the unreleased source and describe strict []/null semantics.

## Final review / completed gates

All four checklist items are complete. Lead reviewed every changed product/documentation/test diff and verification output; original owners corrected the Player-origin credibility gate, raw-edge versus prompt-array distinction, explicit empty-name/global restoration, dashboard canonical-edge representation, obsolete alias-refusal tests, independent fixture imports, and SQL fixture fidelity. Cross-review and separate checks-owner audit found no remaining blocking defect in scope.

Behavioral PHP8.2 gates passed for runtime, missing-helper rejection, influence, Player persistence, reflection, overhearing, dashboard data, persistence logging and reflection observer/importer. Public ACK/reflection cases prove exactly one model-stub call for valid empty maps and zero for malformed maps. Pinned CHIM normalization supplies the canonical prior; the50→47 update, alias removal, locked revalidation, metadata/custom_info precedence, replay, zero judgments and rollback are covered.

PostgreSQL15 scratch fixture passed direct PostgresStoreDb writer/transaction checks: exact[] conversion, parameterized alias removal/upsert, malformed-map refusal, second-connection commit visibility and rollback of edges/aliases/ledger/timeline. This is not a full native CHIM persistence/history pipeline or durability/gameplay proof. PHP fixture exit0, cluster stop0 and exact scratch removal0 were observed. Outer stdin Bash runner exited2 after cleanup due to a CRLF final exit argument; it is transparently recorded and does not justify a repeat of the sufficient SQL test.

Lead independently checked native PHP8.5.11 syntax for all five changed server files, all eight changed test files and the pinned core fixture: all passed. Final git diff --check -- server docs tests passed. Gaming VHD Windows metadata exactly matches baseline length180175765504 and LastWriteTimeUtc2026-10-04T14:34:55.0918612Z; checks owner reports all distros stopped after terminating only the owned test clone. No gaming distro command, CHIM service/bootstrap, live provider/player database, install, package, commit, push, publication or pin change occurred. Protected dirty PCV task diff remains its pre-existing EOF blank line.

Evidence: verification.md, core-report.md and adapters-report.md. HEAD a83da0a4d5e342e79aecc411afbbeff96363b7d1 and published v0.1.17 tag4ec1de5fd035fed33f4645d96f1c8e5e0fb079a8 unchanged. The implementation is saved in the working tree; publish only through a separately authorized release with a new version.
