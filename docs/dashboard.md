# Mind Poisoning dashboard

The read-only Interactions/Logs dashboard is included in the v0.1.3 development-candidate prerelease. It shows retained plugin records and query-time relationship state; it is not a live event monitor or audit guarantee. Live web-server authentication and PostgreSQL behavior remain unverified.

## Page and access

The packaged endpoint path is `ext/mind_poisoning/dashboard.php`; its stylesheet and text-free poster illustration (`dashboard.css` and `dashboard-art.png`) are in the same directory. The title is selectable HTML text. The manifest `config_url` points to `../ext/mind_poisoning/dashboard.php` from CHIM's server-plugin UI.

Choose Day or Night from the labeled controls above the poster. The mode is stored in the `theme=day|night` query parameter, so ordinary tab links, filter submissions, and the log download retain it without JavaScript or browser storage. Day is the default.

The controller permits a request when the web server supplies a nonempty `REMOTE_USER`, or when the client address is loopback (`127.0.0.1` or `::1`) and the request has no `Forwarded`, `X-Forwarded-For`, or `X-Real-IP` header. It does not add a login. A reverse proxy that reaches PHP over loopback but strips those headers can appear to be a local request; deployments with that topology must require web-server authentication on the route. Remote access must not expose the endpoint anonymously. The remote authentication wiring has not been verified in a live CHIM deployment.

The page accepts GET requests only. Interactions and Logs are ordinary links with bounded GET filters. The Logs download returns filtered plugin records as JSONL; it does not return the shared CHIM log. Removing the plugin stops future changes to affinity but does not undo affinity or history already stored by CHIM.

## What it shows

The dashboard separates a model proposal, a commit-confirmed applied delta, the historical before/after values, and the current affinity read when the dashboard query runs. An unconfirmed commit is labeled **Unconfirmed**; it is not shown as no change. An unset relationship explicitly defined as default-zero is shown as `0 (default)`. Ambiguous, invalid, and unavailable values remain distinct.

When the database source is unavailable, retained log records can still appear as unverified or unattributed history; current affinity is unavailable. The page does not infer active-profile attribution from those records. Its diagnostic details show sanitized allowlisted plugin fields, not raw speech, prompts, credentials, or arbitrary exception/provider text.

## Read limits

- Log input is limited to the last 2 MiB and 1,000 parsed plugin records. The UI may report that older records are missing. The CHIM log itself can be truncated by CHIM; this view does not provide an archival audit trail.
- Database reads use a separate read-only transaction. The reader uses the guarded `sql::$connString` compatibility seam after confirming the expected CHIM class file and private property; it does not instantiate `sql` or run the CHIM bootstrap. If a future core version changes that shape, database data is shown as unavailable. The reader caps the NPC catalog at 1,000 rows and reads at most 100 NPC rows; each stored ledger is capped at 64 KiB and each relationships JSON value at 16 KiB. The interaction list is capped at 200 entries.
- The current affinity value is a query-time read. It can differ from the value immediately after a historical event.
- Local fixtures and read-only SQL-planning checks do not prove live PostgreSQL behavior, remote authentication, provider calls, client ACK behavior, or in-game behavior.

## Local preview

Run the view against an explicitly labeled synthetic fixture from the repository root:

```sh
php -S 127.0.0.1:8080 -t server tests/dashboard_preview.php
```

Open `http://127.0.0.1:8080/dashboard.php`. Append `?theme=night` for Night mode. Fixture-only states are available with `?preview=empty`, `?preview=database-unavailable`, and `?preview=logs-unavailable`; no CHIM bootstrap, database, or provider is used. Focused checks are `php tests/dashboard_preview.php --self-test`, `php tests/dashboard_data_test.php`, `php tests/dashboard_http_test.php`, and `php tests/dashboard_integration_test.php`. These use synthetic or isolated temporary fixtures only; none contacts a live CHIM service.
