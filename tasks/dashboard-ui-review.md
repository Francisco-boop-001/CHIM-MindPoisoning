# Dashboard UI review and package evidence

## Local visual and browser review

The preview at `http://127.0.0.1:18085/dashboard.php` uses synthetic fixture data. It is not a live CHIM dashboard.

- Desktop Interactions and Diagnostics were reviewed at a 1440×900 viewport: [interactions](dashboard-ui-desktop-interactions.jpg), [diagnostics](dashboard-ui-desktop-diagnostics.jpg).
- Mobile pages were reviewed at a 390×844 viewport: [interactions](dashboard-ui-mobile-interactions.jpg), [diagnostics](dashboard-ui-mobile-diagnostics.jpg), [viewport and gutter](dashboard-ui-mobile-viewport.jpg). Measured `innerWidth=390`, `clientWidth=375`, and document/body `scrollWidth=375`; the dashboard shell ended at x=361.2, leaving about 14 px of right gutter with no horizontal overflow.
- The [empty](dashboard-ui-empty.jpg), [database unavailable](dashboard-ui-database-unavailable.jpg), and [logs unavailable](dashboard-ui-logs-unavailable.jpg) states were reviewed.
- Browser checks used the synthetic fixture: submitting `q=0123456789abcdef01234567`, outcome `committed`, and level `info` returned one diagnostic row; tab navigation preserved active filters; clearing filters returned to the unfiltered interactions tab. The filtered JSONL download was exercised once and contained fixture data only. The first keyboard Tab focused the skip link.

## Source/package evidence

- `php tests/dashboard_preview.php --self-test`, `php -l server/dashboard_view.php`, and `php -l tests/dashboard_preview.php` passed during the UI work. Lead's final dashboard data, integration, HTTP, and package gates passed; `python -m unittest discover -s tests -p 'test_package.py' -v` reported four passing tests.
- The lead built verification-only artifacts from the current source and verified their payload bytes: `dist/dashboard-check/mind_poisoning.tar.gz` (41,008 bytes; SHA-256 `e4bdfc5b896d587c87999dbc31b263ed3b927e65afbfccbd3df44ac7c0aea015`) and `dist/dashboard-check/mind_poisoning-0.1.2.dwpkg` (193,743 bytes; SHA-256 `b2c435138be85f4862f571c11a6539c612a78721b1dd94f205b566ee0f70d2c9`). Both archive verifiers accepted the exact source payload, including all four dashboard files.
- Published v0.1.2 assets remain the earlier eight-file payload. The current allowlist has twelve files, but the dashboard is unreleased; `dist/dashboard-check` artifacts are verification-only and are not publication assets. Manifest version and release/catalog pins remain unchanged.

## Limits

Preview and fixture checks do not prove remote authentication, live PostgreSQL behavior, provider calls, client ACK delivery, or in-game behavior. The database reader fails closed if the expected CHIM `sql::$connString` compatibility shape changes. A reverse proxy that strips forwarding headers can make a proxied request appear local, so that topology requires web-server authentication at the route.
