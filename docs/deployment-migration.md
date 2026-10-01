# Deployment repositories, packages, and migration

Mind Poisoning and Private Conversation use separate deployment identities. Publish and verify each versioned asset before advancing its `main` manifest or catalog pointer. `CHIM-Plugins` remains the historical collection and migration hub; preserve its existing release tags and assets.

## Canonical source and package index

| Plugin | Candidate | Deployment repository | Manifest | Versioned release |
| --- | --- | --- | --- | --- |
| Mind Poisoning | 0.1.14 PRE-ALPHA | [CHIM-MindPoisoning](https://github.com/Francisco-boop-001/CHIM-MindPoisoning) | `server/manifest.json` | [`mind_poisoning-v0.1.14`](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/tag/mind_poisoning-v0.1.14) |
| Private Conversation | 0.1.8 PRE-ALPHA | [CHIM-PrivateConversation](https://github.com/Francisco-boop-001/CHIM-PrivateConversation) | `server/manifest.json` | [`private_conversation-v0.1.8`](https://github.com/Francisco-boop-001/CHIM-PrivateConversation/releases/tag/private_conversation-v0.1.8) |

Each release has three separate consumer formats: a repository `.tar.gz` with one package directory for Plugin Manager ingestion, a versioned schema-4 `.dwpkg` for CHIM file sync, and an MO2 ZIP containing that `.dwpkg` at `CHIM/server-plugins/<package>/<version>.dwpkg`. Use the release's own `SHA256SUMS.txt`; do not substitute `releases/latest`.

Mind Poisoning remains developed at this repository root. Its embedded `plugins/private_conversation/` tree is a pinned integration fixture and is not included in the Mind Poisoning runtime package. The canonical Private Conversation authoring tree is the [CHIM-PrivateConversation deployment repository](https://github.com/Francisco-boop-001/CHIM-PrivateConversation); the sibling historical checkout is not the source for future releases. For each matched tag, keep the embedded test snapshot aligned with the standalone release source, and verify the clean export before packaging.

## Existing-install migration

### Mind Poisoning 0.1.13

The published 0.1.13 channel uses a `<version>` package URL under `CHIM-Plugins`, so its existing manifest can discover a newer manifest from the old hub. Keep a version-matched 0.1.14 compatibility archive under the old hub and update the old hub's current manifest only after the same 0.1.14 bytes and the new-repository release have passed clean-tag checks. The 0.1.14 package manifest identifies `Francisco-boop-001/CHIM-MindPoisoning`; verify the installed version and `git_repo` before relying on later updates from the new deployment repository. The old 0.1.13 tag and assets remain unchanged.

### Private Conversation 0.1.4 and 0.1.5

Both published manifests point to literal, version-specific `CHIM-Plugins` package assets: [0.1.4](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/private_conversation-v0.1.4) and [0.1.5](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/private_conversation-v0.1.5). In particular, 0.1.5 does not substitute a future version into its package URL, so its Plugin Manager update button cannot migrate itself to the new repository. Keep the 0.1.4 and 0.1.5 assets unchanged. Replace the existing PCV server package once with 0.1.8 using manual `.dwpkg` sync or a supported installer channel configured for the exact `Francisco-boop-001/CHIM-PrivateConversation` repository. Then verify the installed manifest reports version `0.1.8` and that `git_repo` value. The standalone 0.1.8 manifest reads the dedicated repository's `main/server/manifest.json` and uses a version-substituted release URL, so later releases can update through that channel after the versioned assets and `main` manifest are advanced. The [0.1.8 `CHIM-Plugins` bridge](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/private_conversation-v0.1.8) is a versioned download, not a way for the old 0.1.4/0.1.5 literal URLs to self-migrate.

### Private Conversation 0.1.8 state migration

Before updating, quiesce old PCV requests and keep HTTP access restricted through the first state resolution. Migration runs on first state access, not installation. The default state path uses a validated operating-system temporary root separate from `PCV_LOG_DIR`; the operating system may clean it. A valid legacy state directory moves only by atomic `rename()` when the destination is absent and both paths share a filesystem. Migration fails closed on corrupt, unsafe, unknown or conflicting data and on `EXDEV`; the legacy source remains in place, with no copy, merge or reset.

The private path depends on the effective PHP worker UID, canonical extension path, and temporary-root namespace. A CLI temp directory or matching UID does not establish the HTTP worker's path; separate namespaces such as systemd `PrivateTmp` can resolve different roots. Until migration succeeds, legacy state may remain HTTP-readable if server overrides are ignored, so the package is not an independent access boundary. This source and isolated-fixture evidence does not establish installed CHIM or PHP-FPM behavior.

For either plugin, use one server-package route at a time. Disable or remove older enabled MO2/file-sync copies before switching to Plugin Manager; do not stack versions. A download or catalog selection alone does not prove which code is installed.

## Catalog identity and verification limits

At inspected CHIM revision `cf5030f15781637498be86debe26fcf102f5690d`, the catalog lookup returns the first entry whose `git_repo` **or** package `name` matches. Unique `git_repo` values prevent these two plugins from colliding; a legacy installed manifest can still select its matching entry by unique package name after the catalog row moves to the new repository. The prepared Mind Poisoning catalog draft uses the new identity. It has not been submitted or approved, and no catalog edit is part of this release work.

The cross-plugin reflection check is intentionally source-bound. The immutable Mind Poisoning 0.1.14 tag retains its matching Private Conversation 0.1.6 snapshot and can run the evaluator-to-observer-to-importer fixture from that clean source tag. That historical snapshot is distinct from the newer Private Conversation 0.1.8 standalone source and the corresponding current collection-main integration snapshot; this PCV bridge does not retag or rebuild Mind Poisoning 0.1.14. The standalone PCV `reflection_registry_check.php` requires matched MP 0.1.14 evaluator/API fixtures; run it with that checkout or use the pinned check from the MP source tree. A PCV-only export cannot provide MP source. Runtime packages include only each plugin's explicit server-file allowlist, not the other plugin or test suites.

These repository, manifest, fixture, and package checks do not establish clean live installation, proxy/authentication topology, live database/provider behavior, or Skyrim speech/audio delivery. Both candidates remain PRE-ALPHA; the compatibility reference is unchanged and is not an installation pin.
