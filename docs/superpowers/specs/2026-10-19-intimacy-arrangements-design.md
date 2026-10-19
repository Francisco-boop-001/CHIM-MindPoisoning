# Automatic recognition of directed intimacy arrangements

## Goal and scope

Recognize adult NPC declarations naturally in supported spoken dialogue and registered reflection, retain their current directed intimacy arrangement separately from CHIM emotional type and numeric affinity, and expose it to prompts, diagnostics and an opt-in SHARMAT policy. No automatic affinity bonus/cost, reciprocal edge, shared agreement, current-consent guarantee or romantic type substitution. No CHIM core/OStim edits, new table/daemon, new trait ladder, semantic dedupe or live/provider test. User explicitly delegates satisfactory design selection followed by implementation/publication/PR; retain existing runtime safety gates.

## Chosen representation

Use an optional bounded `arrangements` map in `plugin_extended_data.mind_poisoning`, keyed by canonical `player` or `npc:<positive catalog ID>`. It belongs to that NPC row only. Closed statuses: `casual`, `romantic`, `none`; absent means unknown. Casual describes current non-romantic intimacy as the owner reports it; romantic describes the owner's reported arrangement without rewriting their emotional type. None requires explicit current absence/ending. Proposed, desired, hypothetical, quoted and third-party arrangements do not change the record. Existing CHIM relation/note/best/worst are fallible context, never arrangement authority.

Bound the map at 64 entries, source groups at the existing 24-line reply cap, and record fields to a closed schema. Do not add a duplicate per-arrangement history ring: existing bounded processing history already records transitions. Store exact owner ID, target token, origin basis, immutable event/utterance source references and source digest(s), with no raw text in the current record. Exact source text/evidence remains governed by existing history privacy; ordinary logs contain only fixed fields/codes/counts. A full map refuses a new target, while updates/endings of existing targets remain possible. Malformed state is not silently repaired.

## Why plugin-owned storage

Pinned core normalization and native relationship setters preserve extra edge keys, but its relationship editor reconstructs a fixed set and the AI builder can replace edges. Therefore an extra relationship-edge field is not reliable. The existing MP namespace is included in atomic NPC/history snapshots and is not replaced by ordinary core relationship edits. No migration or new StoreDb method is required: writeNpc already accepts the MP namespace object.

Arrangements follow CHIM's restored plugin timeline under BOTH policies. NEVER_CLEAR_RELATIONSHIP_DATA can retain current affinity/type while restoring an older MP namespace. This is intentional and documented, not a promise that arrangements survive independently. Same-scope nextLedger carries validated arrangement state; new/mismatched-scope baselines clear it. Never rebuild it from affinity, type, notes, old receipts or model memory. Source/scope/identity mismatches and pruned source groups are unavailable for behavior, not authorization.

## Recognition and ownership

Global CHIM setting `mind_poisoning_intimacy_enabled` defaults false. It gates automatic classification, not a manual relationship editor. Existing Player-input gossip and listener/overhearing behavior stay unchanged.

For an ordinary NPC ACK, add a dedicated bounded classifier on a specialized exact-source preflight. One fixed addressed target is supplied by server correlation, never chosen by model output. Validate the original event, NPC speaker, exact addressee, active scope, utterance, interaction generation, pause, profile/relationship locks and source text. NPC-to-Player requires an explicit/uniquely verified source Player route; a same-named catalog NPC or ambiguous route fails closed. A literal Player source is distinguished from its transport display name; do not reuse a sentinel reflection shortcut blindly. Skip broadcast/sentinel/unregistered reflection/Player-as-speaker routes.

The direct path uses `source_kind=intimacy` and opinion_owner_id=speaker ID. Keep original source speaker/listener roles for revalidation. The transaction locks/writes ONLY the speaker owner row, atomically with its receipt and history snapshot; never store the speaker's declaration as listener-owned state. Dedicated classification makes at most ONE additional configured-provider call per eligible source line, outside locks, default off. It does not multiply by witnesses. Null/no claim still gets an exact source receipt so retries do not call the provider again sequentially; existing concurrent-ACK limitations remain explicit.

Registered reflection may extend its existing same-owner model output/judgments with an optional per-subject arrangement change, reusing the same call/transaction and all v1/v2 registration checks. No second reflection classifier call. Exact named candidates only; no-subject reflection stays no-call. Player words never establish the NPC's agreement. A reflection report remains private owner perspective, not public/shared truth.

Direct model shape is exactly {"intimacy":null} or {"intimacy":{"status":"casual|romantic|none","evidence":"exact current excerpt"}}. No model-selected identity or extra keys. Optional reflection field uses the same status/evidence with exact supplied subject keys. Validate byte/type/enum/excerpt bounds; model semantic accuracy remains a separate provider/gameplay question. Compare full captured prior state under the owner lock; older/duplicate events cannot overwrite a newer declaration. Preserve rollback and uncertain-commit reporting.

## Optional read API

Namespace ChimMindPoisoning, capability `MIND_POISONING_INTIMACY_API_VERSION = 1`.

`mindPoisoningReadIntimacyArrangement(int $ownerNpcId, string $targetSubject, ?StoreDb $store = null): array`

Only canonical target tokens accepted. Use the existing store/read boundary and active playthrough, exact owner/target/catalog identity and immutable source validation. Optional store is a deterministic fixture seam; normal call constructs the existing read adapter but must not write, baseline, initialize storage or call a provider. API returns a closed projection: `state` active|ended|none|unavailable, `kind` casual|romantic|null, `owner_id`, `target`, `playthrough_id` when known, and fixed basis/reason/source references as needed. No raw dialogue, model prose or shared-agreement flag. Missing/old MP, malformed/stale/pruned/wrong-scope or ambiguous identity never grants the new policy lane. Final action validation re-reads current state; do not authorize solely from stale prompt/schema state.

## Prompt and dashboard consumption

Use CHIM's supported prompt-injection registration (`character_bottom`, namespaced ID) to add a small actor-owned bounded projection, not arbitrary prompt globals or a new context engine. Supply at most eight current records, distinguish self-report/private reflection, say reciprocity and exclusivity are not established, and add no context for unavailable state. Keep names safely encoded as data and no credentials/raw evidence. Verify the actual renderer/caller with production helper fixtures.

Extend existing dashboard/history/log summaries to distinguish proposal, applied arrangement, zero change and uncertain commit. Show owner→subject, prior/current kind, origin and fixed reason. Arrangements do not masquerade as affinity/type changes; counters and outcomes must truthfully include arrangement-only commits. Preserve read-only/authentication/escaping/download contracts. No new write endpoint is requested.

## SHARMAT policy

Add one explicit existing-config option `use_intimacy_arrangements`, default false. When enabled, a validated active exact-pair record may replace ONLY ordinary relationship-type/liking qualification for that pair, allowing dislike and intimacy to coexist without changing affinity. Preserve all applicable protection, arousal, orientation, marriage/affair, current decision/refusal, combat, scene/framework, catalog, roster and native target restrictions. If a particular floor also implements a distinct restriction, split the predicates rather than erase that restriction. Preserve per-action Player offer snapshots: NPC eligibility never grants Player eligibility. No undefined/nonexistent API or unavailable state can grant a start.

An ended/none record removes the arrangement lane and returns to ordinary PR7 policy; it does not mean a permanent ban on every future advance. Existing explicit refusal flags remain authoritative. Casual context/crush preservation is independent of the sex-eligibility switch, as with the existing non_romantic_types designation. A romantic arrangement does not force an emotional type conversion. Native dispatch and recipient willingness are separate from outbound eligibility and server acceptance.

## Verification and delivery

Use standalone approved Windows PHP -n and synthetic data, no WSL/live DB/provider/game actions. Red-to-green must cover actual source correlation, owner locks/receipts/history, disabled baseline, parser counterexamples, direct NPC/Player ambiguity, same-scope carry-forward and restore/pruned-source behavior, exact retries and older updates, reflection v1/v2 reuse, failing writes/rollback/uncertain commit, dashboard escaping and current prompt consumption. SHARMAT tests must exercise actual offer/schema/filter for negative affinity with correct exact pair, unrelated Player exclusion, missing/old/off/ended behavior, custom/built-in controls, and all changed restrictions. No actual gameplay claim.

Publish MP as experimental BETA CANDIDATE only after complete gates; explicitly require the upstream SHARMAT arrangement consumer for its action-policy effects. Preserve existing releases and live setup. Maintain upstream PR7 compatibility work separately in its explanation; choose an incremental contribution that avoids duplicating overlapping PRs. Include exact commands, why separate state is needed, storage/editor/restore tradeoffs and the separately authorized gameplay checklist. Release assets must be verified against a clean tag before mutable update pins advance.

## Source-confirmed reflection and switch clarification

The inspected reflection basis hashes prior evidence/profile, not the newly registered reflection text. With recognition enabled, a new valid registered source can therefore require an arrangement-only result even when the affinity basis was already processed. Use the existing one request, keep affinity/type pending subjects distinct, and require delta0/no type change on already-processed subjects. Exact source retries still skip before model, including null result receipts. This changes intent tracking without manufacturing new experience for affinity. No extra reflection classifier request and no-subject remains no-call.

Recognition-off stops new classifications/writes; it does not erase or invalidate stored state. SHARMAT consumption remains an independent setting. No shared mutual-state, arbitrary time expiry, or automatic affinity price is introduced.

## Implementation review clarifications

- A direct arrangement is speaker-owned: the speaker's manual locks block its update; the addressee's relationship lock does not protect the speaker's row. Exact addressee identity is still required and the addressee is never written.
- The ordinary ACK and its additional arrangement evaluation use the existing request-log fork mechanism. Their terminal ownership/results remain distinct and batch-correlated.
- SHARMAT may cache bounded projections only during offer/schema construction; final dispatch clears/bypasses that cache and rereads current state. Production-derived call counts justify this narrow cache; the public MP API remains uncached.
- On a new scope, disabled preserve policy treats old arrangement state as absent and clears it through the existing ledger reset. Enabled preserve policy runs the existing owner baseline before model work and skips its triggering source. No identity is faked to reuse the baseline helper.
