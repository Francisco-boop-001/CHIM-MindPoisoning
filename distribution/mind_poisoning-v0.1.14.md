# Mind Poisoning v0.1.14 — PRE-ALPHA candidate

This candidate adds an explicit version contract for the optional Private Conversation reflection integration. The deployment manifest now identifies the dedicated Mind Poisoning repository; see the [deployment and migration guide](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/blob/mind_poisoning-v0.1.14/docs/deployment-migration.md) before updating an existing install.

## Companion API and diagnostics

- `server/reflection.php` declares `\ChimMindPoisoning\MIND_POISONING_REFLECTION_API_VERSION = 1`. Compatible companions check for this exact version before evaluation work.
- The v0.1.13 optional request observer is retained for companion integration. A companion may attach it to the reflection evaluator's request-local `RequestLog` while Mind Poisoning keeps its ordinary evaluation and sink delivery.
- The observer gets an allowlisted record and fixed level, including the validated `config_id`, `event_id`, and `utterance_id` for correlation. Its projection omits opt-in debug `model_reason`; raw dialogue, prompts, speech digests, claim tokens, and exception text are not forwarded.
- The observer is best-effort, installed on one logger instance, and does not replay prior records. It is not a durable event bus. The existing diagnostic sink retains its prior opt-in debug behavior.

See the [integration API](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/blob/mind_poisoning-v0.1.14/docs/integration-api.md) for input, registration, importer, and reason-code limits.

## Focused evidence and limits

The evaluator → observer → Private Conversation importer fixture covers committed changes, confirmed zero change, provider failure, unconfirmed commit, and a warning-level skip using an in-memory store and temporary log paths. The API registry fixture and metadata checks also pass. These isolated checks do not establish live database/provider behavior, installed extension order, or game/audio delivery.

Mind Poisoning remains **PRE-ALPHA**. Its compatibility reference is unchanged and is not an installation pin. The official CHIM catalog entry has not been submitted or approved. Use one package route per CHIM server; replace older enabled packages instead of stacking them.

Release assets are published under [CHIM-MindPoisoning](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/tag/mind_poisoning-v0.1.14). Existing v0.1.13 installs use the versioned old-hub bridge package at [CHIM-Plugins v0.1.14](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.14) before the update channel moves to the new repository. Verify checksums from the selected release.
