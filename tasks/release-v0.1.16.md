# Mind Poisoning 0.1.16 publication — 2026-10-02

## Result and identity

The user authorized commit, push and publication of the reviewed reply-cap correction. Both releases are public, non-draft PRE-ALPHA prereleases:

- [Canonical deployment release](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/tag/mind_poisoning-v0.1.16), published `2026-10-02T22:07:24Z`.
- [Collection/migration mirror](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.16), published `2026-10-02T22:07:26Z`.

Release commit: `ace3d21a78208b18187e2a3650a77eaddd2011dd`. Annotated tag object: `7d36c518ec57c5f201a3ea8b66265d47416ba1e0`. Exact executed/staged/commit tree: `5b09512ed7e76e1638ddad66df7c06a53eb0e3a2`. Release pin: `mind_poisoning-v0.1.16`; the compatibility reference remains `cf5030f15781637498be86debe26fcf102f5690d`, not an enforced deployment pin.

The product delta is one shared 24-line cap used by four v2 reply guards. The v1 API, v2 capability version 2, eight-subject bound, joined-text bounds and persistence/replay gates are unchanged. Current metadata and the stale-version update fixture were corrected. The root gaming-distro prohibition and historical environment corrections are included. No PCV source or manifest was staged.

## Focused execution and review

The lead explicitly staged only reviewed files, used `git write-tree` and `git archive` to export the exact index tree, then ran the following in `DwemerAI4Skyrim3-test` under an explicit `WSL_DISTRO_NAME` guard. No default or gaming distro was entered. PHP version: 8.2.29.

```text
wsl.exe -d DwemerAI4Skyrim3-test -- bash /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/dist/release-0.1.16/run-clean-checks.sh
php tests/reflection_reply_test.php
php tests/manifest_update_check.php
php -l server/reflection.php
php -l server/store.php
php -l tests/reflection_reply_test.php
php -l tests/manifest_update_check.php
```

Both fixtures exited 0; all four syntax checks passed. Reply stdout: `reflection reply checks passed`. All 46 stderr lines were expected sanitized JSON records (36 info, 10 warning), with no PHP diagnostics. Update stdout confirms the actual clone's extracted manager/installer helpers resolve dynamic next versions and unique plugin identities in both catalog orders, retaining the legacy cases; its stderr is empty. Fetches are stubbed, and no production bootstrap is loaded. Reply assertions establish 13-line ordered evaluation/one effect/all IDs, the 24/25 boundary, text overflow without truncation/mutation, and retained source/replay/v1 checks through in-memory model/store seams. Earlier red-to-green evidence remains in the [correction report](reflection-reply-cap-2026-10-02.md).

Metadata owner `bridge_contract`, lead and independent reviewer `review_identity_aliases` inspected the relevant diffs, contracts, source bytes and complete focused output. Final source/evidence gate: approved, no remaining defects. Syntax, isolated execution and environment metadata are distinct evidence tiers.

After execution, `wsl.exe --terminate DwemerAI4Skyrim3-test` exited 0. Windows-only enumeration reported both CHIM distros stopped. Gaming VHD metadata remained exactly LastWriteTimeUtc `2026-10-02T14:26:40.7745769Z`, ticks `639265480007745769`, length `180272234496`. This is a metadata comparison, not a complete disk-content audit.

## Packaging and publication evidence

The existing builder was run once for each format in `source-staged` and independently in `source-tag`:

```text
python <clean-source>/scripts/package.py --format dwpkg
python <clean-source>/scripts/package.py --format repository-tar-gz
python <clean-source>/scripts/package.py --format mo2-sync-zip
```

Builder checks verified each archive's allowlisted payload, manifest and format constraints. The clean annotated-tag export matches all 311 tracked source files in the executed staged-tree export, including product, tests and builder. All three package formats and the generated ASCII/LF checksum file match byte for byte across both builds:

| Asset | Bytes | SHA-256 |
| --- | ---: | --- |
| `mind_poisoning-0.1.16-mo2.zip` | 622724 | `501e5f28966649fc82cd2c5148384c2e07f1394fa65cd222dae3e1ca9ee00679` |
| `mind_poisoning-0.1.16.dwpkg` | 886056 | `46b13ee7177a0ceb6231378432397eee5f90feae4be47c2e787ad3583050408d` |
| `mind_poisoning.tar.gz` | 620950 | `b190082a9a9bc03a778d548b06c714df876f47e6c8ca1e3422915f8f23439438` |
| `SHA256SUMS.txt` | 278 | `7cdabccad1d9731da97c13d3f47ea161173861cc2962727f7f9e2bb411f1d99f` |

The tag was pushed to both repositories without force. Draft prereleases were created with exactly these four assets and the reviewed release body. Fresh draft downloads in both repositories matched tag builds, file sets and checksum entries. Both were then published; fresh public downloads matched again. Only after that did both remote `main` branches fast-forward from `5e0af10` to `ace3d21`. GitHub API checks confirmed public visibility, non-draft prerelease state, all four size/digest pairs, main refs and exact manifest bytes/version 0.1.16. A subsequent evidence-only commit saves this receipt and completed task ledger without changing the released source/tag or packages.

Raw execution, build sources, downloads and API receipts remain under ignored `dist/release-0.1.16/`. Helpers there are release orchestration only and are not shipped product files.

## Preservation and limits

The 0.1.15 tag object remains `c04314e8d27bd506fee890bb18a94a4054ee90ec` in both repositories. All four historical asset names, sizes and SHA-256 API digests match the saved pre-publication baseline. No old asset or tag was replaced. The unrelated dirty PCV task file retains SHA-256 `93125ca439851affdcb38aa59ccb08db5f16970022f101edc636b6ed6477c4bf`; critique/submission drafts and screenshots remain untracked and unpublished.

The published release remains PRE-ALPHA. Isolated fixture execution and reproducible packages do not establish live database durability/concurrency, provider judgment/transport, installation/update execution, companion v2 reply grouping, native audio playback or gameplay reliability. The embedded PCV 0.1.8 snapshot remains a v1 caller. User-reported 0.1.15 pair-path results are attributed separately and do not establish longer solo replies. No plugin was installed, no live provider/database was called, no catalog entry submitted, and no gaming-distro command executed. Use one installation route and replace older enabled copies.
