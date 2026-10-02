# Reflection reply fixes — 2026-10-02

## Scope and success criteria

Implement the source-confirmed listener collision fix, conservative shared subject aliases, and an opt-in full-reply reflection API. Preserve the existing v1 caller and exact correlation. Document diagnostics at the layer that actually runs. Success requires focused behavioral regressions, reviewed diffs, truthful failure/commit reporting and an explicit caller contract.

## Global Constraints

- Work only in `K:/ActorwrightExchange/projects/CHIM-MindPoisoning-reflection-v2` on `fix/reflection-reply-v2`, based on `74ca8c97d30045822170477aad87824fb39b8222`.
- Private Conversation, including its embedded snapshot, and installed CHIM/mod/game files are read-only. Do not change version, release, manifest, package metadata or published pins; do not install, publish, push or use live databases/providers.
- Preserve the v1 constant value and function signature because PCV at c46d650 requires exactly API version 1. Expose v2 separately with explicit capability discovery.
- Use Ponytail FULL, trace affected callers before editing, reuse existing model/transaction/logger machinery, and preserve other contributors' edits. The lead does not write product code. Implementation owners do not spawn subagents.
- Preserve identity, sentinel, playthrough/configuration, interaction/pause/lock, revalidation, replay and transactional checks. Model calls remain outside locks. Log only fixed sanitized fields/reasons; committed means confirmed commit.
- Existing fixtures use in-memory stores, synthetic model responses and isolated temporary files. WSL PHP is an execution runtime only, not permission for live CHIM reads or writes.
- Keep source evidence distinct from fixture execution and reported live evidence. Natural playback, provider reliability and PostgreSQL durability remain unverified by these checks.

## Task 1: Listener identity and conservative subject aliases

Ownership: `server/reflection.php` listener guard only; `server/influence.php` subject matching only; `tests/reflection_test.php` listener/alias-race regressions; `tests/influence_test.php` alias regressions. Lead acceptance refinement extends ownership to `server/store.php` shared subject revalidation and `tests/runtime_test.php` catalog-race cases. PCV remains read-only.

Remove the catalog-name veto for reflection listeners after the Player transport and exact source sentinel have been checked. Retain rejection of a different NPC listener and of the sentinel as ACK listener; retain unique registered actor checks and ordinary pair routing. Reproduce same-named Player/NPC rejection before fixing it.

Extend shared `findSubjects` using its existing grouping/boundary/overlap machinery. Candidate aliases: name before ` the `; name without a trailing bracketed suffix; first word of a multiword name when at least three Unicode letters and not a conservative title stoplist (include Jarl, Sir, Lady, Lord, Captain, Commander, General, Guard, King, Queen, Prince, Princess, Master, Mistress, Doctor, Dr, Sergeant). Derive aliases from normalized names, deduplicate per NPC, and group with full names and Player identities. Ambiguous aliases must never identify one of their owners; preserve unambiguous full-name matches. Exclude speaker/listener subjects using existing identities, including their aliases. Match whole names with Unicode boundaries and allow ASCII/curly possessives. Keep model reference confirmation mandatory; aliases are candidate detection, not identity proof.

Focused regressions: Aela/Aela's/Aela’s -> Aela the Huntress; Delia -> Delia [Bandit Thug Archer]; ambiguous Lydia surnames -> no short-name subject; Jarl alone -> no titled NPC; alias/full-name collision and Player-name collision fail closed; exact full name survives ambiguous short alias; own speaker aliases excluded; ordinary unrelated words/substrings do not gain subjects. Show red-to-green evidence and lint changed PHP files.

Before saving, rerun shared subject matching against the current locked catalog and active Player identity. Every selected NPC/Player token must remain unambiguous and canonical; preserve the existing full-name/actor checks. Prove alias ambiguity introduced during model work prevents affinity/ledger/history writes in ordinary and reflection paths, including Player collision; explicit full-name speech remains viable in the same catalog. Declare the store's shared matcher dependency using the existing require_once pattern.

## Task 2: Versioned full-reply evaluator and atomic replay protection

Ownership after Task 1 review: `server/reflection.php`, `server/store.php`, `server/influence.php` reflection context only if needed, new focused `tests/reflection_reply_test.php`, relevant existing store/reflection tests when required. Add no new runtime file or StoreDb method unless a demonstrated contract gap requires it. Preserve Task 1 changes.

Implement a separate v2 entry point/capability while keeping v1 callers unchanged. Finalize the exact registration/signature in a lead-approved contract before editing. Registration carries an ordered bounded list of exact source event/utterance IDs and subtitle hashes from one server-registered reply; no caller-supplied evaluated dialogue. Verify all listed sources from StoreDb, same registered actor and explicit sentinel, emitted/spoken states, UTF-8 text/hash agreement, unique IDs and stable order. Join trimmed source subtitles with a specified separator; only the exact final native ACK triggers evaluation, and its speech hash must match the final source line. Reject overflow rather than truncate (max eight lines, max 2000 Unicode characters total, plus bounded byte size).

Reply membership/order must be established by the companion's immutable server registration and repeatable revalidation callback, never inferred from recency or numeric adjacency. Document the trust boundary and any additional source-backed grouping evidence available. Revalidate every exact source before persistence and before commit, along with all existing checks. A middle line becoming aborted, changed or missing after the model must prevent saving.

Deduplicate every covered event/utterance ID with existing ledger/floor semantics before provider work and atomically under the lock; no second effect from v1/v2 replay or overlapping registrations. Preserve one relationship update/history snapshot for a reply, confirmed zero decisions, rollback and uncertain commit reporting, and legacy ledger readability. Do not present final ACK/emitted earlier lines as proof of full playback.

Focused checks: subject only in first line evaluated; final-only ACK; aborted/missing/mismatched/mixed-actor/sentinel line fails before provider; invalid order/duplicates/over-cap rejected; source mutation after model rolls back; all covered IDs prevent replay/overlap; confirmed zero records dedupe; false commit remains uncertain; v1 and pinned PCV integration continue working. Record exact source/fixture limitations and lint changed PHP.

## Task 3: Diagnostic coverage and consumer handoff

Ownership after Task 2 review: MP `server/reflection.php` / `server/logging.php` only for a proven diagnostic gap; focused reflection/logging tests; `docs/integration-api.md`, MP `server/README.md` and contributor notes for the new contract. PCV stays read-only.

Trace all reflection entry/exit paths and the consumer routing at PCV c46d650. Prove terminal records for genuine invoked skips, using fixed reasons and truthful severity. If MP lacks normal diagnostics when the optional logger is omitted, reuse RequestLog default delivery without adding a second logger/record when supplied. Contain logging failures. Do not manufacture an MP record for an upstream route that never calls MP; document that PCV must report those skips. Avoid global noise from unrelated ACKs.

Publish the exact v2 function signature, fields, text normalization/caps, final ACK rule, immutable registration/revalidation obligations, replay behavior, optional logging and v1 negotiation. State PCV's current v1-only check requires future caller work to use v2, while existing callers continue to work. Include concise tester limitations and the relevant checks. No version/release metadata changes.

## Verification and handoff

Baseline: `influence_test.php` and `reflection_test.php` passed under PHP 8.2.29 before edits. Each task adds/runs only checks answering its concrete risks. Task reviewers assess both spec and quality using diff packages; lead reviews affected call paths and evidence. Whole-branch review follows all tasks. Preserve the worktree and review evidence until integration is explicitly requested; no release pin advances.
