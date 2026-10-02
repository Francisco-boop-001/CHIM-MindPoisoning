# Mind Poisoning 0.1.15 publication evidence

Published **PRE-ALPHA** on 2026-10-02. The user explicitly authorized incorporating the reviewed fixes, committing, pushing and publishing. No installation, modlist/core edit, live database/provider call, catalog submission or maintainer message was performed.

## Incorporated source

- Reviewed fix branch: `bf02d1ac332513d407d3646970c04ea9753a9a8d`.
- Original `work/mind-poisoning` fast-forwarded to those fixes, then merged the newer collection `main` at `34ad2a1f9d86bfa4b8811510aef4fee77a662a44`. Merge: `492579ca1c1ce42e0cbceb23a8ef595f4dde62e9`.
- Release source: `4ac3b7713066216aeb37f4275ed907fa3bd99c19`.
- Immutable annotated tag `mind_poisoning-v0.1.15`: `c04314e8d27bd506fee890bb18a94a4054ee90ec`, targeting the release source above in both publishing repositories.
- The metadata owner updated only MP release fields/current documentation. Lead and scoped independent review corrected a broken release-body relative link, omitted fix descriptions and an overbroad catalog-change statement before source freeze. Version, channel, repository identity and candid API/runtime limits agree.

The reviewed listener collision, conservative aliases, locked subject revalidation, opt-in full-reply evaluator/all-member replay ledger and default reflection diagnostics are now incorporated. API v1 remains version 1; the separate reply capability advertises version 2. The embedded PCV 0.1.8 snapshot remains a v1 caller. Publishing MP does not implement its future v2 caller.

## Focused verification

The merged checkout passed these PHP 8.2.29 commands once, with stdout/stderr unsuppressed:

```text
wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/reflection_observer_test.php
wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/reflection_reply_test.php
wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/runtime_test.php
```

All exited 0. The observer/importer covered five v1 outcomes with the updated embedded PCV source. Reply output included 40 valid sanitized JSON records and no PHP diagnostics. Runtime emitted its two deliberate fixed failure diagnostics (`snapshot-verification-failed`, `player-alias-ambiguous`) and its success marker. Exact scope/hash/assertion evidence: [merged verification](release-v0.1.15-merged-verification.md). Metadata checks and scoped whitespace passed: [owner report](release-v0.1.15-metadata.md), [review](release-v0.1.15-metadata-review.md).

After building, a direct byte comparison confirmed **all server PHP files and all three executed fixture files equal the clean tag export**. Product/test/builder bytes were unchanged by the release metadata pass; no broad suites were repeated. The inherited unrelated PCV task-file EOF warning was preserved and excluded from staging; the release's staged whitespace checks passed.

## Reproducible assets

Candidate source exported with `git archive --format=zip 4ac3b7713066216aeb37f4275ed907fa3bd99c19`. A second independent export used `git archive --format=zip mind_poisoning-v0.1.15`. Each clean export ran its existing `scripts/package.py` with `--format dwpkg`, `--format repository-tar-gz` and `--format mo2-sync-zip` under Python 3.12.

The existing builder verified allowlisted payload bytes, inner/outer manifests, checksums, CRC, deterministic metadata, one tar wrapper directory and the one versioned DWPkg MO2 member. All three candidate archives and the independently regenerated checksum list matched the clean tag build byte for byte.

| Asset | Bytes | SHA-256 |
| --- | ---: | --- |
| mind_poisoning-0.1.15-mo2.zip | 622452 | `f17ccf41f680de93cf21845d53ebe12922e8b5fc88882782d6ece8d9b2ceaa22` |
| mind_poisoning-0.1.15.dwpkg | 884918 | `c65108d0e9f97c3c1362da9a255f153e4e6d027db41cdf2776b32f432cbae4ad` |
| mind_poisoning.tar.gz | 620673 | `078401449cecdf4a644382dc6ed237984856ce1758894db51a5b94eed5c2afa5` |
| SHA256SUMS.txt | 278 | `b4370a9f3dc5d7088af86e032e81c0716832190a41419c7dc9b390af643d8188` |

## Publication order and public checks

1. Pushed only the new immutable tag to `deployment` and `origin`.
2. Created two draft prereleases with `gh release create`, `--verify-tag`, `--draft`, `--prerelease`, `--latest=false`, the reviewed `--notes-file` and all four assets.
3. Downloaded both drafts with `gh release download`; verified the exact file sets, complete bytes and each downloaded checksum entry against the clean-tag candidates. REST's public release-by-tag lookup did not expose drafts; authenticated `gh release view` confirmed their tag, draft/prerelease state and asset identities instead.
4. Published both with `gh release edit --draft=false --prerelease --latest=false`. Public API confirmed `draft=false`, `prerelease=true`, the exact tag and all asset sizes/digests.
5. Only then fast-forwarded canonical and collection `main` to the release source. Verified both main refs, annotated tag/target, and decoded public manifests: version `0.1.15`, dedicated `git_repo`, unchanged development-candidate status.
6. Downloaded every public asset again into fresh directories. Both public sets equal the candidates by full bytes and all checksum entries. The prior 0.1.14 assets retain their previous digests, and the prior annotated tag remains `700ea2672a44663c52760bc74e336902baa344f6`.

Public releases:

- [Canonical Mind Poisoning](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/tag/mind_poisoning-v0.1.15): published `2026-10-02T14:52:10Z`.
- [Collection migration bridge](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.15): published `2026-10-02T14:52:11Z`, with identical assets.

Builds/downloads are retained under ignored `dist/release-0.1.15/`. A subsequent evidence-only commit advances main without changing tagged payloads, versions or package bytes.

## Preservation and limits

The newer published PCV source was incorporated unchanged from collection main. Its unrelated dirty task file still hashes to `93125ca439851affdcb38aa59ccb08db5f16970022f101edc636b6ed6477c4bf`; critique/submission drafts and screenshots remain untracked and unpublished. Staging was explicit and excluded those paths.

This proves source incorporation and reproducible publication, not installed-version, live PostgreSQL durability/concurrency, provider quality/latency, clean-server installation, companion v2 grouping/subtitle correspondence or playback. Final ACK/emitted rows are line-attempt evidence. The compatibility reference is unchanged and not an enforced deployment pin. A new live acceptance run and separate companion adoption remain necessary. Use one installation route and replace older enabled packages; no installed files were changed here.
