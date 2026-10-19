# Intimacy arrangements: feelings did not become simpler

Liking somebody, sleeping with them and agreeing what any of that means are different questions. Mind Poisoning keeps the first two pieces of relationship data CHIM already has—affinity and emotional type—and adds a separate, optional record of an NPC's **own reported intimacy arrangement**. No affection points are awarded for completing the paperwork.

## What the record means

| Report | Meaning |
| --- | --- |
| `casual` | The NPC reports an ongoing non-romantic intimacy arrangement with this person. |
| `romantic` | The NPC reports an ongoing romantic intimacy arrangement with this person. |
| `none` | The NPC explicitly reports no current arrangement, including an ending. |
| No record | Unknown. An empty file is not a vow of chastity. |

Each record belongs to one NPC and one exact counterpart. The other person's opinion is separate. Neither a record nor matching records establish exclusivity, mutual agreement or willingness right now.

Affinity and emotional type do not change merely because an arrangement is recorded. An NPC can dislike someone and still report casual intimacy with them. `romantic` here describes the reported arrangement; it does not replace a CHIM emotional type with `romantic`. Existing gossip can separately affect feelings under its ordinary rules.

## How recognition works

This is automatic recognition, once enabled; there is no per-character arrangement form to fill out.

- An NPC speaking directly to an exact partner can describe an **existing current arrangement**. The source line must prove who spoke and whom it addressed.
- A compatible registered Private Conversation reflection can describe the reflecting NPC's own arrangement with a named, resolvable person. Ordinary context injection does not become a reflection receipt.
- Wishes, invitations, hypothetical plans, quotations, third-party gossip and the Player's claims are not grounds for an arrangement; the classifier is instructed to leave them unchanged. “I wish we were lovers” is not a signed tenancy agreement for somebody else's feelings.
- An explicit ending or revision can update the same record. Repeating the exact acknowledged source is a retry; a new spoken line remains new evidence.

Recognition uses the configured model. Exact source/evidence checks constrain its answer; they cannot prove the model understood irony, tense or a particularly exhausting situationship correctly. A proposal or joke misread as a current arrangement is a bug worth reporting.

### A small example

Suppose A's affinity toward B is **−15**, the emotional type is **suspicious**, and A clearly describes their current arrangement as casual. The intended result is:

| Field | After recognition |
| --- | --- |
| A → B affinity | −15, unchanged by recognition |
| A → B emotional type | suspicious, unchanged by recognition |
| A → B arrangement | casual, as A reports it |
| B → A arrangement | unknown unless B supplies their own qualifying report |

A later explicit ending removes A's active arrangement lane. “I wish we had that arrangement” should leave it unchanged. In a registered reflection, A must identify B by a resolvable name; a direction or pronoun alone is insufficient. These are intended model interpretations to test, not guaranteed outputs.

### Enable it deliberately

Recognition is **off by default**. The server operator can set CHIM's general setting `mind_poisoning_intimacy_enabled` through its existing administrator-managed settings/bootstrap mechanism:

```php
chimSetGeneralSetting('mind_poisoning_intimacy_enabled', true);
```

This is PHP for the server operator, not a Skyrim console command. Mind Poisoning adds no settings-write URL. Use the same supported configuration workflow as other CHIM general settings; do not paste arbitrary code into a game conversation.

Direct NPC speech can add **one synchronous configured-model request per eligible acknowledged line**, including lines that contain no third person's name. ScriptQueue can split one reply into several lines; this is not one call per conversation. It does not multiply by witnesses. Registered reflection extends its existing evaluation instead of adding a second classifier call; recognition enables the existing 4,096-output-token budget for the enlarged reply. Actual usage and price depend on the provider. With recognition enabled, a fresh registered reflection may still make that one call when its affinity basis was already processed, so it can record a new arrangement or ending. An exact source retry still skips; a reflection with no resolvable subject still makes no call. Short split lines may not contain enough evidence to recognize an arrangement.

Turning recognition off stops new classifications; it does not delete saved records. Back up database/configuration before enabling experimental behavior on a real server.

## What SHARMAT needs

The optional SHARMAT integration requires the arrangement-API consumer in our upstream contribution. Installing Mind Poisoning alone does **not** update SHARMAT or make OStim start.

SHARMAT has its own independent, default-off `use_intimacy_arrangements` option in that contribution. A validated active exact-pair record can satisfy the ordinary relationship-type/liking portion of eligibility. Negative affinity is therefore possible without changing that number. Other applicable SHARMAT rules, current decisions, target restrictions and scene requirements still apply. Eligibility is permission to consider an action, not proof that its recipient agrees or the animation actually ran.

Casual reports also provide non-romantic context and protect against automatic-crush conversion in the submitted consumer. An intentional later relationship change remains possible. An ending removes the additional arrangement eligibility; ordinary SHARMAT policy still decides whether a later advance is possible. It is not a permanent blacklist.

Private Conversation continues to own scene direction and reflection registration. Sworn & Scorned owns promises and grievances. Neither is rewritten to share a new relationship engine. Mind Poisoning owns the reported arrangement; SHARMAT owns action policy. Our organizational chart is, regrettably, more stable than the relationships.

## Where it lives, and what reload does

The bounded records live in Mind Poisoning's existing plugin data, outside CHIM's core relationship edges. The inspected CHIM editor rebuilds those edges from known fields and would discard an extra plugin field there. Existing core edits should not erase this separate namespace.

Arrangements follow **the restored plugin timeline under both restore policies**. With `NEVER_CLEAR_RELATIONSHIP_DATA` enabled, core affinity/type may remain newer while the restored arrangement is older. This feature does not promise an independent, permanent arrangement history. A new scope starts without importing old arrangements; nothing is inferred from affinity, emotional type or old notes.

Missing/pruned source evidence, identity uncertainty, malformed data or the wrong scope cannot provide SHARMAT's additional eligibility. There are at most 64 current counterparts per NPC; updating or ending an existing one remains possible at that limit. The existing bounded decision history records transitions; it is not a complete diary.

Keep a pre-update database/configuration backup if you may roll back. Older MP code does not understand the new intimacy receipt kind; replacing only the code with an older version can leave that version refusing the newer ledger. Turning recognition off is supported and preserves saved state; it is different from downgrading the code. Do not edit receipts by hand to force a rollback.

## Check what happened

Use **Plugin Page → Interactions** to distinguish what the model proposed from what was confirmed saved. Arrangement changes are separate from affinity and type changes. A zero affinity delta can accompany a saved arrangement; an unconfirmed commit must remain uncertain. Older companion summaries may still report zero opinion change because affinity/type did not change; the MP arrangement details describe the separate saved state.

The dashboard remains read-only. Prompts receive bounded, owner-attributed context, including whether the source was private reflection. That context does not make another NPC aware of a private thought. Normal logs use fixed codes and metadata, not dialogue excerpts. Use **Logs → Download filtered plugin log** for a report; include the versions, recognition setting, speaker, exact counterpart, expected state, displayed outcome and before/after arrangement. Review exports before sharing.

## Evidence limits

This feature is experimental. Deterministic fixtures can verify source binding, parsing, ownership, atomic writes, retries, restored state and the SHARMAT code path. They do not prove the real model's semantic accuracy, an installed-game response, recipient willingness or OStim playback. Those need a separately authorized gameplay test with a database backup or isolated server.
