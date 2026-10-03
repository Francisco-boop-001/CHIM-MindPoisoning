# Overheard gossip implementation

## Checklist

- [x] Extend the exact NPC ACK source snapshot to carry bounded `eventlog.people` only for the opt-in path.
- [x] Add a default-off CHIM general setting and bounded, uniquely resolved overhearer selection.
- [x] Build one recipient-batched model request with per-listener context, closed response validation, and independent persistence.
- [x] Revalidate overhearer role, addressed target, exact people snapshot, identities, setting, interaction scope, and locks before writes/commit.
- [x] Preserve truthful per-listener log and dashboard outcomes with a fixed exposure role.
- [x] Add focused ACK-path tests for the feature contract and update relevant dashboard checks.
- [x] Review the complete diff and prepare the lead verification commands.

## Design notes

- Feature key: `mind_poisoning_overhearing_enabled`, read through the fresh-query `chimGetGeneralSettingBool()` with a false default. The CHIM core source-only probe confirmed its getter reaches `chimGetGeneralSettingRow()` and performs a database fetch on each call. In an admin-managed CHIM bootstrap, operators can use `chimSetGeneralSetting('mind_poisoning_overhearing_enabled', true, 'Enable Mind Poisoning NPC ACK overhearing')` to enable and pass `false` to disable; no plugin endpoint is added.
- Scope is the exact ACK row's global `eventlog.people` membership snapshot. Membership is not proof of playback or a Private Conversation scene.
- The current addressed ACK route and reflection/Player paths stay separate. Each overhearer gets its own existing listener transaction, ledger, history snapshot, and request correlation.
- If more than four uniquely resolved and otherwise eligible overhearers remain, skip the entire extra-recipient batch and continue the addressed path. Unknown, ambiguous, player aliases, speaker, addressed listener, and duplicate identities are removed before that cap.
- This worker did not run PHP, WSL, provider, or database tests. The lead's isolated-clone verification is recorded below.
- Sequential duplicate ACKs are rejected from each listener's existing ledger before another provider call. There is no pending-evaluation reservation, so concurrent duplicate ACKs can still race to spend provider calls; the existing per-listener commit lock and ledger remain the effect boundary.
- The provider response is bounded to 32 KiB, at most 5 recipients, and at most 8 subjects per recipient. Prompt construction is capped at 128 KiB; batch calls request 4096 tokens. SQL returns `people` only when at most 2,048 bytes and carries a separate fixed oversized flag; oversized rosters are rejected for extras while the ordinary direct route proceeds.
- Ordinary NPC ACK authorization still requires the exact explicit target to match the addressed NPC. Overhearers instead require unique current NPC identity plus membership in the exact source `people` snapshot; under the save lock the code rechecks that snapshot, speaker/target tuple, source identity, active playthrough, setting, and listener lock. The snapshot is not proof that anyone played or heard audio.
- A per-listener cleanup exception can report a failed cleanup after a write; each terminal record retains its own `commit_state`, including `unconfirmed`. Each child request has a distinct request ID with a shared sanitized `batch_id`; the hook result continues to report the addressed listener's result.
- Batch prompt construction uses the same `promptNpcCopy()` conversion as the direct ACK route before reusing `buildMessages()`, so each listener's JSON-backed relationship context has the shape the existing prompt reader expects. Forked request logs inherit the sanitized observer and share its reentrancy guard; a child observer that logs through another fork cannot recurse.
- Unexpected witness lookup, overhearer inspection, addressed inspection, and prompt-building exceptions fail closed to the existing direct route and log fixed error codes: `audience_lookup_failed`, `audience_preflight_failed`, `addressed_preflight_failed`, and `overhearing_prompt_build_failed`. Exception text is not logged.

## Review

Self-review complete: source changes are limited to the approved NPC ACK flow, persistence seam, request logging/dashboard exposure, model token bound, capability version constant, and targeted in-memory tests. Player source lookup remains on its old path, and reflection constants/flows are untouched. `git diff --check` reported no whitespace errors for the tracked owned changes.

Lead verification completed in the isolated test clone. `tests/overhearing_test.php`, `tests/runtime_test.php`, `tests/dashboard_data_test.php`, `tests/logging_test.php`, `tests/store_logging_test.php`, `tests/reflection_reply_test.php`, `tests/reflection_observer_test.php`, and `embeddedPCVreflection_full_reply` all exited 0. The 11 changed PHP files linted successfully. The overhearing test printed `Overhearing ACK tests passed.` with empty stderr and exercised the six-person cap, zero-delta confirmed commit, catalog ambiguity, and sanitized exception diagnostic cases. All 11 frozen-file hashes matched after verification. The authorized alternative test clone stopped with exit 0; the gaming VHD remained unchanged.
