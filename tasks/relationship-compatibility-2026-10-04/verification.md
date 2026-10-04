# Relationship compatibility verification — 2026-10-04

## Scope and source

The checks cover empty relationship maps, CHIM-canonical Player aliases, malformed stored maps, model-context consistency, locked revalidation, dashboard reads, and the StoreDb write contract. The canonicalization fixture is the complete upstream `RelationshipManager` source from commit `cf5030f15781637498be86debe26fcf102f5690d`, recorded in `tests/fixtures/README.md`; its fetched bytes have SHA-256 `4497443C9480C7D7FF5D5DEABCEC9AD5AB4CDEC6F28F1FD044F89594D71E06AB`. Tests call its normalization methods directly rather than implementing their weighting rules.

## Red gate

Before product edits, the test owner ran the baseline public persistence fixture in `DwemerAI4Skyrim3-test`:

```text
wsl.exe -d DwemerAI4Skyrim3-test -- /bin/bash --noprofile --norc -c 'cd /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning && php tests/runtime_test.php'
```

The baseline stdout excerpt was:

```text
Mind Poisoning persistence failed at snapshot-verification-failed.
Mind Poisoning persistence failed at player-alias-ambiguous.
Mind Poisoning persistence failed at player-alias-ambiguous.
relationship compatibility cases: {"empty NPC map":"invalid","empty Player map":"invalid","duplicate Player keys":"failed"}
```

The process exited 255 when the following assertion compared those three statuses with the expected `committed` values. The excerpt above is verbatim; the assertion explanation is a summary, not a reconstructed fatal-error line.

## Focused behavioral checks

Commands ran inside the explicitly named test clone with `/bin/bash --noprofile --norc`; each used the repository path shown above. The runtime suite passed with:

```text
Mind Poisoning persistence failed at snapshot-verification-failed.
Mind Poisoning persistence failed at player-relationship-normalization-failed.
relationship compatibility cases: {"empty NPC map":"committed","empty Player map":"committed","duplicate Player keys":"committed"}
runtime store checks passed
test_exit=0
```

The first two lines are expected logs from negative test fixtures. The public ACK cases also prove one provider call each for NPC-only and Player-only utterances with stored `relationships=[]`, object-shaped writes, neutral defaults for new edges, and one history row per commit. The addressed alias case checks one model call, prompt affinity 50, persisted affinity 47, alias removal, and custom-info selection against the pinned helper at the same key order. Existing zero-change and replay cases remain covered.

Additional focused command results:

| Command | Result |
| --- | --- |
| `php tests/runtime_test.php --relationship-helper-unavailable` | Exit 0; `relationship helper unavailable: failed closed before model` |
| `php tests/influence_test.php` | Exit 0; `influence checks passed` |
| `php tests/player_store_test.php` | Exit 0; `player store checks passed`; its PostgreSQL subfixture skipped because no scratch socket was configured |
| `php tests/reflection_test.php` | Exit 0; `reflection checks passed`; includes empty-map commit and malformed Player-edge zero-call rejection |
| `php tests/overhearing_test.php` | Exit 0; `Overhearing ACK tests passed` |
| `php tests/dashboard_data_test.php` | Exit 0; `dashboard_data_test: ok`; independently loads the pinned helper and checks the canonical Player dashboard value |
| `php tests/store_logging_test.php` | Exit 0; `store logging checks passed` |
| `php tests/reflection_observer_test.php` | Exit 0; `reflection observer/importer: 5 cases passed` |

The duplicate-key prompt/save test uses actual affinity/type/custom-info output from the pinned helper. Player-origin NPC gossip verifies canonical listener-to-Player credibility while excluding Player as a subject. Tests also cover strict raw stored-map validation, prompt-copy map conversion, explicit empty Player-name behavior with ambient `PLAYER_NAME`, global restoration, lock-time alias revalidation, invalid locked aliases, and preservation of unrelated relationship and extended-data entries.

Existing `runtime_test.php` checks retain zero-delta snapshot semantics, exact replay dedupe, and rollback after snapshot verification failure (listener and ledger restored; no history row). `reflection_test.php` checks zero-change basis dedupe and replay. `overhearing_test.php` exercises the changed recipient admission path and existing independent-recipient/uncertain-commit handling. These in-memory rollback checks do not substitute for the SQL alias-removal transaction check below.

## Static gates and diff review

Native PHP 8.5.11 `-n -l` passed for all five changed server PHP files and all eight changed test PHP files plus the pinned source fixture (nine test/fixture files, in addition to the five server files). The test owner also linted the existing `overhearing_test.php` entry point used for the recipient-path gate; all ten checked PHP files passed. The test owner independently reviewed the finished server diff against the helper, stored-map, persistence, and adapter contract; no additional product defect was found. Final `git diff --check -- server docs tests` exited 0 after the core-owned SQL fixture edit. The pinned fixture SHA-256 rechecked as `4497443C9480C7D7FF5D5DEABCEC9AD5AB4CDEC6F28F1FD044F89594D71E06AB`.

## Isolated PostgreSQL fixture

The test owner alone entered `DwemerAI4Skyrim3-test`. Immediately before the SQL run, `wsl.exe --list --verbose` showed all distributions stopped. The gaming VHD was `D:/DwemerAI4Skyrim3/ext4.vhdx`, length `180175765504`, `LastWriteTimeUtc=2026-10-04T14:34:55.0918612Z`.

The scratch root was `/tmp/mp-store-timeout-relcompat-20261004-7b3a`, with PostgreSQL data at `/tmp/mp-store-timeout-relcompat-20261004-7b3a/data` and its Unix socket at `/tmp/mp-store-timeout-relcompat-20261004-7b3a/socket`. PostgreSQL 15 was initialized and controlled as the unprivileged `postgres` user. The cluster configuration set `listen_addresses = ''`, socket directory to that exact private path, and port `5432`. Before schema creation, the socket check returned:

```text
/tmp/mp-store-timeout-relcompat-20261004-7b3a/socket:5432 - accepting connections
cluster_settings=true|/tmp/mp-store-timeout-relcompat-20261004-7b3a/data|/tmp/mp-store-timeout-relcompat-20261004-7b3a/socket
```

The exact PHP test command was:

```text
MP_STORE_TIMEOUT_TEST_PG_SOCKET=/tmp/mp-store-timeout-relcompat-20261004-7b3a/socket php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/store_timeout_test.php
```

The PHP fixture output and exit were:

```text
store_timeout_test: timeout bounds, direct writer normalization/removal, commit visibility, rollback, malformed-map refusal, setting restoration, and lock release passed
store_timeout_test_exit=0
```

The SQL assertions exercised `PostgresStoreDb::writeNpc` directly: exact empty NPC and Player maps became JSONB objects; parameterized Hawke removal and Player47 upsert preserved a separate Guard edge and metadata; nonempty-array and null maps did not write; a second connection observed the state only after commit; rollback restored alias, Player edge, ledger and timeline. This is writer/transaction evidence, not a full PostgreSQL `persistJudgments` pipeline run. End-to-end request and history behavior was exercised through the MemoryStore public seams above; SQL rollback left the isolated history table empty.

The cluster run was sent to bash stdin from a PowerShell here-string. PowerShell passed CRLF line endings, so after PHP exited 0 and cleanup had run, Bash reported `/bin/bash: line 63: exit: 0\r: numeric argument required`; the outer runner exited 2. This is not reported as an overall exit 0. The test's own printed exit was 0, and the trap reported `pg_stop_exit=0`, `scratch_remove_exit=0`, and `scratch_cleanup=removed`. The cluster was not rerun. For a future stdin runner, remove carriage returns from the script before piping it to bash.

After cleanup, `wsl.exe --list --verbose` showed all distributions stopped, including `DwemerAI4Skyrim3-test`. The gaming VHD still measured length `180175765504`, `LastWriteTimeUtc=2026-10-04T14:34:55.0918612Z`; the lead independently repeated the metadata comparison and confirmed both values unchanged.

## Limits

These are portable PHP and isolated scratch-PostgreSQL checks. No gaming distro, CHIM bootstrap, Apache/service, external provider, player database, installation, package, publication, or in-game behavior was exercised.
