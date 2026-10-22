# Mind Poisoning 0.1.30 — verification and limits

## Published release

Mind Poisoning 0.1.30 was published as standard, non-prerelease GitHub release [421044454](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/tag/mind_poisoning-v0.1.30), from immutable tag `mind_poisoning-v0.1.30` at source commit `3ce96b5640796db948dae33444dd403ea4ed74a6`. Eight package tests passed. Two clean-tag exports were independently rebuilt; all four installer formats matched byte-for-byte. Authenticated draft-download verification and anonymous public-download verification each passed for all five release assets. Public sizes and SHA-256 values:

| Asset | Bytes | SHA-256 |
| --- | ---: | --- |
| [Recommended MO2 ZIP](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.30/mind_poisoning-0.1.30-mo2.zip) | 685,272 | `0d7470b74e21febab040d07883d88398adae812f5dc1b3f386d790f90d99e87d` |
| [CHIM DWPkg](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.30/mind_poisoning-0.1.30.dwpkg) | 1,229,301 | `8e028fbca8c698bb3b92aca88fc7aeb029326224e4ad78ebad24f50a550a556e` |
| [Server TAR](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.30/mind_poisoning.tar.gz) | 681,791 | `469a156d371e42f16f1f9c6ed04f02f8c9c84b97ad6ea95b2b1af14084451e0f` |
| [Flat CHIM discovery TAR](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.30/chim-plugin.tar.gz) | 681,719 | `68dd14787f52b32d80b54e972047c06dea86f555c0bea60d742eaaf53e745b34` |
| [SHA256SUMS.txt](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.30/SHA256SUMS.txt) | 363 | `790d6e62bd5e29124b779dbfeadcca35690ac0e4bed649a42c02d56aad5de636` |

The draft and public checks matched the same sizes and hashes. A checksum verifies downloaded bytes, not CHIM synchronization.

All 34 tested source files match the tag. The test run used manifest version 0.1.29; the release-only manifest version/description and operator README copy were excluded from runtime-source binding. The tests verify the source changes, while the clean-tag build and independent rebuild verify package reproducibility; neither establishes an installed CHIM runtime or gameplay result.

## Verified source and test evidence

- **Written evidence and parsing:** `written_evidence_test.php` passed 234 checks; `written_store_test.php` and `intimacy_request_test.php` passed. Coverage includes canonical sender identity without rewriting old edges, fenced JSON parsing, a 65,536-byte raw envelope limit, and the unchanged 2,000-code-point/8,000-byte decoded-body limits.
- **Disposable PostgreSQL fixtures:** `intimacy_child_data_native_test.php` passed 41 checks for history byte caps, optional metadata failure isolation, arrangement refusal with ordinary affinity/type/receipt persistence, and cleanup ownership/retry. `written_identity_native_test.php` passed 14 checks, including a valid JSON-escaped envelope larger than 16 KiB with the exact raw bytes and digest preserved. The native fixtures used synthetic rows, fixed judgments and direct store methods; they made no provider calls.
- **Focused Linux integration:** ten provider-free pure/in-memory suites passed in the disposable Linux environment: `runtime_test.php`, `restore_policy_test.php`, `overhearing_test.php`, `reflection_observer_test.php`, `written_evidence_test.php`, `written_store_test.php`, `intimacy_request_test.php`, `intimacy_store_child_recheck_test.php`, `reflection_reply_test.php`, and `store_logging_test.php`. The POSIX observer/importer fixture passed all six cases: committed delta, type-only commit, zero change, provider failure, unconfirmed commit and warning skip.
- **Dashboard:** eight standalone Windows PHP reader/HTTP/integration fixtures passed, including the Host matrix, field-value search and log-tail boundary cases. `dashboard_recency_test.php` reported its explicit skip because no isolated PostgreSQL socket was configured; dashboard recency was not natively exercised.
- **Overhearing and restore policy:** six focused cases and the full `restore_policy_test.php`, `runtime_test.php` and `overhearing_test.php` suites passed on standalone Windows PHP with synthetic fixtures. The Windows runtime suite skipped its symlink cases because that environment did not allow the required symlink setup.
- **Syntax and patch hygiene:** PHP syntax checks passed for all changed server and test PHP files. The owned source/test diffs passed `git diff --check`.

The focused suites used synthetic HTTP/store seams or in-memory fixtures; only the two native checks above used the authorized disposable PostgreSQL fixture. No live-server Semaphore/manager failure-injection check was performed. The checks did not use an installed CHIM server or real model provider, and they do not establish real-model judgment, live discovery, installation or gameplay. PCV and SHARMAT source were not changed.

## Compatibility and runtime limits

The Department adapter calls the MP reader functions directly, whose signatures remain unchanged. Its owner completed a read-only review, found no source break, and confirmed the seven supplied clean-source hashes match the 0.1.30 sources. The current 0.1.29 adapter correctly refuses 0.1.30. A future reviewed server-pin update appears sufficient; no DLL rebuild is indicated. This is source-level compatibility evidence, not proof that a reviewed pin is deployed to a live Department instance.

No live CHIM install, provider call, real-model evaluation or gameplay session was performed. No live-server Semaphore/manager failure-injection check was performed. Live discovery and installed behavior therefore remain unverified. The checks do not establish real-model judgment or Skyrim gameplay. PCV and SHARMAT source were not changed.

No API version or function signature changes, maturity promotion, new age policy, provider claim or gameplay feature is included. Mind Poisoning remains a **BETA CANDIDATE**. Standard release classification supports CHIM discovery without promoting maturity.
