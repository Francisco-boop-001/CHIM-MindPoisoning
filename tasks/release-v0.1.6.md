# Mind Poisoning v0.1.6 publication evidence

- Release: https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.6
- Source commit: a596906; annotated tag: mind_poisoning-v0.1.6.
- Public GitHub prerelease, not draft. PRE-ALPHA development candidate.
- Compatibility reference unchanged: cf5030f15781637498be86debe26fcf102f5690d.

## Verification

Fresh publication gates: seven Python package tests, Node refresh behavior, isolated PHP dashboard integration, and installed Manager/installer helper compatibility check passed. Earlier browser evidence for both tabs, pause/resume, filters, details/focus/scroll, and Day/Night appearance is recorded in todo.md.

All three packages rebuilt byte-identically from clean tagged source. The FOMOD ZIP contains a byte-identical standalone DWPkg. All four uploaded assets were downloaded with GitHub CLI and compared exactly before main advanced. Main push 78663d3..a596906 succeeded; the subsequent evidence-only commit does not change release payloads.

| Asset | Bytes | SHA-256 |
| --- | ---: | --- |
| mind_poisoning-0.1.6.dwpkg | 868502 | 5cbe6dd64f11640cb5660f36a28ea78e48f30c4a5eb48f3db40e739c0940b0a9 |
| mind_poisoning.tar.gz | 699004 | de49cc6ee5adb2b7d9c73faac8d538587f5dc5284625fdefa5259701d3c24674 |
| mind_poisoning-0.1.6-mo2.zip | 701009 | 1d863942e090883f449ef52156ca86f44bc7e57ae55a8f5fc4bc64ae1784211a |
| SHA256SUMS.txt | 279 | 6a8b8685c7c1903d5e1593601963c99d53dacd13f1f7fc68799b3585a2db6714 |

## Limits

No installed CHIM, MO2 profile/modlist, or game changes. No live CHIM deployment, database writes/concurrency, provider, client ACK delivery, deployed authentication, or in-game acceptance proof. Synthetic tests and browser preview are not live-runtime certification. Catalog submission remains pending and was not included in this publication.
