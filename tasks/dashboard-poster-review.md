# Dashboard poster redesign review

## Result

Rebuilt the plugin dashboard around the supplied distressed Nordic print reference: original whispering-travelers artwork, a selectable “MIND POISONING” heading, printed Interactions/Logs tabs, and explicit day/night appearance links. The existing safe renderer, source labels, filters, and download behavior remain in place. This is a local synthetic preview, not a live CHIM dashboard proof.

## Changes

- `server/dashboard-art.png` — local text-free poster illustration; 3,615,533 bytes.
- `server/dashboard_view.php` and `server/dashboard.css` — reference-led layout, responsive art crop, query-preserving appearance control, and compact ledger presentation.
- `scripts/package.py` — added the same-directory art asset to the plugin allowlist.
- `tests/dashboard_preview.php` — poster, theme preservation, navigation, and escaping fixture coverage.
- `tests/repository_tar_check.py` — changed the extracted-file diagnostic to report the dynamic allowlist count.
- `docs/dashboard.md`, `tasks/todo.md`, and `tasks/lessons.md` — operator guidance, checklist, and reusable visual-correction lesson.

After lead review found insufficient day-mode text contrast, the day semantic colors were darkened. Against the day ground (`#bdb7a7`), the measured text ratios are: muted 5.10:1, slate 5.04:1, rust 4.80:1, moss 5.18:1, and quiet 4.72:1. Night colors retain their reviewed palette.

## Verification

- `php tests/dashboard_preview.php --self-test` — PASS (independent focused run by influence; covers view output, filters, theme links, and escaping).
- `php tests/dashboard_http_test.php` — PASS (runtime owner; controller/theme and local asset route).
- `php tests/dashboard_integration_test.php` — PASS (runtime owner; real modules in an isolated local fixture).
- Browser review used the local preview at `127.0.0.1:18085` with explicitly synthetic records. Tablet filter controls fit without horizontal document overflow. Mobile document width matched the client viewport; the narrower client-width reading on night screenshots is the vertical scrollbar gutter.
- Influence owns source-byte/archive verification after the payload freeze. This report does not claim archive verification.

## Visual evidence

All captures are saved under `tasks/`:

| Capture | View | Evidence |
|---|---|---|
| `dashboard-poster-desktop-night.jpg` | Desktop, night | Full poster and interactions ledger; 1526px CSS viewport. |
| `dashboard-poster-desktop-day.jpg` | Desktop, day | Ledger at scroll position 620px after the contrast correction; 1526px CSS viewport. |
| `dashboard-poster-tablet-700-night-logs.jpg` | Tablet, night, Logs | 700×900 CSS viewport; filters fit and both faces/whispering hand remain visible. |
| `dashboard-poster-mobile-390-day.jpg` | Mobile, day | 390×844 CSS viewport; poster crop, both faces, tabs, source notice, and search entry. |
| `dashboard-poster-mobile-390-night.jpg` | Mobile, night | Same mobile view in night mode. |
| `dashboard-poster-mobile-390-night-logs.jpg` | Mobile, night, Logs detail | Expanded warning record and bounded JSON; values wrap inside the detail panel. |

The browser preview visibly labels its records as synthetic. No CHIM install, live database, provider, game, authentication, or production-log operation was performed. Runtime archive packaging remains with influence; no version, tag, deployment pin, or installed file changed in this UI task.
