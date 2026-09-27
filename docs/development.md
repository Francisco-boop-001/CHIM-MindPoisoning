# Development guide

Mind Poisoning v0.1.1 is the development-candidate prerelease for HerikaServer. Download the [repository archive](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.1/mind_poisoning.tar.gz) or [CHIM sync package](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.1/mind_poisoning-0.1.1.dwpkg) from the [release page](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.1). The older [v0.1.0 prerelease](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.0) contains earlier source. Compatibility reference: `cf5030f15781637498be86debe26fcf102f5690d`; this is not a deployment pin. The official CHIM catalog entry has not been submitted or approved. Live runtime behavior remains unverified.

## Runtime contract

- Process only `_speech` ACKs bound to one chat event by exact utterance ID, then validate speaker, listener, explicit non-broadcast target, and active playthrough. Use the client-reported ACK `speech` for subject extraction and model evidence; this is the client's text report, not independent proof of audio playback. Keep the event ID and identities as the persistence anchor. Do not use fuzzy or tail matching to select an event.
- Evaluate only explicitly named known NPCs or Player, up to 8 subjects and 12,000 bytes of speech. Model judgments are integer deltas from -5 to +5, including zero; resulting affinity is clamped to -100..100.
- Change only the listener-to-subject affinity edge. Preserve relationship types and unrelated data; do not write Skyrim relationship ranks or promote hearsay to shared world knowledge.
- Allow passive ACKs while the global CHIM interaction switch is On and the captured request generation remains current. Preserve Off, stale-generation, playthrough-token, and runtime-lease checks; recheck interaction state after the synchronous model call.
- Require global relationship processing enabled, `NEVER_CLEAR_RELATIONSHIP_DATA=false`, and a positive configured `RELLLM_CONNECTOR` using `openrouterjson` or `openrouterjsoncached`. Do not use a plugin-selected alternate connector. Calls are synchronous with a 12-second I/O timeout and a 1024-token limit; neither guarantees a hard wall-clock deadline.
- If the current event has a Player subject, reject ambiguous legacy Player aliases before a paid model call and revalidate during commit. NPC-only events remain eligible. Skip locked listener profiles and manual relationship locks. Persistence atomically updates affinity, the bounded exact-event ledger, and the full NPC/history snapshot; zero judgments snapshot too. The ledger retains at most 128 event IDs with an eviction floor. Some upstream relationship writers do not take the shared advisory lock and may overwrite a later update.
- Include at most the last 8 relevant prior events from the same playthrough, with up to 8 subject judgments per event. This untrusted context guides the model; it is not a cooldown or reliable repetition detector.

## Build and checks

Prerequisites: Python 3.10+ and PHP 8.2 for the PHP checks. No provider credentials or live database are needed for these local fixtures.

After source freeze, build into the versioned directory so the published-era artifacts under `dist/` remain as evidence:

```sh
python -c "from pathlib import Path; from scripts.package import build_package, build_repository_archive; root=Path.cwd(); out=root / 'dist' / '0.1.1'; build_repository_archive(root, out / 'mind_poisoning.tar.gz'); build_package(root, out / 'mind_poisoning-0.1.1.dwpkg')"
```

The repository tarball has one top-level `mind_poisoning/` directory and seven payload files; `manifest.json` is at the package root after the installer strips that directory. The `.dwpkg` is the separate schema-4 package-manager format. These version-pinned assets are built from the frozen v0.1.1 payload.

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
```

These fixture checks do not prove live PostgreSQL transactions/concurrency, provider behavior, or in-game behavior. The synchronous candidate has not been verified in play; its I/O timeout does not bound total wall time. The documented `.dwpkg` sync route is not verified by these tests.

## Release and catalog

Repository: [Francisco-boop-001/CHIM-Plugins](https://github.com/Francisco-boop-001/CHIM-Plugins). The v0.1.1 release uses tag `mind_poisoning-v0.1.1`; its version-pinned repository `.tar.gz` and schema-4 `.dwpkg` are separate formats. The [v0.1.0 prerelease](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.0) is the earlier release.

The catalog snippet is `distribution/plugin_repository_entry.json`. Its `manifest_url` and `package_urls` use the same plugin-specific v0.1.1 tag. Keep `status` as `development_candidate`; the official catalog entry has not been submitted or approved. Avoid `releases/latest`, which is shared across plugins.

There is an upstream multi-plugin matching limitation: `ui/server_plugins.php` returns the first entry whose repository or package name matches. Before adding another catalog entry with this same `git_repo`, CHIM should prioritize an exact package-name match. Tag-pinned URLs avoid release selection ambiguity but do not fix catalog identity matching.

## Design and evidence

See the [accepted design](../tasks/design.md), [verification record](../tasks/verification.md), [repository package report](../tasks/repository-package-report.md), and [client ACK evidence review](../tasks/critique-client-evidence.md). Earlier package evidence concerns v0.1.0 and does not certify this candidate's runtime. Fixture checks do not establish live database, provider, or in-game behavior.
