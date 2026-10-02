# Full-reply reflection contract (implementation authority)

## Compatibility and entry point

Preserve `ChimMindPoisoning\MIND_POISONING_REFLECTION_API_VERSION = 1` and the existing `mindPoisoningEvaluateReflection` signature/registration. Add `ChimMindPoisoning\MIND_POISONING_REFLECTION_REPLY_API_VERSION = 2` and `mindPoisoningEvaluateReflectionReply` with the same argument types/order/defaults as v1: registration, gameRequest, StoreDb, revalidate, optional requestModel, optional RequestLog. This is an opt-in capability; existing PCV remains a v1 caller.

## Registration

Exact top-level keys: all eight v1 keys plus `lines`. Existing top-level event_id, utterance_id and speech_hash identify the final line and must equal its tuple. Other v1 identity/scope/sentinel validation remains unchanged. `lines` is an ordered PHP list containing 1–8 exact-key arrays `{event_id: positive int, utterance_id: valid utt_ string, speech_hash: lowercase SHA256}`. Event IDs must increase strictly; utterance IDs must be unique. Increasing IDs reject a reversed list but do not establish reply membership. No dialogue field, recency lookup, client-supplied group, or synthesized ACK is permitted.

The callback receives the complete original v2 registration, not a stripped v1 surrogate. It must verify one immutable, complete ordered output list captured from a single companion server request, the same claim/configuration/playthrough/actor and active interaction scope, at pre_model and both transaction checks. It must reject lists mixed across replies even if every source row separately matches. MP has no source-backed reply-group identifier and must not claim independent proof of group membership. The registration/callback is a server-only trust boundary; this entry point is not a public HTTP interface.

## Sources, text and trigger

Resolve every exact utterance through existing StoreDb methods and require its registered event ID, registered actor, explicit non-broadcast sole `explicit_disable_rechat` target and delivery state emitted/spoken. Parse with existing `reflectionSourceParts`; use its trimmed UTF-8 body and require SHA256 agreement with that line's registration. No raw caller dialogue is evaluated. Missing, aborted, malformed or mismatched sources fail closed before model work.

The native ACK must identify the final line, registered actor and supported Player transport listener and must have the same trimmed subtitle digest as the final registered/source line. Earlier ACKs are informational non-final skips, not evaluations. A different/malformed ACK must not become a valid non-final skip by identity alone.

Join source bodies in registration order with one ASCII space. The joined text, including separators, must contain at most 2000 Unicode code points (`mb_strlen(..., 'UTF-8')`) and at most 8000 bytes; no truncation. Source parsing retains its existing 16384-byte bound and ACK parsing retains its existing limits. Overflow gets a fixed, clear rejection reason. Evidence excerpts and model candidates use this joined text; the persistence event uses its SHA256 digest while diagnostics keep the exact final event/utterance anchor.

`emitted` is not proof of hearing or successful audio playback. A final ACK plus source rows establishes a registered line-attempt sequence, not proof that all earlier lines were heard.

## Persistence and replay

Reuse the v1 evaluator/model/context/persistence safeguards. Extend internal prepared-event handling minimally to retain a bounded snapshot of all registered sources. Revalidate every source against its captured exact identity/data/hash/allowed state after acquiring the actor lock and immediately before commit; transitions between emitted and spoken may remain valid, aborted/missing/changed sources may not. Revalidate the full companion registration at existing checkpoints.

Before provider work and under the transaction, reject if any covered event/utterance is already recorded or below the existing eviction floor. Atomically record every covered tuple using the existing bounded ledger entry format (earlier member entries can have empty judgments; the final entry carries the actual judgments); preserve legacy readers and one reply-level relationship write/history snapshot. All-zero confirmed decisions still record every member. No new StoreDb interface or database tables are needed. A false/throwing commit remains failed/unconfirmed in diagnostics.

## Diagnostics

Reuse existing schema/observer with final tuple correlation and fixed reasons only. Do not log dialogue, lists of speech hashes, claim tokens or exceptions. Provide terminal records for every invoked v2 evaluation, including caps, source mismatches, non-final ACK, replay and persistence failure. Record exact underlying persistence reasons in existing persistence events. Calls the companion never makes cannot be diagnosed by MP; companion routing remains its owner's responsibility.

## Required adversarial cases

First-line-only subject; final versus earlier ACK; malformed/different-actor early ACK; aborted/missing/hash-mismatched/foreign-actor/foreign-target source; reversed/duplicate list; callback rejects mixed-reply list; count/character caps and multibyte counting; source mutation after model and after staged write; overlap/replay through v1 and v2 including confirmed zero; uncertain commit; existing v1/pinned observer integration.
