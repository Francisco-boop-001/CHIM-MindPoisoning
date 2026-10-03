# Defensive-programming assessment — 2026-10-03

## Scope and conclusion

The user relays a concern that defensive programming inflates Mind Poisoning. Independent agent `defensive_review` assessed baseline commit `9eff9b280cb06526050467653b13f9563a4d93e2` without modifying files or running code. The five principal request/model/logger/reflection/store files total approximately 3,489 physical lines; line count alone does not establish a defect.

Conclusion: no general pattern of needless defensive programming was established. Much of the size implements distinct Player, NPC and reflection contracts plus atomic persistence. Preserve trust-boundary checks and checks separated by model calls/locks. There is a narrow error-classification flaw to correct in the ordinary parser path being changed for overhearing; no broad cleanup is justified by this assessment.

## Concrete examples and counterarguments

- `server/prerequest.php:392-505`: client ACK bounds, identifier parsing, exact event/target and unique actor checks guard untrusted input before paid model work and relationship mutation. Their volume is visible, but deleting them would accept ambiguous/malformed events.
- `server/prerequest.php:203`, `:643-656` and `server/store.php:708-832`: interaction/pause, event/profile/identity and lock checks occur before model work, after it and under the listener lock. They are temporally distinct; a provider call permits mutable state to change.
- `server/store.php:942-989` and `:1386`: state readback and history verification before commit add database reads, but enforce the current affinity/ledger/history write contract. This is a real integrity requirement rather than speculative hardening.
- `server/prerequest.php:613-627` and `server/logging.php:201`: catching every parser `Throwable` as an invalid model response can disguise an internal programming error as a warning. Separate expected `JudgmentValidationFailure` from unexpected failures in the affected ordinary/batch path. Keep exception containment and fixed safe reasons. Similar reflection callback error reporting is a separate potential improvement and is not silently bundled into this feature.
- `server/prerequest.php:16` and `:392-461`: early ACK metadata extraction partially overlaps full validation. A shared parser could reduce drift, but early correlation remains useful if bootstrap fails. Leave it unless the feature naturally supplies an equally clear shared path without losing diagnostics.

## Overhearing consequence

At the baseline, `PostgresStoreDb::acknowledgedEvent()` and normal NPC `sameEvent()` omit the event's `people` roster. Carrying and rechecking that bounded exact snapshot is necessary when it authorizes additional listeners. Reuse the existing per-listener lock, dedupe and atomic save; do not create a group transaction or duplicate the full evaluator for every witness. Distinct listener terminal records are needed because the existing logger/persistence/dashboard represent one opinion owner.

This is source analysis, not runtime, database, provider or gameplay proof. Any changed-path simplification must retain observable behavior and receive focused verification; no cosmetic LOC target is imposed.

## Final implementation assessment

The new feature adds substantial orchestration to `server/prerequest.php`. Its main complexity follows the approved requirements: one provider request, independent listener contexts/results, closed response validation, original witness authorization, and individually atomic writes with truthful partial outcomes. The batch builder/parser adapt the existing ordinary ACK builder/validator rather than creating another evaluator; the extra JSON adaptation is a readability/performance tradeoff, not an established correctness defect.

The criticism did identify useful work when made concrete. The owner delayed addressed preflight until eligible extras exist, avoiding repeated reads on ordinary fallback. The roster scan now stops on the fifth eligible extra rather than continuing to probe a crowd that must be skipped. Unexpected parser, witness lookup/preflight and prompt-building failures are surfaced with fixed error reasons rather than benign filtering or invalid-model warnings. No arbitrary exception text is logged. Forked logs preserve the observer and share its reentrancy guard; native cleanup records are attributed to the actual listener.

Independent final review found no remaining blocker. All eleven frozen changed PHP/test files still match their hashes; eight focused isolated behavior/integration gates and eleven PHP lints passed. The exception fixture runs with diagnostics off and confirms that witness lookup failure logs a sanitized error, leaves the witness unwritten, and preserves the addressed commit. The maximum-six-member fixture proves one injected model call and five independent listener results, including a confirmed zero change.

Do not remove checks across the provider wait and locked commit merely because they look repetitive. Conversely, do not call every broad catch necessary hardening: hiding a query failure as normal filtering was a genuine diagnostics defect, and it was corrected. No broad lifecycle extraction, new job system or speculative abstraction is justified by this review. The source remains more complex than the direct-only plugin, and real provider/database/game acceptance of the new batch path is still required.

Bounded review caveat: `promptNpcCopy($speaker)` remains before the new prompt-build catch, as in the legacy direct path. Malformed speaker data therefore reaches the existing outer fixed failure handling; the new `overhearing_prompt_build_failed` event describes listener conversion/builder failures, not every possible shared speaker-copy failure. No new regression was demonstrated there, so it was not expanded into a separate cleanup task.
