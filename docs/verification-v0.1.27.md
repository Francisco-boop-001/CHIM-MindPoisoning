# Mind Poisoning 0.1.27 — verification and limits

## Release status

This document is prepared for the 0.1.27 BETA CANDIDATE release. The clean release commit/tag, versioned package build, draft upload and public-download checksum verification are pending. Do not treat the planned release links as proof of available assets. The standard GitHub release classification is intended to support CHIM topic discovery; the maturity label remains BETA CANDIDATE.

Read-only public refs checked on 2026-10-19: `main` is `f21a00abbb393c03a8faf226c0707281776d175d`; the immutable `mind_poisoning-v0.1.26` tag peels to `43c87804875bad104fd955c344d5b300b9b9bcac`. The latest published release is 0.1.26, with `prerelease=false`. No public 0.1.27 tag or release was present. The anonymous Releases API does not show drafts, so this check cannot rule out an unpublished draft.

The 0.1.26 release remains unchanged. Its public asset digests are:

| Asset | SHA-256 |
| --- | --- |
| `chim-plugin.tar.gz` | `c7301ebbff61649bdbe672a83489b96601afb4496f7d094d9c71aa635bf371bf` |
| `mind_poisoning-0.1.26-mo2.zip` | `278d982e46c84555cba82148dce9be4d2170a28da8e22d5269bea618daf76402` |
| `mind_poisoning-0.1.26.dwpkg` | `e7464767cc55b02ffafa02b5df6fcbd6f1c2472611a01af97036b77a8a0a9409` |
| `mind_poisoning.tar.gz` | `68c4f99b8281a6f254cd8a512a469289b22a87b66c33edac227174e0d5e0812e` |
| `SHA256SUMS.txt` | `b3f70885bd385fa07db148b10db7b0a935a0ffb2ed826ac63a7bddf772bdf5e6` |

## What changed

Mind Poisoning adds an optional written-evidence v1 API for a compatible server-side producer. The event is a confirmed, privacy-bounded exposure recorded under the reserved physical `info` / `written_evidence_v1` marker. MP revalidates the strict source envelope, current identities, recipient, scope, controls and replay state before applying ordinary bounded recipient-owned affinity/type judgments. The claimed author and other people mentioned in the text are possible subjects, not newly informed witnesses. The source does not prove actual authorship, payment or reading. It cannot set intimacy arrangements.

The API introduces no table, migration, background worker or producer. Enqueue registers a bounded request-local hint, not a durable job. A compatible producer must create and re-enqueue eligible sources; a status result alone does not reserve processing. The matching producer is separate and is not included in this release.

## Deterministic verification

Standalone Windows PHP fixtures used `-n`, the bundled PHP 8.3.35 runtime and explicit `mbstring`. The final evaluator fixture passed 93 checks; the focused source/status fixture passed 18 checks. Store and existing persistence suites passed, including duplicate-document refusal, the 1,024-entry no-eviction receipt cap, scope/restore handling, source mutation/pruning, INIT changes, runtime-control changes, rollback and zero-change receipt behavior. These are deterministic fixtures, not real-model tests.

The retained native integration fixture passed against the production MP adapter and a disposable native database boundary. It processed one strict `info` / `written_evidence_v1` source through the producer/store seam, public MP evaluator and recipient opinion/history writer; an exact replay made no second model callback. It recorded one deterministic model callback and zero provider calls. The producer was a development candidate in the fixture; this does not announce a public Correspondence package or prove player availability.

The unchanged official manager installed three exact development candidates for a cross-plugin check. MP preserved the same recipient's unrelated plugin namespace and the replay result was unchanged. Candidate package payload verification matched all 24 MP runtime files; the development manifest override was the only MP package override. This was not a 0.1.27 release package build or an installed end-user release.

Retained local evidence is in `artifacts/chim-correspondence-implementation-20261019/`: `mp-cross-final-native.txt`, `candidate-consumers-final.json`, `final-payload-verification.json`, `mp-owner-handoff.md` and `mp-owner-source-inventory.json`. The runtime API and store owner hashes at freeze were `2c8ca4fd20a545741faa6107b17c48aef427d8cc1165c6d6d1e23dac00ed3ae1` and `c65ff0f4ea51dd861e900e0a76320bd71140461fd8f62f6d9617204dc037b009`, respectively. Release metadata and documentation do not change those runtime files.

## Limits and remaining release gates

- No real provider call or model-quality evaluation was performed. Tests cannot establish whether a model understands quoted claims, sarcasm, authorship or context correctly.
- The integration fixture does not establish that a public compatible producer exists, that a recipient reads a message in game, or that Skyrim delivers/plays any scene.
- Native tests cover the retained transaction path but do not establish new-source commit-acknowledgment-loss durability, broad concurrency, or behavior across multiple hosts.
- The 0.1.26 arrangement feature remains separate and default-off. A written event is not grounds for an intimacy arrangement, mutual agreement or current willingness.
- Clean commit/tag export, all four installable assets, `SHA256SUMS.txt`, draft/public byte comparison and post-publication main update remain pending.

Before publication, record the final release commit and tag, rebuild from that clean tag, compare each uploaded and public asset with the local byte/hash inventory, and verify 0.1.26 still resolves to the same tag and asset digests. Advance mutable `main` only after public asset verification. No installation, provider, live-server or gameplay claim follows from these checks.
