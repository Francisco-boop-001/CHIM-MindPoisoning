# Dashboard data integration review

Read-only source review of `server/dashboard_data.php` against the installed CHIM source and existing plugin persistence contract on 2026-09-27. No product files were edited, no installed PHP was run, and no database, endpoint, or provider was accessed. Reader tests/smoke were not run; the data module was still being edited.

## Confirmed integration issue

At the initial review snapshot, `dashboardParseLogLine()` required the timestamp and level groups to touch (`][`). Installed `lib/logger.php:80,95` emits a timestamp ending in a space before the `[level]` group, so a native record is shaped as `[timestamp] [level] {json}` and the parser would discard it. This would hide diagnostics and prevent log correlation for stored ledger entries. I reported it to influence and root. The current shared working tree now permits whitespace and an omitted timestamp at `server/dashboard_data.php:190`, which matches both Logger forms; I did not run the reader or its new fixture, so that correction remains unverified here.

## Source checks

- Active playthrough and Player-name selection match `PostgresStoreDb::activePlaythrough()` in `server/store.php:630-640`. The installed schema source creates `chim_meta.playthrough_profiles` with `id`/`is_active` and later adds metadata `player_name` (`lib/playthrough_autosave.php:27,30`); core switching writes the current `player_name` to `public.core_player` (`lib/playthrough_switching.php:152-153`). Reading `core_player` avoids using potentially stale profile metadata.
- The NPC table and JSON columns match the plugin persistence adapter (`server/store.php:676,691,754-766`) and the existing read-only SQL planning artifact. The reader bounds names, row counts, relationship JSON, ledger JSON, log bytes, and parsed log records. Its explicit `BEGIN ISOLATION LEVEL REPEATABLE READ READ ONLY` precedes its reads and a local statement timeout; no live PostgreSQL behavior was tested.
- The reader requires `postgresql.class.php` only to inspect the guarded `sql` class shape. It does not construct `sql` or call its reconnecting helpers. The class file's included `chim_interaction.php`, `playthrough_runtime.php`, and `logger.php` define helpers/classes; the filesystem lease and optional playthrough guard are reached from the `sql` constructor. The direct reader uses a forced-new PgSql connection, then begins a read-only transaction.
- The parser rebuilds records from an explicit field allowlist. It excludes raw speech, names, prompt text, debug `model_reason`, and arbitrary exception strings. Relationship labels come from database names and must still be HTML-escaped by the renderer.
- The data source reads only plugin-marked structured records from a bounded `chim.log` tail, not eventlog text or the generic debugger endpoint. A readable native log with zero matching plugin rows is a valid empty result; it is not proof of a complete audit trail. The logger's fallback `error_log()` sink may be elsewhere, and log retention/thresholds can omit older records.

## Verification boundary

These are source and schema-contract comparisons only. The query was not executed against PostgreSQL during this review, and the reader's output was not exercised with the real renderer. The previously recorded SQL `EXPLAIN` gate is planning evidence, not runtime or concurrency proof.
