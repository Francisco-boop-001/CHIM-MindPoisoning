# Transaction-local database wait bounds

`PostgresStoreDb::beginForListener()` now applies limits immediately after its owned `BEGIN`: `lock_timeout` is capped at 1,000 ms and `statement_timeout` at 3,000 ms. Each value is transaction-local. A smaller positive setting is preserved; a zero or looser setting is reduced to the plugin bound. PostgreSQL restores the prior session values on commit or rollback. These bound individual lock waits and statements, not total transaction or request wall time.

## Evidence

- Before the store change, the focused test failed on the zero-timeout case: `Transaction-local timeout settings were incorrect.` This reproduced the missing bounds against the actual `PostgresStoreDb` using an isolated PostgreSQL 15 cluster.
- The final command was `MP_STORE_TIMEOUT_TEST_PG_SOCKET=/tmp/mp-store-timeout-6cc5/socket php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/store_timeout_test.php`; output: `store_timeout_test: timeout bounds, setting restoration, rollback, and lock release passed`.
- The fixture checked disabled, stricter-positive, and looser-positive session values; confirmed commit and rollback restore those session values; observed a long statement canceled near the 3-second bound; and held a listener row lock from a second connection. Through `persistJudgments()`, the contended request failed at revalidation, logged `revalidate-event-failed` with `commit_state=not_attempted`, rolled back and released its advisory lock, and left listener data and history unchanged.
- PHP lint passed for `server/store.php` and `tests/store_timeout_test.php`; `git diff --check` passed for the owned changes. The TCP-disabled `/tmp/mp-store-timeout-6cc5` cluster was stopped and removed.

## Limits

The plugin does not parse PostgreSQL error text to label a failure as a timeout. The current `pg_query()` false path does not expose a structured result SQLSTATE; persistence therefore keeps its existing fixed stage reason. Server-level timeout defaults were not queried. This verifies behavior on a disposable PostgreSQL 15 schema, not the installed CHIM database or live concurrency.
