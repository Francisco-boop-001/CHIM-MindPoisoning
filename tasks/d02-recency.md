# D02 ledger recency selection

The database reader now ranks relevant listener rows by the greatest valid retained event ID in that listener's Mind Poisoning ledger for the active playthrough. It expands events only when the namespace is at most 64 KiB and the events value is an array of at most 128 items. Event IDs must be JSON numbers containing positive signed-bigint decimal values, paired with a nonempty string utterance ID. Invalid IDs sort as unknown; they cannot fail a cast or outrank a valid event. Ties use the numeric NPC ID. The SQL query fetches one row beyond the 100-row cap and PHP trims only that bounded result.

The active-profile predicate is applied before ranking. A high event ID from another profile cannot displace current activity. The separate 1,000-row identity catalog also sorts by numeric NPC ID; a recently selected listener beyond that cap keeps the existing `NPC #id` fallback, and a subject whose identity is outside that catalog keeps an unavailable current-affinity value. The source is marked limited. SQL ranking intentionally does not duplicate the full PHP ledger validator: other namespace/event corruption remains reported by the existing invalid-ledger path.

## Verification

`tests/dashboard_recency_test.php` exercised the production `dashboardReadDatabase()` query against a fresh, isolated PostgreSQL 15 cluster containing synthetic tables and rows. The cluster used a private `/tmp/mp-d02-recency-20260927-influence-02/socket` Unix socket, disabled TCP listeners, and was stopped and removed after the check.

```text
PHP lint: server/dashboard_data.php — no syntax errors
PHP lint: tests/dashboard_recency_test.php — no syntax errors
dashboard_recency_test: actual PostgreSQL selection passed (recent row cap, active profile, numeric ties, invalid event IDs, oversized event array, identity cap)
```

The fixture covers 108 active-profile ledgers competing for 100 slots, a newer other-profile decoy, a newest high-ID listener, numeric `2`/`10` ties, oldest-row eviction, the identity cap/fallback, string and out-of-range IDs, a 129-event array, a missing utterance ID, and empty history. The first isolated run failed the identity-cap assertion because `ORDER BY id` referred to the `id::text` output alias and included numeric ID 2000 in the first 1,000. Qualifying the underlying bigint column fixed that observed mismatch; the rerun passed.

This proves the query shape and selection behavior on a disposable PostgreSQL cluster with a synthetic schema. It does not prove compatibility with a live CHIM database, transaction behavior under concurrent writes, dashboard deployment, or game behavior. The fixture can be run against a deliberately created D02 scratch cluster by setting `D02_TEST_PG_SOCKET` to its private socket path; it skips when that variable is absent and refuses sockets/data directories outside the exact D02 `/tmp` namespace.
