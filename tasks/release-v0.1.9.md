# Mind Poisoning v0.1.9 publication evidence

- Release: https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.9
- Source commit: `0502c979b1622ebd62592bfcf2850908acbd6153`; annotated tag: `mind_poisoning-v0.1.9`.
- Public PRE-ALPHA prerelease, not draft; published 2026-09-29T23:21:00Z.
- Compatibility reference unchanged: `cf5030f15781637498be86debe26fcf102f5690d`. This is not an installed deployment pin.

## Verification

Fresh runtime and logging fixtures, touched PHP lint, installed manifest-update helper checks and seven packaging tests passed. GNU tar strip-one extraction matched all 15 allowlisted source files. The earlier disposable PostgreSQL 15 timeout check exercised real statement cancellation, contention through production persistence, rollback/no writes, restored session settings and advisory lock release; its evidence was reviewed without repeating the test. See store-timeout-report.md and pause-runtime-report.md for that distinction.

All three packages rebuilt byte-identically from a clean git-archive export of the annotated tag. The MO2 ZIP has exactly CHIM/server-plugins/mind_poisoning/0.1.9.dwpkg, valid CRC, and the embedded bytes equal the separate DWPkg. Four assets were uploaded to a draft prerelease, downloaded and compared byte-for-byte. Every SHA256SUMS entry and GitHub asset digest matched. The draft was published before main advanced from bbf55fc to the source commit; this evidence-only follow-up does not alter release payloads.

| Asset | Bytes | SHA-256 |
| --- | ---: | --- |
| mind_poisoning-0.1.9.dwpkg | 914538 | 5f0f20ed10f336653c7be5f4d02c722e4ebcd66a9aa2b9a1dab2deb30e37a1f0 |
| mind_poisoning.tar.gz | 708091 | 6e596b2b9c9d93370086ff0243a8c0a4485634e6cb342cc87af5a942f4d21081 |
| mind_poisoning-0.1.9-mo2.zip | 709562 | 9f5170b745d011202fd373fb2c352db6292e2343c22c4f4a148533414fd5a351 |
| SHA256SUMS.txt | 276 | 595818221b36bd5d6313fc88c32cafeb98df9e32e91bf0c7fd628f1e6f661769 |

## Limits

Installed CHIM, live database, game files and MO2/modlist were not modified. No live provider, clean-server install or gameplay test was performed. The model call remains synchronous; pause is sampled before/after model work and is not atomic cancellation. Database limits apply per operation, not to total request duration. Prior aggregate invalid-payload events remain undiagnosed. MO2 may still show its custom-content warning, and native installation is unverified. User critique and pending catalog submission files remain outside the release commits.
