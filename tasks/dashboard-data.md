# Dashboard data layer

The reader returns bounded, sanitized plugin records and active-profile relationship context. It does not retain new history or return speech, evidence, or model rationale. When database verification is unavailable, log-derived rows are labeled unverified; a missing edge is distinct from an observed numeric value.

## Read limits

- Reads at most the final 2 MiB of `log/chim.log`, drops a partial first or unfinished last line, and keeps at most 1,000 valid plugin records. CHIM logger writes are best-effort, so fallback or suppressed records may be absent.
- Retains at most 1,000 NPC identity rows and 100 relevant ledger rows; each query fetches one extra row as a truncation sentinel. Per returned row, relationship JSON is limited to 16 KiB and the Mind Poisoning namespace to 64 KiB. Oversized data is omitted and the model reports a limit notice.
- Renders at most 200 recent interactions. Candidate pruning and all source caps set the limited indicator instead of presenting the result as complete.
- Database connection uses a forced connection with a 5-second connect timeout, a repeatable-read read-only transaction, and a 3-second statement timeout. It queries only the active-profile identifier/player name, bounded NPC identities, relationships, and the plugin-owned `mind_poisoning` namespace. Cleanup rolls back the read-only transaction and closes the connection.

## Verification and limits

`tests/dashboard_data_test.php` passed under the configured WSL PHP runtime:

```text
dashboard_data_test: ok
```

The fixture covers the RequestLog JSON inside CHIM's source-confirmed timestamp/level envelope, per-field privacy filtering, outcome normalization, missing/invalid current values, active-playthrough and request isolation, identity ambiguity, no-active-profile attribution, and interaction truncation.

The native PostgreSQL connection/query path was not opened or executed. Schema and logger envelope compatibility were checked against installed source only; this report is not live PostgreSQL, dashboard deployment, CHIM runtime, or game proof.
