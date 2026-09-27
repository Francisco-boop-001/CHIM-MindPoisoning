# Development guide

Mind Poisoning `0.1.0` is an experimental development candidate for HerikaServer. Compatibility reference: `cf5030f15781637498be86debe26fcf102f5690d`; this is not a deployment pin. The official CHIM catalog submission and approval are pending.

## Runtime contract

- Process only exact client `_speech` acknowledgements correlated with the emitted event, identities, text, and active playthrough. Ignore aborts, generated speech, stale or ambiguous events, and Player-as-listener events.
- Evaluate only explicitly named known NPCs or Player, up to 8 subjects and 12,000 bytes of speech. Model judgments are integer deltas from -5 to +5, including zero; resulting affinity is clamped to -100..100.
- Change only the listener-to-subject affinity edge. Preserve relationship types and unrelated data; do not write Skyrim relationship ranks or promote hearsay to shared world knowledge.
- Require CHIM interaction to be allowed, global relationship processing enabled, `NEVER_CLEAR_RELATIONSHIP_DATA=false`, and a positive configured `RELLLM_CONNECTOR` whose connector type is `openrouterjson` or `openrouterjsoncached`. Do not use an alternate connector. Calls are synchronous with a 12-second I/O timeout and a 1024-token limit; neither guarantees a hard wall-clock deadline.
- Skip listeners with a locked profile or manual relationship lock. Persistence atomically updates affinity, the bounded exact-event ledger, and the full NPC/history snapshot. The ledger retains at most 128 event IDs with an eviction floor. Some upstream relationship writers do not take the shared advisory lock and may overwrite a later update.

## Build and checks

Prerequisites: Python 3.10+ and PHP 8.2 for the PHP checks. No provider credentials or live database are needed for these local fixtures.

From the repository root:

```sh
python scripts/package.py --format repository-tar-gz
```

This builds `dist/mind_poisoning.tar.gz`, with a single top-level `mind_poisoning/` directory and seven payload files. `manifest.json` is at the package root after the installer strips that directory. The separate default command `python scripts/package.py` builds `dist/mind_poisoning-0.1.0.dwpkg` for the schema-4 package manager.

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

These fixture checks do not prove live PostgreSQL transactions/concurrency, provider behavior, or in-game behavior. The documented `.dwpkg` sync route is not verified by these tests.

## Release and catalog

Repository: [Francisco-boop-001/CHIM-Plugins](https://github.com/Francisco-boop-001/CHIM-Plugins). The v0.1.0 prerelease tag is `mind_poisoning-v0.1.0`; publish `mind_poisoning.tar.gz` as the preferred catalog/Plugin Manager asset. The schema-4 `.dwpkg` is a separate optional distribution route, not a manual upload to that UI.

The catalog snippet is `distribution/plugin_repository_entry.json`. It pins `manifest_url` to `server/manifest.json` and `package_urls` to the release asset at the same plugin-specific tag. Its default channel is `Development candidate` and `status` remains `development_candidate`. Official catalog inclusion is not yet approved. For later versions, move both URLs to the same new plugin tag; avoid `releases/latest`, which is shared across plugins.

There is an upstream multi-plugin matching limitation: `ui/server_plugins.php` returns the first entry whose repository or package name matches. Before adding another catalog entry with this same `git_repo`, CHIM should prioritize an exact package-name match. Tag-pinned URLs avoid release selection ambiguity but do not fix catalog identity matching.

## Design and evidence

See the [accepted design](../tasks/design.md), [verification record](../tasks/verification.md), and [repository package report](../tasks/repository-package-report.md) for the implementation rationale and checked evidence. Fixture checks do not establish live database, provider, or in-game behavior.
