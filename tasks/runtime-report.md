# Mind Poisoning runtime report

## Files

- `server/store.php` — acknowledged-event reads, connection guard, bounded dedupe, native transactional relationship/ledger update, and verified full history snapshot.
- `server/prerequest.php` — `_speech` gate, exact event/actor correlation, subject/prompt composition, model dispatch, and persistence handoff.
- `tests/runtime_test.php` — isolated store and composed-hook outcome checks.

## Runtime path

The hook runs only for `_speech`. It respects `chimInteractionAllowed()` before the core's later interaction-Off termination, the relationship connector enable switch, a positive `RELLLM_CONNECTOR` ID, and `NEVER_CLEAR_RELATIONSHIP_DATA` using core boolean parsing. It validates the bounded callback and exact utterance ID, then requires one emitted/spoken `chat` event with matching speaker, exact core utterance, and an explicit non-broadcast target containing the named listener. It resolves unique NPC identities and rejects Player as speaker or listener, profile-locked listeners, and any nonempty manual relationship lock.

The current Player name is read from `core_player.value` for `id='player_name'`; the active profile query supplies the playthrough ID only. If no current Player name exists, subject matching still supports the fixed Player/Dragonborn aliases. Subject context is bounded to eight candidates. The hook checks the bounded ledger before the synchronous model call, sends only prompt copies of the decoded NPC rows, validates every returned judgment, and then delegates to the transactional store. It makes no call for aborts, malformed/unmatched events, broadcasts, duplicates, disabled interaction, or invalid/locked listener state.

The store uses `eventlog.rowid`, the actual eventlog primary key. It rejects missing/unknown delivery states and accepts only `emitted` or `spoken`. Event revalidation holds `FOR SHARE` through commit; listener locking precedes the event lock. All transactional reads, writes, rollback, snapshot insertion, and verification use the retained native PostgreSQL connection to avoid `sql` helper reconnects inside the transaction. A guarded reflection read is limited to the inspected private `sql::$link` property and fails closed for an unsupported connection or non-idle caller transaction. Relationship edges are changed with native `jsonb_set` paths, only the `mind_poisoning` plugin namespace is updated, and the current whole-NPC timestamp is `max(existing gamets_last_updated, source event gamets)`.

History insertion mirrors `NpcMaster::backupNpcById` using columns discovered from the locked current row, replacing `id` with `npc_id`, adding `created`, and setting the relationship source marker. It verifies the inserted history row by ID and native JSONB equality predicates, so object key order does not affect verification. The history and current row changes share one transaction. A zero judgment still records dedupe state and a snapshot without changing the relationships JSON.

## Red before implementation

The first runtime harness run failed because the store module had not yet been created:

```text
wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/runtime_test.php
PHP Warning: require_once(.../server/store.php): Failed to open stream: No such file or directory
PHP Fatal error: Failed opening required .../server/store.php
exit code: 1
```

## Verification

Final syntax checks:

```text
wsl.exe -d DwemerAI4Skyrim3 -- php -l /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/server/store.php
No syntax errors detected in .../server/store.php

wsl.exe -d DwemerAI4Skyrim3 -- php -l /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/server/prerequest.php
No syntax errors detected in .../server/prerequest.php

wsl.exe -d DwemerAI4Skyrim3 -- php -l /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/runtime_test.php
No syntax errors detected in .../tests/runtime_test.php
```

The runtime suite exited 0 after the JSONB object-representation regression was fixed:

```text
wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/runtime_test.php
Mind Poisoning persistence failed: Full listener history snapshot verification failed.
Mind Poisoning persistence failed: Multiple legacy Player relationship aliases are ambiguous.
Mind Poisoning hook failed: Response is not valid JSON.
runtime store checks passed
exit code: 0
```

The three log messages above are expected failure-path fixtures: atomic rollback on injected history verification failure, rejection of ambiguous legacy Player aliases, and rejection of malformed model output. The composed success fixture uses the production store and influence modules with an in-memory `StoreDb` and injected judgment callable. Its write adapter JSON-encodes and decodes the plugin namespace before verification, matching PostgreSQL's JSONB object-shaped decode. It checks exact source correlation, prompt inclusion of listener credibility and listener/speaker affinity context, NPC and Player updates, clamp behavior, history creation, and pre-model dedupe. Additional cases cover interaction Off, disabled relationship processing, invalid connector ID, aborts, broadcasts, manual/profile locks, restore policy, missing/unknown/aborted delivery states, duplicate and stale conditions, event ordering/floor bounds, zero judgments, and write rollback.

## JSONB object representation regression

Before the fix, the strengthened adapter fixture exposed that expected ledger entries remained associative PHP arrays while the simulated database roundtrip decoded JSON objects as `stdClass`:

```text
Mind Poisoning persistence failed: Listener update verification failed.
Fatal error: Valid judgments should commit.
expected: 'committed'
actual: 'failed'
exit code: 1
```

`canonicalJsonValue()` now converts non-list PHP arrays to key-sorted JSON objects, while keeping list arrays ordered. After the fix, the same full runtime suite exited 0 as shown above. `server/prerequest.php` was also normalized to LF; raw-byte check returned `CR_bytes=0`.

The lead separately ran `tasks/sql-check.sql` against a read-only PostgreSQL session with `transaction_read_only=on`. `EXPLAIN` planned the active profile/current-player-name query, rowid-based event reads and lock query, one-edge update, history equality verification, and dynamically mapped history insert. The literal JSONB check confirmed creation of a missing `relationships` parent while preserving an unrelated empty object; the catalog comparison found no unmatched master/history column types. This was read-only SQL planning, with no `ANALYZE` and no executed mutation.

## Limits

- Store tests use an in-memory adapter. The PostgreSQL SQL was planned against the live schema, but the transaction, advisory-lock contention, native connection reflection, snapshot rollback, and concurrency behavior were not executed against PostgreSQL.
- No live provider, HTTP request, CHIM endpoint/bootstrap, installed-server write, or in-game playback test was run. The model callable in composition tests is a fixture; model adapter behavior is covered separately in `tasks/model-report.md`.
- The hook is synchronous in the `_speech` acknowledgement path. `HTTP_TIMEOUT=12` is an I/O timeout, not a guaranteed hard wall-clock deadline or interruption of server work.
- The shared advisory lock coordinates CHIM paths that honor it. Other direct relationship writers that do not take the lock can still race; core was not changed.
- When `NEVER_CLEAR_RELATIONSHIP_DATA` is enabled, processing fails closed because CHIM preserves current relationships but restores historical plugin dedupe state.
