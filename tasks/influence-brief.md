# Task 1 — influence evaluation contract

Own `server/influence.php`, `tests/influence_test.php`, and `tasks/influence-report.md` only. Read `tasks/design.md` first. Others share the workspace; preserve all edits. No subagents. Ponytail full; lead reviews and commits, you do not commit. Installed distro and F: stay read-only. Execute PHP against project files via WSL; no actual provider/DB calls.

Implement a small pure-function module in namespace `ChimMindPoisoning`, without dependencies or I/O. Understand existing `RelationshipManager` Player normalization and relationship format read-only before coding.

## Interfaces (fixed for parallel consumers)

`findSubjects(array $event, array $npcs, string $playerName): array`

- event: `utterance_id` string, `speaker_id` int, `listener_id` int, `speaker_name`, `listener_name`, `text` strings, `gamets` numeric, `event_id` integer, `playthrough_id` string.
- npcs: list of rows with `id` positive int and `npc_name` string. Return associative map keyed `player` or `npc:<id>`, each value `{name: canonical relationship key, id: positive int or null for Player}`. Only explicit text mentions, unicode word/name boundaries, no substring false positives, no ambiguous duplicate names, no speaker/listener as subjects. Player actual name plus Player/the Player/Dragonborn aliases normalize to name `Player`. Do not guess pronouns. Handle overlaps conservatively.

`buildMessages(array $event, array $speaker, array $listener, array $subjects): array`

- speaker/listener rows include `npc_name`, optional `personality`, `extended_data` as decoded PHP array containing relationships.
- Return existing fast_request-compatible system/user messages. Put utterance/identities/relationship context in encoded data, explicitly untrusted. Judge listener->subject only using credibility, listener's prior relation, speaker bias, and bounded relevant personality. All candidates must receive a judgment including zero. No relation-type edits, no truth laundering, no automatic blame of Player for another NPC's statement. One model request for the line.
- Exact model JSON schema: `{"judgments":[{"subject":"player or npc:<id>","delta":integer -5..5,"reason":"brief","evidence":"verbatim excerpt from utterance"}]}`.

`parseJudgments(string $response, array $subjects, string $utterance): array`

- Return judgments keyed by subject token: values `{delta:int, reason:string, evidence:string}`. Every candidate exactly once. Zero valid. Invalid JSON/schema, duplicate/unknown/omitted subjects, numeric strings/floats/bools, out-of-bounds delta, oversized/empty reason/evidence, or evidence not in utterance throws `UnexpectedValueException`; never silently coerce. A single exact enclosing JSON markdown fence may be accepted, no heuristic JSON repairs.
- Bound reason/evidence at 600 UTF-8 bytes each and input response at 16 KiB. Empty candidates may produce empty map. Avoid unused abstraction/config.

## Checkable outcomes

Small assert/check based PHP runner, no framework. Demonstrate initial failure before implementation. Verify positive/negative/zero and multi-subject decision shape, Player normalization, boundary false positives, ambiguous duplicate NPC name, self/listener excluded, bogus target/injection text, invalid JSON/numbers/quoted evidence, missing/duplicate decisions. Tests must test outcomes rather than matching source text.

Record exact commands/output and files in report; return concise status plus concerns. Runtime caller and persistence are owned by another task, do not write those files.
