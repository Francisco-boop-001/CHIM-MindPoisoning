# Runtime debug report

## Findings

1. A present malformed `plugin_extended_data.mind_poisoning` value was treated as if the namespace were absent. For example, a scalar value was converted to an empty ledger, allowing the next acknowledgment to overwrite it and commit. The store now accepts only an absent namespace as first use; a present namespace must contain a valid playthrough ID, floor, and bounded event list. Preflight and transactional persistence use the same validation. A structurally valid ledger from another playthrough can still start a fresh ledger.
2. Persistence rechecked the active profile ID after model evaluation but did not compare the current Player name. A same-profile rename during the model call could therefore commit a Player judgment using the old name and alias context. Persistence now returns `stale` when a Player subject was evaluated with a nonempty name that no longer matches the active name. NPC-only judgments remain eligible.

## Reproduction and verification

The focused `MemoryStoreDb` fixtures were run before and after the fixes with the configured WSL PHP runtime.

Before the fixes, the malformed-ledger test failed with:

```text
expected: 'invalid'
actual: 'committed'
```

Before the fixes, the same-profile rename fixture kept profile ID `1`, changed the active name from `Dovah` to `Dovah Prime` inside the model callback, and failed with:

```text
expected: 'stale'
actual: 'committed'
```

After the fixes:

```text
$ wsl.exe -d DwemerAI4Skyrim3 --exec php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/runtime_test.php
Mind Poisoning persistence failed: Full listener history snapshot verification failed.
Mind Poisoning persistence failed: Multiple legacy Player relationship aliases are ambiguous.
Mind Poisoning hook failed: Response is not valid JSON.
runtime store checks passed
```

The three log lines are expected rollback/rejection fixtures in the existing suite.

The suite also verifies that absent namespace first use commits, scalar/null/empty-object namespaces fail closed without changing NPC or history state, a valid other-playthrough namespace resets, and an NPC-only judgment can commit after the Player name changes.

Both changed PHP files passed syntax checks:

```text
No syntax errors detected in /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/server/store.php
No syntax errors detected in /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/runtime_test.php
```

## Limits

The Player rename is a fixture callback between preflight and persistence. It demonstrates stale-name revalidation for that sequence; it does not prove real PostgreSQL concurrency or lock behavior. No live database writes, provider requests, installed-server edits, or in-game runs were performed. The fixture run does not replace PostgreSQL transaction/runtime verification.
