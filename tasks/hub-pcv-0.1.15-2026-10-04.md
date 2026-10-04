# Hub PCV 0.1.15 snapshot update - 2026-10-04

## Checklist

- [x] Validate the supplied archive and compare it with the prior PCV 0.1.14 archive.
- [x] Import the 0.1.15 ZIP entries, preserving the dirty plan and existing local extras.
- [x] Update current hub pins, links, summary, and exact root release note copy.
- [x] Verify archive equality, protected baselines, and scoped documentation changes.

## Source and inventory

- Immutable tag object: `fef06949c8e87c03713bef3132613590fe4efb88`; peeled commit: `5d003023e45058ec94ec4c22727552a352069e83`.
- Authority archive: `dist/hub-pcv15-2026-10-04/tag-source.zip`; SHA-256 `4bb300889143de0eac2eba5701142ff59bfd56bf9a16a26aac4acfcaabddf2b2`. It contains 195 unique file entries, passes ZIP CRC validation, and every entry matches the supplied extracted tag source.
- Against the PCV 0.1.14 archive: 3 additions, 9 changed files, 0 deletions.

Added:

- `distribution/private_conversation-v0.1.15.md`
- `tasks/live-issues-2026-10-04.md`
- `tests/live_fixes_check.php`

Changed:

- `README.md`
- `server/context_pre.php`
- `server/log.php`
- `server/manifest.json`
- `server/preprocessing.php`
- `server/reflection.php`
- `server/scope.php`
- `tasks/audit-2026-10-03.md`
- `tests/scope_check.php`

Deleted: none.

## Snapshot and hub changes

- All 195 ZIP paths are present in `plugins/private_conversation/`; 194 match byte-for-byte. The only exception is the preserved dirty `tasks/logging-improvements-plan.md`, SHA-256 `93125ca439851affdcb38aa59ccb08db5f16970022f101edc636b6ed6477c4bf`.
- Preserved local extras: `tests/autonomous_presence_check.php` (SHA-256 `ebecbedb448453cf873d6c5cc41613fa7dd3b61ce3dcb368c7d5d42f48595045`) and ignored `scripts/__pycache__/build-package.cpython-312.pyc` (SHA-256 `6032d8d6b42c00f5c443a56cf13882a6916cf7da08c67d56db21461c8d45190b`).
- Updated current PCV version references, summary, table row, release links and download URLs in `README.md`; updated current pins and inventory/test-history prose in the assigned developer, migration, integration and server documents. The historical 0.1.14 source notes and 0.1.12 observer-test evidence remain labeled as historical.
- Copied the published release note to `distribution/private_conversation-v0.1.15.md` byte-for-byte; SHA-256 `ca430e9d202e4cfc3c7a19999ebdd93154b9e0c29492681e7e1711c6c76cfd2a`.
- The lead verified all four public assets byte-for-byte against clean-tag builds and confirmed the supplied checksum. The receipt records MO2 ZIP `3f964e41754ea487b1d3402309d310e5b4573246ea281b39b60a8ba52669d4af`, DWPkg `0d408bd5e0b12f5da38d2ddf206de075277436a50f410e0af9618102537a3087`, tar archive `f4ef7e9092c1d88f01225584323b39f05d6e795237bc5460f20e91b4a5497330`, and `SHA256SUMS.txt` `561be894d1077bd8561503bd9dff0e50cd2717fd418e8b968445b9cde6cfa843`.

## Source review and limits

- The published 0.1.15 note attributes this candidate to fixes from the first in-game session on 0.1.14: CHIM background chatter no longer cancels a scene reply; wrap-up/end-scene phrases work by voice; solo wrap-up ends reflection; and ACKs during a long reflection report `reply_in_progress`. The note reports offline fixtures and a clone check; this hub task did not independently repeat them, and the original live cause remains unconfirmed.
- Reflection API v1/v2 contracts are unchanged. The recorded five-case observer/importer pass used PCV 0.1.12 before this pin; no post-pin PHP or game test was run. The current fixture resolves to 0.1.15.
- Published MP 0.1.17 normally skips sentinel-targeted lines when no NPC catalog row resolves. It lacks an explicit reserved-name veto if CHIM resolves a row literally named `explicit_disable_rechat`; a separately reviewed source candidate adds the guard but remains unreleased. No MP product file, version, tag, or release was changed by this hub subtask. The live cause is unconfirmed: sanitized diagnostics omit the reflection API version and line count, and PCV server registry line presence/count is still needed before attributing a specific incident.
- No WSL distro, installed CHIM, provider, database, or Skyrim game was used. Lead verification that the public assets match clean-tag builds is package evidence, not post-pin runtime proof.

## Verification

- `dist/hub-pcv15-2026-10-04/verify-hub.py`: passed archive byte-equality, protected-work and eligible MP baseline hashes, manifest versions, current README links, and exact note-copy checks. Its baseline check excludes the separately owned unreleased sentinel files `server/prerequest.php` and `tests/overhearing_test.php`.
- `git diff --check` passed for the six assigned tracked hub documents.
- Current PCV manifest is 0.1.15; root Mind Poisoning manifest remains 0.1.17. No package build, release, tag, install, or publication was performed here.

## Review

Only the assigned pinned snapshot, hub documents, exact root release note copy, and this task record were written. The dirty plan, local extras, unrelated work, historical 0.1.14 evidence, and MP release artifacts were preserved.
