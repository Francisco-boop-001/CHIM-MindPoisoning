# Mind Poisoning 0.1.18 publication receipt — 2026-10-04

## Result and release identities

PRE-ALPHA 0.1.18 is public in both repositories:

- [Canonical deployment release](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/tag/mind_poisoning-v0.1.18)
- [Collection mirror release](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.18)

Release commit: `83908fe9e6a9962a51ccd3e409d2c586bc46e346`.
Annotated tag: `mind_poisoning-v0.1.18`; object `37b57b6f583c16a3d8d0e5c072bcd37b97882c93`.
Verified tree: `2fc748458c383203589f5903b79e6c88cadc842b`.

This carries the reviewed relationship compatibility fixes and previously committed reserved-sentinel guard. The source commit staged exactly 30 reviewed files; unrelated Private Conversation work, critique, drafts, screenshots and recovery files were excluded. The hub keeps Private Conversation 0.1.15.

## Verification

Prior behavioral and direct PostgreSQL writer/transaction evidence is recorded in [relationship verification](relationship-compatibility-2026-10-04/verification.md). Completed behavior suites were not repeated for publication. That record distinguishes MemoryStore request/history proof from direct SQL writer/transaction proof and reports the outer Bash runner exit 2 separately from PHP fixture exit 0.

New release checks used independent native PHP 8.5.11:

```text
php -n <clean-export>/tests/manifest_update_check.php
php -n -d extension_dir=<native-extensions> -d extension=mbstring <clean-export>/tests/influence_test.php
python dist/release-0.1.18/verify-release.py sources
python dist/release-0.1.18/verify-release.py build source-staged assets-staged
python dist/release-0.1.18/verify-release.py build source-tag assets-tag
python dist/release-0.1.18/verify-release.py verify assets-staged
```

All exited 0. `CHIM_HERIKASERVER_ROOT` selected only the scratch reference containing two immutable consumer files at CHIM revision `cf5030f15781637498be86debe26fcf102f5690d`; original environment state was restored. The metadata fixture exercised actual pinned manager/installer functions with stubbed manifest fetches, including cross-plugin catalog identity order and single-entry cases. It proves metadata/update resolution, not archive installation. The second PHP check verifies the canonicalizer with Git-normalized upstream fixture bytes; its LF SHA-256 matches the fixture README. No WSL command was executed in this publication task.

All 411 clean-tag source files equal the executed staged-tree export. The unchanged deterministic builder validated each package. An independent agent approved the source and package gates: the DWPkg has 19 unique expected members with schema-4 outer/schema-2 inner manifests and internal checksums; the MO2 ZIP embeds exactly that DWPkg under `CHIM/server-plugins/mind_poisoning/0.1.18.dwpkg`; the tar has 18 safe entries under one `mind_poisoning/` root. All three packages plus the ASCII/LF checksum file match across staged and tagged builds.

## Published assets

| Asset | Bytes | SHA-256 |
| --- | ---: | --- |
| `SHA256SUMS.txt` | 278 | `b79a1dc05220887c9962d6b1b28999de4cb6c3f31017835dec4f7d48155d05a2` |
| `mind_poisoning-0.1.18-mo2.zip` | 632190 | `4621e083ffd0ed68d8ba958e773465f030ffc896b1ce6068e42260cb5b1bcfce` |
| `mind_poisoning-0.1.18.dwpkg` | 941024 | `f8dd5ec02be920188fab5cff89acc85e189f3550fc2223437bb7045d16985aad` |
| `mind_poisoning.tar.gz` | 630358 | `fe77f57338219ec2b72257127a0c0d94deeb75ec84dc7173ffa710dcb2230c41` |

## Publication order and preservation

New tags were pushed without force. Both releases were created as drafts with exactly four assets; fresh downloads from each draft matched tag-built bytes and checksums. Both drafts were then published as prereleases. Fresh public downloads matched again, and API checks confirmed non-draft prerelease state, exact asset sizes/digests and correct tag refs. Only afterward did both main branches fast-forward from `a83da0a` to the release commit. Public README, Mind Poisoning manifest and pinned Private Conversation manifest bytes match the tag.

The previously published 0.1.17 tag object `4ec1de5fd035fed33f4645d96f1c8e5e0fb079a8` and peeled commit `bd5f5ae341f41e8762dc658f6c68a5b7f27cc18c` remain unchanged in both repositories. All four 0.1.17 asset identities, sizes and SHA-256 API digests match saved baselines. No older release/tag was targeted for mutation. Six protected file hashes match. The dirty PCV task file and local-only material remain excluded.

This receipt, completion of the plan/ledger, and the late review clarification are saved in a subsequent evidence-only commit. They do not modify or retag the shipped release. Raw outputs, exports, assets, baselines and publication checks remain in ignored `dist/release-0.1.18/`.

The first interpreter-version check closed stdout early through `Select-Object -First 1` and returned nonzero; a complete `php -n -v` then exited 0. No behavioral check was affected. The official guide web fetch failed, so no current catalog-policy review is claimed; pinned consumer source supplied the metadata evidence.

## Limits

PRE-ALPHA remains appropriate. No installation, live provider/player-database call, CHIM service, gaming-distro operation, modlist change, audio or in-game test occurred in publication. Existing historical live observations remain historical; these source/package checks do not establish broader gameplay reliability, full SQL request/history durability, or native MO2/Plugin Manager installation. Catalog submission and maintainer acceptance are separate tasks.
