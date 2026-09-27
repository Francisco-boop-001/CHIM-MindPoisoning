# Influence and model debug review — 2026-09-27

## Result

No consequential subject-resolution, prompt/parser, or connector-adapter defect was reproduced. No PHP or fixture changes were justified.

## Focused review

- The actual caller in `server/prerequest.php:175-200` resolves candidates from the database identity catalog, checks each candidate again before prompting, parses only the returned judgments, then delegates persistence. `server/store.php:526-534` casts catalog IDs to integers. This keeps prompt tokens tied to canonical NPC IDs; no independent name-to-ID path was found.
- The suspected NPC-key mismatch is not a defect. `server/influence.php:154-211` reads NPC relationship keys by exact canonical name. The inspected core `RelationshipManager::normalizeTargetName()` in `lib/relationship_manager.php:186-212` canonicalizes Player aliases only; its relationship evaluator normalizes the target and then accesses `$currentRels[$target]` directly (`ext/relationship_system/relationship_llm.php:1470-1474`). The plugin's exact NPC-key lookup therefore matches the core's storage behavior. Player aliases and the event's actual player name are handled separately and covered by the existing legacy-name fixture in `tests/influence_test.php`.
- `server/influence.php:277-365` requires valid JSON, one judgment per candidate, an allowed token, an integer delta from -5 through 5, and exact nonempty evidence from the utterance. The existing fixture covers invented/duplicate/missing subjects, float/string deltas, fabricated evidence, and invalid bounds. No path from model-authored text to a new relationship target was found.
- `server/model.php:22-105` uses instance methods on `LLMConnector`, validates the configured connector row and driver, and restores the globals it scopes on success or failure. Read-only inspection confirmed those production methods are instance methods (`lib/core/llm_connector.class.php:147,260,425`). Both installed OpenRouter `fast_request` branches accept `MAX_TOKENS` and let `FORCE_MAX_TOKENS` override it (`connector/openrouterjson.php:1228-1233`, `connector/openrouterjsoncached.php:2038-2043`).

## Verification boundary

No tests were rerun because this review found no failing case and made no source changes. The prior accepted fixture results are recorded in `tasks/verification.md`; they are not live runtime proof. The installed WSL source was read only. No database mutation, provider request, plugin installation, or game test was performed.

Provider response quality, live connector latency/failure behavior, and CHIM/game integration remain unverified. Setting `HTTP_TIMEOUT` to 12 does not establish a hard wall-clock deadline.
