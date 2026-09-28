# Runtime bug hunt

Reviewed 2026-09-27. Scope was the current ACK-to-model-to-persistence flow, typed model failures, commit/cleanup logging, and the previously open identity concern. No installed CHIM/mod code, live database, provider, HTTP endpoint, or game was executed.

## Confirmed finding: common-word NPC name can become a candidate

`findSubjects()` groups all catalog names and searches them with a case-insensitive token-boundary regex (`server/influence.php:81-139`). A unique NPC named `May` is therefore selected from ordinary prose even when it is not being addressed as a person. This reproduced the earlier open MP-06 finding in `critique.md:57,174`. The first hunt deferred a lexical heuristic because it could reject lowercase names while still accepting sentence-initial common words. A later explicit request authorized semantic classification at the model boundary; the follow-up fix is below.

Focused reproduction used the pure resolver with a unique NPC named `May` and ACK text `You may trust Jarl Balgruuf.`:

```text
{"npc:8":{"name":"May","id":8}}
```

A temporary assertion in `tests/influence_test.php` failed as expected:

```text
PHP Fatal error: Uncaught RuntimeException: A lowercase common word must not identify a one-word NPC name.
exit code: 1
```

That temporary resolver-only assertion was removed from the regular suite. Case-sensitive single-word matching would reject valid lowercase client/transcribed names and still accept sentence-initial common words such as “May I…”. The current inputs have no entity-confidence or alias metadata that can disambiguate these cases reliably. A stopword list or NLP/entity-recognition layer would add policy and behavior beyond a minimal root fix.

## Other scoped paths

The current code and prior focused reports were checked for contrary or newly exposed failures:

- ACK generation/Off gates, transformed ACK text with exact event/actor binding, and Player-alias preflight have focused coverage in `tasks/critique-runtime-fixes.md`.
- Model setup/response reason typing and warning/error severity have focused coverage in `tasks/l01-runtime.md`.
- Malformed stored ledger, same-profile Player rename, and rollback-safe persistence have focused coverage in `tasks/debug-runtime.md` and `tasks/runtime-report.md`.
- Post-commit release failure summaries and logger exception containment have focused coverage in `tasks/debug-logging-hook.md` and `tasks/logging-runtime.md`.

The read-through of the current model, hook, store-finally, and logger-finish paths found no additional confirmed defect. Those previously covered suites were not rerun; this hunt made no production or regular-test changes.

## Follow-up fix: semantic mention decision

The parser now requires a strict JSON boolean `subject_mentioned` for each model judgment. A false value with a nonzero delta, or any non-boolean value, fails closed with `judgment_mention_invalid`. A false/zero result remains valid. The parser strips this transient decision and returns the existing `{delta, reason, evidence}` shape, so persistence and ledger schema are unchanged. The prompt explains that resolver candidates are lexical possibilities, common words such as “May” can be incidental, generic role references such as “the guard” do not establish a catalog NPC named Guard, and capitalization cannot decide identity.

The before-change focused run was red:

```text
wsl -d DwemerAI4Skyrim3 --exec php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/influence_test.php
PHP Fatal error: Uncaught RuntimeException: A nonzero judgment without a semantic mention decision must fail closed.
exit code: 1
```

After the parser/prompt and fixtures changed, these focused runs were green:

```text
wsl -d DwemerAI4Skyrim3 --exec php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/influence_test.php
influence checks passed
exit code: 0

wsl -d DwemerAI4Skyrim3 --exec php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/runtime_test.php
Mind Poisoning persistence failed at snapshot-verification-failed.
Mind Poisoning persistence failed at player-alias-ambiguous.
runtime store checks passed
exit code: 0
```

The two runtime failure logs are existing injected failure-path fixtures. The composed tests verify that `You may trust Jarl Balgruuf.` still supplies `May` as a candidate, but a model response of `subject_mentioned=false, delta=2` fails before persistence with no listener mutation or history snapshot. A second composed fixture verifies that `I met may by the gate.` still supplies lowercase `May`; a true semantic decision commits and updates the relationship. A parser test also checks that the normalized result contains no new persisted field.

`influence.php` and `tests/runtime_test.php` PHP lint checks both reported no syntax errors.

## Verification limits

- The new reproduction ran only `findSubjects()` under the configured local WSL PHP runtime; it demonstrates the resolver false positive, not an actual game line or a database write.
- The semantic label remains an LLM judgment, not deterministic entity recognition. The fixtures prove parser and injected-model contract behavior; they do not prove provider accuracy on all wording or speech-to-text variants.
- No live CHIM/database/provider/game test was run. No release, version, manifest pin, protected distro, or mod was changed.
