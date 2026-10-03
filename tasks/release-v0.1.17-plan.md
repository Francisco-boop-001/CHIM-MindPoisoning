# Mind Poisoning 0.1.17 publication plan — 2026-10-03

The user explicitly authorizes commit, push and publication of the reviewed overhearing work and PCV 0.1.12 hub snapshot update. Publish PRE-ALPHA in the canonical deployment repository and collection mirror using the existing release procedure. Publication does not authorize installation, gaming-distro commands, live providers/databases, PCV code changes/publication or catalog submission.

- [x] Inspect exact working tree, prior source freeze/runtime evidence, protected files, remote main/tag/release state and relevant lessons.
- [x] Delegate and review 0.1.17 metadata/current documentation and truthful release notes; retain default-off and live limits.
- [x] Stage only reviewed feature/hub/release files, preserve the dirty PCV plan, export the exact index tree and verify focused release behavior/metadata in the authorized test clone.
- [x] Commit the verified tree, create a new immutable annotated tag, independently rebuild/export and compare all source/package/checksum bytes.
- [x] Push the new tag, create draft PRE-ALPHA releases, compare downloaded assets, publish, then fast-forward update main branches.
- [x] Verify public assets/manifests/refs and historical preservation; record the receipt and preserve unrelated work.

Baseline: branch `work/mind-poisoning`, HEAD `9eff9b280cb06526050467653b13f9563a4d93e2`; current published MP 0.1.16. Eight isolated fixtures and eleven changed-PHP lints passed against the eleven-file freeze in `dist/overhearing-2026-10-03/final-source-freeze.json`. Feature and independent review are recorded in `overheard-gossip-2026-10-03.md` and `defensive-programming-review-2026-10-03.md`. The hub imports the verified PCV 0.1.12 tag; all tag paths must be staged except the protected plan, whose existing HEAD bytes remain in the clean export. This exception is documented rather than published as somebody else's dirty work.

Fresh preflight confirms both remote main refs equal the baseline, the new tag/release is absent in both repositories, and the index has no staged files. Old 0.1.16 tag object `7d36c518ec57c5f201a3ea8b66265d47416ba1e0` and peeled commit `ace3d21a78208b18187e2a3650a77eaddd2011dd` match in both repositories; all old public asset identities were saved under ignored `dist/release-0.1.17/previous-*.json`. Windows reports both CHIM distros stopped and unchanged gaming VHD metadata. Unqualified Git lookup initially failed ownership validation; every Git operation now uses exact command-scoped `safe.directory` and `-C` without changing global configuration. The official modders-guide fetch was unavailable, so this release uses the already-inspected consumer contracts and does not claim fresh guide verification or submit a catalog entry.

Protected plan: `plugins/private_conversation/tasks/logging-improvements-plan.md`, SHA-256 `93125ca439851affdcb38aa59ccb08db5f16970022f101edc636b6ed6477c4bf`. Preserve critique, submission drafts, screenshots and local-only PCV files. Historical release tags/assets remain immutable. Derive package names/version from the new manifest; no `latest` download URLs.

Ownership: original `hub_pcv12` owner prepares metadata/current docs/release note only; original `defensive_review` performs read-only release review; lead owns plan, explicit Git operations, clean-export verification, package builds and publication. Lead writes no product code. Reuse the feature owner only for a reproduced defect.

Runtime route: explicit `DwemerAI4Skyrim3-test` only, Windows preflight refuses running gaming distro and requires unchanged gaming VHD metadata. Session baseline ticks `639266333216225615`, bytes `182184837120`. Enter no default/gaming distro, administer no CHIM services and call no actual provider/database. Stop only the test clone after checks. Use exact clean-source bytes, not a release candidate inferred from previous working-tree execution.

## Review

All gates passed. The release commit/tag and both public releases are verified; both main branches advanced only after public download verification. Nine exact-source fixtures and eleven lints passed. Final scope/source/output review found no blocker. The release receipt records hashes, immutable history, protected edits and runtime limitations. Synthetic/isolated execution and package reproducibility are not real batch provider, PostgreSQL durability, installed update, scene membership or playback proof. See [release-v0.1.17.md](release-v0.1.17.md).
