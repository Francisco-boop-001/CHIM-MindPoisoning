# Written evidence release and SHARMAT author review — 2026-10-19

User authority: commit, push and publish the completed Mind Poisoning work; inspect SHARMAT code to clarify the author's concerns. Further SHARMAT changes remain pending discussion. Candidate version 0.1.27 is subject to fresh public-ref confirmation.

## Checklist

- [x] Confirm public refs, candidate scope and reusable verification receipts.
- [x] Review the current SHARMAT PR read-only; distinguish general fixes, policy changes and MP-specific compatibility, then prepare precise questions.
- [x] Delegate release metadata and documentation with exclusive ownership; review every diff and preserve frozen runtime files.
- [ ] Commit the explicit MP allowlist, build from the immutable tag and independently reproduce all installable assets.
- [ ] Push and verify draft downloads; publish a standard release clearly labelled BETA CANDIDATE, verify public downloads, then advance the update branch.
- [ ] Update only MP's current hub information and retain other plugins' pins/content.
- [ ] Record exact refs/checksums and the final review.

## Scope and evidence

Source: `feature/written-evidence-v1`, based on published main f21a00a. Its origin is a local source checkout: publication must target the dedicated public MP repository explicitly. Completed source evidence is in `artifacts/chim-correspondence-implementation-20261019/`; final native/package receipts supersede earlier preliminary runs. Reuse these checks rather than rerunning a database campaign for release metadata.

Owners: sharmat_build — release preflight and, after approval, version/docs/notes; sharmat_tests — independent evidence and package verification; sharmat_cleanup — SHARMAT read-only code review; lead — task records, diff/evidence review, Git/publication orchestration. All preserve others' edits. Lead writes no product code.

Safety: no gaming distro, live server, installed mods, game data, provider calls or service actions. No SHARMAT code/branch/PR changes. No Correspondence or Sworn & Scorned changes. No maturity promotion. New correspondence-autonomy requests remain separate from this frozen release.

## Review

Pre-publication review: current public main is f21a00a, latest standard release 0.1.26; authenticated release lookup and public tag checks find no 0.1.27. Independent inventory review matches all 24 runtime and six supporting files to the completed deterministic/native evidence. SHARMAT PR7 remains open at d6c6da5; its current-casual crush exemption bypasses the separate eligibility toggle. SHARMAT source remains untouched. Metadata/docs are delegated and publication gates are pending.
