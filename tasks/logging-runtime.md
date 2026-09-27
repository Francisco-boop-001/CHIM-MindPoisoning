# ACK lifecycle logging evidence

## Changes

`server/prerequest.php` creates the request logger before store construction and threads it through the ACK handler and persistence call. Each handled ACK emits one terminal summary; diagnostic mode also emits the debug-only start event. Bounded logs retain validated correlation IDs, stage/reason codes, model and persistence outcomes, and elapsed times. Diagnostic mode records validated judgment proposals with subject tokens, deltas, and bounded model rationale. There are no dedicated raw speech, evidence, actor-name, prompt, or exception-message fields, though untrusted model rationale may itself contain game text or names.

The hook distinguishes bootstrap, preflight (including payload validation), correlation, model, post-model gate, and persistence stages. Interaction Off, malformed interaction state, stale generation, malformed/oversized payload, model request failure, judgment validation failure, and persistence failures retain safe specific reasons without changing the existing return statuses. Dedupe preflight also records `ledger-invalid`, `ledger-floor`, or `duplicate-event` while continuing to return `duplicate` and avoiding paid model work.

`tests/runtime_test.php` exercises successful correlation and one-start/one-summary behavior, secret suppression, Off and malformed state, Off during model evaluation, malformed and oversized input, provider and validation failures, persistence failure details, corrupt/floor/true duplicate ledgers, throwing sinks, and isolated bootstrap constructor failure plus include-scope preservation.

## Verification

Commands ran through WSL distro `DwemerAI4Skyrim3` against the local plugin fixture:

- `php -l /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/server/prerequest.php` — exit 0, no syntax errors.
- `php -l /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/runtime_test.php` — exit 0, no syntax errors.
- `php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/runtime_test.php` — exit 0, `runtime store checks passed`; the two emitted persistence messages are the existing injected snapshot-verification and Player-alias failure fixtures.
- `php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/logging_test.php` — exit 0, `logging checks passed`.
- `php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/store_logging_test.php` — exit 0, `store logging checks passed` after its runtime fixture; it emits the same two expected injected failure messages.
- `git diff --check -- server/prerequest.php tests/runtime_test.php` — passed. Both owned files contain zero CR bytes.

## Red/green evidence and limits

Before the hook consumed the store's new reason output, the combined focused run stopped at the corrupt-ledger assertion: expected `ledger-invalid`, received the generic `duplicate`. After wiring the reason output, the runtime fixture passed, including corrupt ledger, below-floor, and exact duplicate cases.

These checks use in-memory store fixtures and a child PHP process with a stub database object. They do not establish behavior against live PostgreSQL transactions, the installed HerikaServer logger/runtime, an LLM provider, or in-game/client speech delivery. No installed server/mod, live database, provider, or CHIM core files were accessed or changed.
