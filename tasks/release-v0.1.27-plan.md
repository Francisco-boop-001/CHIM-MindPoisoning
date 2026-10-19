# Written evidence release and SHARMAT author review — 2026-10-19

User authority: commit, push and publish the completed Mind Poisoning work; inspect SHARMAT code to clarify the author's concerns. Further SHARMAT changes remain pending discussion. Candidate version 0.1.27 is subject to fresh public-ref confirmation.

## Checklist

- [x] Confirm public refs, candidate scope and reusable verification receipts.
- [x] Review the current SHARMAT PR read-only; distinguish general fixes, policy changes and MP-specific compatibility, then prepare precise questions.
- [x] Delegate release metadata and documentation with exclusive ownership; review every diff and preserve frozen runtime files.
- [x] Commit the explicit MP allowlist, build from the immutable tag and independently reproduce all installable assets.
- [x] Push and verify draft downloads; publish a standard release clearly labelled BETA CANDIDATE, verify public downloads, then advance the update branch.
- [x] Update only MP's current hub information and retain other plugins' pins/content.
- [x] Record exact refs/checksums and the final review.

## Scope and evidence

Source: `feature/written-evidence-v1`, based on published main f21a00a. Its origin is a local source checkout: publication must target the dedicated public MP repository explicitly. Completed source evidence is in `artifacts/chim-correspondence-implementation-20261019/`; final native/package receipts supersede earlier preliminary runs. Reuse these checks rather than rerunning a database campaign for release metadata.

Owners: sharmat_build — release preflight and, after approval, version/docs/notes; sharmat_tests — independent evidence and package verification; sharmat_cleanup — SHARMAT read-only code review; lead — task records, diff/evidence review, Git/publication orchestration. All preserve others' edits. Lead writes no product code.

Safety: no gaming distro, live server, installed mods, game data, provider calls or service actions. No SHARMAT code/branch/PR changes. No Correspondence or Sworn & Scorned changes. No maturity promotion. New correspondence-autonomy requests remain separate from this frozen release.

## Review

Pre-publication review: current public main is f21a00a, latest standard release 0.1.26; authenticated release lookup and public tag checks find no 0.1.27. Independent inventory review matches all 24 runtime and six supporting files to the completed deterministic/native evidence. SHARMAT PR7 remains open at d6c6da5; its current-casual crush exemption bypasses the separate eligibility toggle. SHARMAT source remains untouched. Metadata/docs are delegated and publication gates are pending.


Final review: product commit `9e2ee6be1c83db937422f34e7ebe6061d63bf1f9` is immutably tagged `mind_poisoning-v0.1.27`. Independent clean-tag builds of all four installers match; all five draft and all five unauthenticated public downloads match staged bytes and SHA-256. Public 0.1.26 asset digests remain unchanged. Hub MP-only commit `46a191ddffcd7b7cd930e98cd5ae7e60c56ded7d` is pushed; companion sections and rows are preserved. This final documentation receipt advances main only after public-download verification. Source/native behavior checks were reused by exact hash; no new database, provider, game or WSL tests were needed. SHARMAT remains clean at `d6c6da5`, with PR #7 open and unchanged. Author questions and the eligibility/crush-policy distinction are recorded in `artifacts/mp-release-0.1.27-20261019/sharmat-author-code-review.md`. No live server, installed mod or game data was touched. BETA CANDIDATE status is unchanged.
