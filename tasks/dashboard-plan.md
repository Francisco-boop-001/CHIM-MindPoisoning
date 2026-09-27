# Plugin dashboard implementation plan

Goal: deliver an attractive read-only CHIM plugin journal with Interactions and Diagnostics tabs, safe plugin-log downloads, and accurate proposed/applied/current affinity distinctions.

User approved implementation and chose local access plus web-server-authenticated remote access. Design uses warm paper, ink, spruce and brass; semantic HTML/native forms/details, no remote assets/framework/marketing decoration. Preserve existing L-01 work. No live installation/database/provider execution, migrations, release/pin changes or critique edits.

## Architecture and ownership

- runtime: server/dashboard.php controller and tests/dashboard_http_test.php; manifest config_url discovery evidence. Gate GET by actual loopback REMOTE_ADDR or server REMOTE_USER, ignoring forwarded identity. Auth precedes data access. Security/no-store headers, SSR and bounded sanitized JSONL download. No core bootstrap.
- influence: server/dashboard_data.php and tests/dashboard_data_test.php. dashboardFilters(query) validates bounded GET filters. dashboardLoad(serverRoot,filters) reads bounded plugin log tail and active CHIM ledger/current affinities with read-only SQL; no schema setup. Returns explicit missing/limited source notices. No permanent history store.
- packaging: server/dashboard_view.php and dashboard.css, tests/dashboard_preview.php, visual report. renderDashboard(model,filters) escapes all output and renders native navigation/filters/details. Test-only fixture never ships. Later owns allowlist/docs integration when file set freezes.
- Lead: contracts, review, evidence only; does not write product code.

## Frozen view contract

Namespace ChimMindPoisoning. Model keys: version, generated_at, notices (string list), source {logs,database,limited}, interactions, records. Interaction keys: request_id,event_id,utterance_id,playthrough_id,timestamp,speaker,listener,outcome,reason,model_ms,persistence_ms,changes. Changes: subject,label,proposed,before,after,applied,current,current_state. Missing numerical values are null. Current is page-query-time value, not historical result. Proposed is never confused with net clamped delta. Confirmed commits only provide applied before/after; uncertain outcomes stay uncertain. Ledger zero proposal can establish zero applied but not historical before/after. Match log evidence by playthrough,event,utterance,listener. No full dialogue/raw provider text in UI/export.

Filters: tab (interactions/diagnostics), q (<=120 chars), outcome, level. Page action dashboard.php, CSS dashboard.css. Download via tab=diagnostics&download=1 preserving filters; only sanitized bounded records. Logs read <=2 MiB tail, <=1000 records, <=200 rendered interactions; explicit limitations for bounded DB selection. No caller-selected file path.

## Acceptance and checks

- [ ] Data tests: malformed/hostile records, bound enforcement, allowlist export, active-playthrough isolation, uncertain/confirmed/clamped/zero values, filters and missing DB/log sources.
- [ ] Controller tests: deny remote/spoofed identity before reads; allow loopback/server user; GET-only; safe response/export headers and no unfiltered shared-log export.
- [ ] UI: actual isolated browser desktop and narrow screenshots, both tabs, filters/download, keyboard focus, empty/unavailable, no overflow or fabricated production content. Lead inspects screenshots and returns defects.
- [ ] Package: explicit allowlist includes PHP/CSS; both formats verify exact source payload and exclude preview/fixture/reports. No version bump.
- [ ] Lead diff/call-path/security review and focused final verification; record limits. Static/fixture/browser evidence is not live CHIM certification.
