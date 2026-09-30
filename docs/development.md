# Development guide

Mind Poisoning v0.1.10 is a PRE-ALPHA development-candidate prerelease. Use the [repository archive](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.10/mind_poisoning.tar.gz), [CHIM sync package](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.10/mind_poisoning-0.1.10.dwpkg), or [plain MO2 ZIP](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.10/mind_poisoning-0.1.10-mo2.zip) from the [v0.1.10 release page](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.10); check its `SHA256SUMS.txt` before use. The reported v0.1.7 FOMOD crash remains unresolved; MO2 may show a Skyrim content warning for the plain ZIP's CHIM server files. The prior [v0.1.9](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.9), [v0.1.8](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.8), [v0.1.7](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.7), [v0.1.6](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.6), [v0.1.5](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.5), [v0.1.4](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.4), [v0.1.3](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.3), [v0.1.2](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.2), [v0.1.1](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.1), and [v0.1.0](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.0) remain available. Compatibility reference: `cf5030f15781637498be86debe26fcf102f5690d`; this is not a deployment pin. The official CHIM catalog entry has not been submitted or approved. Live runtime behavior remains unverified.

## Structured logging

The v0.1.2 tarball and `.dwpkg` include the structured logging helper. The earlier v0.1.1 assets remain unchanged. The helper does not change the compatibility reference.

In builds that include the helper, routine request summaries use CHIM's native `Logger` when it is already loaded, writing to the server's `log/chim.log`. If CHIM's logger is unavailable, the helper falls back to PHP `error_log()` (the PHP/Apache error sink in the inspected deployment). It does not select a plugin-specific log path or change CHIM's global log level. Each JSON payload is a single escaped line, but CHIM adds its timestamp/level prefix, so the whole `chim.log` file is not pure JSONL. CHIM's configured minimum level can suppress plugin records; warning/error records are also duplicated to the PHP/Apache error log.

The default level is info. To opt in to diagnostic details for a test, set `MIND_POISONING_LOG_LEVEL=debug` in the environment inherited by the PHP web worker, then reload/restart that worker as required by its service. Setting the variable only in a shell does not change an already-running worker. Records include a per-request `request_id` and, when available, the validated `utterance_id`; use those to follow one callback and correlate it with CHIM's chat event.

The helper accepts only allowlisted IDs, result codes, timings, counts, numeric affinity values, and subject tokens. There are no dedicated raw-speech, name, prompt, or credential fields, and arbitrary exception messages are dropped. Optional debug `model_reason` text is untrusted and capped at 240 UTF-8 bytes; it can still contain names or a limited excerpt of game/model text, so leave debug off unless diagnosing a test. No `request_finished` record is guaranteed if PHP exits before finishing, and logging failures are swallowed so they cannot break the ACK; a failed sink can therefore lose records.

Persistence records include `commit_state`: `not_attempted` means no COMMIT was sent, `confirmed` means the store reported a successful COMMIT, and `unconfirmed` means COMMIT was attempted without reliable confirmation. `committed: true` is emitted only for a confirmed commit. A false value means the plugin did not confirm the commit; it does not prove the database made no change if the connection failed while acknowledging COMMIT. `cleanup_failed` is reported separately because rollback/release cleanup can fail after a confirmed commit.

The current event stream uses debug-only `ack_started`, `ack_eligible`, `model_started`, and per-subject `judgment_proposal` details. `model_finished` is info for a valid response, warning for an invalid response, and error for a failed call. `persistence_finished` reports persistence results, while `persistence_cleanup_failed` is error-level. One final `request_finished` record summarizes skipped, rejected, failed, or committed outcomes; cleanup failure raises its level to error without rewriting a confirmed commit outcome.

Typed, allowlisted reason codes for known connector/request failures and model-response validation rejections were introduced earlier and remain in v0.1.6. Connector/request failures remain error-level, and validation rejections retain warning-level reporting. Unexpected exceptions use generic fallback reasons; exception and provider text are not logged. Published v0.1.2 through v0.1.5 assets remain historical and unchanged.

## v0.1.4 changes

The model now returns a strict `subject_mentioned` boolean for each lexical name candidate. A non-mention must have zero delta; missing/non-boolean decisions and nonzero non-mentions fail closed before persistence. This lets the model distinguish a name such as May from the ordinary word “may” without a capitalization heuristic. It is still model judgment, not deterministic entity recognition, and false lexical candidates can still use a subject slot.

An empty readable CHIM log is now shown as empty rather than truncated. The installed plugin manifest also includes Plugin Manager schema-2 update metadata and a per-plugin candidate channel. Published v0.1.3 installs lack that metadata and need a one-time v0.1.4 package sync first. After that, the Manager can use the installed manifest's channel for version checks; an official catalog entry is needed for catalog listing, not for that installed-manifest fallback. Until catalog submission/approval, use direct file sync for initial installation.

Run the focused logging checks from the repository root with `php tests/logging_test.php` and `php tests/store_logging_test.php`.

The inspected CHIM UI truncates `.log` files larger than 25 MiB when its index path runs; that is truncation, not archival rotation. The installed Apache logrotate rule covers `/var/log/apache2/*.log`, not CHIM's `log/chim.log`. Do not assume that the CHIM log has a separate rotating archive.

## Dashboard

The v0.1.7 candidate packages a read-only dashboard at `ext/mind_poisoning/dashboard.php`, with its data reader, stylesheet, script, and poster art beside it. It samples at most 100 listeners selected by their newest valid retained event IDs; it is not an exhaustive global history view. The dashboard refreshes the current filtered page five seconds after the preceding request finishes, with a 15-second timeout, explicit pause, and hidden-tab pause. It does not call the model/provider or write data. See the [dashboard guide](dashboard.md) for access assumptions, read limits, and fixture checks. The Windows `localhost` route was checked with an isolated responder; installed CHIM authentication and dashboard use remain unverified.

## v0.1.5 changes

- The dashboard reader selects at most 100 active-profile listeners by each listener's newest valid retained event ID before applying its limit. A real PostgreSQL check used an isolated synthetic schema; compatibility and concurrency against a live CHIM database remain unverified.
- The dashboard's existing 1536×1024 poster is now WebP: 649,836 bytes instead of 3,615,533 bytes, about 82% smaller. The source PNG is preserved outside the runtime package.
- The release includes an MO2 import wrapper containing exactly `CHIM/server-plugins/mind_poisoning/0.1.5.dwpkg`. The wrapper is for local file sync, not the repository tarball format used for catalog/Plugin Manager ingestion.
- The dashboard guide describes the Windows-to-WSL `localhost` path. The isolated localhost responder does not prove installed CHIM routing, web authentication, or live dashboard behavior.

## v0.1.6 changes

- Added same-origin automatic refresh for the read-only dashboard. Polls begin five seconds after the previous request completes; a request times out after 15 seconds. Manual pause, hidden tabs, and active interaction pause polling. A failed refresh leaves the last successful regions visible and reports stale status.
- Refresh sends only a GET for the current filtered page. It does not call the model/provider or write data. Without JavaScript, users can reload manually.
- The release includes a MO2 FOMOD file-sync wrapper containing `CHIM/server-plugins/mind_poisoning/0.1.6.dwpkg`. MO2 may still show its custom-content warning; the wrapper does not claim to remove it.
- Existing v0.1.3 installs need one file-sync upgrade to add schema-2 update metadata; v0.1.5 installs already have it. The official catalog entry remains unsubmitted and unapproved.
- Live CHIM authentication, PostgreSQL write/concurrency behavior, provider behavior, client ACK delivery, and in-game/save-load behavior remain unverified. The isolated dashboard preview is not an installed CHIM test.

## v0.1.7 changes

- Added a Player-origin path for CHIM's `inputtext`, `inputtext_s`, `ginputtext`, and `ginputtext_s` requests. It snapshots the post-insert eventlog source tuple, requires one route-selected listener, and correlates through `input_<positive rowid>`; this does not fabricate a `_speech` ACK.
- The listener evaluates the Player's submitted text about explicitly named other NPCs. The Player and listener are excluded as subjects. `source_people` can contain bystanders; it does not define additional listeners. Narrator, everyone/broadcast, ambiguous routes, and uncertain identities are skipped.
- Player-origin records use an explicit `speaker_kind=player`; missing legacy IDs are never used to infer Player. Structured logs have no dedicated Player name or speaker-ID fields, though opt-in debug model rationale can quote names or a short text excerpt. The existing ledger format remains unchanged.
- Live CHIM input delivery, PostgreSQL writes/concurrency, provider behavior, and in-game acceptance remain unverified. The model can misclassify a mention; test only in an isolated server/database and game profile.

## Runtime contract

- Published v0.1.6 processes `_speech` ACKs bound to one chat event by exact `utt_` ID, then validates speaker, listener, explicit non-broadcast target, and active playthrough. Use the client-reported ACK `speech` for subject extraction and to check that model-cited excerpts occur in that report. The excerpt match does not establish that a claim is true or independently prove audio playback. Keep the event ID and identities as the persistence anchor. Do not use fuzzy or tail matching to select an event.
- v0.1.7 adds `postrequest.php` for `inputtext`, `inputtext_s`, `ginputtext`, and `ginputtext_s`. It matches CHIM's post-insert eventlog snapshot to exactly one row by type, timestamp, game time, data, local timestamp, and session, then uses `input_<positive rowid>` in the existing `utterance_id` correlation slot. This is a distinct Player-origin event, not a fabricated `_speech` ACK. It requires one route-selected NPC listener and skips narrator/everyone-broadcast modes or ambiguous routes/identities; `source_people` may include bystanders and does not define the addressee. Logs carry `speaker_kind=player` without dedicated Player speaker-ID/name fields; opt-in debug rationale may quote names or a short text excerpt. Legacy records without a marker are not inferred to be Player. The ledger shape is unchanged.
- Candidate extraction uses case-insensitive whole-word matches, so ordinary words can create false candidates for NPCs with common-word names. The v0.1.4 model response requires a strict boolean `subject_mentioned`; false requires delta zero. The parser rejects a missing/non-boolean flag or false with a nonzero delta, then strips the flag before passing the unchanged judgment shape to persistence. The prompt asks the model to distinguish person references from ordinary-word uses without relying on capitalization alone, so lowercase names remain eligible. This is semantic model judgment, not deterministic entity recognition; a misclassification remains possible. False lexical candidates still count toward the cap of 8 subjects and may be sent with the same single model call.
- Evaluate only known NPCs or Player, up to 8 subjects and 12,000 bytes of speech. Judgments use integer deltas from -5 to +5, including zero; resulting affinity is clamped to -100..100. All judgments still require valid bounded reasons and exact utterance excerpts, including zero decisions.
- Change only the listener-to-subject affinity edge. Preserve relationship types and unrelated data; do not write Skyrim relationship ranks or promote hearsay to shared world knowledge.
- Allow passive ACKs while the global CHIM interaction switch is On and the captured request generation remains current. Preserve Off, stale-generation, playthrough-token, and runtime-lease checks; recheck interaction state after the synchronous model call.
- Profile scope is explicit: zero active CHIM Playthrough Saves profiles uses `unprofiled` shared-server database scope; exactly one active profile retains its numeric isolation; multiple, invalid, or ambiguous profile states skip evaluation. With CHIM auto-switch Off, there is no reliable unique Skyrim save ID. Source matching and deduplication remain within the shared server database, so loading another game save without switching its database/profile is not automatically isolated.
- Require global relationship processing enabled, `NEVER_CLEAR_RELATIONSHIP_DATA=false`, and a positive configured `RELLLM_CONNECTOR` using `openrouterjson` or `openrouterjsoncached`. Do not use a plugin-selected alternate connector. Calls are synchronous with a 12-second I/O timeout and a 1024-token limit; neither guarantees a hard wall-clock deadline.
- If the event has the Player as subject or speaker, reject ambiguous legacy Player aliases before a paid model call and revalidate during commit. NPC-only events remain eligible. Skip locked listener profiles and manual relationship locks. Persistence atomically updates affinity, the bounded exact-event ledger, and the full NPC/history snapshot; zero judgments snapshot too. The ledger retains at most 128 event IDs with an eviction floor. Some upstream relationship writers do not take the shared advisory lock and may overwrite a later update.
- Include at most the last 8 relevant prior events from the same listener and playthrough, with up to 8 subject judgments per event. This untrusted context guides the model; it is not a cooldown or reliable repetition detector.

## v0.1.10 diagnostics and transaction bounds

For `_speech` payload validation, v0.1.10 keeps the existing `invalid-payload` or `oversized` return status and warning severity for malformed payloads, while `request_finished.reason` records a fixed safe subreason. The v0.1.9 rejection-code set was `payload_raw_type_invalid`, `payload_raw_oversized`, `payload_invalid_utf8`, `payload_json_invalid`, `payload_root_invalid`, `payload_field_missing`, `payload_field_type_invalid`, `payload_field_oversized`, `payload_field_empty`, `payload_utterance_id_missing`, `payload_utterance_id_type_invalid`, and `payload_utterance_id_invalid`. These distinguish input-shape failures without logging the body, speech, names, or invalid field values. Released v0.1.9 treated an absent `utterance_id` as warning `payload_utterance_id_missing`; v0.1.10 skips only otherwise-valid absent or empty/whitespace-only string IDs as info status `untracked-speech`, reason `utterance_id_absent`. Wrong-type and malformed IDs remain warnings.

A post-release shape-decoding analysis in the user-supplied `critique.md` report correlates all 18 observed historical `invalid-payload` warnings, including the six previously highlighted, with valid ACK JSON lacking `utterance_id`. This guide attributes that result to the report and does not claim to have independently re-decoded the requests. The v0.1.10 correction skips only otherwise-valid absent or empty/whitespace-only string IDs as info status `untracked-speech`, reason `utterance_id_absent`; wrong-type and malformed IDs remain warnings. This change is not in released v0.1.9. See the [ACK diagnostics report](../tasks/ack-diagnostics-report.md) for v0.1.9 fixed-code test evidence.

After `BEGIN`, `PostgresStoreDb::beginForListener()` sets transaction-local PostgreSQL limits: `lock_timeout` is capped at 1,000 ms and `statement_timeout` at 3,000 ms. Smaller positive inherited values are preserved; zero or looser values are reduced to the plugin cap. PostgreSQL restores the prior session settings on commit or rollback. These limits apply to each lock wait and statement, not total transaction or request wall time. Failures retain the existing safe persistence stage reason; the adapter does not parse database error text or guess SQLSTATEs. The [store timeout report](../tasks/store-timeout-report.md) records the isolated PostgreSQL 15 check.

## v0.1.10 operator pause control

Introduced in v0.1.9 and retained in v0.1.10, the operator-managed file is `data/mind_poisoning.json` under the CHIM server root, outside the plugin package. `ENGINE_PATH` must resolve to an absolute CHIM root with `main.php` (a symlink is allowed). A missing `data/` directory or missing control file means enabled. A `data/` symlink is accepted if it resolves to a readable directory; dangling, non-directory, or unreadable data paths fail closed. The control itself must be a regular non-symlink file no larger than 1,024 bytes. It accepts only the object shape `{"enabled":true}` or `{"enabled":false}`, with normal JSON whitespace (space, tab, CR, or LF); duplicate keys and escaped spellings of the key are rejected. Malformed, extra/missing, or unreadable state fails closed with status `control-invalid` and reason `pause_control_invalid`; `enabled:false` returns `plugin-paused` with reason `plugin_paused`. Each check clears PHP's stat cache. These result codes are fixed and the path/control contents are not logged. Full CHIM-server replacement/restore preservation is unverified.

Operators can update the file with an atomic same-directory rename. From the CHIM root, pause with:

```sh
mkdir -p -m 0755 data
tmp=$(mktemp data/.mind_poisoning.XXXXXX) &&
printf '%s\n' '{"enabled":false}' > "$tmp" &&
chmod 0644 -- "$tmp" &&
mv -f -- "$tmp" data/mind_poisoning.json
```

To resume, use the same commands with `{"enabled":true}`. Ensure the PHP worker can traverse the directory and read the file; adjust existing `data/` permissions if needed. Both NPC ACK and Player-input flows read state before model work and again after model validation, immediately before persistence. If the post-model read observes `enabled:false`, persistence is not started. An atomic rename after a read has opened the old file may leave that read observing the old contents, and a pause after the second check can race with persistence already starting. A pause does not cancel an in-progress provider request, undo committed affinities, or clear the event ledger. State changes are filesystem-administered; the dashboard remains read-only and has no write endpoint. Package updates leave this non-packaged file outside the plugin payload, but CHIM server replacement/restore behavior has not been verified.

## Build and checks

Prerequisites: Python 3.10+ and PHP 8.2 for the PHP checks. No provider credentials or live database are needed for these local fixtures.

To reproduce the published v0.1.8 formats, use a clean checkout of the `mind_poisoning-v0.1.8` tag and build into that checkout's `dist/0.1.8/`. Do not run this historical build command from the current working tree, which contains newer v0.1.10 changes and could overwrite versioned artifacts with different bytes:

```sh
python -c "from pathlib import Path; from scripts.package import build_package, build_repository_archive, build_mo2_sync_archive; root=Path.cwd(); out=root / 'dist' / '0.1.8'; out.mkdir(parents=True, exist_ok=True); build_package(root, out / 'mind_poisoning-0.1.8.dwpkg'); build_repository_archive(root, out / 'mind_poisoning.tar.gz'); build_mo2_sync_archive(root, out / 'mind_poisoning-0.1.8-mo2.zip')"
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
node tests/dashboard_refresh_test.js
```

`manifest_update_check.php` reads the installed Manager/installer source (default `/var/www/html/HerikaServer`; override with `CHIM_HERIKASERVER_ROOT`) and extracts pure helper declarations. Its fetch is locally stubbed; it does not run the pages or contact endpoints.

These fixture checks do not prove live PostgreSQL transactions/concurrency, provider behavior, or in-game behavior. The synchronous candidate has not been verified in play; its I/O timeout does not bound total wall time. The documented `.dwpkg` sync route is not verified by these tests.

## v0.1.7 MO2 file-sync wrapper

The wrapper is `dist/0.1.7/mind_poisoning-0.1.7-mo2.zip` and contains the versioned package at `CHIM/server-plugins/mind_poisoning/0.1.7.dwpkg`. It uses a FOMOD file mapping for MO2 installation. It is not the repository `.tar.gz` format used for catalog/Plugin Manager ingestion; MO2 may still show its custom-content warning. See the [current player installation guide](mind-poisoning.md). Packaging does not imply live CHIM loading, database, provider, authentication, or in-game validation; the plugin has no migrations.

## Release and catalog

Repository: [Francisco-boop-001/CHIM-Plugins](https://github.com/Francisco-boop-001/CHIM-Plugins). The v0.1.10 PRE-ALPHA candidate uses tag `mind_poisoning-v0.1.10`; its repository `.tar.gz`, schema-4 `.dwpkg`, and plain MO2 ZIP are separate formats. The prior [v0.1.9](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.9) and earlier [v0.1.8](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.8), [v0.1.6](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.6), [v0.1.5](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.5), [v0.1.4](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.4), [v0.1.3](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.3), [v0.1.2](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.2), [v0.1.1](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.1), and [v0.1.0](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.0) remain available as historical versions. The v0.1.7 FOMOD wrapper remains historical and its reported crash is unresolved.

The catalog snippet is `distribution/plugin_repository_entry.json`; its separate submission draft remains pending maintainer discussion and has not been submitted. The installed plugin manifest URL stays on `main/server/manifest.json`; the channel package URL substitutes the fetched manifest version into the plugin-specific `mind_poisoning-v<version>` release path. Existing installed manifests provide the same update channel if the catalog lookup misses. Publish and verify the tagged asset before advancing the `main` manifest/catalog pointer so it does not advertise a missing package. Existing v0.1.3 installations need one file-sync upgrade because their installed manifest lacks `schema_version: 2`; v0.1.5 manifests already enable version comparisons. Keep `status` as `development_candidate`; the official catalog entry has not been submitted or approved. Choose one install route per CHIM server: if Plugin Manager installs the plugin, disable/remove any older MO2 file-sync source before its next SAVE LOAD. A disposable Skyrim save alone does not isolate the `unprofiled` shared-database scope. Avoid `releases/latest`, which is shared across plugins.

There is an upstream multi-plugin matching limitation: `ui/server_plugins.php` returns the first entry whose repository or package name matches. Before adding another catalog entry with this same `git_repo`, CHIM should prioritize an exact package-name match. Version-specific package URLs avoid release selection ambiguity but do not fix catalog identity matching.

## Design and evidence

See the [accepted design](../tasks/design.md), [verification record](../tasks/verification.md), [repository package report](../tasks/repository-package-report.md), and [client ACK evidence review](../tasks/critique-client-evidence.md). Earlier package evidence concerns v0.1.0 and does not certify this candidate's runtime. Fixture checks do not establish live database, provider, or in-game behavior.
