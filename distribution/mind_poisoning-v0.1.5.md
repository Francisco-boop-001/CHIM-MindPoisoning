# Mind Poisoning v0.1.5 PRE-ALPHA

Development-candidate prerelease for HerikaServer. The compatibility reference remains `cf5030f15781637498be86debe26fcf102f5690d`; it is not a deployment pin. The official CHIM catalog entry has not been submitted or approved.

## Changes

- The read-only dashboard now chooses at most 100 active-profile listeners by each listener's newest valid retained event ID before applying the cap. It is a recent-listener sample, not an exhaustive global history query.
- The fixed 403 guidance tells Windows users opening a WSL-hosted CHIM server to use the configured `localhost` origin, preserve its port and path, then choose **Plugin Page**. The access gate is unchanged and does not allow the VM gateway; this route advice was checked with an isolated responder, not an installed CHIM service.
- The 1536×1024 dashboard poster is now WebP at 649,836 bytes, down from a 3,615,533-byte PNG (about 82% smaller). The original image is preserved outside the runtime package.
- Added a deterministic MO2 import wrapper containing `CHIM/server-plugins/mind_poisoning/0.1.5.dwpkg`. The wrapper is for CHIM client file sync; it is not the repository archive used by the catalog/Plugin Manager route.
- Retains the v0.1.4 semantic mention check, empty-log reporting, and schema-2 update metadata. Existing v0.1.3 installs need one file-sync upgrade to install the schema-2 manifest before Manager version checks can use the per-plugin candidate channel.

## Packages

- [Repository archive](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.5/mind_poisoning.tar.gz) — for catalog/Plugin Manager ingestion; contains one top-level `mind_poisoning/` directory.
- [CHIM sync package](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.5/mind_poisoning-0.1.5.dwpkg) — schema-4 package for server file sync.
- [MO2 import wrapper](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.5/mind_poisoning-0.1.5-mo2.zip) — import as a separate MO2 mod for an isolated test profile.
- [SHA256SUMS.txt](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.5/SHA256SUMS.txt) — checksums for the release assets.

## Verification and limits

Focused PHP, dashboard, manifest-consumer, and Python packaging checks passed. The dashboard query selection was checked against an isolated PostgreSQL instance with a synthetic schema; the Windows-to-WSL `localhost` route was checked with an isolated responder. Neither check proves installed CHIM behavior. Live CHIM database writes/concurrency, provider calls, clean-server loading, deployed dashboard authentication, client ACK delivery, native log delivery, and in-game/save-load behavior remain unverified. The model can still misclassify ordinary-word name candidates. The synchronous connector I/O timeout is 12 seconds, not a hard wall-clock bound. Existing installs may retain the old PNG if their installer overlays files rather than cleaning stale assets; no cleanup logic is included.

Test only in an isolated server/database and separate game profile. The plugin has no migrations. Removing it stops future evaluations but does not undo stored affinity or history.
