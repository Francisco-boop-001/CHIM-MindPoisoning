# Mind Poisoning v0.1.4

Development-candidate prerelease for HerikaServer. The compatibility reference remains `cf5030f15781637498be86debe26fcf102f5690d`; it is not a deployment pin.

## Changes

- Added a semantic mention decision to the existing model judgment. Each lexical subject candidate requires a strict `subject_mentioned` boolean; a non-mention must have zero delta. Missing or malformed decisions and contradictory nonzero deltas fail closed before persistence. This reduces ordinary-word name matches such as “May” in “You may trust him,” but the model can still misclassify a mention. It is not deterministic entity recognition, and false lexical candidates can still consume one of the eight subject slots.
- Fixed the dashboard's empty-log handling. A readable zero-byte CHIM log is shown as empty rather than incorrectly marked as truncated; unfinished nonempty tails remain limited and ignored.
- Added Plugin Manager `schema_version: 2` metadata and a per-plugin candidate channel with a main-branch manifest and version-specific package URL. The installed manifest can serve as the update source when catalog lookup misses; an official catalog submission is still separate and has not been approved.

Published v0.1.3 installs have a manifest without the schema-2 update gate. They need a one-time v0.1.4 package file-sync/reinstall to install the new manifest; later version checks can then use its candidate channel. Because the official catalog entry is not approved, use the direct `.dwpkg` sync path for initial installation and that one-time upgrade.

## Downloads

- [Repository archive](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.4/mind_poisoning.tar.gz) for catalog/Plugin Manager ingestion; it contains one top-level `mind_poisoning/` directory.
- [CHIM sync package](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.4/mind_poisoning-0.1.4.dwpkg) for server file sync; this is the separate schema-4 format.
- [Release page](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.4).

## Limits

Focused source and fixture checks do not establish live PostgreSQL writes or concurrency, provider behavior, client ACK delivery, deployed dashboard authentication, native log delivery, or in-game/save-load behavior. ACK speech remains client-reported text, not independent proof of playback. Some upstream relationship writers do not take the plugin's advisory lock. The model call is synchronous with a 12-second I/O timeout, not a hard wall-clock bound. Removing the plugin stops future evaluations but does not undo stored affinities or history.
