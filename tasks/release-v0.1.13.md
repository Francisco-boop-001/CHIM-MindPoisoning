# Mind Poisoning v0.1.13 publication evidence

- Public PRE-ALPHA prerelease: https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.13
- Published at `2026-10-01T01:49:00Z` (2026-09-30 in America/Caracas).
- Source commit: `f5bdd53480db5b14ff3b8f8eb64fcff78f1368c1`; annotated tag `mind_poisoning-v0.1.13`.
- Compatibility reference unchanged: `cf5030f15781637498be86debe26fcf102f5690d`.

## Reviewed changes

Optional sanitized, request-local diagnostic observer and validated reflection correlation; invalid prior-affinity rejection under the existing lock; contained callback PHP warnings; truthful reflection rejection severity; read-only dashboard affinity bounds. The lead reviewed product, regression and release metadata diffs. Only 20 explicit MP files entered the source commit; pending embedded Private Conversation work, user critique, artwork captures and catalog drafts were excluded.

## Verification

The preceding API/bug-hunt checks passed: logging, store logging, reflection, dashboard current-affinity bounds, and five evaluator/observer/PCV-importer cases (committed change, zero change, provider failure, unconfirmed commit, warning skip). Changed PHP lint passed. These are isolated fixture executions, detailed in `reflection-diagnostics-api-2026-09-30.md` and `bug-hunt-reflection-api-2026-09-30.md`. Runtime source stayed unchanged during release preparation, so those checks were not repeated.

Fresh release gate: `python tests/test_package.py`, seven tests, exit 0. Manifest schema/version/channel/compatibility checks and scoped Git whitespace checks passed. The existing builder still allowlists exactly 17 server files; no packaging-format changes or new runtime dependency were made.

Clean source exports used `git archive --format=zip` for the release commit and annotated tag. Each exported builder produced DWPkg, repository tar and plain MO2 ZIP twice, with identical bytes, and verified archive contents against the exported source. The MO2 ZIP contains exactly `CHIM/server-plugins/mind_poisoning/0.1.13.dwpkg`, identical to the standalone DWPkg. All four commit/tag assets matched byte for byte. The ignored orchestration helper and exports remain beneath `dist/release-v0.1.13/`.

The tag was pushed before draft creation. All four draft assets were downloaded using `gh release download`, compared byte for byte with the clean-tag builds, and every downloaded checksum entry verified. GitHub asset digests matched local hashes. GitHub confirmed `isDraft=false`, `isPrerelease=true`, and four assets after publication. Only then was remote main fast-forwarded from `f0784bc` to the release source commit. Public main's manifest confirms 0.1.13 and the unchanged compatibility reference; the remote annotated tag resolves to the source commit. This evidence follow-up changes no release payload or tag.

| Asset | Bytes | SHA-256 |
| --- | ---: | --- |
| mind_poisoning-0.1.13.dwpkg | 866841 | d0bf636bf17357c212c73d83047794e2e1254499f5c099a0cb95257be82be8cd |
| mind_poisoning.tar.gz | 616881 | 952c0df5a948ff3993679448df1c945ee49c966c34f35511f1331e8759b39a98 |
| mind_poisoning-0.1.13-mo2.zip | 618638 | 86451f5891fec97dcd89ecb13c6704dd16409dd9b74810d79cba2aef0bc4fa9f |
| SHA256SUMS.txt | 278 | d9e5a56fa921dc5b52c08cd7cf4cd29b7c8c9359d14ffdb7f02488fe73e5bd17 |

## Limits and review

Still PRE-ALPHA. No installed CHIM, live database/provider, modlist or game change occurred. Isolated checks and reproducible packages do not prove installed reflection integration, PostgreSQL durability/concurrency, provider reliability, playback or native MO2 installation. Prior Player-origin gameplay evidence does not validate this new bridge. Compatibility reference remains unchanged; no official catalog submission occurred. Replace older enabled package versions and use one install route. Unrelated contributor work remains preserved.
