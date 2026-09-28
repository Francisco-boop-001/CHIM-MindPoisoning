# Development guide

Mind Poisoning v0.1.5 is a PRE-ALPHA development-candidate prerelease. Download the [repository archive](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.5/mind_poisoning.tar.gz), [CHIM sync package](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.5/mind_poisoning-0.1.5.dwpkg), or [MO2 import wrapper](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.5/mind_poisoning-0.1.5-mo2.zip) from the [release page](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.5). Earlier [v0.1.4](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.4), [v0.1.3](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.3), [v0.1.2](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.2), [v0.1.1](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.1), and [v0.1.0](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.0) prereleases contain earlier source. Compatibility reference: `cf5030f15781637498be86debe26fcf102f5690d`; this is not a deployment pin. The official CHIM catalog entry has not been submitted or approved. Live runtime behavior remains unverified.

## Structured logging

The v0.1.2 tarball and `.dwpkg` include the structured logging helper. The earlier v0.1.1 assets remain unchanged. The helper does not change the compatibility reference.

In builds that include the helper, routine request summaries use CHIM's native `Logger` when it is already loaded, writing to the server's `log/chim.log`. If CHIM's logger is unavailable, the helper falls back to PHP `error_log()` (the PHP/Apache error sink in the inspected deployment). It does not select a plugin-specific log path or change CHIM's global log level. Each JSON payload is a single escaped line, but CHIM adds its timestamp/level prefix, so the whole `chim.log` file is not pure JSONL. CHIM's configured minimum level can suppress plugin records; warning/error records are also duplicated to the PHP/Apache error log.

The default level is info. To opt in to diagnostic details for a test, set `MIND_POISONING_LOG_LEVEL=debug` in the environment inherited by the PHP web worker, then reload/restart that worker as required by its service. Setting the variable only in a shell does not change an already-running worker. Records include a per-request `request_id` and, when available, the validated `utterance_id`; use those to follow one callback and correlate it with CHIM's chat event.

The helper accepts only allowlisted IDs, result codes, timings, counts, numeric affinity values, and subject tokens. There are no dedicated raw-speech, name, prompt, or credential fields, and arbitrary exception messages are dropped. Optional debug `model_reason` text is untrusted and capped at 240 UTF-8 bytes; it can still contain names or a limited excerpt of game/model text, so leave debug off unless diagnosing a test. No `request_finished` record is guaranteed if PHP exits before finishing, and logging failures are swallowed so they cannot break the ACK; a failed sink can therefore lose records.

Persistence records include `commit_state`: `not_attempted` means no COMMIT was sent, `confirmed` means the store reported a successful COMMIT, and `unconfirmed` means COMMIT was attempted without reliable confirmation. `committed: true` is emitted only for a confirmed commit. A false value means the plugin did not confirm the commit; it does not prove the database made no change if the connection failed while acknowledging COMMIT. `cleanup_failed` is reported separately because rollback/release cleanup can fail after a confirmed commit.

The current event stream uses debug-only `ack_started`, `ack_eligible`, `model_started`, and per-subject `judgment_proposal` details. `model_finished` is info for a valid response, warning for an invalid response, and error for a failed call. `persistence_finished` reports persistence results, while `persistence_cleanup_failed` is error-level. One final `request_finished` record summarizes skipped, rejected, failed, or committed outcomes; cleanup failure raises its level to error without rewriting a confirmed commit outcome.

Typed, allowlisted reason codes for known connector/request failures and model-response validation rejections were introduced earlier and remain in v0.1.5. Connector/request failures remain error-level, and validation rejections retain warning-level reporting. Unexpected exceptions use generic fallback reasons; exception and provider text are not logged. Published v0.1.2, v0.1.3, and v0.1.4 assets remain historical and unchanged.

## v0.1.4 changes

The model now returns a strict `subject_mentioned` boolean for each lexical name candidate. A non-mention must have zero delta; missing/non-boolean decisions and nonzero non-mentions fail closed before persistence. This lets the model distinguish a name such as May from the ordinary word “may” without a capitalization heuristic. It is still model judgment, not deterministic entity recognition, and false lexical candidates can still use a subject slot.

An empty readable CHIM log is now shown as empty rather than truncated. The installed plugin manifest also includes Plugin Manager schema-2 update metadata and a per-plugin candidate channel. Published v0.1.3 installs lack that metadata and need a one-time v0.1.4 package sync first. After that, the Manager can use the installed manifest's channel for version checks; an official catalog entry is needed for catalog listing, not for that installed-manifest fallback. Until catalog submission/approval, use direct file sync for initial installation.

Run the focused logging checks from the repository root with `php tests/logging_test.php` and `php tests/store_logging_test.php`.

The inspected CHIM UI truncates `.log` files larger than 25 MiB when its index path runs; that is truncation, not archival rotation. The installed Apache logrotate rule covers `/var/log/apache2/*.log`, not CHIM's `log/chim.log`. Do not assume that the CHIM log has a separate rotating archive.

## Dashboard

The v0.1.5 candidate packages a read-only dashboard at `ext/mind_poisoning/dashboard.php`, with its data reader, stylesheet, and poster art beside it. It samples at most 100 listeners selected by their newest valid retained event IDs; it is not an exhaustive global history view. See the [dashboard guide](dashboard.md) for access assumptions, read limits, and fixture checks. The Windows `localhost` route was checked with an isolated responder; installed CHIM authentication and dashboard use remain unverified.

## v0.1.5 changes

- The dashboard reader selects at most 100 active-profile listeners by each listener's newest valid retained event ID before applying its limit. A real PostgreSQL check used an isolated synthetic schema; compatibility and concurrency against a live CHIM database remain unverified.
- The dashboard's existing 1536×1024 poster is now WebP: 649,836 bytes instead of 3,615,533 bytes, about 82% smaller. The source PNG is preserved outside the runtime package.
- The release includes an MO2 import wrapper containing exactly `CHIM/server-plugins/mind_poisoning/0.1.5.dwpkg`. The wrapper is for local file sync, not the repository tarball format used for catalog/Plugin Manager ingestion.
- The dashboard guide describes the Windows-to-WSL `localhost` path. The isolated localhost responder does not prove installed CHIM routing, web authentication, or live dashboard behavior.

## Runtime contract

- Process only `_speech` ACKs bound to one chat event by exact utterance ID, then validate speaker, listener, explicit non-broadcast target, and active playthrough. Use the client-reported ACK `speech` for subject extraction and to check that model-cited excerpts occur in that report. The excerpt match does not establish that a claim is true or independently prove audio playback. Keep the event ID and identities as the persistence anchor. Do not use fuzzy or tail matching to select an event.
- Candidate extraction uses case-insensitive whole-word matches, so ordinary words can create false candidates for NPCs with common-word names. The v0.1.4 model response requires a strict boolean `subject_mentioned`; false requires delta zero. The parser rejects a missing/non-boolean flag or false with a nonzero delta, then strips the flag before passing the unchanged judgment shape to persistence. The prompt asks the model to distinguish person references from ordinary-word uses without relying on capitalization alone, so lowercase names remain eligible. This is semantic model judgment, not deterministic entity recognition; a misclassification remains possible. False lexical candidates still count toward the cap of 8 subjects and may be sent with the same single model call.
- Evaluate only known NPCs or Player, up to 8 subjects and 12,000 bytes of speech. Judgments use integer deltas from -5 to +5, including zero; resulting affinity is clamped to -100..100. All judgments still require valid bounded reasons and exact utterance excerpts, including zero decisions.
- Change only the listener-to-subject affinity edge. Preserve relationship types and unrelated data; do not write Skyrim relationship ranks or promote hearsay to shared world knowledge.
- Allow passive ACKs while the global CHIM interaction switch is On and the captured request generation remains current. Preserve Off, stale-generation, playthrough-token, and runtime-lease checks; recheck interaction state after the synchronous model call.
- Require global relationship processing enabled, `NEVER_CLEAR_RELATIONSHIP_DATA=false`, and a positive configured `RELLLM_CONNECTOR` using `openrouterjson` or `openrouterjsoncached`. Do not use a plugin-selected alternate connector. Calls are synchronous with a 12-second I/O timeout and a 1024-token limit; neither guarantees a hard wall-clock deadline.
- If the current event has a Player subject, reject ambiguous legacy Player aliases before a paid model call and revalidate during commit. NPC-only events remain eligible. Skip locked listener profiles and manual relationship locks. Persistence atomically updates affinity, the bounded exact-event ledger, and the full NPC/history snapshot; zero judgments snapshot too. The ledger retains at most 128 event IDs with an eviction floor. Some upstream relationship writers do not take the shared advisory lock and may overwrite a later update.
- Include at most the last 8 relevant prior events from the same listener and playthrough, with up to 8 subject judgments per event. This untrusted context guides the model; it is not a cooldown or reliable repetition detector.

## Build and checks

Prerequisites: Python 3.10+ and PHP 8.2 for the PHP checks. No provider credentials or live database are needed for these local fixtures.

Build the v0.1.5 release formats into `dist/0.1.5/` from the source tree:

```sh
python -c "from pathlib import Path; from scripts.package import build_package, build_repository_archive, build_mo2_sync_archive; root=Path.cwd(); out=root / 'dist' / '0.1.5'; out.mkdir(parents=True, exist_ok=True); build_package(root, out / 'mind_poisoning-0.1.5.dwpkg'); build_repository_archive(root, out / 'mind_poisoning.tar.gz'); build_mo2_sync_archive(root, out / 'mind_poisoning-0.1.5-mo2.zip')"
```

To reproduce published v0.1.2 assets historically, use a clean checkout of the `mind_poisoning-v0.1.2` tag, not current source. Build into that checkout's `dist/0.1.2/`:

```sh
python -c "from pathlib import Path; from scripts.package import build_package, build_repository_archive; root=Path.cwd(); out=root / 'dist' / '0.1.2'; build_repository_archive(root, out / 'mind_poisoning.tar.gz'); build_package(root, out / 'mind_poisoning-0.1.2.dwpkg')"
```

Published v0.1.2 assets contain eight payload files, including structured logging; the earlier v0.1.1 assets contain seven. v0.1.3 introduced the thirteen-file dashboard payload; v0.1.4 retains that payload. v0.1.5 keeps 13 files but replaces `dashboard-art.png` with `dashboard-art.webp`; the preserved PNG master under `assets/` is not packaged. Keep all historical assets distinct from current source. The repository tarball has one top-level `mind_poisoning/` directory and `manifest.json` is at the package root after the installer strips that directory. The `.dwpkg` is the separate schema-4 package-manager format.

## v0.1.5 MO2 file-sync wrapper

The wrapper is `dist/0.1.5/mind_poisoning-0.1.5-mo2.zip` and contains exactly `CHIM/server-plugins/mind_poisoning/0.1.5.dwpkg`. Import that ZIP as a separate MO2 mod for an isolated test profile. It is not the repository `.tar.gz` format used for catalog/Plugin Manager ingestion. See the [v0.1.5 installation guide](local-candidate-v0.1.5.md). Packaging does not imply live CHIM load, migration, database, provider, or in-game validation; the plugin has no migrations.

Run the local checks:

```sh
python -m unittest discover -s tests -p 'test_package.py' -v
php -l server/influence.php
php -l server/model.php
php -l server/prerequest.php
php -l server/store.php
php tests/influence_test.php
php tests/model_test.php
php tests/runtime_test.php
php tests/logging_test.php
php tests/store_logging_test.php
php tests/dashboard_preview.php --self-test
php tests/dashboard_data_test.php
php tests/dashboard_http_test.php
php tests/dashboard_integration_test.php
php tests/dashboard_empty_log_test.php
php tests/manifest_update_check.php
```

`manifest_update_check.php` reads the installed Manager/installer source (default `/var/www/html/HerikaServer`; override with `CHIM_HERIKASERVER_ROOT`) and extracts pure helper declarations. Its fetch is locally stubbed; it does not run the pages or contact endpoints.

These fixture checks do not prove live PostgreSQL transactions/concurrency, provider behavior, or in-game behavior. The synchronous candidate has not been verified in play; its I/O timeout does not bound total wall time. The documented `.dwpkg` sync route is not verified by these tests.

## Release and catalog

Repository: [Francisco-boop-001/CHIM-Plugins](https://github.com/Francisco-boop-001/CHIM-Plugins). The v0.1.5 PRE-ALPHA candidate uses tag `mind_poisoning-v0.1.5`; its repository `.tar.gz`, schema-4 `.dwpkg`, and MO2 wrapper are separate formats. Earlier [v0.1.4](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.4), [v0.1.3](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.3), [v0.1.2](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.2), [v0.1.1](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.1), and [v0.1.0](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.0) remain available as historical versions.

The catalog snippet is `distribution/plugin_repository_entry.json`; its separate submission draft remains pending maintainer discussion and has not been submitted. The installed plugin manifest URL stays on `main/server/manifest.json`; the channel package URL substitutes the fetched manifest version into the plugin-specific `mind_poisoning-v<version>` release path. Existing installed manifests provide the same update channel if the catalog lookup misses. Publish and verify the tagged asset before advancing the `main` manifest/catalog pointer so it does not advertise a missing package. Existing v0.1.3 installations need one file-sync upgrade because their installed manifest lacks `schema_version: 2`; the v0.1.5 manifest enables subsequent version comparisons. Keep `status` as `development_candidate`; the official catalog entry has not been submitted or approved. Avoid `releases/latest`, which is shared across plugins.

There is an upstream multi-plugin matching limitation: `ui/server_plugins.php` returns the first entry whose repository or package name matches. Before adding another catalog entry with this same `git_repo`, CHIM should prioritize an exact package-name match. Version-specific package URLs avoid release selection ambiguity but do not fix catalog identity matching.

## Design and evidence

See the [accepted design](../tasks/design.md), [verification record](../tasks/verification.md), [repository package report](../tasks/repository-package-report.md), and [client ACK evidence review](../tasks/critique-client-evidence.md). Earlier package evidence concerns v0.1.0 and does not certify this candidate's runtime. Fixture checks do not establish live database, provider, or in-game behavior.
