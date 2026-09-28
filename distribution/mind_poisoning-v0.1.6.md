# Mind Poisoning v0.1.6 PRE-ALPHA

Development-candidate prerelease for HerikaServer. The compatibility reference remains `cf5030f15781637498be86debe26fcf102f5690d`; it is not a deployment pin. The official CHIM catalog entry has not been submitted or approved.

## Changes

- Added same-origin auto-refresh to the read-only dashboard. It requests the current filtered view five seconds after the prior request completes and stops waiting after 15 seconds.
- The dashboard can be paused manually and pauses while its tab is hidden or the user is interacting with live data. A failed or timed-out refresh keeps the last successful data visible. Without JavaScript, reload the page normally.
- Refresh makes a GET request to the dashboard's read-only data path. It does not call the model/provider or write data.
- Added a MO2 FOMOD file-sync wrapper containing `CHIM/server-plugins/mind_poisoning/0.1.6.dwpkg`. MO2 may still show its custom-content warning; no automatic warning suppression is included.
- Existing v0.1.3 installations need one manual file-sync upgrade to gain the schema-2 update metadata. v0.1.5 installations already have it.

## Packages

- [Repository archive](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.6/mind_poisoning.tar.gz) — for catalog/Plugin Manager ingestion; contains one top-level `mind_poisoning/` directory.
- [CHIM sync package](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.6/mind_poisoning-0.1.6.dwpkg) — schema-4 package for server file sync.
- [MO2 FOMOD wrapper](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.6/mind_poisoning-0.1.6-mo2.zip) — contains the versioned `.dwpkg` at the CHIM sync path. It is not the repository archive format.
- [SHA256SUMS.txt](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.6/SHA256SUMS.txt) — checksums for release assets.

## Verification and limits

Focused source, package, PHP fixture, and dashboard refresh checks cover the packaged files and isolated dashboard behavior. They do not prove a live CHIM install. Live PostgreSQL writes/concurrency, provider calls, client ACK delivery, remote dashboard authentication, native log delivery, and in-game/save-load behavior remain unverified. The model can still misclassify ordinary-word name candidates. The synchronous connector I/O timeout is 12 seconds, not a hard wall-clock bound.

Test only in an isolated server/database and separate game profile. The plugin has no migrations. Removing it stops future evaluations but does not undo stored affinity or history.
