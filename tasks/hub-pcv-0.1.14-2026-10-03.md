# Hub PCV 0.1.14 snapshot update — 2026-10-03

## Checklist

- [x] Inspect the prior 0.1.12 hub record, current docs, protected baseline, and exact 0.1.14 tag archive inventory.
- [x] Refresh only the imported 0.1.14 tag paths; preserve the protected dirty plan and local-only extras.
- [x] Update current hub links/descriptions and integration guidance; keep imported PCV README and root release note byte-identical to the tag.
- [x] Compare the finished snapshot to the archive, check documentation diffs, and record exact changed paths and verification limits.

## Source

- Immutable tag: `private_conversation-v0.1.14`, annotated tag object `142dfdb8f40066d6cf7e8f8acb59c65bf191d77f`, peeled commit `3b7214d5a6d9bb4bf660167f4009275fe09ef2c2`.
- Lead-provided clean tag archive: `dist/hub-pcv14-2026-10-03/tag-source.zip`; its 192 file entries were hash-checked against the extracted tag-source files. The extracted folder also contains lead-generated package build outputs under `dist/hub-pcv14-2026-10-03/tag-source/dist/release-v0.1.14/`; those are not Git tag paths and are excluded from the import.
- Lead verified the four published assets against clean-tag builds; this record does not repeat package or public-download verification.

## Imported snapshot inventory

The embedded working copy was compared against the exact 192 paths in `tag-source.zip`, not the expanded folder's untracked package outputs. It has 191 byte-identical tag files; the sole tag-path exception is the protected dirty `plugins/private_conversation/tasks/logging-improvements-plan.md`, retained at SHA-256 `93125ca439851affdcb38aa59ccb08db5f16970022f101edc636b6ed6477c4bf`. Its prior index/HEAD version was not changed. No tag paths were deleted. Two paths outside the PCV tag remain in the working copy: `plugins/private_conversation/tests/autonomous_presence_check.php`, tracked in this MP repository, and the ignored local cache `plugins/private_conversation/scripts/__pycache__/build-package.cpython-312.pyc`, which is not part of a clean source export.

Added from the PCV 0.1.14 tag (20):

- `plugins/private_conversation/distribution/private_conversation-v0.1.13.md`
- `plugins/private_conversation/distribution/private_conversation-v0.1.14.md`
- `plugins/private_conversation/docs/superpowers/specs/2026-10-03-pcv-0.1.13-reliability-design.md`
- `plugins/private_conversation/docs/superpowers/specs/2026-10-03-pcv-0.1.14-roleplay-design.md`
- `plugins/private_conversation/tasks/audit-2026-10-03.md`
- `plugins/private_conversation/tasks/reliability-verification-2026-10-03.md`
- `plugins/private_conversation/tasks/roleplay-verification-2026-10-03.md`
- `plugins/private_conversation/tests/active_ttl_renewal_check.php`
- `plugins/private_conversation/tests/in_game_commands_check.php`
- `plugins/private_conversation/tests/log_reason_literals_check.php`
- `plugins/private_conversation/tests/page_notes_check.php`
- `plugins/private_conversation/tests/reflection_presence_check.php`
- `plugins/private_conversation/tests/reflection_scope_error_check.php`
- `plugins/private_conversation/tests/roleplay_settings_check.php`
- `plugins/private_conversation/tests/roleplay_ui_check.php`
- `plugins/private_conversation/tests/scene_resilience_check.php`
- `plugins/private_conversation/tests/state_recovery_check.php`
- `plugins/private_conversation/tests/strict_rechat_check.php`
- `plugins/private_conversation/tests/turn_hooks_check.php`
- `plugins/private_conversation/tests/turn_plan_check.php`

Updated to the PCV 0.1.14 tag (18):

- `plugins/private_conversation/README.md`
- `plugins/private_conversation/scripts/live-test/standard.py`
- `plugins/private_conversation/server/assets/ui-refresh.js`
- `plugins/private_conversation/server/context_pre.php`
- `plugins/private_conversation/server/context.php`
- `plugins/private_conversation/server/index.php`
- `plugins/private_conversation/server/json_response_custom.php`
- `plugins/private_conversation/server/log.php`
- `plugins/private_conversation/server/manifest.json`
- `plugins/private_conversation/server/prepostrequest.php`
- `plugins/private_conversation/server/preprocessing.php`
- `plugins/private_conversation/server/prerequest.php`
- `plugins/private_conversation/server/reflection.php`
- `plugins/private_conversation/server/scope.php`
- `plugins/private_conversation/server/state.php`
- `plugins/private_conversation/tests/scope_check.php`
- `plugins/private_conversation/tests/ui_check.php`
- `plugins/private_conversation/tests/ui_refresh_check.mjs`

There were no published-tag path deletions between the 0.1.12 and 0.1.14 exports. The lead-generated `dist/hub-pcv14-2026-10-03/tag-source/dist/release-v0.1.14/` package outputs in the expanded scratch folder are excluded; they are not archive/tag paths and were not copied into the snapshot.

## Hub-owned changes

- Updated current PCV version/status/download links and concise feature summary in root `README.md`; current installation and compatibility links in `docs/development.md` and `docs/deployment-migration.md` now point to 0.1.14.
- Updated `docs/integration-api.md`, `server/README.md`, and prose-only `server/AGENTS.md` for the .14 v1/v2 contract, the current dynamic fixture target vs historical .12 fixture results, prompt-only scene cards, sentinel-targeted wrap-up boundaries, SHARMAT qualification, and PCV-owned `scope_unavailable` reason.
- Added root `distribution/private_conversation-v0.1.14.md` as an exact copy of the published tag's note. SHA-256: `98745c694de40a9a72040552dc5e50059bcc704569d9f6c04ed08d57a2ef6084`.
- Imported `plugins/private_conversation/README.md` is byte-identical to its published .14 tag file; no source overlay was made. Historical .12 release notes, the immutable published MP .16 tag's .8 snapshot, and all MP release tables/pins were left as historical facts.
- Added this task record. Root `tasks/todo.md`, product PHP outside the PCV pinned snapshot, MP manifest/version/pins, package builders, tests outside the snapshot, and all unrelated dirty/untracked paths were not edited by this subtask.

## Source review and limits

- PCV .14 keeps its exact reflection API v1 requirement and conditionally uses v2 when the exact capability/helpers and a source-proven grouped reply are available; otherwise it falls back to the v1 final line. The imported README's `.14–.15` final-line-only statement is broader than MP .15's published v2 API. The imported README remains unchanged.
- The published PCV .14 README retains its addressed-only summary and future-facing wording that overheard gossip arrives when MP supports judging every witness. Hub guidance clarifies that MP .17 already offers bounded, optional overhearing on ordinary addressed NPC ACKs; this is not a blanket every-witness guarantee.
- PCV's whole-input `end scene` command runs before activation and is consumed without reaching an NPC. `wrap up:` routes one closing turn, normally to `explicit_disable_rechat`, ends scene state and disables the relationship queue for that request. Sentinel-targeted lines do not have an addressed NPC, so they are skipped before MP .17 witness overhearing. If a SHARMAT pin selects a member instead, eligibility follows the actual source, ACK, and ordinary MP gates; MP does not receive a PCV wrap-up flag or a sentinel-only witness path.
- Group turn spreading narrows choices to unspoken members in groups of three or more and relaxes strict rechat targeting only on those turns; CHIM still determines rechat count. The scene card is prompt context, not dialogue or eventlog evidence. Turn-length settings are model guidance. PCV roleplay guidance preserves per-NPC conditions; intimate SHARMAT listener interplay is source/offline checked, not gameplay-certified.
- PCV `scope_unavailable` describes a PCV reflection-scope read failure. A false revalidation callback maps to MP's generic stale-registration result; do not attribute PCV's reason to MP.
- The five-case MP observer/importer pass and PCV full-reply fixture pass recorded in prior hub docs were run against embedded PCV .12 before this pin. The current working-tree observer fixture dynamically resolves to .14, but no post-pin PHP test was run or claimed here. Lead's source-flow review covers the MP .17 sentinel boundary. No live provider, database, service, installation, or game test was performed by this subtask.

## Verification

- Archive inventory: 192 unique file paths; every archived file hash matched its extracted source counterpart. Against the prior .12 export: 20 additions, 18 content changes, zero deletions. The lead-generated `dist/hub-pcv14-2026-10-03/tag-source/dist/release-v0.1.14/` artifacts are absent from the archive and were not copied into the snapshot.
- Post-import comparison: all 192 tag paths are present; 191 are byte-identical, zero are missing, and the only mismatch is the protected plan. The two non-tag working-copy paths above remain. The imported README and root release note match tag bytes; the PCV snapshot manifest reports `0.1.14`.
- All seven hashes in `protected-before.json` matched after import. The protected plan retains its exact recorded SHA-256.
- `git diff --check` passed for the assigned tracked hub docs (`README.md`, `docs/development.md`, `docs/deployment-migration.md`, `docs/integration-api.md`, `server/README.md`, `server/AGENTS.md`). The root release note is byte-identical to the published tag (SHA-256 above).
- Per the lead's verification, all four published .14 assets matched clean-tag builds, seven protected baselines and 37 MP/product/builder/test hashes remained unchanged, and the current README release header/download links resolve to .14/.17 without a .13 release URL. No PHP or game/runtime test was run for the newly pinned PCV .14 snapshot; the prior .12 observer/full-reply results are explicitly historical.
