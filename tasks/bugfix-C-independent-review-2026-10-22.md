# Independent review C — dashboard findings 10–12

## Scope and result

Read-only review of `server/dashboard.php`, `server/dashboard_data.php`, the four changed dashboard tests, `tasks/bugfix-C-2026-10-22.md`, and the retained C RED/GREEN/hash artifacts. I found no correctness or security defect in the reviewed changes. I did not edit product or test files and did not rerun tests.

## Review

- **Local Host gate and handoff** (`server/dashboard.php:25–119`): unauthenticated loopback access requires a strictly local Host, no forwarding headers, and the expected loopback peer address. The Host parser accepts only `localhost`, validated IPv4 in 127/8, or bracketed `::1`, with a bounded valid port; malformed and non-local values fail closed. The unauthenticated `local=1` handoff is restricted to non-loopback peers, uses a fixed `localhost` authority, validates the script path and port, and bounds the rebuilt query. Therefore the hostile-Host loopback denial cannot turn into a redirect or load dashboard readers. The existing `REMOTE_USER` path remains the server-authenticated access path.
- **Search values** (`server/dashboard_data.php:1746–1828`): recursive traversal searches scalar field values, including nested arrays/objects, while excluding property names. Strings are searched as stored, so quotes and backslashes are searchable without JSON escaping; numeric and boolean scalars retain readable JSON representations. The added fixtures cover key exclusion, nested quote/backslash values, ordinary text, and the interaction matcher.
- **Bounded log tail** (`server/dashboard_data.php:149–217`): the byte before the bounded offset determines whether the first tail fragment is incomplete. A cut immediately after LF preserves the full record beginning at the offset; a cut within a record discards only that leading fragment. An unfinished final line is still dropped and marked limited. The aligned complete-record regression has a corrected RED against the old unconditional discard and passes in the retained suite.
- **Integration fixture**: copying `restore_policy.php` matches the dashboard reader's fixture dependency, and the whitespace-tolerant markup assertion still requires the exact `dt`/`dd` elements and text.
- **Department boundary**: the C report records the Department owner's confirmation that its adapter calls the PHP reader helpers directly, not the HTTP entry point. Reader signatures/shapes are unchanged. Treat this only as source-level compatibility; the 0.1.29 hash pin needs the Department owner to review/re-pin the clean 0.1.30 hashes. No deployed-copy compatibility was established.

## Coverage note

`dashboard_empty_log_test.php` covers an unfinished tail at EOF and the newly fixed case where the bounded offset begins exactly at a record boundary. It does not directly construct a bounded offset in the middle of a record. The `previousByte !== "\n"` discard path is straightforward on inspection, so this is a non-blocking test-coverage gap rather than a found behavior defect.

## Evidence reviewed

- Every `*-suite3.exit.txt` capture is `0`; the eight non-PostgreSQL suites report pass. `dashboard_recency_test.php` reports its explicit skip because no isolated PostgreSQL socket was configured.
- The corrected tail RED records the command, failure, and temporary old-condition setup; its matching GREEN passes. Search and Host REDs fail on the reported key-match and Host-less access, and the corresponding final GREENs pass.
- Independently recomputed SHA-256 and Git blob hashes for both product files and all four changed dashboard tests match `suite3-run-manifest.txt` and `green-source-hashes.txt`.
- The HTTP test uses an isolated fixture server. No deployed server, database service, provider, game, or Department copy was exercised.

## Verdict

Approve the reviewed C changes for lead integration, with the non-blocking mid-record tail-fixture gap recorded above. Department version/hash re-pin remains an owner gate before 0.1.30; it is not evidence of deployed compatibility.
