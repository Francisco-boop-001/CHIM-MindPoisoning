# Francisco's CHIM Plugins

This project is intended as the shared GitHub home for Francisco's CHIM plugins, starting with **Mind Poisoning**. The GitHub account is `Francisco-boop-001`; the repository name is still pending confirmation. Until then, use the explicit `REPOSITORY_NAME` placeholder in [the Plugin Manager catalog entry](distribution/plugin_repository_entry.json). This project has not published a release or catalog listing.

Mind Poisoning remains in its existing source layout as the first plugin in the shared home.

## First plugin: Mind Poisoning

Version 0.1.0 development candidate for HerikaServer. Compatibility reference: `cf5030f15781637498be86debe26fcf102f5690d`; this is not a deployment pin.

## Status

This remains a development candidate: live PostgreSQL writes/concurrency, provider behavior, and in-game behavior are not verified. Use it only in an isolated test environment; do not deploy or promote a server pin.

## Behavior

The hook processes only an exact CHIM client `_speech` acknowledgement matched to its emitted utterance ID, speaker, listener, text, event ID, and active playthrough. It changes only the listener's affinity toward explicitly named known NPC or Player subjects. Player cannot be the listener. The model returns a delta from -5 through +5, including zero; total affinity is clamped to -100 through +100. No native Skyrim relationship rank is written and hearsay is not added to shared world knowledge.

Events with more than 8 subjects or speech over 12,000 bytes are skipped. Dedupe keeps at most 128 event IDs and advances a numeric floor as entries are evicted. ACKs at or below that floor are skipped, including previously unseen out-of-order events below it.

A listener with nonzero `lock_profile` or a manual relationship lock is skipped; `relationships_locked` follows CHIM's PHP `!empty` semantics.

Processing is disabled when the global relationship feature is off, CHIM interaction is Off, or `NEVER_CLEAR_RELATIONSHIP_DATA=true`. The latter setting preserves affinity scores but rewinds plugin ledger state during core restore. Only the configured `openrouterjson` or `openrouterjsoncached` connector is accepted; this plugin does not select another connector. A configured provider's own model fallback may still apply. Calls are synchronous; `HTTP_TIMEOUT=12` is an I/O timeout, not a hard wall-clock limit.

Persistence uses the existing listener advisory lock and a transaction with a row lock. A guarded `sql::$link` compatibility shim obtains the connection; native statements retain that connection because the core helper may reconnect and autocommit. The affinity change, exact-event ledger, and full NPC/history snapshot are written together, including zero decisions. A write or verified-snapshot failure rolls back the transaction. Upstream relationship writers that do not coordinate on the lock can still overwrite a plugin change later.

## Distribution

For CHIM's catalog and Plugin Manager, the primary distribution is a tag-pinned GitHub release asset named `mind_poisoning.tar.gz`. The catalog does not provide a manual `.dwpkg` upload action. The installer needs GNU `tar`; runtime also requires the relationship feature enabled, interaction not Off, `NEVER_CLEAR_RELATIONSHIP_DATA=false`, and one supported configured connector. The plugin adds no runtime dependencies, migrations, or daemon.

### Publish the first candidate

1. Replace `REPOSITORY_NAME` in [the catalog entry](distribution/plugin_repository_entry.json) with the confirmed repository slug under `Francisco-boop-001`, then publish this source tree to that public repository. Use the selected repository if it already exists; do not create a replacement. Preserve `server/manifest.json` at that source path. Both catalog URLs must be publicly fetchable without authentication.
2. From this project root, build the repository archive:

   ```powershell
   python scripts/package.py --format repository-tar-gz
   ```

   This produces `dist/mind_poisoning.tar.gz`, with one top-level `mind_poisoning/` directory containing the seven payload files, with `manifest.json` at the package root. CHIM's Plugin Manager strips that one directory during extraction.
3. Create tag `mind_poisoning-v0.1.0` from the exact source snapshot used for the archive, then create a GitHub release for that tag. Mark it as a **prerelease** and attach the asset as `mind_poisoning.tar.gz`.
4. Submit the catalog entry to CHIM's authoritative `ui/data/plugin_repository.json`. It points `manifest_url` at the tagged source `server/manifest.json` and `package_urls` at the same tag's release asset. The entry's default channel is `Development candidate`, and `status` remains `development_candidate`; no stable channel is configured.

For each new version, create a new plugin-specific tag/release asset and update both catalog URLs to that same tag. The catalog does not advance them automatically. Keep the explicit tag URLs; `releases/latest` could select a release published for another plugin in the shared repository. The example catalog file is documentation only; release and catalog publication await repository confirmation.

### Multi-plugin catalog limitation

The inspected CHIM UI at `ui/server_plugins.php:368-379` returns the first catalog entry whose repository **or** package name matches. The first Mind Poisoning entry is unaffected, but a later entry sharing this GitHub repository can be mistaken for it before the exact package name is reached. CHIM's UI must prioritize exact package identity before adding another same-repository catalog entry. Tag-pinned manifest and asset URLs prevent `latest` release ambiguity; they do not fix catalog identity matching.

The default `python scripts/package.py` command still builds a separate schema-4 `.dwpkg`; CHIM's catalog route has no manual `.dwpkg` upload action. Removing the plugin stops future evaluations but does not remove or reverse affinity values already stored in CHIM.

## Checks

Packaging fixture checks:

```powershell
python -m unittest discover -s tests -p 'test_package.py' -v
```

Pure influence check:

```powershell
wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/influence_test.php
```

Model adapter check, including syntax checks:

```powershell
wsl.exe -d DwemerAI4Skyrim3 -- bash -lc "set -e; php -l /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/server/model.php; php -l /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/model_test.php; php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/model_test.php"
```

Runtime fixture check:

```powershell
wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/runtime_test.php
```

It prints `runtime store checks passed`; expected failure-path fixture logs and the full command evidence are in [`tasks/runtime-report.md`](tasks/runtime-report.md). All fixtures are local and do not prove live database transactions/concurrency, provider behavior, or in-game behavior. Current repository-tar build, hash, and extraction evidence will be recorded in [`tasks/repository-package-report.md`](tasks/repository-package-report.md). The earlier schema-4 package-manager fixture and archive evidence remain in [`tasks/package-report.md`](tasks/package-report.md).
