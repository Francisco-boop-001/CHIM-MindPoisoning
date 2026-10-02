# Solo reflection integration API

`server/reflection.php` exposes the opt-in evaluator below. Loading it does not register a hook or call the model.

The API declares `\ChimMindPoisoning\MIND_POISONING_REFLECTION_API_VERSION = 1`. Private Conversation 0.1.6 checks for exactly version 1 before using the reflection evaluator; a missing or unsupported version is treated as unavailable. This check is a companion compatibility contract, not a CHIM core pin.

The evaluator function, `StoreDb`, and `RequestLog` are in namespace `ChimMindPoisoning`; import them or use their fully qualified names. Mind Poisoning never loads Private Conversation. A companion such as PCV attaches the observer and passes its `RequestLog`; Mind Poisoning continues its normal evaluation and ordinary sink delivery.

```php
use ChimMindPoisoning\RequestLog;
use ChimMindPoisoning\StoreDb;
use function ChimMindPoisoning\mindPoisoningEvaluateReflection;

mindPoisoningEvaluateReflection(
    array $registration,
    array $gameRequest,
    StoreDb $store,
    callable $revalidate,
    ?callable $requestModel = null,
    ?RequestLog $requestLog = null
): string
```

The registration has exactly these keys: `event_id`, `utterance_id`, `actor_id`, `actor_name`, `playthrough_id`, `config_id`, `rechat_target_hint`, and `speech_hash`. It identifies one positive event ID and `utt_` ID, the NPC owner, active playthrough/configuration, the `explicit_disable_rechat` sentinel, and the SHA-256 digest of the acknowledged subtitle. The game request must be the matching native `_speech` ACK with `speaker`, `listener`, `speech`, and `utterance_id`. The evaluator checks the exact emitted/spoken source event and subtitle; it does not select a row by recency or text similarity.

`$revalidate($registration, $phase)` must read the companion's private registration and return whether the same scope is still active. It is called at `pre_model` and twice during `transaction`, so it must be read-only and safe to repeat. `$requestModel` is a test/integration seam; if omitted, the normal Mind Poisoning request function is used. The return value is an evaluator status string; it does not replace the structured request log.

## Full-reply reflection API (v0.1.15)

The Mind Poisoning 0.1.15 package adds the opt-in `MIND_POISONING_REFLECTION_REPLY_API_VERSION = 2` and `mindPoisoningEvaluateReflectionReply()`. The v1 constant and function above remain unchanged, and existing v1 callers continue to use version 1. The current embedded Private Conversation 0.1.8 integration also checks exactly v1 and calls only `mindPoisoningEvaluateReflection`; it has not adopted v2. A companion must make a separate change to capture and revalidate a complete reply before it can call this API. See the [0.1.15 release notes](../distribution/mind_poisoning-v0.1.15.md) for candidate limits.

```php
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

The top-level registration has exactly the eight v1 keys (`event_id`, `utterance_id`, `actor_id`, `actor_name`, `playthrough_id`, `config_id`, `rechat_target_hint`, `speech_hash`) plus `lines`. The top-level event, utterance, and hash identify and equal the final tuple. `lines` is a PHP list of 1–8 exact-key tuples `{event_id: positive int, utterance_id: valid utt_ string, speech_hash: lowercase SHA-256}`. Event IDs increase strictly and utterance IDs are unique. Numeric ordering rejects reversal but does not establish reply membership. The API accepts no caller dialogue, client-supplied group, recency lookup, or synthesized ACK.

The companion's server-only callback receives the complete original registration. At both `pre_model` checkpoints and both `transaction` checks, it must confirm the same claim, configuration, playthrough, actor, active interaction scope, and complete immutable ordered output list captured from one companion request; it must reject a list mixed across replies. MP has no source-backed reply-group identifier and cannot independently prove that grouping. After callbacks, MP re-reads each source; it also checks every source under the actor lock before commit. Missing, aborted, changed, or mismatched rows fail closed; an `emitted` to `spoken` transition remains valid.

For v2, MP resolves every tuple through `StoreDb`, parses the existing explicit `explicit_disable_rechat` source, trims its UTF-8 body, and requires the registered digest to equal SHA-256 of that parsed body. V1 retains its existing contract: the native ACK speech digest must match the single-line registration, while v1 does not compare that digest with the parsed source body. V2's native `_speech` ACK must match the registered actor, supported Player transport listener, and final tuple; an earlier valid ACK returns the fixed informational `reflection-non-final-ack` skip. MP joins source bodies in tuple order with one ASCII space. The joined text is limited inclusively to 2,000 Unicode code points and 8,000 bytes; it is rejected without truncation. A valid base registration with more than eight lines returns `reflection-reply-too-many-lines`; text overflow returns `reflection-reply-too-large`.

Replay checks cover every registered event ID and utterance ID against the existing ledger and numeric eviction floor before provider work and again inside the transaction. A confirmed reply records all tuples in the existing bounded 128-entry ledger in one atomic relationship write and history snapshot; earlier entries have empty judgments and the final entry carries the reply decision. Confirmed zero decisions still record every tuple. No table or `StoreDb` method was added. V2's persisted source digest hashes the joined parsed bodies; terminal diagnostics remain correlated to the final event and utterance. A false or throwing commit remains failed/unconfirmed.

When `RequestLog` is omitted, the shared evaluator creates the normal request logger; when supplied, it reuses that instance and preserves its observer delivery. Each invoked evaluation attempts one terminal `request_finished` summary. Expected skips such as non-final ACK, replay, locked actor, or no eligible subjects are informational. Malformed registration/ACK, source mismatch, and reply caps are warning-level rejections. Provider/internal and persistence failures are errors, including an unconfirmed commit; invalid model responses are warnings. The sanitized schema contains fixed reasons and bounded correlation/status data, not dialogue, source hashes, claim tokens, or exception messages. Delivery is best-effort: logger construction or sink failure cannot change the evaluator result, and a failed sink can drop a record. PCV must log attributable reflection exits before it calls MP; ordinary or unregistered ACKs that never reach MP do not create MP records.

The focused source check is `php tests/reflection_diagnostics_test.php`; the v2 behavior and pinned v1 importer checks are `php tests/reflection_reply_test.php` and `php tests/reflection_observer_test.php`. These use isolated in-memory fixtures. They do not prove companion grouping/subtitle correspondence, live provider behavior, PostgreSQL durability, installed extension order, or audible playback.

## Optional request observer

`RequestLog::observe(?callable $observer): void` installs one callback on that `RequestLog` instance. Each accepted log record is delivered as `(array $record, string $level)` after the normal sink attempt. A second call replaces the callback; `observe(null)` clears it. Records emitted before installation are not replayed. Observer failures are swallowed and cannot change evaluation or persistence, and reentrant observer delivery is suppressed.

The observer receives the sanitized record and fixed level. Validated `config_id`, `event_id`, and `utterance_id` are deliberately included for exact correlation; they are identifiers, not proof of a speaker or listener. Debug `model_reason` is removed from this observer-only copy. The existing diagnostic sink can still receive that bounded, untrusted text when debug logging is enabled; it may contain names or a short game/model excerpt. The observer/importer boundary does not forward raw dialogue, prompts, speech digests, claim tokens, or exception messages.

## Private Conversation revision 2 import (published in 0.1.5)

The importer accepts only `source_kind=reflection` records for `reflection_model_finished`, `persistence_finished`, `persistence_cleanup_failed`, or `request_finished`, with level `debug`, `info`, `warning`, or `error`. It then checks the event-specific outcome and level before writing a fixed Private Conversation record. Unrelated evaluator events and invalid level/outcome pairs are ignored.

| Mind Poisoning evidence | Imported result |
| --- | --- |
| `reflection_model_finished`: valid/info, invalid/warning, failed/error | `reflection.model_finished`: valid/info, invalid/warning (`model_invalid`), failed/error (`model_failed`) |
| Confirmed commit with changes | `reflection.persistence_finished`: committed/info with bounded change tuples |
| Confirmed commit with `changed_count=0` | zero_change/info, reason `zero_change` |
| `commit_state=unconfirmed` | unconfirmed/error, reason `commit_unconfirmed`; this means COMMIT was attempted without confirmation and does not prove that no database change occurred |
| Cleanup failure | cleanup_failed/error; a confirmed commit remains recorded as confirmed |
| Final rejected/failed/skipped result | Rejected at warning, failed at error, and skipped at info. A skipped final summary at warning maps to rejected/warning. |

Every imported record must carry the source's 24-character lowercase `request_id`, a positive event ID, an accepted utterance ID, and the matching configuration ID. The importer writes the source request ID as `linked_request_id` and pins `event_id` plus `utterance_id` in the current PCV request correlation. A conflicting configuration or event/utterance tuple is dropped. This hook is not a general join API: it correlates one solo-reflection tuple, and no pair-evaluation correlation contract is implemented here.

Mind Poisoning validates `config_id` as a generic UUID shape, without enforcing UUID version or letter case. The PCV importer requires lowercase UUIDv4. IDs generated by the PCV registration path satisfy that stricter check; a generic valid MP UUID may be ignored by the importer.

The importer chooses `reason` before `persistence_reason` for `source_reason`. A final `request_finished` record's `reason` is the aggregate terminal status, so its imported reason can be broad (`committed`) or `other`. Use the earlier `persistence_finished` record for the specific fixed persistence reason and commit details; that imported event is the actionable persistence record.

## Isolated cross-boundary check

From `projects/CHIM-MindPoisoning`, run `php tests/reflection_observer_test.php`. The MP 0.1.14 source contains a pinned PCV 0.1.6 integration snapshot; the fixture invokes the actual Mind Poisoning evaluator and observer, then that PCV importer, with an in-memory store and isolated temporary log directories. It covers a changed commit, zero change, provider failure, unconfirmed commit, and a warning-level pre-model skip. It does not load the production bootstrap, use a live provider, or connect to a database.

The standalone PCV 0.1.6 `tests/reflection_registry_check.php` needs the matched MP 0.1.14 source/API fixtures. Run it with that companion checkout available, or run the pinned integration check from the MP 0.1.14 tree. A PCV-only clean source export cannot supply Mind Poisoning's evaluator or test fixtures; runtime packages contain only their own allowlisted server files and never include the other plugin or tests. These fixtures establish source/API compatibility only, not installed extension order, live persistence, provider behavior, or game/audio delivery.
