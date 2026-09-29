# Adversarial pattern audit — 2026-09-29

## Scope and evidence

Read-only review of v0.1.8 source at bbf55fc (release implementation dd35974), installed CHIM compatibility revision cf5030f15781637498be86debe26fcf102f5690d, existing test evidence, and the official guide source at https://github.com/Dwemer-Dynamics/DwemerDynamics/blob/main/chim/modders-guide.html. Three retained reviewers covered storage, hook/platform integration, and diagnostics/model boundaries. Lead reviewed the relevant plugin paths and installed hook ordering, connector switch mapping, and catalog lookup.

No product edits, installed changes, provider calls or database writes. No new tests were run: these are source findings and conditional failure paths, not reproduced runtime incidents. Database-level timeout settings remain unknown. Unrelated dirty task notes and untracked critique/submission drafts were preserved.

## Existing pattern

Distinct Player/NPC source correlation feeds shared evaluation. Model output is constrained before an atomic affinity/ledger/history write. Identity and generation checks occur at more than one time boundary. Logging, dashboard parsing and HTML rendering independently filter or escape data. These are useful controls; repetition alone does not justify consolidation.

## Findings that survive challenge

### 1. ACK failure diagnosis: confirmed missing detail; worthwhile small correction

Evidence: server/prerequest.php:413-431 maps JSON parse errors, non-object root and missing/invalid fields to invalid-payload. Existing tests/runtime_test.php:1323-1330 asserts the generic outcome, not distinct causes.

Assumption: diagnosing real client incompatibility requires knowing which validation failed. Counterargument: logs already report preflight, byte count and model-not-called; full payload logging would expose speech. Neither defeats finite fixed subreason codes, which can distinguish JSON/root/field/ID failures without content. Failure point: strict rejection works but the operator cannot determine what contract failed. Recommendation: retain strict validation and existing status, add bounded content-free reasons and targeted malformed-input checks. This does not establish what caused the six historical rejected requests.

### 2. Persistence wait bounds: confirmed plugin omission; conditional runtime impact

Evidence: server/store.php:940 uses FOR UPDATE; beginForListener at 957 uses pg_try_advisory_lock then BEGIN, without an explicit transaction-local lock/statement timeout. The dashboard already applies a statement timeout at dashboard_data.php:418. Provider I/O timeout in model.php does not cover database work.

Assumption: a noncooperating writer can hold the listener row. Counterarguments: normal RelationshipLLM uses the same advisory key; database-level timeouts might already be configured. Thus indefinite waiting on this installation is not proven. Failure point: passing the advisory try-lock is not proof a later row lock is immediately available. Recommendation: verify inherited settings, then bound plugin-owned transaction waits with SET LOCAL if needed, preserve stricter existing limits, log the timeout distinctly and prove rollback in an isolated two-connection contention test. Never change a shared connection's persistent settings indiscriminately.

### 3. Synchronous model evaluation: confirmed architectural limitation

Evidence: installed main.php:1125 loads prerequest before processor/comm.php at 1132; plugin prerequest.php:562 performs the model call before persistence. CHIM's relationship_system/postrequest.php queues work, and worker.php processes it separately. The official guide presents that pattern for expensive processing. Player postrequest runs after output flushing (main.php:2940-2943), so its visible delay is not established by hook placement alone.

Assumption: ACK processing should not wait on the extra model request. Counterargument: inline evaluation has simpler ordering, and no measured gameplay delay is available. The existing core queue is not a generic drop-in event queue. Failure points for a worker redesign: stale playthrough/generation, changed event identity, duplicate jobs, worker death, retry costs, delayed opinion changes. Recommendation: treat background processing as a justified design candidate; measure first and preserve those contracts before implementation. A worker is not a small cosmetic refactor.

### 4. Plugin-specific pause: useful operational feature, not a safety defect

Evidence: prerequest.php:241-244 gates connector availability through RELLLM_CONNECTOR; installed lib/settings.php:60-71 maps it to RELATIONSHIP_SYSTEM_ENABLED. The plugin has no own enable setting. Existing interaction-generation and restore-policy gates remain effective.

Assumption: users should be able to stop gossip influence while retaining ordinary CHIM relationships. Counterargument: the shared switch and uninstall already stop future work. Those alternatives do not offer that independent control. Failure point in a naive implementation: an in-flight model result writes after pause. Recommendation: consider a plugin-only pause that is checked before model work and before persistence; retain all current global gates. Do not turn the read-only dashboard into an unauthenticated configuration writer.

## Conditional or upstream findings

- **Concurrent duplicate model spend:** prerequest.php:380/494 checks dedupe before model.php is called, but the lock and authoritative ledger recheck occur during persistence. Two overlapping copies can both pay; sequential copies are already blocked and double application is guarded. Assumption: overlapping retries occur. Counterexample: serialized delivery removes the risk. An event-scoped in-flight claim would need expiry/crash semantics; defer until concurrency evidence justifies it. Never hold a database transaction across provider I/O to fix cost duplication.
- **Other writers can overwrite newer edges:** normal RelationshipLLM shares the advisory lock, but RelationshipManager parseChanges/setRelationship can read and later replace a complete relationship map through NpcMaster::updateByArray. A stale map can overwrite a newer edge if calls overlap. No overlap was reproduced. This is already documented, not a newly discovered incident; the complete remedy belongs in cooperating core writers/atomic updates, not a private plugin lock they ignore.
- **Multi-plugin catalog identity:** installed ui/server_plugins.php:368-380 returns the first entry whose repository OR package name matches; :463-467 consumes its channels. Two different plugins under Francisco-boop-001/CHIM-Plugins can therefore select the first same-repository entry. Current one-entry catalog is a strong counterexample to a present failure, but not to the future multi-plugin risk. Require exact package identity to take precedence upstream before adding a second shared-repository catalog entry. Version-pinned downloads do not fix identity selection.

## Ideas rejected as automatic fixes

- A hard repeated-gossip cap is absent, but +/-5 per judgment, +/-100 affinity, exact-event dedupe and eight prior relevant judgments already exist. Different events can still accumulate influence. A cooldown can also suppress genuinely new evidence and intended persuasion. This requires a gameplay-policy decision; it is not automatically a defect.
- Evidence substring validation cannot prove a model's reasoning or a hearsay claim true. A minimum quote length or model-supplied confidence score would not establish that either. Keep the distinction between structurally valid judgments and semantically sound reactions; evaluate the latter with controlled provider/game cases.
- Generic health panels would overlap current request/model/commit records. Absence of records remains ambiguous with best-effort logging and bounded log tails; do not label silence as proof of health or failure.
- Save isolation is not silently missing: unprofiled is explicitly shared-server state; active profiles use scoped identity and runtime leases. Another private save-ID system is not justified without a reliable CHIM identity contract.
- No generic transaction framework, validation rewrite, new database or file splitting is justified by this audit.

## Recommendation

First improve ACK subreason diagnostics and establish safe database wait bounds. Consider independent pause as a small operational feature. Plan background processing separately, with timing evidence and lifecycle acceptance criteria. Track shared-writer/catalog issues as upstream constraints. Preserve PRE-ALPHA status until actual provider/game acceptance exists; no version or compatibility pin changed.
