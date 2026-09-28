# CHIM plugin development

**Checked 2026-09-27.** CHIM hook, manifest, and installer contracts change; recheck the target HerikaServer source and package before release. This project’s compatibility reference and runtime limits are in [runtime-report.md](../tasks/runtime-report.md).

## Choose the extension route

Use **Data/CHIM** for supported data, not server code. The current importer scans only the top level for `_bios.csv`, `_oghma.csv`, `_dynamicoghma.csv`, `_descriptions.csv`, and `_actions.csv`, then uploads them at CHIM startup. `_voices.csv` is read locally for voice mapping. The guide currently warns above 5 MB and rejects files above 10 MB; verify these version-sensitive limits.

Use a **server plugin** for PHP behavior, pages, prompt/request integration, or plugin services. It installs under `HerikaServer/ext/<plugin-name>`. A Skyrim mod can optionally bundle a versioned `.zip` or `.dwpkg` under `Data/CHIM/server-plugins/<plugin-name>/` for server sync. That path is separate from the top-level CSV importer.

## Use named extension points

The current guide lists these loaded filenames: `globals.php`, `preprocessing.php`, `prerequest.php`, `context_pre.php`, `context.php`, `prepostrequest.php`, `postrequest.php`, `context_building.php`, `prompts.php`, `dialogue_prompt.php`, and `json_response_custom.php`. Add only hooks you need, and verify loader timing in the target version. Prompt injection and actor-profile enrichment use `chimRegisterPromptInjection()` and `chimRegisterActorProfileEnricher()`. Custom actions are currently data-driven through the Action Editor/action catalog; do not invent a hook filename for them.

Keep behavior in plugin-owned files and use supported APIs; do not patch an installed core to make a plugin work. To request built-in catalog listing, submit one entry to [Dwemer-Dynamics/HerikaServer](https://github.com/Dwemer-Dynamics/HerikaServer), branch `unstable`, file [`ui/data/plugin_repository.json`](https://github.com/Dwemer-Dynamics/HerikaServer/blob/unstable/ui/data/plugin_repository.json). The catalog points to your separate plugin repository; it does not host the code.

## Keep manifests and packages distinct

Put `manifest.json` at the extracted plugin root. The current Plugin Manager update flow requires `schema_version: 2` and a nonempty `git_repo`; keep its `default_channel` and channel definitions aligned with the catalog entry. `config_url` is needed only for a plugin page, and `config_url_target` is optional (usually `_blank`). A passing archive check does not prove the manifest update gate is satisfied.

For the repository/Plugin Manager route, publish an asset named exactly `<PACKAGE_NAME>.tar.gz` (or `.tar`). It must contain one `<PACKAGE_NAME>/` directory with `manifest.json` and plugin files inside; the installer strips that directory. In a shared multi-plugin repository, use a stable per-plugin manifest URL (for example, raw `main/<plugin-root>/manifest.json`) and a tag-specific asset path (for example, `<plugin>-v<version>/<PACKAGE_NAME>.tar.gz`). The inspected updater substitutes `<version>` in package URLs after it fetches the channel manifest. A repository-wide `releases/latest` can select another plugin’s release. Publish the tagged asset first, then advance the per-plugin manifest or catalog pointer so every advertised URL resolves to an existing asset.

One current multi-plugin UI limitation remains upstream: the inspected `ui/server_plugins.php` returns the first catalog entry matching either repository or package name. A second plugin sharing the same `git_repo` can therefore resolve to the first entry. Tag-specific URLs avoid release selection ambiguity but do not fix this identity match; do not offer another same-repository catalog entry until the UI prioritizes exact package identity.

The `.dwpkg` used by this project is a separate sync format with an outer schema-4 manifest. That envelope is distinct from the installed plugin’s `manifest.json` schema-2 update gate. Verify each intended consumer; one does not prove the other.

## Store plugin state safely

For ordinary per-NPC values, CHIM documents `NpcMaster::getPluginData()`, `setPluginData()`, and `deletePluginData()`. Use a dedicated plugin key so data does not overwrite another plugin’s values. `setPluginData()` replaces only that plugin's namespace in one SQL `UPDATE`, preserving other namespaces; it does not create a history row or touch game timestamps. The data is server-side, is not a public endpoint or game command, is not added to prompts automatically, and follows NPC-history/playthrough restore rules. See the [storage API review](../tasks/storage-api-review.md) for the inspected source contract.

Do not assume a namespaced setter makes multiple writes atomic. CHIM's `sql` wrappers reconnect per operation and expose no public connection-pinning API, so separate helper calls do not guarantee one transaction. If a feature must change plugin data with relationships, dedupe state, or history snapshots, verify the shared connection, rollback, and snapshot behavior for the target server. A write-authorization helper is not itself a transaction. Mind Poisoning uses a narrow reflection adapter for private `sql::$link` to keep those writes on one native connection; it is version-fragile and fails closed if that private shape changes. Fixtures and read-only SQL planning do not prove live transaction or concurrency behavior, and advisory locking only coordinates writers that use the same lock. See the [storage API review](../tasks/storage-api-review.md) and [runtime report](../tasks/runtime-report.md).

## Match evidence to claims

Lint, fixtures, archive hashes, and package-layout checks prove source/package properties, not live CHIM authentication, database behavior, provider quality, client acknowledgements, or Skyrim behavior. Test those claims separately on a clean isolated server and game profile. A separate MO2 profile alone does not isolate server data.

### References checked 2026-09-27

- [CHIM Modders Guide](https://dwemerdynamics.com/chim/modders-guide.html) — import paths, hook names, manifest, packaging, per-NPC data, and catalog instructions.
- [HerikaServer plugin installer on `unstable`](https://github.com/Dwemer-Dynamics/HerikaServer/blob/unstable/ui/server_plugin_installer.php) — channel and package URL handling; version-sensitive.
- [HerikaServer plugin list on `unstable`](https://github.com/Dwemer-Dynamics/HerikaServer/blob/unstable/ui/server_plugins.php) — current catalog identity matching; version-sensitive.
- [HerikaServer catalog file on `unstable`](https://github.com/Dwemer-Dynamics/HerikaServer/blob/unstable/ui/data/plugin_repository.json) — current catalog shape; version-sensitive.
- [Mind Poisoning runtime report](../tasks/runtime-report.md) — compatibility reference and limits for this implementation.
