# CHIM Mind Poisoning

Version 0.1.0 development candidate. Lead review accepted the runtime source, fixture checks, and read-only SQL planning. Live PostgreSQL writes/concurrency, provider behavior, and in-game behavior remain unverified. Compatibility reference: `cf5030f15781637498be86debe26fcf102f5690d`; it is not a deployment pin.

## Behavior and gates

The prerequest hook handles only CHIM client `_speech` acknowledgements matched to the exact emitted utterance and current playthrough. It requires one NPC speaker and one NPC listener and evaluates explicit names of known NPCs or the Player. It does not process aborts, generated text, unmatched or stale events, ambiguous identities, or Player-as-listener events. Events with more than 8 subjects or over 12,000 bytes of speech are skipped.

The model supplies a signed delta from -5 through +5, including zero; resulting affinity stays within -100 and +100. This changes only the listener-to-subject relationship edge. It does not edit Skyrim relationship ranks or publish hearsay as shared world truth.

The hook fails closed when the global relationship feature is off, CHIM interaction is Off, or `NEVER_CLEAR_RELATIONSHIP_DATA` is true. It skips listeners with nonzero `lock_profile` or a manual relationship lock; `relationships_locked` follows CHIM's PHP `!empty` semantics. Only the configured `openrouterjson` or `openrouterjsoncached` connector is supported. The plugin selects no alternate connector, though the configured provider may use its own model fallback. Calls are synchronous and set `HTTP_TIMEOUT=12` and a 1024-token limit; the timeout is not a hard wall-clock deadline.

## State and limits

Persistence uses the guarded `sql::$link` compatibility shim and retains one native connection for the transaction, advisory lock, row lock, relationship update, plugin ledger, and full NPC/history snapshot. Accepted zero decisions also update the ledger and snapshot without changing affinity. Failed writes or snapshot verification roll back the transaction.

The exact-event ledger keeps up to 128 IDs and advances a numeric floor as old entries are evicted. ACKs at or below the floor are skipped, even if unseen or delivered out of order. Core restore with `NEVER_CLEAR_RELATIONSHIP_DATA=true` preserves relationship scores but rewinds plugin ledger state, which is why the hook is disabled under that setting.

Some upstream relationship writers do not take the shared advisory lock and can overwrite an update later. Removing the plugin prevents future evaluations; it does not remove or reverse stored affinity values.

## Package

For an isolated test instance, the server needs the schema-4 plugin package manager and PHP `ZipArchive`. Build with `python scripts/package.py` and upload `dist/mind_poisoning-0.1.0.dwpkg` through that manager. No installation or production deployment was performed. The extension adds no dependencies, migrations, or daemon. Runtime fixture evidence is in `tasks/runtime-report.md`; fixture checks do not prove live PostgreSQL writes/concurrency, provider, or in-game behavior.
