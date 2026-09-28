# Dashboard bug hunt

## Finding and fix

`dashboardReadLogs()` treated a zero-byte, readable `chim.log` as a truncated record list. `explode("\n", "")` yields one empty element, and the unterminated-tail branch set `limited=true`. The dashboard could therefore claim older records were omitted when the log was simply empty.

The reader now applies partial-line handling only to a nonempty tail in `server/dashboard_data.php`. The new `tests/dashboard_empty_log_test.php` checks both cases: an empty readable file is available and not limited; a nonempty unterminated tail remains limited and is ignored.

## Evidence

- Before the fix, the isolated test exited 1 with `An empty readable log was incorrectly reported as truncated.`
- After the fix, `wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/dashboard_empty_log_test.php` exited 0 and printed `dashboard empty-log checks passed`.
- The isolated fixture calls only `dashboardReadLogs()` on temporary files. It does not construct a CHIM database connection or contact a provider.

The existing dashboard data, HTTP, integration, and preview checks already cover parsing/privacy, playthrough attribution, filters/auth/export, and rendering. They were not rerun for this bounded fix. The broader data test includes `dashboardLoad()`, which can enter the native read-only database path; no live database call was made.

## Review limits

No further consequential defect was established in the bounded review. Explicit null affinity continues to follow the existing influence/store convention (`?? 0`); changing that would conflict with current write semantics without evidence that CHIM treats null differently. PostgreSQL, provider behavior, deployed authentication, and CHIM runtime remain unverified here.
