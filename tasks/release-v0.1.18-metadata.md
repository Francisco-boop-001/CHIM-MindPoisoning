# Mind Poisoning 0.1.18 metadata and docs — 2026-10-04

## Checklist

- [x] Read the repository instructions, prior metadata pattern, current docs, and relationship-compatibility verification record.
- [x] Advance current Mind Poisoning metadata and source-facing docs to PRE-ALPHA 0.1.18; preserve published v0.1.17 and Private Conversation 0.1.15 history.
- [x] Record the reserved rechat-sentinel guard, canonical Player behavior, empty-map contract, and verification limits without claiming full native pipeline or gameplay proof.
- [x] Validate JSON metadata, current versioned links, assigned diff scope, and whitespace; leave Git integration, package builds, and publication to the lead.

## Scope and evidence

This is metadata and documentation work only. The source and verification evidence are recorded in [relationship compatibility verification](relationship-compatibility-2026-10-04/verification.md). It reports passing focused PHP request/store seams and a direct `PostgresStoreDb::writeNpc` scratch-PostgreSQL writer/transaction fixture. The SQL fixture's PHP process printed its pass line and exited 0, but its outer PowerShell-to-Bash wrapper exited 2 after cleanup because CRLF reached Bash's final `exit 0` argument. PostgreSQL stop and scratch cleanup reported 0; the cluster was not rerun. This is not full SQL `persistJudgments`, native CHIM, provider, installation, audio, or gameplay evidence.

Mind Poisoning 0.1.18 remains PRE-ALPHA. The older v0.1.10 live Player-origin acceptance record and the user-reported v0.1.15 pair-path evidence summarized in the v0.1.16 release notes remain historical; this candidate's isolated checks do not overwrite or extend them. The embedded Private Conversation integration snapshot remains v0.1.15. At metadata preparation time, source docs use the intended versioned release destinations; this work did not check whether those URLs are publicly live.

## Changes and checks

- Advanced `server/manifest.json` to 0.1.18 while retaining schema version, repository identity, compatibility reference, candidate channel, and version-token package URL.
- Updated the existing draft `distribution/plugin_repository_entry.json` description without changing its channel, repository identity, or package URL. It has not been submitted or approved.
- Added `distribution/mind_poisoning-v0.1.18.md`; advanced current release/download pointers in `README.md`, `docs/development.md`, and the current status and relationship-compatibility text in `docs/mind-poisoning.md`. Historical v0.1.17 release/API anchors and the explicitly historical v0.1.14 installation walkthrough remain intact; PCV 0.1.15 links and snapshot references remain unchanged.
- `ConvertFrom-Json` parsed the manifest and catalog; assertions confirmed v0.1.18 manifest identity, schema and compatibility reference plus the existing candidate channel and `<version>` catalog URL. Current docs consistently point to Mind Poisoning 0.1.18 and Private Conversation 0.1.15, with no remaining current MP 0.1.17 release pointer. `git diff --check` passed for the changed tracked metadata/docs, and the two new Markdown files passed a separate trailing-whitespace check. Final owned-diff review passed.

The lead owns staging, commit/tag/push, package builds, and publication. No install, live provider/database action, WSL command, package build, commit, tag, push, or publication was performed by this metadata owner.
