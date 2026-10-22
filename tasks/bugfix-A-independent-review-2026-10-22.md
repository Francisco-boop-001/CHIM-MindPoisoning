# Independent read-only review — task A

Review scope: findings 1, 8, and 9 in `tasks/bug-hunt-2026-10-22.md`, A's final diff/report/evidence, plus the written-evidence-only cap changes in B-owned `server/store.php`. Reviewed at base `d77c5eeee4fba372d8cfd70ae803ea9a9baf85bf`. No product or test files were changed and no tests were rerun.

## Result

No code correctness defect was identified in the requested source changes.

## Review notes

- **Canonical sender identity:** `writtenEvidenceSubjects()` first requires `writtenEvidenceUniqueNpcIdentity()` to resolve the claimed ID/name uniquely, then derives the relationship subject name from that catalog row (`server/written_evidence.php:385-410,431-501`). The evaluator uses that subject name for both the relationship-type snapshot and persistence (`server/written_evidence.php:751-770`). This fixes the case-only duplicate-edge path without editing the decoded envelope or merging historical relationship keys.
- **Envelope bytes and identity:** The evaluator calculates `source_digest` from the original `source_data` bytes (`server/written_evidence.php:258-284,700-707`). Store-side event identity re-hashes those exact bytes and uses `hash_equals`; the source data is parsed for meaning but not re-encoded (`server/store.php:601-617,658`). The new evaluator regression asserts byte-for-byte source preservation, the canonical edge update, and the absence of a case-variant edge (`tests/written_evidence_test.php:684-746`).
- **Protected relationship type:** Canonical subject names are used to load the existing relationship type (`server/influence.php:352-399`). Persistence compares the stored type with the model's `from`, then retains protected types through `relationshipTypePreservationReason()` (`server/store.php:1710-1773`). The test seeds the canonical `romantic` edge, gives the model that type, proposes `suspicious`, and asserts affinity changes while type remains `romantic` with reason `protected_type_preserved` (`tests/written_evidence_test.php:698-745`).
- **Fenced intimacy response:** `parseIntimacyResponse()` has the same anchored, lowercase `json` fence pattern and trim behavior as the influence parser (`server/intimacy_request.php:77-90`; `server/influence.php:1105-1110`). It preserves the existing response byte cap and downstream schema/evidence validation. Tests cover LF and CRLF fences and reject prose surrounding a fence (`tests/intimacy_request_test.php:200-218`).
- **Encoded and decoded bounds:** The raw envelope is capped at 65,536 bytes before decoding, while the body retains its 8,000-byte and 2,000-codepoint bounds (`server/written_evidence.php:29-56`). The same shared constant is used by store-row normalization, event identity validation, and both written-evidence SQL reads (`server/store.php:11,556-583,597-617,2514-2520,2557-2563`). SQL uses `octet_length`, matching PHP's byte-counting `strlen`. The retained max-envelope probe accepts a 28,139-byte standard JSON-escaped envelope with the full decoded body and configured metadata; the unit test rejects a payload one byte over the encoded cap (`green-encoded-max-envelope-probe.txt`, `tests/written_evidence_test.php:379-428`).

## Verification and remaining gate

A's retained red/green outputs support the parser and in-memory source-row changes: the new tests fail on the old behavior, `green-written-evidence.txt` reports 234 evaluator checks passed, `green-intimacy-request.txt` passes, and `green-written-store.txt` passes. A also records syntax checks and `git diff --check` as clean. I inspected those outputs; this review did not execute them again.

The SQL cap is correct by source inspection, but its real PostgreSQL retrieval path is not verified here. A's current report says the B-owned native SQL regression remains pending, and B's current report also marks the native lease/test and review pending (`tasks/bugfix-A-2026-10-22.md:24-26`, `tasks/bugfix-B-2026-10-22.md:6-10,23-25`). Keep the database-cap claim pending until that regression output is available. No database, installed CHIM, game, provider, or live environment was used for this review.
