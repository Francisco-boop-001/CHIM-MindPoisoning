# Mind Poisoning v0.1.7 PRE-ALPHA

Development-candidate prerelease for HerikaServer. Compatibility reference remains `cf5030f15781637498be86debe26fcf102f5690d`; it is not a deployment pin. The official CHIM catalog entry has not been submitted or approved.

## Changes

- Added Player-origin gossip for CHIM `inputtext`, `inputtext_s`, `ginputtext`, and `ginputtext_s` requests. The hook correlates the post-insert eventlog snapshot by the exact source tuple and records its positive row ID as `input_<rowid>` in the existing event-key field. It does not fabricate an NPC `_speech` acknowledgement or prove audio delivery.
- The route selects one NPC listener. Other names in `source_people` may be bystanders, not additional listeners. Narrator, everyone/broadcast mode, ambiguous routes, and uncertain identities are skipped. Player-origin gossip considers named NPCs other than the Player and listener as subjects.
- Logs use the explicit `speaker_kind=player` marker without dedicated Player name or speaker-ID fields. Opt-in debug model rationale remains untrusted and can quote a name or short game-text excerpt. Older unmarked records are not inferred to be Player.
- The existing ledger schema and bounded, read-only dashboard remain unchanged; the dashboard displays explicitly marked Player-origin records as Player and labels `input_<rowid>` as an input event.
- Existing v0.1.3 installs need one manual package sync to receive the schema-2 update metadata. v0.1.5 and v0.1.6 installs already include it.

## Packages

- [Repository archive](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.7/mind_poisoning.tar.gz) for catalog/Plugin Manager ingestion; it contains one top-level `mind_poisoning/` directory.
- [CHIM sync package](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.7/mind_poisoning-0.1.7.dwpkg) for server file sync.
- [MO2 FOMOD wrapper](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.7/mind_poisoning-0.1.7-mo2.zip) for CHIM file sync. MO2 may still display its custom-content warning; the wrapper does not suppress it.
- [SHA256SUMS.txt](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.7/SHA256SUMS.txt) for release asset checksums.

## Verification and limits

Focused PHP fixtures, lints, and package checks passed. They do not prove live CHIM input delivery, PostgreSQL writes or concurrency, provider behavior, dashboard authentication, or in-game behavior. The synchronous model call can delay the source request; its 12-second I/O timeout is not a hard wall-clock bound. The model can still misclassify names or mentions. The plugin has no migrations.

Test only in an isolated server/database and separate game profile. Removing the plugin stops future evaluations but does not undo stored affinity or history.
