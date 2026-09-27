# Mind Poisoning 0.1 — accepted scope and implementation design

The requested feature changes an NPC listener's CHIM affinity toward the Player or another known NPC after a spoken claim. Praise may increase affinity, slander may reduce it, and disbelief or irrelevant statements may yield zero. The existing speaker/listener evaluator remains responsible for their direct interaction. This plugin adds only the listener/subject edge.

The previous viability proposal and the user's explicit development request supply scope and execution authorization. New source stays in this repository; the distro and F: installation stay read-only. This is architectural work using the existing extension surface. No external deployment or pin promotion is included.

## Ground truth and chosen approach

- Inspected server commit `cf5030f15781637498be86debe26fcf102f5690d`; PHP 8.2.29. No existing implementation or prior plugin test evidence found.
- `main.php:1125` loads `ext/**/prerequest.php` before `processor/comm.php`. `_speech` reports playback; `_speech_abort` reports cancellation. Both terminate before postrequest. Use `_speech` only; do not infer delivery from generated text.
- `lib/chat_helper_functions.php:2014` assigns per-line `utterance_id`; `eventlog` stores the emitted row and game timestamp. Require exact ID correlation; no fuzzy dialogue match.
- Confirmed playback is CHIM's client report, not independent proof of human-like hearing. Scope is the direct named listener, not all nearby companions.
- Prefer a synchronous call during the acknowledgement request, whose existing shared runtime lease protects explicit playthrough switching. This avoids a second queue/daemon and schema migrations. It adds model latency to the callback. Provider-specific timeout support must be verified; never claim a universal wall-clock cap.
- Use canonical relationship state plus owned `plugin_extended_data.mind_poisoning` state. A transaction, existing per-NPC advisory lock and row lock must preserve core writes and atomically dedupe/apply/snapshot. The exact rollback/read guard is an implementation acceptance gate.

## Behavior and safety contract

- Handle only exact `_speech` records with one resolvable NPC speaker and one resolvable NPC listener. Require exact emitted event identity and reject aborted, missing, ambiguous, invalid or stale events.
- Recognize explicit names of known NPCs and the Player's canonical aliases. Resolve IDs before model evaluation, reject duplicate names and exclude speaker/listener as subjects. Ambiguous pronouns and unknown people are intentionally not guessed.
- Every eligible named subject receives an explicit model judgment, independent of `RELATIONSHIP_UPDATE_CHANCE`. Respect the global relationship enable setting and configured relationship connector.
- Changes per utterance are integers from -5 through +5; zero is valid. Total affinity stays -100 through +100. Preserve type and all unrelated relation/plugin fields.
- Model output is untrusted: require known subject tokens, one judgment each, bounded reason, exact quoted evidence from this utterance, and valid JSON. No model-authored SQL, executable commands, native rank changes or arbitrary relationship targets.
- Evaluate against listener's prior view of speaker and subject; include speaker bias where available. Treat a claim as hearsay; do not write it into shared world knowledge or public rumors. Instruct conservative change and zero for unsupported/repeated claims.
- Idempotency is by exact utterance/recipient. Concurrent duplicate callbacks must not apply twice. Persist a bounded event ledger with an explicit floor if needed, so eviction cannot make an older event eligible again. Tie state to restored NPC history and validate current event existence/timeline at commit.
- Manual relationship locks prevail. Failed provider, malformed output, missing state, failed DB write or failed history snapshot leaves no partial affinity/dedupe state. Catch failure at the hook boundary without breaking CHIM acknowledgement processing.

## Module boundaries

- `server/influence.php`: pure subject resolution, prompt construction and response validation.
- `server/store.php`: exact event/actor reads and transactional persistence; no model call.
- `server/model.php` (only if useful): existing LLMConnector adapter with scoped globals restored after success/failure.
- `server/prerequest.php`: thin hook composing the above; no generated-speech or abort processing.
- Package script and docs: schema4 archive, deterministic hashes, isolated actual-manager validation, exact source manifest.

No live LLM calls are authorized for tests. Fixture judgments test orchestration and validation; they do not prove model quality. Real database integration is a separate evidence tier and may be unavailable under the read-only distro boundary. A candidate can be delivered with explicit limits; it cannot be represented as in-game verified or promoted.

## Verification questions

1. Does speech from A about C change only B->C (including C=Player), in both directions and zero?
2. Are aborted/unmatched/ambiguous lines, duplicate callbacks, malformed/model-invented targets and locked listeners harmless?
3. Are affinity, dedupe state and timeline snapshot atomic on failure, and do restored/stale events fail closed?
4. Are provider globals restored and unsupported timeout paths handled explicitly?
5. Does the real package manager accept the archive in workspace-only scratch, and reject tampering without damaging the prior payload?

## Pin status

Candidate version 0.1.0; compatibility reference is the inspected server commit. No live plugin pin exists and no deployment pin may advance on static or fixture evidence alone.
