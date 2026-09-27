# CHIM Mind Poisoning

Version 0.1.0 development candidate for HerikaServer. Compatibility reference: `cf5030f15781637498be86debe26fcf102f5690d`; this is not a deployment pin.

## Status

Lead review accepted the runtime source, fixture checks, and read-only SQL planning. This remains a development candidate: live PostgreSQL writes/concurrency, provider behavior, and in-game behavior are not verified. Use it only in an isolated test environment; do not deploy or promote a server pin.

## Behavior

The hook processes only an exact CHIM client `_speech` acknowledgement matched to its emitted utterance ID, speaker, listener, text, event ID, and active playthrough. It changes only the listener's affinity toward explicitly named known NPC or Player subjects. Player cannot be the listener. The model returns a delta from -5 through +5, including zero; total affinity is clamped to -100 through +100. No native Skyrim relationship rank is written and hearsay is not added to shared world knowledge.

Events with more than 8 subjects or speech over 12,000 bytes are skipped. Dedupe keeps at most 128 event IDs and advances a numeric floor as entries are evicted. ACKs at or below that floor are skipped, including previously unseen out-of-order events below it.

A listener with nonzero `lock_profile` or a manual relationship lock is skipped; `relationships_locked` follows CHIM's PHP `!empty` semantics.

Processing is disabled when the global relationship feature is off, CHIM interaction is Off, or `NEVER_CLEAR_RELATIONSHIP_DATA=true`. The latter setting preserves affinity scores but rewinds plugin ledger state during core restore. Only the configured `openrouterjson` or `openrouterjsoncached` connector is accepted; this plugin does not select another connector. A configured provider's own model fallback may still apply. Calls are synchronous; `HTTP_TIMEOUT=12` is an I/O timeout, not a hard wall-clock limit.

Persistence uses the existing listener advisory lock and a transaction with a row lock. A guarded `sql::$link` compatibility shim obtains the connection; native statements retain that connection because the core helper may reconnect and autocommit. The affinity change, exact-event ledger, and full NPC/history snapshot are written together, including zero decisions. A write or verified-snapshot failure rolls back the transaction. Upstream relationship writers that do not coordinate on the lock can still overwrite a plugin change later.

## Package and prerequisites

The server needs the schema-4 plugin package manager and PHP `ZipArchive`. Runtime use also requires the relationship feature enabled, interaction not Off, `NEVER_CLEAR_RELATIONSHIP_DATA=false`, and one supported configured connector. The plugin adds no dependencies, migrations, or daemon.

For an isolated test instance, build the package from this project with `python scripts/package.py`, then upload `dist/mind_poisoning-0.1.0.dwpkg` through the server's schema-4 plugin package manager. The server needs PHP `ZipArchive`; runtime use also needs the supported configured connector and feature settings described above. No installation was performed. Removing the plugin stops future evaluations but does not remove or reverse affinity values already stored in CHIM.

## Checks

Packaging fixture checks:

```powershell
python -m unittest discover -s tests -p 'test_package.py' -v
```

Pure influence check:

```powershell
wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/influence_test.php
```

Model adapter check, including syntax checks:

```powershell
wsl.exe -d DwemerAI4Skyrim3 -- bash -lc "set -e; php -l /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/server/model.php; php -l /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/model_test.php; php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/model_test.php"
```

Runtime fixture check:

```powershell
wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/runtime_test.php
```

It prints `runtime store checks passed`; expected failure-path fixture logs and the full command evidence are in [`tasks/runtime-report.md`](tasks/runtime-report.md). All fixtures are local and do not prove live database transactions/concurrency, provider behavior, or in-game behavior. Archive-manager verification and exact archive hashes are recorded in [`tasks/package-report.md`](tasks/package-report.md).
