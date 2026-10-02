# Mind Poisoning v0.1.16 metadata handoff

## Checklist

- [x] Keep the release PRE-ALPHA and set the candidate manifest version to 0.1.16.
- [x] Point current MP links and release indexes at 0.1.16 while retaining historical 0.1.15 assets and the 0.1.14 bridge instructions.
- [x] Document the only reply-cap change: v2 increases from 8 to 24 lines; API v1, v2 capability version 2, eight subjects, and joined-text limits remain unchanged.
- [x] Keep the embedded Private Conversation 0.1.8 caller identified as v1-only; update the source/API fixture pins in the integration guide.
- [x] Remove stale fixed MP/PCV release assertions from `tests/manifest_update_check.php`; retain numeric-version validation and derive each next-version fixture from its manifest. Preserve the historical PCV 0.1.5 fixture.
- [x] Add the 0.1.16 release body and this metadata handoff.
- [x] Complete the Windows-only metadata, link, and scoped whitespace checks below.

## Files

`server/manifest.json`, `README.md`, `server/README.md`, `server/AGENTS.md`, `docs/integration-api.md`, `docs/development.md`, `docs/deployment-migration.md`, `tests/manifest_update_check.php`, `distribution/mind_poisoning-v0.1.16.md`, and this report.

The version-agnostic `distribution/plugin_repository_entry.json` was inspected and did not need a release-number change. Historical package links, the CHIM-Plugins 0.1.14 compatibility bridge, prior release notes, and the embedded PCV sources were left in place.

## Windows-only checks

- PowerShell `ConvertFrom-Json` parsed the MP manifest, catalog entry, and embedded PCV manifest. MP version/repository/package template matched 0.1.16; PCV snapshot remains 0.1.8.
- The three tagged absolute source-document links in the release body resolve to files in the current source tree.
- Static inspection of `tests/manifest_update_check.php` found no fixed MP 0.1.14, PCV 0.1.6, or fake PCV 0.1.7 candidate assertion; the historical PCV 0.1.5 lookup fixture remains.
- `git diff --check -- server/manifest.json README.md server/README.md server/AGENTS.md docs/integration-api.md docs/development.md docs/deployment-migration.md tests/manifest_update_check.php` exited 0.
- A PowerShell scan of the ten owned files found no trailing whitespace or conflict markers.

The PHP update-flow fixture and syntax checks were not run here; the lead owns the clean-export runtime gate.

## Evidence limits

The recorded isolated fixture evidence accepts 13 and 24 reply lines, rejects 25 and joined-text overflow without mutation, and remains fixture-only. A separate user-reported 0.1.15 pair-path account is attributed as unverified report evidence; it does not establish v2 solo behavior or gameplay. No PHP/WSL, package build, install, provider, database, tag, commit, or publication action is part of this metadata handoff. The lead owns the clean-export runtime and release gates.
