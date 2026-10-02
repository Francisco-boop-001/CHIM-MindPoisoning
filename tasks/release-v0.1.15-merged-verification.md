# v0.1.15 merged-source compatibility verification

## Result

PASS for the three assigned merged-source fixture gates. Each ran once on the original checkout, exited 0, and printed its success marker. No product/test/PCV/metadata edits, Git mutations, live bootstrap, database/provider calls, or installed CHIM access were performed. This report is the only file written by this verification task.

## Snapshot

- Checkout: `K:/ActorwrightExchange/projects/CHIM-MindPoisoning`, branch `work/mind-poisoning`.
- HEAD before and after fixtures: `492579ca1c1ce42e0cbceb23a8ef595f4dde62e9`.
- Merge parents: reviewed work `bf02d1ac332513d407d3646970c04ea9753a9a8d` and newer main `34ad2a1f9d86bfa4b8811510aef4fee77a662a44`.
- Embedded PCV manifest at execution: `0.1.8`.
- Runtime verified with `wsl.exe -d DwemerAI4Skyrim3 -- php --version`: PHP CLI `8.2.29`.
- Existing unrelated dirty/untracked paths were preserved. Another contributor owns release metadata/docs; these fixtures emitted the current pre-release metadata value `0.1.14`. They do not validate a subsequently built v0.1.15 archive.

These SHA256 values matched before and after all three fixture runs:

| Source / fixture | SHA256 |
| --- | --- |
| `server/reflection.php` | `FAD2B3DF33B577F0F611161CE106AAB38BF34191AE7199E7D79ABC76D0EB3726` |
| `server/store.php` | `606CA429A3C1B3839AFEAD660372F93FFCE5B228FA497C00EF1353F069DD5C4C` |
| `server/influence.php` | `F3437577ED098D380D4886CC5A3504A189620A16DEE03335CCD60BA585523B2C` |
| `server/logging.php` | `3F21017254430F694ECA0708A56A68EF0B58A21AE86C6442E2F513DF61886DD7` |
| `plugins/private_conversation/server/log.php` | `76D7BE47ED7104AF391012B5C5DC491C700A95F7626E51A62DC0E29867BB9F7F` |
| `plugins/private_conversation/server/reflection.php` | `A2BC64BE75E377BCA078F45C6C3907D1F39EAB42344FA38F3AAE3ECC9FE5FC17` |
| `plugins/private_conversation/server/manifest.json` | `B167FF7575897F7BBE7E2CA68C69EC71E134023862F0B910C67E2095D378BB5F` |
| `tests/reflection_observer_test.php` | `F32A245FDCCA86158284957C7A5C0B4D1A274E1A9DC096252EE277BDE6803C6F` |
| `tests/reflection_reply_test.php` | `C64668B0CC6D9D56F3BBB230AF1AD93051E516DA61D398ADF50B94C218C55C3A` |
| `tests/runtime_test.php` | `AB68AE915D302D96B679F246961870B137C4DA73834084D73B527FB13495EEA3` |

## Isolation inspected before execution

- `tests/reflection_observer_test.php:26-34` loads fixture definitions and the updated embedded PCV importer, with `PCV_LOG_TESTING=true`; it does not invoke PCV's real registration/ACK route. Its fixture uses `MemoryStoreDb`, a fake model callback, and an isolated importer log directory under `sys_get_temp_dir()` with shutdown cleanup (`:39-66,105-129`).
- The updated PCV test-directory seam requires CLI/testing mode, canonical containment in the temporary root, and a safe directory (`plugins/private_conversation/server/log.php:809-824`). The importer writes only to that isolated test directory in this fixture.
- Shared runtime setup assigns `ENGINE_PATH` to a random temporary fixture root, creates a dummy root marker/control directory, and cleans up on shutdown (`tests/runtime_test.php:595-615`). The reply fixture loads only these definitions before exercising its in-memory seams (`tests/reflection_reply_test.php:4-6`).
- Runtime's existing bootstrap-failure subprocess checks use absent or invalid stub database globals to exercise contained failure, rather than starting a live CHIM bootstrap or making a database connection (`tests/runtime_test.php:1801-1849`; `server/prerequest.php:682-692`; `server/postrequest.php:17-35`). No installed server or MO2 paths were accessed.

## Commands and unsuppressed results

```powershell
wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/reflection_observer_test.php
```

Exit 0:

```text
reflection observer/importer: 5 cases passed (committed delta, zero change, provider failure, unconfirmed commit, warning skip)
```

Concrete merged risk covered: the existing v1 consumer fixture now imports records through embedded PCV 0.1.8 on this merged source snapshot. The fixture asserts correlation, sanitized import, fixed outcomes/severity, confirmed changes, zero decisions, provider failure, uncertain commit, and warning rejection. No PCV code was changed by this task.

```powershell
wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/reflection_reply_test.php
```

Exit 0, success marker `reflection reply checks passed`. Unsuppressed command output contains 40 valid MP schema-1 JSON records: 30 info and 10 warning. Warnings correspond to the fixture's deliberate malformed/mismatched ACK, source/hash, and registration cases. The fixture's captured sinks separately assert caps and uncertain-commit records. All displayed JSON records parsed successfully; no PHP warning, notice, deprecation, or fatal-error diagnostic appeared.

```powershell
wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/runtime_test.php
```

Exit 0, complete unsuppressed output:

```text
Mind Poisoning persistence failed at snapshot-verification-failed.
Mind Poisoning persistence failed at player-alias-ambiguous.
runtime store checks passed
```

The two messages are intentional exercised failure paths:

- `tests/runtime_test.php:974-979` sets `failSnapshot=true`, expects failed persistence, and asserts relationship/ledger rollback and empty history.
- `tests/runtime_test.php:1019-1024` creates both `Dragonborn` and `Player` relationship keys, expects failed persistence rather than guessing, and asserts no mutation. This is the legacy relationship-key ambiguity case, not the new subject-catalog race case.

No command redirected or suppressed stderr. The tool returned each command's stdout/stderr together, and the complete output was inspected and scanned for PHP diagnostics. The observer harness itself captures its five subprocess stderr streams and surfaces them on failure (`tests/reflection_observer_test.php:152-167`); this run does not independently expose successful child stderr. No fixture was modified to change that existing harness behavior.

## Limits

These are isolated in-memory fixture results on the named merged snapshot. They establish compatibility and replay/persistence validation behavior for those fixtures. They do not certify live PostgreSQL durability/concurrency, provider judgment or transport, installed extension ordering, companion v2 grouping/subtitle correspondence, audible playback, or the final archive/release assets. Source hashes and HEAD were unchanged during these checks. Publication/packaging and metadata updates remain the release owner's work.
