# Mind Poisoning v0.1.10 publication evidence

- Public PRE-ALPHA prerelease: https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.10
- Source commit: `7679ab74945637f9a513523f9a1d67c8f494746c`; annotated tag: `mind_poisoning-v0.1.10`.
- Compatibility reference unchanged: `cf5030f15781637498be86debe26fcf102f5690d`.

## Verification

The focused A-01 RED/GREEN runtime and logging checks and touched PHP lint passed before release preparation; the product diff remained unchanged and those tests were not repeated. Fresh release gates passed: seven packaging tests, four installed manifest-helper checks and GNU tar strip-one extraction matching all 15 allowlisted payload files. No live server writes or provider calls were used.

All three packages rebuilt byte-for-byte from a clean git-archive export of the annotated tag. The MO2 ZIP contains exactly CHIM/server-plugins/mind_poisoning/0.1.10.dwpkg, passes CRC, and embeds the separate DWPkg's exact bytes. All four draft assets were downloaded and compared with local files, and all SHA256SUMS entries verified. Publication preceded main advancement from 5901d9f to the source commit. This evidence follow-up does not alter payloads.

| Asset | Bytes | SHA-256 |
| --- | ---: | --- |
| mind_poisoning-0.1.10.dwpkg | 915832 | 0a68747c0fc0c5536c12ea8df31d52208ca4f692128df1aeba22b6a849c69cbf |
| mind_poisoning.tar.gz | 708442 | 4b6b421b0a7f594f03302bb04a22a3ccdfbafda385612b0acda2e06d7d3f3504 |
| mind_poisoning-0.1.10-mo2.zip | 709919 | b902a5113fc4bc294e11a2a5e0ca6c2147ed7f97bce8fb281a990cb55f63a6aa |
| SHA256SUMS.txt | 278 | 2953111739b44d1b9f90c339a3f66259402e42852c5d88ec3536111211e2d5f5 |

## Limits

Installed CHIM, live database, game and MO2/modlist were untouched. Publication does not install the update; the last read-only installation check found v0.1.7. Live acceptance, provider behavior and native installation remain unverified. ID-less acknowledgements are still ineligible for evaluation; no correlation is guessed. Choose one installation route and isolate the server database for acceptance testing; a disposable Skyrim save alone does not isolate stored relationships. User critique and catalog-submission drafts remain untracked and excluded.
