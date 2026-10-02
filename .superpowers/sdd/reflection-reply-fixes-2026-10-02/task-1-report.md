# Task 1 report: reflection listener and subject aliases

## Status

Complete on `fix/reflection-reply-v2`, based on `74ca8c97d30045822170477aad87824fb39b8222`.

## Changes

- Removed the reflection listener catalog-name veto. The ACK must still resolve to a Player transport, and the stored source must still pass `reflectionSourceParts()` with the exact `explicit_disable_rechat` target before actor identity resolution. A distinct NPC listener and `explicit_disable_rechat` as the ACK listener remain rejected. Registered actor uniqueness checks are unchanged.
- Extended shared `findSubjects()` with the requested name-before-`the`, bracket-suffix, and qualifying first-word aliases. Aliases are deduplicated per NPC and grouped with full names and existing Player identities, so collisions remain ambiguous. The existing overlap rule keeps an unambiguous full name when its shorter alias is ambiguous. Title-only aliases are excluded for every derived alias while full catalog names remain matchable.
- Added focused coverage for Aela possessives, Delia's suffix, Lydia ambiguity/full-name precedence, every listed title, title-derived aliases, Player/alias collisions, speaker aliases, Unicode first words, short words, and unrelated substrings. Added reflection cases for a same-named Player/NPC, a distinct NPC listener, the sentinel as ACK listener, and a source missing the exact sentinel.

## Red-to-green evidence

After adding the regressions and before the implementation change:

- `influence_test.php` exited 1 at the new possessive assertion: `ASCII and curly possessives should match the full-name NPC alias.`
- `reflection_test.php` exited 1 with expected `committed`, actual `event-mismatch` for a Player listener named `Dragonborn` when `Dragonborn` also existed in the NPC catalog.

After the changes, both tests exited 0 and printed `influence checks passed` and `reflection checks passed`.

## Verification

Runtime: WSL PHP `8.2.29`.

Commands and results:

- `wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning-reflection-v2/tests/influence_test.php` — exit 0, `influence checks passed`.
- `wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning-reflection-v2/tests/reflection_test.php` — exit 0, `reflection checks passed`.
- `wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning-reflection-v2/tests/runtime_test.php` — exit 0, `runtime store checks passed`. It also printed the fixture's expected `snapshot-verification-failed` and `player-alias-ambiguous` failure-path diagnostics.
- `php -l` under the same WSL runtime passed for `server/influence.php`, `server/reflection.php`, `tests/influence_test.php`, and `tests/reflection_test.php`; each reported `No syntax errors detected`.
- `git diff --check -- server/influence.php server/reflection.php tests/influence_test.php tests/reflection_test.php` — exit 0, no output.

## Limits and scope

These are isolated synthetic fixtures. They do not establish live CHIM database behavior, provider behavior, PCV runtime integration, audio playback, or in-game acceptance. No store, PCV, installed CHIM, package metadata, or release files were changed. The task changes and this report are committed locally; nothing was pushed or published.

## Fix round 1 — commit-time subject revalidation

### Changes

- `persistJudgments()` now re-runs shared `findSubjects()` after acquiring the listener transaction and refreshing the NPC identity catalog, using the active playthrough's current Player name. Every selected token, including `player`, must still resolve to the same canonical name and ID; otherwise persistence returns `stale/subject-catalog-stale` before relationship, ledger, or history writes. Existing canonical actor checks, full-name subject checks, and `npcById()` row revalidation remain in place.
- `server/store.php` now `require_once`s `influence.php`, because standalone store loading can reach `persistJudgments()` and the new guard uses `findSubjects()`. A direct standalone include check passed without initializing a database.
- Added the derived-alias exclusion for `The`, with checks that `The` alone is not a candidate for `The Courier` while the full canonical name remains usable. Split bare Aela, ASCII possessive, and curly possessive inputs into separate assertions; speaker and listener alias exclusions are both checked.
- Added ordinary and reflection model-time catalog-race cases. Each ambiguous alias case confirms no affinity, ledger, or history mutation; each matching full canonical-name case remains committable. Added an ordinary case where a newly introduced NPC alias collides with a selected Player token.

### Red-to-green evidence

- Before the NPC subject revalidation guard, `runtime_test.php` failed with expected `stale`, actual `committed` after the model inserted `Lydia Hart`; `reflection_test.php` failed the same way. Both passed after the guard.
- With the Player token temporarily bypassing current-subject validation, `runtime_test.php` failed with expected `stale`, actual `committed` for the exact `Dragonborn trusts her.` collision. With the shared selected-token check applied before the Player branch, it passed and asserted `findSubjects()` no longer returns `player` for the updated catalog.
- Before adding `The` to the derived-alias exclusion, `influence_test.php` failed at `The definite article must not become a standalone alias.` It passed after the stoplist update, including the full `The Courier` match.

### Verification

Runtime was WSL PHP 8.2.29. Final commands and results:

- `wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning-reflection-v2/tests/runtime_test.php` — exit 0, `runtime store checks passed`; it also printed the fixture's expected `snapshot-verification-failed` and `player-alias-ambiguous` diagnostics.
- `wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning-reflection-v2/tests/reflection_test.php` — exit 0, `reflection checks passed`.
- `wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning-reflection-v2/tests/influence_test.php` — exit 0, `influence checks passed`.
- `wsl.exe -d DwemerAI4Skyrim3 -- php -r "require '/mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning-reflection-v2/server/store.php'; echo 'store include passed', PHP_EOL;"` — exit 0, `store include passed`.
- `php -l` under the same WSL runtime passed for `server/store.php`, `server/influence.php`, `tests/runtime_test.php`, `tests/reflection_test.php`, and `tests/influence_test.php`; each reported no syntax errors.
- `git diff --check -- server/store.php server/influence.php tests/runtime_test.php tests/reflection_test.php tests/influence_test.php` — exit 0, no output.

### Limits

These checks use isolated in-memory fixtures and a standalone include check only. The optional PostgreSQL path in `player_store_test.php` remained unconfigured and was not run; no live database, server, provider, PCV, installed CHIM, or game state was accessed. No release metadata was changed or pushed.
