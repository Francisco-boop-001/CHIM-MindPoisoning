# Written-evidence API v1

Introduced in Mind Poisoning 0.1.27, hardened in 0.1.28 and unchanged in 0.1.29, this optional server-to-server interface lets a compatible producer submit one confirmed written-message exposure. It changes only the NPC recipient's ordinary opinions. It is not an inbox or letter-delivery feature; it does not prove who wrote or paid for a message, notify absent actors, or establish an intimacy arrangement. A compatible producer is required and is not included in the MP package.

## Public entrypoints

Load `server/written_evidence.php`, namespace `ChimMindPoisoning`. The namespaced constant `MIND_POISONING_WRITTEN_EVIDENCE_API_VERSION` is 1.

- `writtenEvidenceCapability(): array` returns `version=1`, `input_type=written_evidence`, `source_event_type=info`, `source_marker=written_evidence_v1`, `max_body_codepoints=2000`, `max_body_bytes=8000`, `recipient_only=true`.
- `writtenEvidenceStatus(int $eventId, ?StoreDb $store = null): array` rereads the exact source, identities, scope, collision candidates and recipient receipt. It is SELECT-only and does not initialize storage, baseline state, load/decrypt credentials or call a model.
- `enqueueWrittenEvidence(int $eventId, ?StoreDb $store = null): array` validates the source and registers only its event ID in a request-local hint list. `pending/written_hint_registered` means a volatile hint, not a durable job.
- `evaluateWrittenEvidence(int $eventId, ?StoreDb $store = null, ?callable $requestModel = null, ?RequestLog $requestLog = null): array` performs recipient evaluation and the normal atomic opinion/receipt/history write.
- `drainWrittenEvidenceHints(?StoreDb $store = null, ?callable $requestModel = null): array` attempts at most one hinted source. Empty hints return an empty array without opening a store. The MP postrequest hook drains after ordinary work and never during INIT.

An optional model substitute has the exact shape `callable(array $messages): string`. It returns raw JSON to the production judgment parser; it does not bypass source, receipt, identity or transaction checks. Normal callers omit it and use MP's existing connector. Model calls occur outside the database transaction.

The normal native adapter also accepts an existing trusted server connection: `new PostgresStoreDb(?RequestLog $requestLog = null, mixed $trustedPgConnection = null)`. A supplied PostgreSQL connection must be healthy and idle. It is never taken from a request, DSN or user payload, and does not require changing global database state. Omitting it preserves the existing core-helper path. Additional source-read methods are an optional `WrittenEvidenceStoreDb` capability; existing `StoreDb` implementations remain valid for ordinary speech.

## Exact source and trust boundary

Only the physical eventlog pair `type=info` and `sess=written_evidence_v1` is evidence. The logical input/source kind remains `written_evidence`. Ordinary info and the former provisional custom event type are rejected. Core's generic history readers exclude info; an unknown custom type can leak into unrelated histories. The producer must block client submissions of the reserved marker before core logging and guarantee unique server publication.

The event's people field is exactly the recipient's canonical name between pipes. Its data is the strict v1 envelope: schema version, document UUID, current scope, NPC recipient, claimed NPC/Player sender, original body, confirmed exposure receipt, and confirmed attachments. Unknown or missing fields and incorrect types are refused. UUIDs are canonical lowercase strings; profile scope is `unprofiled` or a canonical positive numeric string. Bodies are bounded by both 2,000 code points and 8,000 bytes. Attachments are bounded, exact stable form IDs with positive quantities; canonical gold identity and kind must agree.

The canonical JSON fixture belongs to the producer at `contracts/written-evidence-v1.json`, SHA-256 `194da266679a0e21c823659a59279b46dcca2eeed2df13f371bbd0bde4a5c623`. The fixture bytes were unchanged by the physical carrier correction. A privileged eventlog writer remains trusted; the marker is not a cryptographic attestation.

Identity-conflict scans use 16-row keyset pages and a three-second total time budget checked between synchronous reads. A read already in flight can finish after the budget; if the deadline has expired, the result is unavailable and a partial scan is never treated as conflict-free.

The recipient must resolve uniquely to a current NPC and must not collide with Player aliases. Claimed sender attribution remains uncertain. That sender can be an opinion subject even when only the envelope names them; third-party candidates must resolve from the original body using MP's conservative name rules. The recipient is excluded. Existing recipient personality and prior relationship context inform evaluation. A confirmed gift can support gratitude if the model accepts that interpretation; no affinity bonus is automatic.

## Persistence and restore

Normal bounded affinity/type changes, a processing receipt and the NPC history snapshot commit together under MP's existing owner lock. Exact source bytes, identities, candidate resolution, scope, INIT boundary, interaction generation, pause and connector state are rechecked. No utterance ID, physical speaker, addressed listener or reflection registration is fabricated.

Written receipts are separate from the 128-event speech journal and its floor. The per-owner `written_receipts` map holds at most 1,024 document identities, with no eviction. At capacity, new processing is refused. Within a playthrough, one canonical `document_id` is bound to one source event and recipient: an exact retry of that source can return its recorded receipt, but re-reading or exposing the same document in another event or to another recipient is a source identity conflict. A genuinely new exposure needs a new document ID. Each receipt binds event/document/receipt/operation/scope/recipient and an internal source digest; its confirmed outcome is `committed` or `zero_change`. Zero and no-subject decisions are legitimate receipts. Affinity is never used to infer prior processing. An exact retry differs from a new document repeating the same story.

The receipt map follows restored MP state. With relationship preservation disabled, a surviving in-scope source may remain eligible even when it predates INIT; the captured INIT must still remain unchanged through persistence. With preservation enabled, the source must be after the current INIT, and the existing scope-baseline policy applies before evaluation. A missing, pruned or replaced source cannot report processed merely because a receipt survived. Producer-private publication anchors and their restore handling remain the producer's responsibility.

## Results, scheduling and diagnostics

Public results use fixed `status` and `reason` fields. Statuses are `invalid`, `pending`, `queued`, `paused`, `failed`, `abstained` or `processed`; this implementation registers volatile hints as pending rather than claiming queued work. Status/capability do not check credential readiness. A processed result requires a fresh matching source and confirmed receipt; an attempted unconfirmed commit is a failure, never a success or a guaranteed rollback.

There is no new background service. Hints are bounded to three event IDs per request, and one drain attempts one source. The producer must re-enqueue pending eligible sources after its exposure transaction closes. Its oldest-pending selection prevents a newest-letter rendering window from starving older letters under ordinary finite workloads. There is no independent retry without a later recipient prompt or explicit enqueue; a repeatedly failing oldest pending source can block progress until addressed.

Written diagnostics retain fixed source/event/scope/recipient identifiers, bounded actual changes, timings and truthful commit state. A preflight stop has its own fixed terminal diagnostic and explicitly reports that model and persistence were not attempted; it is not an applied-interaction card. They omit body, prompts, model prose, digests, credentials, sender-presence claims and private provenance, including in debug mode. The dashboard renders recipient-owned log results without invented speech correlation. Missing retained persistence records remain unavailable; a terminal record alone is not proof of the applied delta.

## Evidence limits

Standalone deterministic tests exercise production parsing, evaluation and persistence with a synthetic store. A retained native integration fixture also passed through the production MP adapter/evaluator and a disposable database boundary using a development producer candidate. It made one deterministic model callback and zero provider calls; replay did not call the model again. A development producer in a fixture is not a public companion release. These checks do not prove real-model quality, installed end-user behavior, message delivery/reading, or gameplay. No live game or installed mods were used. See the [0.1.29 verification summary](verification-v0.1.29.md) for current evidence and release limits.
