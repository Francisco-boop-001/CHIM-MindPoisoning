# Mind Poisoning 0.1.30 — verification and limits

## Release status — prepared, not published

The source and focused test gates passed for the 0.1.30 candidate. At this record's preparation, there is no 0.1.30 tag or package build, and no public release asset or download checksum has been verified. The 0.1.29 release remains the current published release. After the clean tag and assets exist, build and independently reproduce all four package formats. Verify staged draft downloads and `SHA256SUMS.txt` with authenticated access before publication; after publication, verify anonymous public downloads against the same sizes and SHA-256 values before moving `main` or updating hub pins. A checksum proves downloaded bytes, not CHIM synchronization.

The native and integrated tests below ran against the reviewed source while `server/manifest.json` still reported 0.1.29. The subsequent 0.1.30 manifest edit changes only its version and description. These tests therefore verify the source changes; they do not prove an installed or packaged 0.1.30 build.

## Verified source and test evidence

- **Written evidence and parsing:** `written_evidence_test.php` passed 234 checks; `written_store_test.php` and `intimacy_request_test.php` passed. Coverage includes canonical sender identity without rewriting old edges, fenced JSON parsing, a 65,536-byte raw envelope limit, and the unchanged 2,000-code-point/8,000-byte decoded-body limits.
- **Disposable PostgreSQL fixtures:** `intimacy_child_data_native_test.php` passed 41 checks for history byte caps, optional metadata failure isolation, arrangement refusal with ordinary affinity/type/receipt persistence, and cleanup ownership/retry. `written_identity_native_test.php` passed 14 checks, including a valid JSON-escaped envelope larger than 16 KiB with the exact raw bytes and digest preserved. The native fixtures used synthetic rows, fixed judgments and direct store methods; they made no provider calls.
- **Focused Linux integration:** ten provider-free pure/in-memory suites passed in the disposable Linux environment: `runtime_test.php`, `restore_policy_test.php`, `overhearing_test.php`, `reflection_observer_test.php`, `written_evidence_test.php`, `written_store_test.php`, `intimacy_request_test.php`, `intimacy_store_child_recheck_test.php`, `reflection_reply_test.php`, and `store_logging_test.php`. The POSIX observer/importer fixture passed all six cases: committed delta, type-only commit, zero change, provider failure, unconfirmed commit and warning skip.
- **Dashboard:** eight standalone Windows PHP reader/HTTP/integration fixtures passed, including the Host matrix, field-value search and log-tail boundary cases. `dashboard_recency_test.php` reported its explicit skip because no isolated PostgreSQL socket was configured; dashboard recency was not natively exercised.
- **Overhearing and restore policy:** six focused cases and the full `restore_policy_test.php`, `runtime_test.php` and `overhearing_test.php` suites passed on standalone Windows PHP with synthetic fixtures. The Windows runtime suite skipped its symlink cases because that environment did not allow the required symlink setup.
- **Syntax and patch hygiene:** PHP syntax checks passed for all changed server and test PHP files. The owned source/test diffs passed `git diff --check`.

The focused suites used synthetic HTTP/store seams or in-memory fixtures; only the two native checks above used the authorized disposable PostgreSQL fixture. No live-server Semaphore/manager failure-injection check was performed. The checks did not use an installed CHIM server or real model provider, and they do not establish real-model judgment, live discovery, installation or gameplay. PCV and SHARMAT source were not changed.

## Remaining release and compatibility gates

1. Create and inspect the immutable 0.1.30 source tag; build DWPkg, repository/channel TAR, flat CHIM Plugin TAR and MO2 sync ZIP from clean exports. Run the eight package tests and independently rebuild all four formats from the same tag, comparing each byte-for-byte.
2. Before publication, verify the staged draft downloads and `SHA256SUMS.txt` with authenticated access. Publish the release, then verify anonymous public download sizes and SHA-256 values against that checksum file before moving `main` or updating hub pins. No 0.1.30 asset hash is recorded here because the tag and package artifacts do not yet exist.
3. The Department adapter still pins 0.1.29 and seven hashes. Its owner must review and re-pin 0.1.30 before a deployed Department compatibility claim. This does not block a standalone MP release.
4. Live 0.1.30 discovery, installation, provider behavior, real-model interpretation and gameplay remain unverified.

No API version or function signature changes, maturity promotion, new age policy, provider claim or gameplay feature is included. Mind Poisoning remains a **BETA CANDIDATE**. Standard release classification supports CHIM discovery without promoting maturity.
