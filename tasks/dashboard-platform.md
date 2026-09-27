# Dashboard platform feasibility

Read-only source review of installed `DwemerAI4Skyrim3` at `/var/www/html/HerikaServer` on 2026-09-27. No installed PHP, CHIM endpoint, database, provider, or protected mod was executed or changed.

## Route and access

- `ui/server_plugins.php:454,459,506` reads each installed `ext/*/manifest.json` and opens a nonempty `config_url` as its Plugin Page. An installed plugin manifest uses this field for a direct page under `ext/`. A plugin-owned `ext/mind_poisoning/dashboard.php` route therefore needs no core menu edit. The menu page groups in `ui/tmpl/navbar.php:271-318` are static arrays; no dynamic extension-page hook was found. A relative config URL from `ui/server_plugins.php` avoids embedding the installation's web-root prefix.
- The sampled UI/API files use `session_start()` in some paths, but no app-level user authorization check appeared in `server_plugins.php`, `index.php`, `profile_loader.php`, or `api/chim_debugger_logs.php`. Root `.htaccess` only sets permissive CORS headers and request-body settings. Whether Apache or a reverse proxy adds access control is deployment-specific and was not established. Session presence alone is not authentication.
- The dashboard controller allows exact loopback (`127.0.0.1` or `::1`) only when no `Forwarded`, `X-Forwarded-For`, or `X-Real-IP` header is present, or a nonempty server-provided `REMOTE_USER`. Forwarded headers never grant identity; their presence removes the loopback exemption. A proxy that strips all address headers cannot be detected by the plugin, so it must enforce remote authentication and set `REMOTE_USER`. This is an explicit dashboard gate, not a claim about CHIM's built-in login.

## Read-only data path

- Avoid `chimRuntimeBootstrap()` for a dashboard request: `lib/runtime_bootstrap.php:191-219` loads configuration, constructs `sql`, ensures the `plugins` schema (`:136-145`), and defaults `run_db_updates` to true (`:156`). `profile_loader.php:76` also includes `lib/automatic_backup.php`, whose automatic initialization is at `:344-388`.
- Avoid constructing `sql` for read-only dashboard access. `lib/postgresql.class.php:13-36` calls `ptr_runtime_enter()`, can call `pas_guard()` when the playthrough header is present, and executes `SET search_path`; its public query helpers can reconnect (`:41`). No public read-only connection/configuration factory was found. The data reader uses a narrowly version-bound, no-constructor PgSql connection and read-only transaction; it must fail closed on incompatible adapter shape and must not emit the private connection property.
- `ui/api/chim_debugger_logs.php:12,42,80,200+` starts a session and returns a generic CHIM log tail with selectable log types and up to 2,000 lines. It is too broad for this dashboard: the plugin download must be built from bounded, parsed `plugin=mind_poisoning` records only. Do not export the shared log or raw event speech.

## Recommended deployment shape

Use the manifest Plugin Page entry for the server-rendered plugin controller. Keep its JSONL download on the same GET route, with a fixed filename and no file-path parameter. The read-data layer reports database/log availability independently, limits output, reads current affinity separately from committed event history, and leaves unattributed/unconfirmed values labeled as such. Use plugin-owned PHP/CSS only; no core route, schema, service, or remote UI dependency is needed.

## Limits

This is source-level feasibility evidence only. No live read-only PostgreSQL connection, server-rendered page, web-server authentication configuration, or browser session was tested. External Apache/reverse-proxy authorization remains unknown.

## Controller fixture evidence

The plugin-owned controller and an isolated install-shaped HTTP fixture were added after the route/auth/data contracts froze. WSL PHP lint passed for `server/dashboard.php` and `tests/dashboard_http_test.php`; the fixture printed `dashboard_http_test: ok`. It exercises loopback and server `REMOTE_USER` access, denies remote and forwarded-loopback requests before loading data code, checks GET/filter failures and safe headers, and verifies bounded sanitized JSONL output plus unavailable-log suppression. The fixture was written after the controller, so no pre-change failing run was captured; its passing result is post-change verification only. It does not execute the installed CHIM tree or prove live DB, proxy, or browser behavior.
