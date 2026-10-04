# Mind Poisoning v0.1.18 — PRE-ALPHA candidate

This source candidate carries the reserved rechat-sentinel guard committed after v0.1.17 and repairs relationship compatibility around empty maps and CHIM's canonical Player identity.

## Changes

- The reserved `explicit_disable_rechat` listener marker is not resolved as an NPC recipient, even if a catalog row has the same name. The guard applies to addressed ACKs and the overhearer roster. A real member listener pinned by SHARMAT remains subject to the ordinary source, ACK, and Mind Poisoning eligibility checks.
- At opinion-owner stored-data boundaries, a missing `relationships` property or the exact empty PHP array `[]` is treated as an empty map. Explicit `null`, nonempty arrays, malformed map shapes, and malformed stored Player edges fail closed. When a relationship edge is actually written, the store serializes the resulting map as a JSONB object and preserves unrelated metadata and edges; accepting `[]` alone does not trigger a row migration.
- Player credibility and subject reads use CHIM's canonical relationship normalizer from compatibility revision `cf5030f15781637498be86debe26fcf102f5690d`. The same canonical Player edge feeds preflight, model context, dashboard reads, and locked persistence. A nonzero Player-subject update removes legacy Player-name alias keys and writes `Player` within the same locked persistence transaction as affinity, the dedupe ledger, and the NPC/history snapshot. Player-speaker-only and NPC-only updates do not rewrite aliases. If the required core normalizer is unavailable or fails, the Player read fails closed before model work.

The v0.1.17 default-off NPC ACK overhearing feature remains. Its capability marker and eight-subject/joined-text limits are unchanged. The embedded Private Conversation integration snapshot is v0.1.15; the v1 reflection API contract remains unchanged. Published v0.1.17 notes and the v0.1.15 Private Conversation notes remain historical.

## Verification and limits

The focused PHP checks used the explicitly authorized `DwemerAI4Skyrim3-test` clone and the complete pinned CHIM normalizer source. The public request/store seams cover empty NPC and Player maps, duplicate Player-name selection, alias removal, malformed values, Player-origin listener credibility, dashboard reads, locked revalidation, zero changes, replay, and rollback. The historical [v0.1.10 live acceptance record](../tasks/live-acceptance-v0.1.10.md) still documents two Player-origin commits and two expected skips; separate user-reported v0.1.15 pair-path observations remain recorded in the [v0.1.16 release notes](mind_poisoning-v0.1.16.md). This candidate verification neither replaces nor expands those historical live reports.

The isolated PostgreSQL fixture exercised `PostgresStoreDb::writeNpc` directly: exact empty maps became objects with neutral edges; parameterized alias removal and canonical Player upsert preserved an unrelated edge and metadata; array/null maps did not write; a second connection observed committed changes only after commit; and rollback restored the edge, alias, ledger, and timeline. This is direct writer/transaction evidence, not a full PostgreSQL `persistJudgments` pipeline or history-snapshot execution.

The fixture's PHP process printed its pass line and exited 0. After cleanup, the outer PowerShell-to-Bash wrapper exited 2 because CRLF reached Bash's final `exit 0` argument; PostgreSQL stop and scratch cleanup both reported 0. The cluster was not rerun. Native PHP syntax checks and the scoped whitespace gate passed. The evidence record is [relationship compatibility verification](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/blob/mind_poisoning-v0.1.18/tasks/relationship-compatibility-2026-10-04/verification.md).

These checks do not establish current native CHIM request dispatch, live provider behavior, general database-pipeline durability, installed extension order, audio playback, or gameplay acceptance. The official CHIM catalog entry has not been submitted or approved. Package availability and checksums are listed on the release page.

## Candidate packages

Use one server-package route per CHIM server and replace older enabled packages instead of stacking them. Existing v0.1.13 installs still need the historical [CHIM-Plugins v0.1.14 bridge](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.14) before migrating to this deployment repository. See the [deployment and migration guide](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/blob/mind_poisoning-v0.1.18/docs/deployment-migration.md) and [integration API contract](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/blob/mind_poisoning-v0.1.18/docs/integration-api.md).

- [Repository archive for Plugin Manager](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.18/mind_poisoning.tar.gz)
- [CHIM sync package](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.18/mind_poisoning-0.1.18.dwpkg)
- [Plain MO2 import ZIP](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.18/mind_poisoning-0.1.18-mo2.zip)
- [Release page and checksums](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/tag/mind_poisoning-v0.1.18)

The PRE-ALPHA maturity remains. CHIM compatibility reference `cf5030f15781637498be86debe26fcf102f5690d` identifies the source used for normalization; it is not an installation pin.
