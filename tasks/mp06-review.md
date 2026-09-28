# MP-06 common-word subject review

This review describes the current working-tree change. Published v0.1.3 assets do not include it.

## Flow and recommendation

`findSubjects()` first builds a lexical candidate map from case-insensitive whole-word matches in the ACK text (`server/influence.php`). The hook sends those stable `player` / `npc:<id>` tokens and names through its existing model request (`server/prerequest.php` and `buildMessages()`). `parseJudgments()` strictly validates the response before `persistJudgments()` receives it; storage requires one valid judgment for every candidate and persists the existing `{delta, reason, evidence}` fields (`server/store.php`).

The minimal safe semantic boundary is a required strict boolean `subject_mentioned` on each model judgment. The prompt asks the existing model to distinguish a person reference from an ordinary-word use, and to answer false with zero when it is not a person or is uncertain. The parser rejects missing/non-boolean values and false with nonzero delta, then strips the flag before persistence. This needs no second model request, persistent field, or ledger migration. Exact evidence and all existing token/reason/delta checks remain in force.

This makes common-word handling model-dependent. It does not stop a lexical false candidate from reaching the model or consuming one of the eight subject slots; it does not guarantee correct language understanding. Capitalization is insufficient because ASR may lowercase a real name, and sentence-initial common words may be capitalized. Do not claim deterministic entity recognition or add a stopword/case heuristic.

## Acceptance and failure cases

- A lowercase candidate name remains lexically eligible and may receive a normal judgment when context clearly refers to the NPC.
- A common-word use, such as `May` in “You may trust Jarl Balgruuf,” is classified as not referring to that NPC and has delta zero.
- A generic role label, such as “the guard,” does not identify the catalog NPC named Guard by itself; uncertain identity yields false/zero. Sentence-initial common-word use is not treated as a person solely because it is capitalized.
- `subject_mentioned` must be a JSON boolean for every stable candidate token. Missing, numeric/string, or other values reject the complete response.
- `subject_mentioned=false` with nonzero delta rejects the complete response; no partial persistence occurs. False with zero is normalized to the existing judgment shape before storage.
- `subject_mentioned=true` follows the existing exact-token, integer range, reason, and quoted-evidence validation. Unknown, duplicate, or omitted candidates still fail closed.

## Read-only review

The current source follows this boundary: `parseJudgments()` requires the five-key judgment shape, a strict boolean, and zero when false; it returns only `{delta, reason, evidence}`. The hook catches parse failures before `persistJudgments()`, whose validator and ledger schema remain unchanged. This avoids a persistence migration and fails closed on malformed or contradictory model output.

`tests/influence_test.php` contains focused assertions for lexical retention of both “You may” and a lowercase `May` name, false/nonzero rejection, strict boolean typing, false/zero normalization, and true/lowercase parser acceptance. Its prompt assertion checks for the required `subject_mentioned` field rather than matching prompt prose. The lead ran the focused influence and runtime fixtures; both exited 0. These checks prove parser enforcement, not that a live provider will classify a phrase correctly. No live provider, database, CHIM server, or game behavior was tested here.
