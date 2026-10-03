# Hub PCV 0.1.12 snapshot update — 2026-10-03

## Checklist

- [x] Inspect repository state, prior instructions/evidence and the protected dirty-file hash.
- [x] Verify the published PCV tag, source contract, deterministic assets and actual public download bytes.
- [x] Refresh the embedded PCV snapshot from the immutable tag while preserving the protected dirty plan and local-only extras.
- [x] Update hub release descriptions and v1/v2 integration guidance without overlaying the imported PCV README.
- [x] Audit the exact inventory, source equality, checks and protected/unrelated state; record verification limits.

- [x] Correct current working-tree versus immutable-tag observer-fixture wording.
- [x] Add explicitly unreleased overhearing API guidance without changing published MP release tables.
- [x] Record the lead's current-working-tree observer and full-reply fixture results.

## Review

### Published source and assets

- Read-only PCV checkout tag: `private_conversation-v0.1.12`, annotated tag object `cfcf784f88c5095ff564fc6549b5a92ba4a6ffc8`, peeled commit `f6025739b9d75dfd693cf518660af497abe5ff51`. Remote tag refs matched these local refs. Public release metadata reported `draft=false`, `prerelease=true`.
- Clean tag export and deterministic package build used the release's own `scripts/build-package.py`; generated assets compared byte-for-byte with the corresponding public downloads. SHA-256:
  - `private_conversation-0.1.12.dwpkg`: `f935d8ee1d93ec7e92d9d6f5b09c48267c3133859a011a8c77cc88cdb8b449fe`
  - `private_conversation-0.1.12-mo2.zip`: `07f2ff54435297d447bde52b51c7b0c71073149b649e1f7f1fa2d0e7e0a71851`
  - `private_conversation.tar.gz`: `4eee7c8b5551fe03e9092c584658edf8f0164ee2c9c7d0aae6242394278eb788`
  - `SHA256SUMS.txt`: `f425be05eae045eb199a65c7a7d33740c223f02653eb71bcb328383ed17c69f6`
- Scratch evidence is under ignored `dist/hub-pcv12-2026-10-03/`. No public asset, tag, manifest pin, or release was changed.

### Source and API review

- Imported PCV 0.1.12 requires the exact reflection API v1 constant. It opts into v2 only when the exact v2 capability/evaluator and source helpers are present. Its adapter proves a reply window from a unique instruction anchor and that request's source rows; if grouping is not proven or exceeds the negotiated cap it falls back to the v1 final-line registration.
- MP 0.1.14 is v1-only; MP 0.1.15 supports v2 up to eight lines; MP 0.1.16 raises that limit to 24. The imported PCV README's `.14–.15` final-line-only sentence is broader than MP .15's source/API contract. The imported README remains byte-identical to the published tag; the discrepancy is explained in hub-owned guidance.
- Public feature summary describes pair/solo, picked groups of two to four, and Free scenes using the nearest six eligible NPCs while excluding the player. The .1.10 tolerance is described only for an already-active scene (current close report, close observation within 60 seconds, or one wider-range report); scene activation still requires close and AI-active evidence. These are source-contract descriptions, not new live claims.
- The MP 0.1.16 working-tree observer fixture resolves the embedded PCV path dynamically, so it loads PCV 0.1.12; the lead's authorized-clone run passed all five cases. The immutable published MP 0.1.16 tag still contains PCV 0.1.8 at that path. The separate PCV full-reply fixture passed grouping, validation, routing, and fallback checks.

### Exact imported snapshot inventory

Comparison of the local snapshot with the 172 paths in the clean published-tag export found 171 byte-identical tag files. The only tag-path exception is the protected local file `plugins/private_conversation/tasks/logging-improvements-plan.md`, which was left untouched and retains SHA-256 `93125ca439851affdcb38aa59ccb08db5f16970022f101edc636b6ed6477c4bf`. No snapshot paths were deleted. Two pre-existing local-only paths were retained and are not in the tag export: `plugins/private_conversation/scripts/__pycache__/build-package.cpython-312.pyc` and `plugins/private_conversation/tests/autonomous_presence_check.php`.

Added from the PCV tag (50):

- `plugins/private_conversation/distribution/private_conversation-v0.1.9.md`
- `plugins/private_conversation/distribution/private_conversation-v0.1.10.md`
- `plugins/private_conversation/distribution/private_conversation-v0.1.11.md`
- `plugins/private_conversation/distribution/private_conversation-v0.1.12.md`
- `plugins/private_conversation/docs/live-server-testing.md`
- `plugins/private_conversation/docs/superpowers/plans/2026-10-03-pcv-0.1.10-reliability.md`
- `plugins/private_conversation/docs/superpowers/plans/2026-10-03-pcv-0.1.11-group-mode.md`
- `plugins/private_conversation/docs/superpowers/plans/2026-10-03-pcv-0.1.12-free-mode.md`
- `plugins/private_conversation/docs/superpowers/specs/2026-10-03-pcv-0.1.10-reliability-design.md`
- `plugins/private_conversation/docs/superpowers/specs/2026-10-03-pcv-0.1.11-group-mode-design.md`
- `plugins/private_conversation/docs/superpowers/specs/2026-10-03-pcv-0.1.12-free-mode-design.md`
- `plugins/private_conversation/scripts/live-test/crowd_live.py`
- `plugins/private_conversation/scripts/live-test/env.sh`
- `plugins/private_conversation/scripts/live-test/install_pkg.py`
- `plugins/private_conversation/scripts/live-test/pcvsim.py`
- `plugins/private_conversation/scripts/live-test/run-php-tests.sh`
- `plugins/private_conversation/scripts/live-test/sim.ps1`
- `plugins/private_conversation/scripts/live-test/smoke.py`
- `plugins/private_conversation/scripts/live-test/solo_retest.py`
- `plugins/private_conversation/scripts/live-test/standard.py`
- `plugins/private_conversation/tasks/feature-requests.md`
- `plugins/private_conversation/tasks/free-mode-verification-2026-10-03.md`
- `plugins/private_conversation/tasks/group-mode-verification-2026-10-03.md`
- `plugins/private_conversation/tasks/live-issues-2026-10-03.md`
- `plugins/private_conversation/tasks/release-0.1.8/package-review.md`
- `plugins/private_conversation/tasks/release-0.1.8/preservation-review.md`
- `plugins/private_conversation/tasks/release-0.1.8/public-state-review.md`
- `plugins/private_conversation/tasks/release-0.1.8/publication-evidence.md`
- `plugins/private_conversation/tests/free_activation_check.php`
- `plugins/private_conversation/tests/free_config_check.php`
- `plugins/private_conversation/tests/free_opener_check.php`
- `plugins/private_conversation/tests/free_ui_check.php`
- `plugins/private_conversation/tests/group_activation_check.php`
- `plugins/private_conversation/tests/group_config_check.php`
- `plugins/private_conversation/tests/group_opener_check.php`
- `plugins/private_conversation/tests/group_routing_check.php`
- `plugins/private_conversation/tests/group_scope_check.php`
- `plugins/private_conversation/tests/group_ui_check.php`
- `plugins/private_conversation/tests/log_shutdown_context_check.php`
- `plugins/private_conversation/tests/presence_active_scene_check.php`
- `plugins/private_conversation/tests/presence_crowd_check.php`
- `plugins/private_conversation/tests/presence_log_volume_check.php`
- `plugins/private_conversation/tests/presence_same_second_check.php`
- `plugins/private_conversation/tests/presence_wide_capture_check.php`
- `plugins/private_conversation/tests/reflection_ack_pending_check.php`
- `plugins/private_conversation/tests/reflection_actor_id_state_check.php`
- `plugins/private_conversation/tests/reflection_full_reply_check.php`
- `plugins/private_conversation/tests/reflection_reply_length_check.php`
- `plugins/private_conversation/tests/solo_guidance_check.php`
- `plugins/private_conversation/tests/ui_logs_host_access_check.php`

Updated to the PCV tag (27; excluding the protected plan above):

- `plugins/private_conversation/.gitattributes`
- `plugins/private_conversation/.gitignore`
- `plugins/private_conversation/README.md`
- `plugins/private_conversation/server/assets/ui-refresh.js`
- `plugins/private_conversation/server/context.php`
- `plugins/private_conversation/server/context_pre.php`
- `plugins/private_conversation/server/index.php`
- `plugins/private_conversation/server/json_response_custom.php`
- `plugins/private_conversation/server/log.php`
- `plugins/private_conversation/server/manifest.json`
- `plugins/private_conversation/server/prepostrequest.php`
- `plugins/private_conversation/server/preprocessing.php`
- `plugins/private_conversation/server/prerequest.php`
- `plugins/private_conversation/server/reflection.php`
- `plugins/private_conversation/server/reflection_receipt.php`
- `plugins/private_conversation/server/scope.php`
- `plugins/private_conversation/server/state.php`
- `plugins/private_conversation/tasks/release-0.1.8/plan.md`
- `plugins/private_conversation/tasks/todo.md`
- `plugins/private_conversation/tests/page_check.php`
- `plugins/private_conversation/tests/reflection_ack_bug_check.php`
- `plugins/private_conversation/tests/reflection_ack_shutdown_check.php`
- `plugins/private_conversation/tests/reflection_direct_ack_capacity_check.php`
- `plugins/private_conversation/tests/reflection_hook_timing_check.php`
- `plugins/private_conversation/tests/reflection_receipt_bug_check.php`
- `plugins/private_conversation/tests/reflection_registry_check.php`
- `plugins/private_conversation/tests/scope_check.php`
- `plugins/private_conversation/tests/ui_refresh_check.mjs`

### Hub-owned changes

- Updated: root `README.md`, `docs/deployment-migration.md`, `docs/development.md`, `docs/integration-api.md`, `server/README.md`, and prose-only `server/AGENTS.md`.
- Added the published tag's `distribution/private_conversation-v0.1.12.md` note at the root; its SHA-256 is `d0611484cc3ee914263cf615d07a2b43a63fe9bbf8f51c4a3bd1e6792b86f3a9`.
- Added this exact inventory and verification record.
- This subtask made no PCV README overlay, MP product/test edits, MP manifest/pin changes, package-builder edits, root `tasks/todo.md` edits, Git index updates, commits, tags, pushes or publication actions. Concurrent MP work was present and left untouched in `server/influence.php`, `server/logging.php`, `server/model.php`, `server/prerequest.php`, `server/store.php`, `tests/overhearing_test.php`, and `tasks/todo.md`.
- The existing dirty PCV plan and unrelated repository changes were preserved; root `tasks/todo.md` was not touched.

### Unreleased overhearing guidance follow-up

- Added a separate, explicitly UNRELEASED contract section to `docs/integration-api.md` and a short linked summary to `server/README.md`.
- It documents the default-off `mind_poisoning_overhearing_enabled` setting, administrator-managed `chimSetGeneralSetting` bootstrap boundary/no new endpoint, draft version marker, one-call listener/token caps, exact `eventlog.people` source and evidence limits, independent listener commits, sequential replay without concurrent provider reservation, and correlation fields. Source review is complete; `listener_role`, `addressed_listener_id`, and `batch_id` are implemented in the working tree, while the feature remains UNRELEASED and game-unverified.
- Published MP 0.1.16 release tables and assets were not changed. Documentation whitespace checks and the lead's focused current-worktree fixtures passed.

### Checks and limits

- Deterministic builder output matched all three public assets and the public checksum file byte-for-byte; public metadata was non-draft prerelease.
- Snapshot comparison: 171/172 published source paths exact, with the single protected-file exception; no deletions; two local-only extras retained. Imported PCV README blob matches the tag blob.
- `git diff --check` passed for all changed hub-owned documentation. In the explicitly authorized test clone, `php tests/reflection_observer_test.php` exited 0 with stdout `reflection observer/importer: 5 cases passed (committed delta, zero change, provider failure, unconfirmed commit, warning skip)` and empty stderr. `php plugins/private_conversation/tests/reflection_full_reply_check.php` exited 0 with stdout `lines=[101,104];thirteen_lines=13;no_anchor,two_anchors,other_request,actor_ordinary_line,aborted_line,final_not_last,over_cap_lines fallback; PASSfullreplygrouping/validation/routing` and empty stderr. All eight focused fixture checks and eleven changed-PHP lint checks exited 0.
- The source-hash freeze remained unchanged; the authorized test clone stopped with exit 0; the gaming VHD remained unchanged. No live database, provider, service, or installation was used. These checks do not establish installed CHIM behavior, live persistence/provider behavior, extension ordering, or in-game/audio delivery.
