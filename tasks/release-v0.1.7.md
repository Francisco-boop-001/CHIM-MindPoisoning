# Mind Poisoning v0.1.7 publication evidence

- Release: https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.7
- Source commit: f2179f002f4b05e3e9f58d10f004ba7bd595d2bd; annotated tag: mind_poisoning-v0.1.7.
- Public GitHub prerelease, not draft. PRE-ALPHA development candidate.
- Compatibility reference unchanged: cf5030f15781637498be86debe26fcf102f5690d.

## Verification

Fresh publication checks passed: composed Player/NPC runtime and Player-store fixtures, influence, logging, dashboard-data, store-logging, seven package tests, and installed manifest-update helper checks. Optional PostgreSQL query mode was explicitly skipped in the publication fixture run; its earlier private-cluster evidence is recorded in todo.md. The legacy package-manager harness requires a synthetic v0.1.0 ZIP; initial attempts with release tar/0.1.7 inputs were rejected by its fixture guards. Its correctly generated 15-file fixture then passed scratch installation and tamper-preservation checks.

All three release packages rebuilt byte-identically from clean git-archive tag source. Uploaded as a draft prerelease, downloaded all four assets, and compared exact bytes before publication. GitHub public asset digests match the local hashes. After publication, main advanced e91491d..f2179f0. The subsequent evidence-only commit does not change release payloads.

| Asset | Bytes | SHA-256 |
| --- | ---: | --- |
| mind_poisoning-0.1.7.dwpkg | 894573 | 45665860f73e34680619361c066cf2fd03cff479133947edfa635a1be725eb98 |
| mind_poisoning.tar.gz | 703476 | 09485884a7c0c4949b05183da61cacb18277428562f4faac34f79bb39c2dcb59 |
| mind_poisoning-0.1.7-mo2.zip | 705583 | d10e26f3781e2087328f187aa5262b9cd0dca133b41278ce354b232b26de0800 |
| SHA256SUMS.txt | 276 | 4e97159ff58ae530aca5dcf1fef836a995c0ffbd5a733aa65afdbdb1b4770c28 |

## Limits

No installed CHIM, MO2 profile/modlist, or game changes. No live CHIM deployment, database writes/concurrency, provider, client input/ACK delivery, deployed authentication, or in-game acceptance proof. Tests and isolated SQL checks are not live-runtime certification. Catalog submission remains pending; user critique and submission drafts were preserved and excluded from commits.
