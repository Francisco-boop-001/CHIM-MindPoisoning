# Independent read-only review — task D

Date: 2026-10-22
Scope: **server/prerequest.php** and D-owned changes in **tests/restore_policy_test.php**, **tests/runtime_test.php**, and **tests/overhearing_test.php**; task report and retained D RED/GREEN outputs. No product or test edits, test reruns, database, provider, installed CHIM, or game access.

## Verdict

No blocking defect found in the reviewed D changes. The addressed path retains its ordinary lock, restore-baseline, and replay checks when optional overhearing is unavailable, invalid, over cap, or has no eligible extras. Batch completion and connector validation changes match the intended contracts.

## Review notes

### Addressed fallback and restore policy

**evaluateOverhearingAck()** returns null for unavailable snapshot capability, invalid roster/speaker data, an over-cap audience, and an empty post-baseline extra set (**server/prerequest.php:312-336, 402-412, 414-466**). The caller only returns a batch result when this helper returns a non-null status. Otherwise it continues through the ordinary listener lock check, **prepareRestorePolicyRecipient()**, and the existing dedupe check (**server/prerequest.php:1310-1327, 1328-1360**). This preserves the direct recipient’s standard guards.

For restore mode, candidate checks in **inspectAckRecipient()** are read-only. The code checks the four-extra cap before calling **prepareRestorePolicyRecipient()** for any extra, then baselines each candidate separately and re-inspects it so scope baseline and event dedupe results are applied before batch selection (**server/prerequest.php:345-413, 414-459**). The addressed listener is separately baseline-checked and re-inspected before selection (**server/prerequest.php:468-528**). The focused scope-mismatch receipt also checks that the addressed baseline establishes its replay floor and that replay does not call the model or change state.

The accepted conservative cap has one consequence: a fifth preliminary eligible candidate counts even if its later scope baseline would fence it. In that case all optional extras are skipped and the ordinary addressed path runs. This is the documented all-or-none policy accepted by the lead, not a blocking finding.

### Completion logging

When no batch is selected, the helper falls back to the ordinary path and **handleSpeechAck()** emits the one terminal summary. For a real batch, **finishBatch()** sets **_finished_by_batch**, finishes the parent once, and the outer handler suppresses a second parent finish (**server/prerequest.php:149-155, 617-627**). Recipient forks retain their own per-listener summaries linked by **batch_id**; the unchanged **tests/overhearing_test.php** assertions expect one summary per listener with distinct request IDs. The new locked-address/no-extra cases assert exactly one terminal summary for the ordinary fallback.

### Connector validation

The ACK path now calls the shared **positiveConnectorId()** helper before event/model work (**server/prerequest.php:933-940**, **server/model.php:35-45**). This matches the model adapter’s strict positive integer contract. The runtime fixture rejects whitespace, plus-prefixed, and float forms; it asserts no model call, no listener snapshot, and one skipped terminal summary (**tests/runtime_test.php:2011-2027**).

## Retained verification inspected

- RED receipts reproduce the prior over-cap/invalid/unavailable fallback failures, duplicate terminal summaries for locked-address cases, and loose connector handling: **K:\ActorwrightExchange\artifacts\mp-bugfix-20261022\D\red-extra-cap.txt**, **red-invalid-roster.txt**, **red-no-witness-store.txt**, **red-locked-address.txt**, **red-no-extra-locked.txt**, and **red-connector-runtime.txt**.
- GREEN receipts show all six focused restore-policy cases and the full restore-policy, runtime, and overhearing suites passing: **green-extra-cap.txt**, **green-invalid-roster.txt**, **green-no-witness-store.txt**, **green-locked-address.txt**, **green-no-extra-locked.txt**, **green-scope-mismatch.txt**, **green-restore-policy-full.txt**, **green-runtime-full.txt**, and **green-overhearing-full.txt**.
- The retained PHP lint outputs report no syntax errors for **server/prerequest.php** and the named tests (**lint-prerequest.txt**, **lint-restore-policy.txt**, **lint-runtime.txt**, **lint-overhearing.txt**).

These receipts are synthetic Windows PHP/in-memory evidence, not native PostgreSQL or live CHIM evidence. The runtime output records Windows symlink-test skips. I inspected retained outputs and source; I did not rerun tests or independently reproduce their exit codes.

## Result

No D correction requested. Keep the cap tradeoff and synthetic-only verification limit in the release record.
