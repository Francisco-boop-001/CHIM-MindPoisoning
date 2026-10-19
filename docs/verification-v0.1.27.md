# Mind Poisoning 0.1.27 — verification and limits

## Release status — published and verified

Mind Poisoning **0.1.27 BETA CANDIDATE** is published at [the versioned release](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/tag/mind_poisoning-v0.1.27), with `draft=false` and `prerelease=false`. Standard classification supports CHIM discovery; it does not promote maturity.

The immutable tag `mind_poisoning-v0.1.27` identifies product commit `9e2ee6be1c83db937422f34e7ebe6061d63bf1f9`. All four installable assets rebuilt byte-for-byte from a clean `git archive` of that exact tag. The 24-file package list matches the reviewed candidate: 22 files are unchanged from the frozen native-test payload, with the approved manifest version/description and packaged README changes recorded separately. Runtime PHP and the package script are unchanged from the verified source.

All five draft downloads and all five unauthenticated public downloads matched the local release files exactly. The update branch advances only after those public checks. The tagged copy of this document records pre-publication knowledge; this `main` copy adds the final receipt without moving the tag.

| Asset | SHA-256 |
| --- | --- |
| `chim-plugin.tar.gz` | `71d99cea168a1763ebac54ddefb373b1092b58d63de0c4ef47a2eb36fd92c954` |
| `mind_poisoning-0.1.27-mo2.zip` | `bc3b044f533f35f29beaeff4341d22896411999798b99cc9b10a0ab8469141a2` |
| `mind_poisoning-0.1.27.dwpkg` | `80d049078d7a1c32c415266ac7736875f68b1ac75078f5366966afd970027c11` |
| `mind_poisoning.tar.gz` | `55d42af7c7ca08348bd5ecc349baaf81053693aa0c8c86ffb750937f01bd4009` |
| `SHA256SUMS.txt` | `bdaf784747634244d910056f6567a99471ee1925071c4fd1306b89ccfdbd87e2` |

Release checks used only local files, GitHub and the independent Windows toolchain. No installed server, game data, distro, database, provider or SHARMAT source was changed or run for publication. Historical tags/assets were not replaced.

## What changed

Mind Poisoning adds an optional written-evidence v1 API for a compatible server-side producer. The event is a confirmed, privacy-bounded exposure recorded under the reserved physical `info` / `written_evidence_v1` marker. MP revalidates the strict source envelope, current identities, recipient, scope, controls and replay state before applying ordinary bounded recipient-owned affinity/type judgments. The claimed author and other people mentioned in the text are possible subjects, not newly informed witnesses. The source does not prove actual authorship, payment or reading. It cannot set intimacy arrangements.

The API introduces no table, migration, background worker or producer. Enqueue registers a bounded request-local hint, not a durable job. A compatible producer must create and re-enqueue eligible sources; a status result alone does not reserve processing. The matching producer is separate and is not included in this release.

## Deterministic verification

Standalone Windows PHP fixtures used `-n`, the bundled PHP 8.3.35 runtime and explicit `mbstring`. The final evaluator fixture passed 93 checks; the focused source/status fixture passed 18 checks. Store and existing persistence suites passed, including duplicate-document refusal, the 1,024-entry no-eviction receipt cap, scope/restore handling, source mutation/pruning, INIT changes, runtime-control changes, rollback and zero-change receipt behavior. These are deterministic fixtures, not real-model tests.

The retained native integration fixture passed against the production MP adapter and a disposable native database boundary. It processed one strict `info` / `written_evidence_v1` source through the producer/store seam, public MP evaluator and recipient opinion/history writer; an exact replay made no second model callback. It recorded one deterministic model callback and zero provider calls. The producer was a development candidate in the fixture; this does not announce a public Correspondence package or prove player availability.

The unchanged official manager installed three exact development candidates for a cross-plugin check. MP preserved the same recipient's unrelated plugin namespace and the replay result was unchanged. Candidate package payload verification matched all 24 MP runtime files; the development manifest override was the only MP package override. This was not a 0.1.27 release package build or an installed end-user release.

Retained local evidence is in `artifacts/chim-correspondence-implementation-20261019/`: `mp-cross-final-native.txt`, `candidate-consumers-final.json`, `final-payload-verification.json`, `mp-owner-handoff.md` and `mp-owner-source-inventory.json`. The runtime API and store owner hashes at freeze were `2c8ca4fd20a545741faa6107b17c48aef427d8cc1165c6d6d1e23dac00ed3ae1` and `c65ff0f4ea51dd861e900e0a76320bd71140461fd8f62f6d9617204dc037b009`, respectively. Release metadata and documentation do not change those runtime files.

## Limits

- No real provider call or model-quality evaluation was performed. Tests cannot establish whether a model understands quoted claims, sarcasm, authorship or context correctly.
- The integration fixture does not establish that a public compatible producer exists, that a recipient reads a message in game, or that Skyrim delivers/plays any scene.
- Native tests cover the retained transaction path but do not establish new-source commit-acknowledgment-loss durability, broad concurrency, or behavior across multiple hosts.
- The 0.1.26 arrangement feature remains separate and default-off. A written event is not grounds for an intimacy arrangement, mutual agreement or current willingness.
- Clean-tag, draft/public download and checksum gates passed. They do not establish live installation or gameplay.

Final local receipts are retained under `artifacts/mp-release-0.1.27-20261019/`: the clean-tag package verification, draft-download verification, public-download verification and release preflight. No installation, provider, live-server or gameplay claim follows from publication checks.
