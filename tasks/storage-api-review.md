# CHIM storage API exception review

Reviewed 2026-09-27. This is a source review only; no installed code, database, or provider was executed.

## Verdict

Keep the narrowly guarded native-connection adapter in `server/store.php` for this compatibility target. The inspected public CHIM APIs do not expose a transaction/connection-pinning facility that can atomically combine the affinity update, plugin ledger, and full NPC history snapshot. Replacing the adapter with `NpcMaster::setPluginData()` plus the existing SQL wrappers would silently weaken that transaction guarantee. The adapter is an explicit private-API compatibility exception, not a supported CHIM transaction API; retain it only while its exact guards and compatibility reference remain valid.

## Source identity and confirmed behavior

- The installed checkout is `/var/www/html/HerikaServer`, revision `cf5030f15781637498be86debe26fcf102f5690d`. The public `dev` ref resolved to the same commit, and `server/manifest.json` pins that same `server_compatibility_reference`. An earlier unqualified lookup reported the plugin checkout's `6dfd009` revision; that result was discarded and is not the installed CHIM revision.
- The pinned [plugin NPC data contract](https://raw.githubusercontent.com/Dwemer-Dynamics/HerikaServer/cf5030f15781637498be86debe26fcf102f5690d/docs/plugin-npc-data.md) documents `NpcMaster::getPluginData`, `setPluginData`, and `deletePluginData` as namespace-scoped APIs. `setPluginData` replaces that plugin's namespace, preserves other namespaces, and does not create a history row or change game timestamps. NPC plugin data travels with normal NPC snapshots/restore and retention. The source implementation is in `lib/core/npc_master.class.php:418-465` at the same revision; `setPluginData` is one `UPDATE ... RETURNING` through `$this->db->fetchOne()`.
- In `lib/postgresql.class.php`, `re_connect()` is private at line 41. Public `query`, `fetchOne`, `fetchAll`, `execQuery`, and insert helpers check the connection before their operation (`query`: 217; `fetchAll`: 376; `fetchOne`: 418; `insert`: 136; `insertReturningId`: 173). A healthy connection is reused; a missing or unhealthy one may be transparently replaced. The limitation is that callers cannot pin or verify connection identity across a sequence of wrapper calls, so a reconnect during that sequence can move a later statement outside an outer native transaction. On reconnect failure the helper logs and calls `die()`, which does not provide normal exception/finally cleanup. The inspected public method inventory contains no begin/commit/rollback or transaction-scoped connection methods. `insert()` has no success result; `insertReturningId()` returns an ID but does not add transaction-scoped connection ownership.
- `NpcMaster::backupNpcById()` is at `lib/core/npc_master.class.php:1437-1464`; it reads the current NPC and then calls the wrapper insert helper to make a history row. It does not offer a caller-supplied connection or compose with the plugin namespace update in one transaction.

These are confirmed source/API findings at the pinned commit. The docs describe API intent; the method bodies determine the connection reuse/reconnect and return behavior above.

## Plugin transaction boundary

`server/store.php` captures `sql::$link` through a narrowly scoped `ReflectionProperty` adapter (`captureConnection` and `assertConnectionIdentity`, lines 934-964), validates the `PgSql\Connection`, and checks that CHIM still exposes the identical connection around native statements. `beginForListener` uses a nonblocking advisory try-lock, then starts `BEGIN` on that retained connection (lines 708-730). Persistence reads/revalidates under the transaction, writes relationship edges plus the owned `plugin_extended_data.mind_poisoning` namespace and timeline in one native `UPDATE` (lines 733-761), dynamically snapshots the full row into history and verifies the written JSONB/timeline (lines 763-813), then commits or rolls back on that same connection (lines 815-845). The wrapper helpers are deliberately not used for those transactional mutations because their reconnect behavior could move a later statement onto a different connection.

This is the smallest mechanism found in the current API for the plugin's atomicity contract. It is version-bound: a CHIM rename, visibility/type change, or connection lifecycle change can invalidate the private `sql::$link` assumption. The adapter checks shape, connection identity, and transaction state and fails closed when those checks fail; it does not turn the private property into a supported public API. A future CHIM API that accepts a connection-scoped transaction and can update plugin data and create/verify history would remove this exception.

The dashboard's independent reader has a second, read-only compatibility seam: `server/dashboard_data.php:340-379` reflects the private nonstatic `sql::$connString` default to open a forced-new connection, then starts `REPEATABLE READ READ ONLY` with a local statement timeout and closes it in `finally` (`492-496`). It avoids constructing `sql` or invoking its reconnecting query methods, but remains coupled to that private property and class file layout.

## Limits and risks

- The adapter's advisory lock coordinates only writers that honor the same lock key. The listener row lock protects the row while held, but this is not a claim of global serializability for all CHIM or external writers.
- A connection loss or error around `COMMIT` can leave the final outcome uncertain; the implementation records commit state as unconfirmed rather than claiming rollback. Native `ROLLBACK` is attempted only on the retained healthy connection.
- The dynamic history insert is based on the current `core_npc_master` row columns and mirrors the inspected `backupNpcById` transformation. Future history/schema or helper semantics changes still require compatibility review.
- Source inspection and fixture/SQL-planning evidence do not prove live PostgreSQL transaction, disconnect, or concurrency behavior. This review performed no DB writes, provider calls, or installed-code execution.
