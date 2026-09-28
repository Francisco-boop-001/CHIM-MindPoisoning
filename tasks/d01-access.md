# D-01 dashboard WSL access

## Result

Kept the dashboard's existing access rule: loopback requests are allowed only without forwarded-address headers; remote requests need a nonempty server-provided `REMOTE_USER`. The observed default gateway is not trusted. The fixed 403 response now tells Windows WSL users to replace the host in their CHIM URL with `localhost`, keep the port and path, then choose **Plugin Page**, or to configure web-server authentication for remote use.

The CHIM plugin page reads the manifest `config_url` at `ui/server_plugins.php:459` and opens it with `window.open()` at lines 505–506. This relative URL stays under the origin used to open CHIM. For the reference configuration, intended post-install URLs are:

- Plugin Manager: `http://localhost:8081/HerikaServer/ui/server_plugins.php`
- Dashboard: `http://localhost:8081/HerikaServer/ext/mind_poisoning/dashboard.php`

Use the configured CHIM port/base path if they differ. These are route instructions, not proof that the installed dashboard was opened successfully.

## Evidence

- Read-only WSL route inspection reported `default via 172.17.224.1 dev eth0 proto kernel`. The controller and HTTP fixture continue to deny this address and other private addresses; route membership alone is not treated as proof of a trusted client.
- A temporary PHP responder bound only to WSL loopback at `127.0.0.1:18963` returned `127.0.0.1` when requested from Windows at both `http://127.0.0.1:18963/` and `http://localhost:18963/`. Its WSL server log also showed loopback clients. The responder was stopped and its temporary file removed.
- `wsl -d DwemerAI4Skyrim3 --exec php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/dashboard_http_test.php` — PASS: `dashboard_http_test: ok`.
- `php -l` under the same WSL distro reported no syntax errors for `server/dashboard.php` and `tests/dashboard_http_test.php`.
- The HTTP fixture denies a public remote with spoofed identity/Host headers, the observed gateway `172.17.224.1`, a private-network address, and a forwarded-header loopback request. Denials return the fixed plain-text guidance, do not redirect or reflect Host, and do not load/call dashboard data. It also confirms the isolated controller allows loopback and a server-authenticated remote.

## Limits

The forwarding test used only the isolated responder; it did not contact the installed Apache/CHIM endpoint, load installed PHP, or query the database/provider. The deployment's actual port, base path, reverse-proxy behavior, and server-authentication configuration remain unverified. A user opening CHIM by its WSL VM address will still receive 403; use its Windows localhost origin for local access. Remote exposure still requires web-server authentication that sets `REMOTE_USER`.
