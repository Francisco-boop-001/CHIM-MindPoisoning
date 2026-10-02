# Mind Poisoning 0.1.16 release plan — 2026-10-02

The user authorizes commit, push and publication of the reviewed reply-cap correction. Scope: Mind Poisoning only, PRE-ALPHA. No installation, gaming-distro command, live provider/database call, PCV change/publication or catalog submission.

- [x] Inspect pending source/docs, prior verification, protected edits and both remote main refs.
- [x] Delegate and review 0.1.16 metadata and release notes; preserve v1, API version 2, historical releases and PCV 0.1.8 references.
- [x] Verify only release-relevant changed behavior/metadata in the authorized disposable PHP environment; preserve gaming VHD metadata and stop only the test clone.
- [x] Commit explicit reviewed files, build from clean commit and clean annotated tag; compare assets/checksums and executed source bytes.
- [x] Push the new tag, verify draft downloads, publish PRE-ALPHA in both repositories, then fast-forward update main refs.
- [x] Verify public assets/refs/manifests, save evidence and preserve unrelated dirty files.

Baseline: branch `work/mind-poisoning`, HEAD and both remote main refs `5e0af10a8cfad72c2580904651442a3eb7245dc5`. Published release 0.1.15. Pending code uses one 24-line reply cap across four guards; previous focused RED/GREEN/lint and independent review are recorded in reflection-reply-cap-2026-10-02.md. Root AGENTS.md permanently prohibits the gaming distro. PCV's dirty task-file hash remains the recorded `93125ca439851affdcb38aa59ccb08db5f16970022f101edc636b6ed6477c4bf`.

Ownership: bridge_contract prepares release metadata/current docs and notes only; lead reviews source/evidence, orchestrates clean-source packaging, explicit Git and publication. Reuse the code owner for any actual defect. No worktree cleanup or unrelated staging.

Verification route: explicitly stage only reviewed files, export the staged Git tree, execute the focused reply and update-flow fixtures plus changed PHP syntax checks against that clean export in the test clone, then commit that same tree. Rebuild the new immutable tag independently and compare payload/test/builder bytes and all consumer archives/checksum entries. This avoids rerunning broad suites and proves the released source equals the executed source despite Windows line-ending normalization.

Preflight: both publishing main refs still match the baseline; the 0.1.16 tag is absent in both repositories. Windows-only WSL management reports gaming and test clones stopped; gaming VHD timestamp is `2026-10-02T14:26:40.7745769Z` (ticks `639265480007745769`), size `180272234496`. Previous public asset digests are saved in ignored `dist/release-0.1.16/previous-*-assets.json`; 0.1.15 tag object remains `c04314e8d27bd506fee890bb18a94a4054ee90ec`. A fresh official guide fetch was unavailable; this run does not claim fresh guide verification. The update-flow gate reads consumer code only from the authorized clone and executes extracted helpers with stubbed manifest fetches.

## Review

Source/metadata and full focused outputs were approved independently with no defects. The exact tested Git tree became release commit `ace3d21a78208b18187e2a3650a77eaddd2011dd`; both clean-source builds, draft downloads and fresh published downloads match. Publication preceded update-main advancement. Final release receipt: [release-v0.1.16.md](release-v0.1.16.md). PRE-ALPHA and runtime limits remain explicit; the gaming environment, embedded PCV and unrelated dirty work were preserved.
