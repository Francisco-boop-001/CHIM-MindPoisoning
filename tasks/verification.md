# Candidate verification

## Local dashboard — 2026-09-27

Three retained gpt-6-luna/max agents implemented the read-only controller, bounded data adapter and journal UI with distinct ownership; lead reviewed every product path and returned concrete defects to owners. User selected loopback or server-authenticated remote access. No CHIM/core/mod changes or live database/provider execution. L-01 work remains included and uncommitted.

Lead commands through WSL PHP: dashboard_data_test.php and dashboard_http_test.php passed; after final product correction, dashboard_data_test.php and dashboard_integration_test.php passed. Integration copies real modules into an isolated root with no core SQL class, so it verifies log-only render/filter/download/CSS/400 behavior, not native database queries. Owner preview self-test and browser checks cover view behavior. Desktop and mobile screenshots were inspected by lead; the fresh mobile viewport confirms wrapping/gutter, with client/scroll width375 and viewport390 including scrollbar. Synthetic preview data is visibly labeled and never packaged.

Python package suite passed4 tests. Both dist/dashboard-check archives were built and verified against current source: mind_poisoning.tar.gz 41008 bytes SHA-256 e4bdfc5b896d587c87999dbc31b263ed3b927e65afbfccbd3df44ac7c0aea015; mind_poisoning-0.1.2.dwpkg 193743 bytes SHA-256 b2c435138be85f4862f571c11a6539c612a78721b1dd94f205b566ee0f70d2c9. These are local verification artifacts, not released replacements for v0.1.2. Public assets and all release/compatibility pins remain unchanged. Native DB, deployed authentication and in-game verification are still required.

## L-01 local failure classification — 2026-09-27

Lead independently ran model_test.php and runtime_test.php via WSL PHP after final review edits: both exit 0, with model adapter checks passed and runtime store checks passed. Runtime emitted its two expected negative persistence fixture messages. Tests verify typed connector causes/global restoration, unavailable connector subprocess, untrusted exception text/code fallback, nine parser rejection codes in model_finished/request_finished, warning severity and unchanged affinity/history on failures. Workers recorded initial failures before implementation and additional influence/logger checks in tasks/l01-runtime.md. Lead reviewed all diffs; git diff --check passed. Documentation/manifest descriptions corrected without changing version, compatibility reference or catalog tag. These are isolated fixtures, not live database/provider/game evidence. Local uncommitted changes; published v0.1.2 artifacts remain unchanged.

## v0.1.2 release gate — 2026-09-27

User authorized publication of the logging update. Fresh logging_test.php and store_logging_test.php (includes runtime_test.php) exited 0. Two negative fixture diagnostics were expected. Final frozen-source builders and verifiers passed for dist/0.1.2: mind_poisoning.tar.gz: 22960 bytes; SHA-256 9821753f05eaee6e965181e5e0417329c544a72021483482ae0ff51057859b0b; mind_poisoning-0.1.2.dwpkg: 104060 bytes; SHA-256 0f2e95fa9f54e12fc9aa50ae2648e77c072bf7896519af4982f67d55a9b7a76e. Compatibility reference stays cf5030f15781637498be86debe26fcf102f5690d. Live-runtime limitations remain; release is a development prerelease. Published source commit 02c7616, annotated tag mind_poisoning-v0.1.2. Both packages matched a clean tag export; downloaded GitHub assets matched local bytes and the SHA-256 digests above. GitHub reports isDraft=false and isPrerelease=true. No official catalog submission or installation performed.

## Logging debug follow-up — 2026-09-27

No product-code defect reproduced. One composed hook regression was added in tests/runtime_test.php: post-commit release failure must retain committed affinity/history and report confirmed commit plus cleanup failure in the terminal record. Worker PHP lint and runtime fixture both exited 0; lead reviewed assertions, failure injection, call paths and recorded output. Helper isolated reproduction confirmed canonical reserved fields; inherited diagnostic rationale has no current production caller. Store review found no reachable reuse or summary inconsistency. Lead byte-compared all eight server files against dist/logging-check/mind_poisoning.tar.gz: zero differences. No package rebuild or pin change. git diff --check passed. Evidence: tasks/debug-logging-helper.md, tasks/debug-logging-hook.md, tasks/debug-logging-store.md. Native logger delivery, PostgreSQL cleanup and live provider/game behavior remain unverified; protected installation unchanged.

## Local structured logging — 2026-09-27

Unreleased source addition based on published main b88a89f. Three gpt-6-luna/max workers implemented shared safe logging, ACK lifecycle and persistence observability with separate ownership. Lead reviewed every diff and returned defects to the original owners; resolved details are in tasks/todo.md. Protected server/mod remained read-only. Existing player-guide clarification is preserved.

Independent final commands: `php tests/logging_test.php` and `php tests/store_logging_test.php` through WSL DwemerAI4Skyrim3. Both exit 0; the latter includes runtime_test.php. Expected two injected failure log lines preceded `runtime store checks passed` and `store logging checks passed`. Logging records and failure isolation are verified in fixtures only; native PostgreSQL cleanup failures, actual CHIM logging delivery/filtering, live provider and in-game behavior remain unverified.

Lead independently verified archive/source equality using both existing archive verifiers. Verification-only tar: 22,997 bytes, SHA-256 `1d8155f4eb4bb70e337f7c7d2099e626ee9fdda8faa821085f8b095a9fa706cd`. Verification-only DWPkg: 104,105 bytes, SHA-256 `0cdc05f5fd95c6de7a09873a83b3fdac66458610a41a2b066ca5b0345b79d1f7`. Both are under `dist/logging-check/`; all eight payload files are LF-only. Published v0.1.1 local artifacts retain their verified publication hashes. Manifest version, compatibility reference, tag/catalog/deployment pins unchanged. No commit, push, publication or installation performed for logging.

Evidence: tasks/logging-platform.md, tasks/logging-runtime.md, tasks/logging-store.md, tasks/logging-package.md. No unchanged influence/model or broad packaging suite rerun; exact payload verifiers cover the one-file allowlist addition.

## v0.1.1 publication gate — 2026-09-27

User authorized commit, push and publication. Remote main verified at `0a207700dbee57d9dff49ac05c7a3c3b42b42d43`; v0.1.1 tag absent before publication. GitHub account verified as Francisco-boop-001. Fresh isolated influence/runtime fixtures both exited 0; runtime emitted the three expected injected failure logs. Product diff remains the reviewed critique fixes. Release-facing docs were reviewed and frozen, then both archives rebuilt and verified against exact source bytes. Final tar: 16,919 bytes, SHA-256 `0d996e6e4502e80a193dd222f6c9adc040f97c15bb57f3b6ddc0be9769101f40`. Final DWPkg: 75,138 bytes, SHA-256 `76a1503765e776203379ad8a7dad16f4b3241c1600f41afe783b34c263e3ea30`. These supersede the local pre-publication artifact hashes below because release documentation changed. Diff whitespace and doc link/version checks passed. Older records below describe their historical handoff state; publication verification follows. Live DB/provider/game checks and deployment pins remain unchanged.

Published https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.1 from c9b7883e26fdb7f4ae161180a90915c299cbd3e1. Remote main and peeled tag both resolved to that commit immediately after publication. GitHub confirmed isDraft=false and isPrerelease=true, with both assets uploaded. Downloaded both assets to ignored dist/0.1.1/remote-verification; exact byte equality, SHA-256 equality, and both source payload verifiers passed. Release tag remains on the reviewed source commit; subsequent evidence-only commits do not alter payload. Official catalog not submitted; no installation or deployment-pin advancement.

## Historical local candidate 0.1.1 — critique fixes

Source base `0a20770` plus the reviewed local changes. Fixed ACK interaction eligibility while preserving Off and generation guards before/after model evaluation; use exact-ID/actor-bound client-reported speech for subjects/evidence; reject ambiguous Player aliases before paid work for Player subjects; provide bounded relevant same-playthrough prior judgments. Earlier ledger-corruption and Player-name guards remain included. Zero-decision snapshots and synchronous execution remain unchanged.

Lead reviewed all product/test/docs diffs and affected callers. Independent WSL runs of `tests/influence_test.php` and `tests/runtime_test.php` exited 0; the runtime run emitted the three existing injected failure logs. Runtime lint passed; focused failing-before/passing-after evidence is in `tasks/critique-runtime-fixes.md` and `tasks/critique-influence-fixes.md`. Separate hook review found no blocking issue. Markdown local links/fences and manifest/catalog version agreement passed. The catalog v0.1.1 URLs are planned, not published.

After source/doc freeze, the existing builders produced these local candidates. Lead independently verified exact archive contents and source-byte equality with `verify_repository_archive` and `verify_archive`; all seven payload files are LF-only:

| Artifact | Bytes | SHA-256 |
| --- | ---: | --- |
| `dist/0.1.1/mind_poisoning.tar.gz` | 16,764 | `7801d8aa888494b00748de679fb062459cd96f9866a9319a03f28cbfb9029d1c` |
| `dist/0.1.1/mind_poisoning-0.1.1.dwpkg` | 74,563 | `ff095717aa03403f4366e19312074606061c063e3c9bec741fb31f638f1273da` |

No unchanged model/packaging suite or real installer/extraction run was repeated: formats/builders/consumer layout are unchanged. The earlier v0.1.0 local archives retain their published hashes. A worker's initial CR check matched a literal backslash-r instead of a carriage-return byte; the corrected check and final archive/source verification passed. No semantic source change or rebuild resulted from that false alarm.

Version 0.1.1 is not committed, pushed, released, installed or catalog-listed. No deployment pin advanced. Actual client passive-header usage and ACK text origin remain unobserved; the implemented trust boundary deliberately uses CHIM's client-reported speech contract, not independent proof of hearing. Live PostgreSQL writes/concurrency, provider quality/latency and in-game/save-load acceptance remain unverified. See `tasks/critique-client-evidence.md` and `tasks/critique-package-report.md`. Historical evidence below applies to its stated earlier revisions/artifacts.

## Latest local debug fixes — 2026-09-27

Starting source: `2a54ac3`. Two persistence defects were reproduced with the in-memory adapter and fixed locally: malformed present dedupe namespaces now fail closed at preflight/commit, and Player judgments reject a changed nonempty Player name between evaluation and transactional revalidation. First-use initialization, valid other-playthrough reset, and NPC-only judgments remain eligible. Lead reviewed the complete diff/callers and independently ran `tests/runtime_test.php` via WSL PHP: exit 0, `runtime store checks passed`, with the three expected injected failure logs. Worker syntax checks passed for both changed PHP files; diff whitespace check passed. Details: `tasks/debug-runtime.md`.

Subject/model and loader reviews found no justified additional changes; see `tasks/debug-influence.md` and `tasks/debug-bootstrap.md`. Those findings are source inspection, not live integration proof. No unchanged packaging/model suites were repeated.

The user subsequently authorized committing and pushing these reviewed source fixes to GitHub. This publication covers source and regression evidence only: the fixes are not packaged, installed or runtime-promoted. Published v0.1.0 assets and all pins are unchanged and do not include these fixes. All earlier archive/source-identity statements below describe their historical candidate, not the current modified source. Live PostgreSQL writes/concurrency, provider and in-game acceptance remain unverified.

## Published development prerelease

User authorized the confirmed public repository `https://github.com/Francisco-boop-001/CHIM-Plugins`. Source is on `main`; release tag `mind_poisoning-v0.1.0` points to publication commit `0a6f3a8`. The README and repository description identify it as the user's home for CHIM plugins, with Mind Poisoning first.

Release: `https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.0`. GitHub reports `isPrerelease=true`, `isDraft=false`; both assets are uploaded. Anonymous HTTPS requests verified that the tagged catalog manifest exactly matches `server/manifest.json`, and both release downloads exactly match the local artifacts and hashes below. The catalog template now contains the confirmed repository URLs without placeholders.

This closes source/release publication, not official CHIM catalog inclusion or live runtime acceptance. Catalog submission/approval remain pending. Protected installations and deployment pins were unchanged. Earlier sections retain historical preparation/verification state; their pending repository-name statements were resolved by this publication.

## Current repository-distribution candidate

This follow-up adds repository tar packaging and distribution documentation only. Runtime PHP and the plugin manifest remain byte-for-byte unchanged from the reviewed source at `a3b54f9`.

- Lead independently ran the four Python packaging tests: all passed, exit 0.
- Lead independently ran `tests/repository_tar_check.py` in WSL against the actual `dist/mind_poisoning.tar.gz`: GNU tar 1.34, the installed installer's `xvfz` and `--strip-components=1` flags, seven source-identical files, project-local scratch cleaned, exit 0. This proves extraction layout, not remote download, the full PHP installer or plugin runtime execution.
- Lead verified both archives against current source. The tarball is 15,193 bytes, SHA-256 `0fd8eaf99fbb6aa40fae0a3f803dbbef41359b9ed55a8ea8debf7ee24d2ee569`. The rebuilt `.dwpkg` is 67,519 bytes, SHA-256 `e4412e96748ed52f5c9c80e1f38657b029b2d6cde3a78737ef34a349794c8c5f`.
- The revised installed README changes the `.dwpkg` bytes; the earlier hash below is historical, superseded by this local candidate. Prior real schema-4 manager evidence remains scoped to that earlier archive. The schema-4 builder and runtime implementation were not changed.
- The catalog JSON uses a dedicated development-candidate channel and explicit per-plugin tag URLs, with `archive_strip_components=1`. Repository name is still required: `REPOSITORY_NAME` is an intentional, visible placeholder. No GitHub publication, catalog submission, installation or pin advancement occurred.
- Shared-repository limit found during final call-path review: installed `ui/server_plugins.php:368-379` returns the first catalog entry whose `git_repo` OR package name matches. With two entries from the same repository, an earlier repository match can select the wrong plugin. First-entry Mind Poisoning is unaffected; before listing a second plugin from this repository, upstream matching must prefer exact plugin identity. Explicit release URLs do not fix this separate lookup problem. No core patch was made.

Detailed deterministic build/extraction evidence: `tasks/repository-package-report.md`. Catalog/docs evidence: `tasks/repository-docs-report.md`. The remaining live-runtime limitations below still apply.

## Scope

Source and candidate artifacts live only in `K:\ActorwrightExchange\projects\CHIM-MindPoisoning`. Inspected server reference: `cf5030f15781637498be86debe26fcf102f5690d`. The installed WSL server and `F:\EldergleamNext\mods\CHIM Beta` were not modified. No deployment pin is advanced.

## Completed local gates

- Lead reviewed all four runtime modules, their affected installed call paths, the fixture runners, package builder, installer harness and documentation. A separate read-only review checked persistence against the installed source. Fixes returned to the original owners include JSON preservation, namespace writes, missing relationship parents, eventlog `rowid`, lock semantics and current Player-name context.
- PHP 8.2.29 lint passed for four runtime modules and four PHP runners. The final lead run printed `influence checks passed`, `model adapter checks passed`, and `runtime store checks passed`, exit 0.
- Runtime stderr contained the three expected injected failures: rejected history snapshot, ambiguous Player aliases, and invalid model JSON. These exercise failure handling; they were not unexpected errors.
- Python 3.12.4 packaging tests: 3 passed, covering deterministic allowlist output, missing payload rejection, and tamper rejection.
- Installed PostgreSQL catalog was inspected in a session forced to `transaction_read_only=on`. Eventlog uses `rowid bigint`; NPC/history JSON fields are JSONB, timestamps numeric, and master/history column mappings match.
- `tasks/sql-check.sql` uses PREPARE/EXPLAIN only for event lookups, row locking, relationship update, history verification and full-row history insertion. All planned successfully. Literal JSONB evaluation confirmed missing-parent creation preserves an unrelated empty object. The final current-player query was separately planned successfully after its correction.
- Relevant current non-secret configuration was read on 2026-09-27: `RELATIONSHIP_SYSTEM_ENABLED=true`, `RELLLM_CONNECTOR=50`, driver `openrouterjson`, `NEVER_CLEAR_RELATIONSHIP_DATA=false`. Configuration was not changed.

## Evidence boundaries

Store tests use a stateful in-memory adapter; model and CHIM parser helpers are fixtures. The lead separately compared helper contracts with installed source. SQL planning establishes schema/type/syntax compatibility, not executed transactional behavior. No live PostgreSQL mutations, contention/concurrency run, provider request, CHIM endpoint/bootstrap, or in-game test was performed.

Live acceptance still needs an isolated game/server test of acknowledged speech, model quality and latency, concurrent/replayed callbacks, transaction failure handling, and save/load restoration. Some upstream relationship writers ignore the advisory lock and can overwrite a later plugin change. The native-connection shim is coupled to the inspected server wrapper. Connector I/O timeout is not a hard wall-clock deadline.

## Package gate

The lead independently verified `dist/mind_poisoning-0.1.0.dwpkg` against current source with `scripts.package.verify_archive`: exact nine ZIP entries (seven payload files plus package manifest and checksums), valid checksums, and payload bytes equal to source. Size: 67,799 bytes. SHA-256: `4ab1c38bc30bd2c09876f9df47fd66853615eee33b154aade6ced3243d44b94f`.

Two actual builds produced the same hash. The installed real package manager accepted the actual archive under explicitly isolated project-local scratch roots, installed seven byte-identical files, rejected a checksum-tampered archive while preserving prior scratch state, and did not invoke the injected migration runner. Command output and entry hashes are recorded in `tasks/package-report.md`; the lead inspected that final evidence. Scratch installation verifies the package contract, not execution of its runtime hook.

The final audit found and resolved a JSON representation mismatch: equivalent mixed PHP array/object ledger data and its database-like decoded object form compared false. The fixture now roundtrips the ledger through JSON and reproduced a failed commit before the fix. The comparator now treats non-list PHP arrays as JSON objects and preserves list order. The same runtime check passed after the correction; lead independently reran it (exit 0) and reviewed the exact diff. All seven package payload files have zero CR bytes. The local source gate is accepted.

## v0.1.3 publication gate — 2026-09-27

User authorized commit, push and publication. Release includes the L-01 failure classification and read-only poster dashboard with day/night modes. Lead reviewed source/documentation changes and actual day/night desktop, tablet and mobile captures; corrected day contrast. Fresh release checks passed: dashboard_integration_test.php, model_test.php, runtime_test.php (expected injected persistence diagnostics), and four Python package tests. These are isolated fixtures, not live CHIM/database/provider/game proof.

Final-source archives in dist/0.1.3 were built and source-byte verified: mind_poisoning.tar.gz, 3585944 bytes, SHA-256 36064fb33a47dc44c9e0cf163c0f9bedf3563c4ad9206e826b33ec8c3769044c; mind_poisoning-0.1.3.dwpkg, 3814500 bytes, SHA-256 8cbf8fec424e6dcb5ae2b720945ce8ea0d36b262a9a278aac8609917ad9546c5. Tag export and published download comparison remain the publication gates. Compatibility reference unchanged; development prerelease only. User-owned critique.md excluded; protected installations untouched.

Publication verified: source commit 7212d7b24aee80d2c5d09e26b0eb14cafd519197, annotated tag mind_poisoning-v0.1.3. A clean git-archive tag export rebuilt both packages byte-for-byte identically. Pushed main and tag atomically. GitHub release https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.3 reports isDraft=false and isPrerelease=true. Downloaded both published packages and SHA256SUMS.txt; all match local bytes, and GitHub asset digests match the package hashes above. Added CSS LF attributes for reproducible Windows checkouts. Official catalog submission and live deployment were not performed; critique.md remains excluded.

## v0.1.4 release gate — 2026-09-27

User authorized commit, push and publication. Includes required semantic mention decisions with strict response validation, empty-log reporting fix, schema-2 manifest/update flow and associated guidance. Lead reviewed implementation and focused failure paths. Fresh influence/runtime/empty-log checks, four Python packaging tests and installed-helper update-contract checks passed. Actual release tar extracted thirteen source-identical files under CHIM's GNU tar flags in isolated scratch. No live database/provider/game proof. Compatibility/deployment unchanged; critique.md excluded.

Source-verified dist/0.1.4 artifacts: mind_poisoning-0.1.4.dwpkg SHA256 7cd1866518bd5221e01cebfadd1352954003129c41afc397a6d2694249dd54b3; mind_poisoning.tar.gz SHA256 4bf2ea0ad7845f481cc89382876a5177b7844e24dfd95f6af31a35dd22c563ac. Clean-tag rebuild and published-download verification pending; release assets must be published before advancing main's mutable update manifest.
