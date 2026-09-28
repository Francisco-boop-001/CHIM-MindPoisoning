# Plugin Manager manifest update flow — 2026-09-27

## Finding

The installed CHIM Plugin Manager only renders its Update action when the installed plugin manifest has `schema_version == 2` and a nonempty `git_repo` (`/var/www/html/HerikaServer/ui/server_plugins.php`, lines 461–475 and 507–516). Its installer writes `channel` and injects `git_repo` when installing, but does not add `schema_version` (`ui/server_plugin_installer.php`, lines 319–361). The tagged v0.1.3 manifest lacks all update-flow fields; a catalog edit cannot retrofit metadata into that release. Existing v0.1.3 installs therefore need a one-time direct/file-sync upgrade to a metadata-bearing package before the Manager can expose version-based updates.

For subsequent releases, the installed consumer supports the existing `candidate` channel ID with an explicit manifest URL to this repository's `main/server/manifest.json` and a plugin-specific release URL containing `/mind_poisoning-v<version>/`. The Manager reads the channel manifest version; the installer fetches that manifest and substitutes `<version>` in package URLs before download. This avoids repository-wide `releases/latest`. `allow_force` is false so ordinary updates depend on remote version comparison. The ID and label remain `candidate` / `Development candidate`, preserving channel identity and release status.

The current installed `findPluginRepositoryEntry` matches either repository or package name and returns the first match. If an earlier catalog entry is added with this same repository, it can select the wrong plugin's channel. The present catalog has only Mind Poisoning for this repository; fixing the shared-core matching rule is outside this plugin-only change.

## Changes

- `server/manifest.json`: added Plugin Manager `schema_version: 2`, `git_repo`, and a candidate channel with explicit main-branch manifest URL and versioned release asset. Kept version `0.1.3`, `development_candidate`, and the compatibility reference unchanged.
- `distribution/plugin_repository_entry.json`: retained the existing candidate channel identity and aligned its manifest and package URLs with the new version-resolution path.
- Added `tests/manifest_update_check.php`, which extracts and runs only pure helper declarations from the installed UI and installer source. It stubs manifest fetches locally and does not include either full entry point.

The manifest's `schema_version: 2` is Plugin Manager update metadata. The schema-4 `.dwpkg` wrapper is a separate format and was not changed. The published `mind_poisoning-v0.1.3` tag still has the old manifest and old archives; this working-source follow-up does not repair or replace them. Build and publish a later version before claiming the metadata path is live.

## Verification

Command:

```text
wsl.exe -d DwemerAI4Skyrim3 -- php -l /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/manifest_update_check.php && wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/manifest_update_check.php
```

Output:

```text
No syntax errors detected in /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/manifest_update_check.php
PASS: actual installed manager helpers resolve the candidate manifest and next-version update URL.
PASS: actual installer helpers fetch the fixture manifest and resolve the plugin-specific release asset without network access.
PASS: legacy v0.1.3 lacks schema-2 update eligibility; new metadata does not change the published package.
PASS: catalog-miss manifest fallback and shared-repository first-match limitation reproduced.
```

The harness exercised extracted pure functions from the read-only installed files `/var/www/html/HerikaServer/ui/server_plugins.php` and `ui/server_plugin_installer.php`; its fetch stub rejects any URL other than the fixture manifest. The simulated next version is derived from the current manifest version, so a subsequent package release does not require a test edit. This is an isolated source-helper/contract check, not full manager rendering or install validation. The full web pages, database, HTTP endpoints, installer download/extraction, and migrations were not run.

The [CHIM modders guide](https://dwemerdynamics.com/chim/modders-guide.html) documents `schema_version: 2` for Plugin Manager updates. The local behavior finding above comes from the installed files named in this report, not a moving upstream branch. The manifest's pinned compatibility reference is [CHIM commit `cf5030f15781637498be86debe26fcf102f5690d`](https://github.com/Dwemer-Dynamics/HerikaServer/tree/cf5030f15781637498be86debe26fcf102f5690d).
