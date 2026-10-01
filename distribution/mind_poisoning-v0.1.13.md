# Mind Poisoning v0.1.13 — PRE-ALPHA candidate

This candidate retains the optional, provenance-bound solo reflection API and adds an optional `RequestLog` observer for companion diagnostics. It also includes four focused fixes from the 2026-09-30 bug hunt.

## Changes

- **Reject invalid stored affinity.** Persistence now rejects non-finite or out-of-range prior affinity with `invalid` / `affinity-invalid` before writes or commit. It does not silently clamp an existing value such as 250 into a different state.
- **Report reflection rejection accurately.** If reflection persistence returns `invalid`, the evaluator still returns `invalid`; its terminal diagnostic is classified as warning-level `rejected` and retains the specific persistence reason.
- **Mark invalid current dashboard values.** The read-only current-affinity presenter labels finite values outside -100 through +100 as invalid. It does not normalize or write the stored value.
- **Contain callback PHP warnings.** Custom sink and observer warnings are suppressed only around diagnostic callback delivery; exceptions remain contained. The callbacks still run and normal sink/domain results remain independent.

## Optional request-log observer

`RequestLog::observe(?callable $observer): void` attaches or detaches one request-local observer without changing the constructor or the normal sink. After attempting normal delivery, the logger calls the observer with an associative sanitized record and the fixed log level. The safe observer projection removes opt-in debug `model_reason`; the ordinary sink's existing debug behavior is unchanged. Names, dialogue, prompts, credentials, subtitle digests and claim tokens are not forwarded.

Passing `null` detaches the observer. Sink or observer exceptions and PHP diagnostics from those callback calls are contained. Observer-triggered reentrant logging still reaches the normal sink but does not recursively call the observer. This is best-effort per-request notification, not a durable event bus or PHP sandbox; trusted callbacks can deliberately emit output or write elsewhere.

Validated reflection registration may add its exact generic UUID `config_id` to the observer correlation fields. Mind Poisoning preserves its existing UUID acceptance; Private Conversation independently uses lowercase RFC 4122 v4 UUIDs at its own importer boundary.

## Focused evidence and limits

The isolated regression fixtures passed for invalid stored affinity, warning containment, reflection terminal severity, dashboard current-affinity bounds, normal sink delivery and sanitized observer correlation. The evaluator/observer/importer fixture covered committed changes, confirmed zero change, provider failure, unconfirmed commit and warning skip. These checks used fixture stores and providers; they do not establish live PostgreSQL behavior, provider reliability, installed-server compatibility or gameplay/audio acceptance.

Mind Poisoning remains **PRE-ALPHA**. Compatibility reference remains `cf5030f15781637498be86debe26fcf102f5690d`; it is evidence metadata, not an installation pin. The official CHIM catalog entry has not been submitted or approved.

Use one server-package route per CHIM server. Replace or disable older enabled packages from the sync source; do not stack versions or leave competing MO2/file-sync and Plugin Manager sources active together.

When published, the release contains a [repository archive](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.13/mind_poisoning.tar.gz), [CHIM sync package](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.13/mind_poisoning-0.1.13.dwpkg), and [plain MO2 import ZIP](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.13/mind_poisoning-0.1.13-mo2.zip). See the [release page](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.13) for published checksums.
