# Pause-control documentation update

The source-checkout docs now describe the pause helper as current unreleased working-tree behavior; published v0.1.8 remains unchanged. Operator instructions use a same-directory temporary file, set PHP-readable permissions, and atomically rename the JSON control. The docs cover the fixed `{"enabled":true}` / `{"enabled":false}` shapes (normal JSON whitespace accepted; duplicate keys and escaped key spellings rejected), the size limit, allowed root/data symlink topology, fail-closed cases and result codes, uncached preflight/post-model checks, the persistence race, and the absence of a dashboard write endpoint. They also record the fixed ACK rejection codes and transaction-local database timeout limits requested for this documentation update.

## Documentation verification

- `git diff --check -- server/README.md server/AGENTS.md docs/development.md docs/mind-poisoning.md` passed.
- Checked local references to `docs/dashboard.md`, `tasks/ack-diagnostics-report.md`, `tasks/store-timeout-report.md`, and `tasks/pause-runtime-report.md`; all exist.
- No code or runtime suites were changed or rerun for this documentation-only pass.

## Runtime evidence and limits

The implementation owner recorded the focused runtime check in [pause-runtime-report.md](pause-runtime-report.md): `tests/runtime_test.php` exits 0 after covering pause preflight, enabled-to-paused and absent-to-paused transitions during model work, malformed controls, invalid roots, allowed `main.php`/`data` symlinks, and secret/path exclusion from logs. PHP lint passed for `server/prerequest.php`, `server/logging.php`, and `tests/runtime_test.php`. The fixture uses temporary CHIM-shaped roots and an in-memory store. It does not verify an installed CHIM service, live database, provider, or Skyrim game. Full CHIM server replacement/restore persistence remains unverified.
