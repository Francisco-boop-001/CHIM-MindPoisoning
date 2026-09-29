# Mind Poisoning v0.1.8 publication evidence

- Release: https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.8
- Source commit: `dd3597412c868c6488f73e396de1d7b8404cd73e`; annotated tag: `mind_poisoning-v0.1.8`.
- Public PRE-ALPHA prerelease, not draft. Compatibility reference unchanged: `cf5030f15781637498be86debe26fcf102f5690d`.
- The MO2 asset is a plain ZIP, not the earlier FOMOD wrapper. It contains exactly `CHIM/server-plugins/mind_poisoning/0.1.8.dwpkg`, identical to the separate DWPkg asset. MO2's content warning may remain.

## Verification

Fresh release checks passed: runtime, logging, dashboard-data, dashboard HTTP/integration, installed manifest-update helpers and seven packaging tests. GNU tar strip-one extraction matched all 15 allowlisted source files. Earlier focused isolated PostgreSQL checks exercised both runtime and dashboard profile queries; this release run did not repeat them or claim live deployment evidence.

All three packages rebuilt byte-identically from a clean git-archive export of the release tag. Uploaded four files to a draft prerelease, downloaded every asset and compared exact bytes, then checked every SHA256SUMS entry. Published only after those comparisons passed. Public GitHub asset digests matched; main advanced from d49a324 to the source commit after publication. The evidence-only follow-up commit does not change release payloads.

| Asset | Bytes | SHA-256 |
| --- | ---: | --- |
| mind_poisoning-0.1.8.dwpkg | 903453 | 877faa75991efef54052762b04ad063149ba2793952295522d27c74bfc3cbcba |
| mind_poisoning.tar.gz | 705402 | f0ed5b20836f12627dbeb234826adbf7ce2f662c0f3021b4676681c10a548022 |
| mind_poisoning-0.1.8-mo2.zip | 706889 | 8a4d2acf022f3b6698f9f9a2f0b70de25333cc2cf9beb9fec3416fe08de8f188 |
| SHA256SUMS.txt | 276 | 16e2cc08c76901dc4f099db2f932f37282be9776e161ced8d951aaa1473fe6b1 |

## Limits

Installed CHIM, game files, MO2/modlist and the live database were not modified. No live provider request, gameplay trial or clean-server installation was performed. The six observed invalid-payload acknowledgements remain undiagnosed. Unprofiled mode represents a shared server database, not isolation between Skyrim saves. User critique and submission drafts were preserved outside the commits; official catalog submission remains pending.
