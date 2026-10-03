# Mind Poisoning 0.1.17 metadata and docs — 2026-10-03

## Checklist

- [x] Read repository instructions, prior lessons, CHIM release guidance, current feature source, fixtures, and release-facing docs.
- [x] Advance only current Mind Poisoning metadata and links to PRE-ALPHA 0.1.17; keep historical 0.1.16 release facts intact.
- [x] Publish the source-level overhearing contract and verification limits in release-facing docs.
- [x] Validate JSON, versioned links/claims, assigned diff scope, and whitespace; leave package build and publication to the lead.

## Scope and evidence

This record covers metadata/documentation only. The product owner’s finalized verification record is `tasks/overheard-gossip-2026-10-03.md`: eight isolated fixtures and eleven changed-file PHP syntax checks passed in the explicitly authorized test clone. Those results do not establish native general-setting operation, real database durability, provider behavior, or gameplay/audio delivery.

Mind Poisoning 0.1.17 remains PRE-ALPHA. NPC ACK overhearing is disabled by default, uses CHIM's administrator-managed general setting, batches the addressed listener with at most four eligible extras in one model call, and persists each listener independently. Exact `eventlog.people` membership is not evidence of playback or Private Conversation scene provenance; sequential replay checks do not reserve against concurrent provider calls.

Published 0.1.16 release notes and tag remain historical and unchanged, including its 24-line v2 reply cap. The embedded Private Conversation snapshot remains 0.1.12. The lead owns staging, package builds, commit/tag/push and publication.

## Changes and checks

- Updated `server/manifest.json` to 0.1.17 while retaining its PRE-ALPHA candidate channel, repository identity, compatibility reference and version-token package URL. Updated the draft `distribution/plugin_repository_entry.json` description without changing its channel or package URL.
- Added `distribution/mind_poisoning-v0.1.17.md`; updated current version/download links and source-contract prose in the root README, `docs/development.md`, `docs/deployment-migration.md`, `docs/integration-api.md`, `server/README.md` and `server/AGENTS.md`.
- Static JSON parsing passed; manifest/catalog version, maturity, repository identity, channel and package URL templates agree. Scoped release-reference review confirmed current links target 0.1.17 while the 0.1.16 full-reply 24-line facts remain historical. `git diff --check` passed on owned tracked files; the new release note and this task record contain no trailing whitespace.
- `tests/manifest_update_check.php` was left unchanged: its version handling derives the next patch from the manifest and has no hard-coded 0.1.16 expectation. No PHP test, package build, Git mutation, publication, database/provider/install action, or game test was run by this metadata owner. The release links are prepared versioned destinations, not a claim that the assets are already public.
