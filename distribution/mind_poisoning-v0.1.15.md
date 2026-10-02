# Mind Poisoning v0.1.15 — PRE-ALPHA candidate

This candidate adds an opt-in full-reply reflection API while preserving the existing v1 single-line API and the current Private Conversation 0.1.8 caller. It is still a development candidate: source fixtures do not establish live database, provider, installation, or game/audio behavior.

## Reflection APIs

Mind Poisoning 0.1.15 preserves `\ChimMindPoisoning\MIND_POISONING_REFLECTION_API_VERSION = 1` and the existing `mindPoisoningEvaluateReflection()` signature and registration contract. It adds a separate capability constant, `\ChimMindPoisoning\MIND_POISONING_REFLECTION_REPLY_API_VERSION = 2`, and opt-in `\ChimMindPoisoning\mindPoisoningEvaluateReflectionReply()` with the same argument order and defaults:

```php
use ChimMindPoisoning\RequestLog;
use ChimMindPoisoning\StoreDb;
use function ChimMindPoisoning\mindPoisoningEvaluateReflectionReply;

mindPoisoningEvaluateReflectionReply(
    array $registration,
    array $gameRequest,
    StoreDb $store,
    callable $revalidate,
    ?callable $requestModel = null,
    ?RequestLog $requestLog = null
): string
```

The v2 registration contains the exact eight v1 fields plus `lines`, an ordered list of 1–8 `{event_id, utterance_id, speech_hash}` tuples. The top-level event, utterance, and hash must match the final tuple. MP resolves every source row, verifies the registered actor, explicit `explicit_disable_rechat` target, allowed delivery state and SHA-256 of the parsed source body, then joins the trimmed bodies in order with one space. Only the matching final native `_speech` ACK triggers evaluation. Earlier valid ACKs are informational skips.

The joined text is capped at 2,000 Unicode code points and 8,000 bytes; over-limit replies are rejected without truncation. Every covered event and utterance is checked against the existing bounded ledger and eviction floor before provider work and again under the actor transaction. A reply makes one relationship/history update and records every covered tuple in that existing ledger; earlier entries have empty judgments and the final entry carries the decision. Confirmed zero decisions also record all members. No reply table or `StoreDb` method was added.

## Companion boundary

Only a companion's server-side registration and repeatable callback can establish that the ordered list is the complete output from one request. The callback must retain that immutable list and revalidate it, along with claim, configuration, playthrough, actor and active interaction scope, at both `pre_model` checks and both transaction checks. It must reject a list mixed across replies. Increasing event IDs reject reversal but do not prove reply membership; MP has no reply-group identifier of its own. This API is not a public HTTP endpoint.

The current Private Conversation 0.1.8 integration checks exactly the v1 constant and invokes only `mindPoisoningEvaluateReflection()`. It has not adopted v2. Using full-reply reflection requires a separate compatible companion change that captures and revalidates the full list. Do not infer that v2 is active merely because Mind Poisoning 0.1.15 is installed.

An `emitted` or `spoken` source row and the final ACK establish a registered line-attempt sequence. They do not prove that earlier lines were heard or that audio playback completed. V2 validates parsed source bodies against registrations; its companion must still establish complete-reply membership and matching captured output.

## Other fixes

- Reflection ACKs accept the configured Player name or the supported `Player`, `the Player`, `Dragonborn`, and `the Dragonborn` listener aliases even when an NPC catalog row has the same name. Other NPC names and `explicit_disable_rechat` are still rejected as ACK listeners; the source itself must retain the explicit sentinel target.
- Pair and reflection subject matching share conservative aliases: the name before `the` (such as `Aela` in `Aela the Huntress`), a terminal bracket suffix removed, or the first word of a qualified name when it has at least three letters. Ambiguous aliases, Player collisions, title stopwords, and the event speaker/listener are excluded. Subject identity and alias matching are repeated under the actor lock; catalog changes that invalidate a selected subject or alias reject the write. A full canonical-name match can still commit when only its short alias becomes ambiguous.
- Reflection constructs its normal request logger when the optional logger is omitted and continues if construction fails. It attempts one terminal summary when logging is available; expected skips remain informational, while malformed ACKs and source mismatches are warning-level rejections.

## Candidate packages

Use one package route per CHIM server; replace older enabled packages instead of stacking them. Check the release's `SHA256SUMS.txt` after publication. Existing v0.1.13 installs first need the historical [CHIM-Plugins v0.1.14 bridge](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.14) before their update channel can move to this deployment repository. See the tagged [deployment and migration guide](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/blob/mind_poisoning-v0.1.15/docs/deployment-migration.md) and [integration API contract](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/blob/mind_poisoning-v0.1.15/docs/integration-api.md) for migration steps and exact API fields.

- [Repository archive for Plugin Manager](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.15/mind_poisoning.tar.gz)
- [CHIM sync package](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.15/mind_poisoning-0.1.15.dwpkg)
- [Plain MO2 import ZIP](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.15/mind_poisoning-0.1.15-mo2.zip)
- [Release page and checksums](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/tag/mind_poisoning-v0.1.15)

The CHIM compatibility reference remains `cf5030f15781637498be86debe26fcf102f5690d`; it is not an installation pin. The official CHIM catalog entry has not been submitted or approved.

## Verification and limits

On merged source revision `492579ca1c1ce42e0cbceb23a8ef595f4dde62e9`, isolated PHP 8.2.29 fixtures passed for ordinary runtime routes, full-reply reflection, and the v1 observer/importer path with the embedded Private Conversation 0.1.8 snapshot. The importer fixture does not invoke PCV's real registration or ACK route. The fixtures use in-memory stores and temporary paths; they do not connect to PostgreSQL, call a live model provider, install the package, or test Skyrim or audible playback. They do not prove a companion can obtain complete reply membership or that source rows correspond to heard subtitles. PRE-ALPHA remains the correct maturity label.
