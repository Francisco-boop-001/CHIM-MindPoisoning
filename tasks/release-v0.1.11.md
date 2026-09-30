# Mind Poisoning v0.1.11 publication evidence

- Public PRE-ALPHA prerelease: https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.11
- Source commit: `c2e05722e9513bd30b8f58f4529d23346e26c6d1`; annotated tag `mind_poisoning-v0.1.11`.
- Compatibility reference unchanged: `cf5030f15781637498be86debe26fcf102f5690d`.

## Verification

Prior focused A-02/A-03 RED/GREEN runtime fixtures and changed PHP lint passed; product code stayed unchanged during release preparation, so these were not repeated. Fresh release gates passed: seven packaging tests, four installed manifest-helper checks using stubbed fetches, and GNU tar strip-one extraction matching all 15 allowlisted files.

All three packages rebuilt byte-for-byte from a clean git-archive export of the annotated tag. The first clean-build invocation correctly rejected an output directory outside that export; rebuilding inside its own dist directory passed. No source or tag changes were needed. The package builder verified archive contents and CRC; the plain MO2 wrapper embeds the versioned DWPkg.

All four draft assets were downloaded and compared byte-for-byte with local files; every downloaded SHA256SUMS entry passed. GitHub confirmed isDraft=false and isPrerelease=true after publication. Only then was main advanced from 35ab91f to the source commit. This evidence follow-up changes no runtime payload.

| Asset | Bytes | SHA-256 |
| --- | ---: | --- |
| mind_poisoning-0.1.11.dwpkg | 917650 | cba77272d992e2f0844983f14c729445cef3246c2b3b426aebc4204b09eac81f |
| mind_poisoning.tar.gz | 708963 | 7106706e094f41a86f37ea9f1c1795581fc96331b3dafb1476bc3dccce0dc475 |
| mind_poisoning-0.1.11-mo2.zip | 710509 | 81d80528eec740c346de89c6c83f0a07040eff7d254df84ddcdd1952ab7b232d |
| SHA256SUMS.txt | 278 | 6c67932c81ffd25743b6bf7359ef24fb59c788467ba3be9a828cd9a04c5d6554 |

## Limits

No installation, live database mutation, provider call, game or modlist change was performed. Prior v0.1.10 Player-origin gameplay evidence does not validate these new fixes live. Natural NPC-origin model/persistence, concurrency/failure recovery and native installation remain incomplete. Use one installation route and only one enabled package version. User critique and catalog-submission drafts remain untracked and excluded; no catalog submission occurred.
