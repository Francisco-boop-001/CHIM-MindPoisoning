# MP-02/03 client/ACK contract review

## Finding

The server-side conditions are confirmed, but the installed client source needed to settle which headers and text it puts on `_speech` is unavailable. MP-02 and MP-03 therefore remain integration risks, not confirmed client behavior. No source or configuration was changed, and no endpoint, database, provider, or game request was executed.

Inspected HerikaServer `cf5030f15781637498be86debe26fcf102f5690d` read-only. The bounded client search covered `F:\EldergleamNext\mods\CHIM Beta` and client-relevant repository files only. That mod contains `AIAgent.dll`, INI files, and `meta.ini`; no C/C++ source, PDB, or other implementation source was present. DLL strings do not establish which call path uses a header or which output field is echoed.

## MP-02 — passive header and gates

- **Confirmed server behavior:** `lib/chim_interaction.php:55-62` makes `chimInteractionAllowed()` false when `HTTP_X_CHIM_PASSIVE` is exactly `1`, when interaction is Off, or when the current interaction generation differs from the generation captured at request start. `chimInteractionBegin()` (`:41-52`) captures `X-CHIM-Generation`, defaulting to the current generation when the header is absent. `_speech` is not in `chimInteractionIsTrigger()` (`:65-74`), so the core does not terminate it as a generation request; `processor/comm.php:632` processes its ACK branch.
- **Unverified client behavior:** The installed files do not show whether `_speech` uses the passive-request path. The server predicate proves that such an ACK would be skipped by the plugin at `server/prerequest.php:59`, but not that the client sends that header on the ACK.
- **Protection to retain:** Interaction Off and the interaction-generation equality check must remain. Separately, the main request's SQL constructor calls `pas_guard(..., true)` (`lib/postgresql.class.php:30-34`); when Playthrough Saves is enabled, `pas_guard()` requires a ready matching `X-CHIM-Playthrough` token (`lib/playthrough_switching.php:119-132`). Its handshake rejects a lower stale `load_id` (`:210-240`). The runtime lease is acquired before the main request proceeds (`lib/postgresql.class.php:13-17`). Client transmission of those headers on `_speech` is also not proven here.
- **Smallest candidate if client source confirms passive ACKs:** Give only the `_speech` hook an ACK-specific gate that allows the passive bit while still requiring interaction enabled and the same captured/current generation. Keep the existing playthrough token/lease, relationship-feature switch, exact ACK identity, and abort exclusion. Do not bypass `chimInteractionAllowed()` globally.

## MP-03 — emitted text, ACK text, and logged text

- At `lib/chat_helper_functions.php:1472-1517`, core creates `responseForSubtitles` separately from `responseForContext`. Subtitle text can be narration-formatted or truncated; when translation text is enabled it can be translated while context changes only if “save translation” is enabled.
- At `:2014-2043`, core assigns an utterance ID and emits a `ScriptQueue` line containing subtitle text, phonetic text, and that ID. At `:2137-2142`, the `chat` event stores `responseForContext` with the same ID and `delivery_state='emitted'`. These server-side values can differ before any client transformation.
- `processor/comm.php:632-697` parses `_speech` and inserts its supplied `speech` field into the speech record. When an utterance ID is present, it looks up the chat row by that exact ID (`:699-717`); its fallback matcher explicitly tolerates text-normalization divergence (`:883-898`). This confirms core identity semantics, not which `ScriptQueue` text the client echoes.
- The plugin currently requires byte-exact equality between ACK speech and the extracted event text (`server/prerequest.php:119-129`) and then uses the logged event text for subjects/model/evidence (`:163-199`). Thus a legitimate client ACK can be rejected if its text differs from logged context; actual occurrence is unverified without client source or a captured ACK/event pair.

## Recommended policy and counterargument

Keep event selection anchored to one exact `utterance_id`; retain exact speaker/listener and explicit non-broadcast target checks, and revalidate the same source row under the transaction. Do not use fuzzy or tail matching to choose a different event.

If the intended evidence is the text the client acknowledges, the smallest server-side change is to use the validated ACK `speech` as the text for subject extraction, model input, and evidence checks after those identity checks, while keeping the event row's ID, speaker, listener, gamets, and playthrough as the persistence identity. This follows core's practice of storing ACK speech and matching its event by exact utterance ID. The counterargument is that ACK text is client-supplied: the ID binds it to an event, but does not prove the text was displayed or spoken. Without client source or a captured pair, changing to ACK text trades false rejection risk for acceptance of altered client text. Keep the current fail-closed check until that contract is confirmed, or explicitly accept that tradeoff before changing runtime behavior.

## Evidence command and limits

Read-only WSL inspection used line-numbered views of `chim_interaction.php`, `chat_helper_functions.php`, `processor/comm.php`, `postgresql.class.php`, and `playthrough_switching.php`, plus targeted searches for generation and playthrough-token call sites. The indicated mod directory was listed for source/PDB files; it contained only the DLL and configuration/metadata files above. No tests were run because this was a bounded source-evidence review. No client ACK was captured; no DLL strings are used as path proof. Consequently the client’s passive-header usage, exact ACK text field origin, and header carriage on ACK remain unknown.
