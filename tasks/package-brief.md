# Task 3 — package and verification tooling

## Goal and ownership

Build a deterministic schema-4 `.dwpkg` from this new server plugin. You own `scripts/package.py`, `tests/test_package.py`, `tests/package_manager_check.php`, `server/manifest.json`, `server/README.md`, `server/AGENTS.md`, root `README.md`, `.gitignore`, and `tasks/package-report.md`. No runtime PHP ownership. Other agents share the repository; preserve their edits. No subagents. Use Ponytail full and targeted red/green checks. Lead owns planning, review and final commits; do not commit/stage other files.

## Fixed contract

- Project: `K:\ActorwrightExchange\projects\CHIM-MindPoisoning` (WSL `/mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning`).
- Plugin `name`: `mind_poisoning`; candidate version: `0.1.0`.
- Target reference: HerikaServer `cf5030f15781637498be86debe26fcf102f5690d`, inspected with PHP 8.2.29; no live deployment or pin advancement.
- Outer schema4 manifest, root checksums.sha256, exact server payload. Read-only source of truth: `/var/www/html/HerikaServer/lib/plugin_package_manager.php` and its focused test fixture.
- Planned runtime payload is `prerequest.php`, `influence.php`, `store.php`, optionally `model.php` if needed after runtime decisions, plus `manifest.json`, `README.md`, `AGENTS.md`. Explicit file allowlist; do not ship tests, tasks, credentials, live config, logs, reference copies, caches, or VCS files. Confirm final names with lead before sealing final archive.
- No migrations, dependencies, install commands or writes to F: / installed WSL tree. Package output stays in project `dist/`.
- One stdlib Python script builds deterministic ZIP-format `.dwpkg` and verifies member list, byte hashes and manifests; no custom framework. Derive outer version/name from inner manifest to avoid two manually maintained versions.
- Use the actual installed package-manager class in an isolated PHP harness with explicit workspace-only server/state/upload paths and injected migration runner that fails if called. Inspect source/test fixture first; no default constructor roots or live HTTP endpoints. No full server unit suite.
- Docs explain plugin is under development until runtime integration accepted. Final behavior will assess client `_speech` acknowledgements, known named subjects, directed listener affinity, no native Skyrim relationship-rank writes. Update details when lead supplies finalized contract; do not invent runtime guarantees.

## Acceptance

- Deterministic archive across repeated builds; valid schema4 upload/install using real manager in scratch roots; source byte equality after install; checksum-tampered archive rejected; preserve prior installed scratch payload on rejection.
- Test missing payload and accidental extra files exclusion. Minimal targeted checks, no dependency installation or broad test reruns.
- Real final archive built only after runtime acceptance. Until then validate script with small fixtures; do not mislabel fixture package as deliverable.
- Report exact commands, complete result summaries, source hashes used, limitations and own file list in `tasks/package-report.md`.
