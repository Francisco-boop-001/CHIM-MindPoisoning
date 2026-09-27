# Candidate verification

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
