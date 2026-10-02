# v0.1.15 release metadata review

**Verdict:** Metadata and compatibility fields pass. One minor release-note accuracy correction remains before publication; no product defect found.

Reviewed the assigned current diff and new release note on HEAD `492579ca1c1ce42e0cbceb23a8ef595f4dde62e9`, plus the metadata owner's report. No fixtures or broad suites were repeated.

## Checks

- `server/manifest.json:3-17`: version `0.1.15`, schema 2, unique deployment repository `Francisco-boop-001/CHIM-MindPoisoning`, `development_candidate`, default `candidate` channel on `main`, canonical manifest URL, and `<version>` release URL agree. Substituting `0.1.15` yields the exact tar asset URL in the release note. Both JSON files parse; the repository entry retains the same identity/channel/update URL (`distribution/plugin_repository_entry.json:8-19`). Descriptions explicitly require opt-in companion registration and do not imply automatic v2 adoption.
- Current candidate and asset references agree across `README.md`, deployment/development guides, and server README. Release-body link inspection finds seven links, all absolute GitHub URLs pinned to v0.1.15 or the intentionally historical v0.1.14 bridge (`distribution/mind_poisoning-v0.1.15.md:44-49`). The three assets are `mind_poisoning.tar.gz`, `mind_poisoning-0.1.15.dwpkg`, and `mind_poisoning-0.1.15-mo2.zip`.
- V1 remains constant 1 and v2 separately constant 2 (`server/reflection.php:9-10`). The metadata diff preserves the reviewed v2 signature/registration/caps/grouping/final-ACK/replay contract (`docs/integration-api.md:28-55`; release note `:7-34`). Embedded PCV remains version `0.1.8`, checks v1, and calls the original evaluator (`plugins/private_conversation/server/manifest.json:3`; `plugins/private_conversation/server/reflection.php:272-273,1048`). Scoped product/test/PCV diff-name inspection returned no changes.
- Listener, conservative alias, under-lock selected-subject revalidation, and omitted-logger fixes match the reviewed source/merged fixture evidence, subject to the wording finding below (`distribution/mind_poisoning-v0.1.15.md:38-40`). The existing multiple-Player-relationship-key precheck is not advertised as new.
- PRE-ALPHA status, current PCV v1-only caller, future v2 companion adoption, immutable grouping boundary, playback limits, and isolated-fixture evidence ceiling are accurate (`distribution/mind_poisoning-v0.1.15.md:1-3,30-34,53-55`). Historical v0.1.14 bridge and release references are retained in the deployment/development guides and server README.
- Scoped `git diff --check` passed with no output. The metadata owner's report also records JSON semantic and new-release-note whitespace checks (`tasks/release-v0.1.15-metadata.md`). Prior source/task gates and the three merged fixtures remain the behavioral evidence; they were not repeated.

## Finding

**Minor — `distribution/mind_poisoning-v0.1.15.md:39`:** “so catalog changes during model work fail before persistence” is broader than the implemented behavior. Changes that invalidate a selected subject cause rejection; an unrelated change or an ambiguous short alias does not reject a still-valid canonical full-name match. The reviewed ordinary/reflection control fixtures deliberately verify that distinction.

Suggested replacement: “Subject identity and alias matching are repeated under the actor lock, so catalog changes that invalidate a selected subject cause rejection before persistence.” The finding was returned to the release owner; this reviewer made no release-note edit.

## Limits and scope

Lead acceptance: the same metadata owner corrected the minor wording and recorded it in release-v0.1.15-metadata.md. The lead inspected the final sentence: only a catalog change that invalidates the selected subject/alias rejects the write, while a full canonical-name match may remain committable. This agrees with the already-passing paired regression controls. No findings remain before source freeze; no product or test change/rerun was needed.

Tags and public assets are pending release-owner creation and have not been checked as published resources. This review certifies the metadata against the reviewed source contract, not archive contents, live installation, PostgreSQL/provider behavior, companion v2 adoption, or playback. This report is the sole file written; no product, PCV, metadata, Git, or live-state mutation was performed.
