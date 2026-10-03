# Mind Poisoning 0.1.17 publication — 2026-10-03

## Result and identity

The authorized overhearing implementation and PCV 0.1.12 hub snapshot update are committed and pushed. Mind Poisoning is published as a public, non-draft PRE-ALPHA prerelease in both repositories:

- [Canonical deployment release](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/tag/mind_poisoning-v0.1.17)
- [Collection mirror](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.17)

Release commit: `bd5f5ae341f41e8762dc658f6c68a5b7f27cc18c`. Annotated tag object: `4ec1de5fd035fed33f4645d96f1c8e5e0fb079a8`. Exact executed index/commit tree: `cc8306547fceffbd1828125942d7d1a23503bce8`. Pin: `mind_poisoning-v0.1.17`; compatibility reference remains `cf5030f15781637498be86debe26fcf102f5690d`, not a deployment pin.

Overhearing is default-off. It uses one model call for the addressed NPC and up to four eligible additional NPCs from the exact source event roster. The response is validated before independent per-listener commits and role-aware diagnostics. Player and reflection v1/v2 contracts remain unchanged. Partial outcomes are possible; sequential dedupe does not reserve provider work against simultaneous in-flight ACKs. Event membership is not hearing or PCV provenance proof.

The source tree also carries the separately approved, already-published PCV 0.1.12 snapshot and current hub links. Fresh tag-export comparison confirms 171 of 172 published PCV tag paths match byte for byte. The protected dirty `tasks/logging-improvements-plan.md` is deliberately not staged; its prior HEAD bytes remain in this source export and its working copy is intact. A pre-existing local-only PCV test remains outside the imported pin. No new PCV product edits, tag, release or upload occurred. MP runtime packages exclude `plugins/` and contain only MP's existing allowlisted payload.

## Exact-source execution and review

Explicit reviewed-file staging produced 106 changed paths. The lead exported the exact index with scoped `git write-tree` and `git archive`; all 371 tracked files later matched the clean release-tag export. The index was unchanged between execution and commit.

Guarded command:

```powershell
& ./dist/release-0.1.17/run-isolated.ps1 -AvailableRunningClone
```

The first attempt refused an already-running clone before entering it. The user then expressly confirmed the clone was available. The runner entered only `DwemerAI4Skyrim3-test`, required unchanged gaming VHD metadata, executed the following against `source-staged`, and stopped only that clone afterward. No default/gaming distro, CHIM bootstrap, actual provider or database was executed. PHP: 8.2.29.

```text
php tests/overhearing_test.php
php tests/runtime_test.php
php tests/dashboard_data_test.php
php tests/logging_test.php
php tests/store_logging_test.php
php tests/reflection_reply_test.php
php tests/reflection_observer_test.php
php plugins/private_conversation/tests/reflection_full_reply_check.php
php tests/manifest_update_check.php
```

All nine fixtures exited 0. All eleven changed-file syntax checks passed: the eight affected server PHP files and three changed/new tests. Every output was reviewed. Runtime stderr contains exactly the two expected injected failures (`snapshot-verification-failed`, `player-alias-ambiguous`). Reflection-reply stderr contains 46 valid sanitized JSON records and no PHP diagnostics. Other fixture stderr and lint stderr are empty.

The fixtures use injected model behavior and in-memory stores. They establish bounded one-call batching, per-listener commit/zero/partial/uncertain outcomes, sequential replay, closed parsing, identity/audience/lock/scope/setting revalidation, role-aware sanitization, truthful failure attribution, preserved Player/reflection behavior, PCV observer compatibility and thirteen-line full-reply routing. The metadata fixture extracts actual Manager/installer helpers from the clone and stubs fetches, checking dynamic versioned downloads and unique identities in both catalog orders without network or installation.

Original owners implemented/fixed feature and metadata; independent `defensive_review` inspected final source hashes, scope, pin import and actual outputs, returning no blocker. Lead reviewed every affected diff, callers, failure paths and verification output and wrote no product code. Prior red-to-green and defensive-programming conclusions are in [the implementation record](overheard-gossip-2026-10-03.md) and [assessment](defensive-programming-review-2026-10-03.md).

## Reproducible packages and publication

The unchanged builder ran independently in clean staged and annotated-tag exports:

```text
python <clean-source>/scripts/package.py --format dwpkg
python <clean-source>/scripts/package.py --format repository-tar-gz
python <clean-source>/scripts/package.py --format mo2-sync-zip
```

Builder archive/payload checks passed. All three packages and the ASCII/LF checksum file match byte for byte across both source builds:

| Asset | Bytes | SHA-256 |
| --- | ---: | --- |
| `mind_poisoning-0.1.17-mo2.zip` | 630713 | `902ce86ee73461d1269019ef6177f022bac7f2cd7e29e9d7ef4af15c5197556b` |
| `mind_poisoning-0.1.17.dwpkg` | 933343 | `f21adcd03318baf1c2452ba2db076682bac7c0a06b9643c62338a1b924ab920f` |
| `mind_poisoning.tar.gz` | 628909 | `030fe06b63ea26455bb95b5d0d83db5832bf083efaa443633466eeac0b4a6f9f` |
| `SHA256SUMS.txt` | 278 | `9e2071d7718ee0a38738158a342393df0da239d0b93a72457c5add46c2fbde06` |

New tags were pushed without force. Both draft prereleases contained exactly these four assets; fresh draft downloads matched tag builds and checksum entries. Both drafts were then published and fresh public downloads matched again. Only afterward did both `main` branches fast-forward from `9eff9b2` to `bd5f5ae`. GitHub API checks confirmed public repository visibility, non-draft prerelease status, exact asset sizes/digests, correct tag refs and exact main manifest bytes/version 0.1.17. A subsequent evidence-only commit records this receipt and completes the plan/ledger; it does not retag or change shipped payloads.

Commands/exports, raw fixture/lint output, protected-file hashes, API records and downloaded assets are retained under ignored `dist/release-0.1.17/`. Release orchestration scripts there are not shipped product files. An initial public-check helper had a PowerShell interpolation parse error; it performed no requests, was corrected, and its full gate passed.

## Preservation and limits

Both repositories retain the unchanged 0.1.16 tag object `7d36c518ec57c5f201a3ea8b66265d47416ba1e0` and commit `ace3d21a78208b18187e2a3650a77eaddd2011dd`. All four historical asset identities, sizes and API SHA-256 digests match saved baselines. No old tag/asset was replaced. Seven protected-file hashes match; critique, submission drafts, screenshots and the dirty PCV plan remain outside publication.

Gaming distro `DwemerAI4Skyrim3` was never entered. Its VHD retains LastWriteTimeUtc ticks `639266333216225615` and size `182184837120`. The authorized runner's fixture and clone-stop exits are 0 with `GamingVhdUnchanged=true`. A later Windows-only enumeration briefly found the test clone restarted outside this runner; no command entered or stopped it. Final Windows-only enumeration reports only unrelated Kali running, with gaming stopped and unchanged metadata. This is a metadata comparison, not a full disk-content audit.

PRE-ALPHA remains accurate. Isolated execution, lint and package hashes do not establish native setting writes, real provider batch correctness/latency, PostgreSQL durability/concurrency, installed update behavior, game playback or broad reliability. No installation, live provider/database call, catalog submission or gaming-distro command occurred. One installation route per server/plugin remains required.
