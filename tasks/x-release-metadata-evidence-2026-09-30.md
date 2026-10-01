# MP 0.1.14 / PCV 0.1.6 metadata gate evidence

## Scope

Reviewed deployment metadata and current install/API documentation for the distinct `CHIM-MindPoisoning` and `CHIM-PrivateConversation` repositories. Historical CHIM-Plugins assets remain referenced as migration sources; no catalog submission, remote write, installed plugin change, or live service was performed.

## Focused manifest/installer gate

Command:

```text
wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/manifest_update_check.php
```

Output:

```text
PASS: actual installed manager helpers resolve the candidate manifest and next-version update URL.
PASS: actual installer helpers fetch the fixture manifest and resolve the plugin-specific release asset without network access.
PASS: legacy v0.1.3 remains outside schema-2 update eligibility; MP 0.1.13 and PCV 0.1.5 resolve their new deployment entries by unique package name.
PASS: MP and PCV identities resolve correctly in both two-entry orders and reject the other plugin in single-entry catalogs.
```

The installer fixture stubs fetches and uses the existing local helper source. It does not contact a catalog or release endpoint.

## Package and documentation checks

The lead's final focused run of `plugins/private_conversation/tests/package_check.py` exited 0 with four tests passing, including deterministic build, explicit file allowlist, manifest identity, repository tar, schema-4 package and MO2 wrapper checks. The later changes were documentation-only; no package input changed afterward.

The final scoped `git diff --check` completed without whitespace errors (Git noted existing CRLF normalization for README files). PowerShell parsed `distribution/plugin_repository_entry.json` and `distribution/submission/mind-poisoning.json`. A relative-link check passed for the 11 current README/developer/release-note documents:

```text
PASS: submission/catalogue JSON parses; local Markdown links in 11 touched documents resolve.
```

These checks establish local metadata/package structure only. They do not prove published assets, installed-channel behavior, live database/provider behavior, proxy/authentication topology, or in-game delivery.
