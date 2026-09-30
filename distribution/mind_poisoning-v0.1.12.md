# Mind Poisoning v0.1.12 — PRE-ALPHA candidate

This release adds an opt-in reflection API for companion plugins. It can evaluate an NPC's opinion of a named person from that NPC's own exactly registered line and its matching native `_speech` acknowledgement. The ordinary pair route is unchanged: when A talks to B about C, B's opinion of C is evaluated. Solo reflection evaluates A's opinion of C and records A as the opinion owner, with no listener. A character does not have to listen to themselves; Skyrim already has enough problems.

The companion must hold a private server-side registration for the exact source event, utterance, actor, playthrough, configuration, rechat sentinel and emitted subtitle digest. Missing, stale, mismatched, aborted or already consumed registrations fail closed. The current source may be `emitted` or `spoken` when the extension hook runs; a matching ACK is a correlated server-side line attempt, not proof that audio played.

The evidence basis is bounded to the actor profile and up to eight earlier spoken utterances in which the actor is known to participate. Affinity and earlier reflection output are excluded. Up to 32 subject tokens are retained for each current basis separately from the 128-event rolling ledger, including when the model returns zero; unchanged evidence is skipped, while a changed basis can be evaluated again.

Reflection reuses Mind Poisoning's parser, model safeguards, pause/generation/scope checks, transaction path and dashboard attribution. Logs show `source_kind=reflection` and the opinion owner without exposing the dialogue, subtitle digest or evidence-basis fingerprint. The API is opt-in and is not called by Mind Poisoning's ordinary `prerequest.php`.

Focused fake-store and isolated companion-registry fixtures cover exact correlation, owner attribution, duplicate protection, stale scope, provider failure and log redaction. They do not establish real database writes, provider reliability, installation compatibility, native delivery order in an installed server or successful audio playback. Prior Player-origin acceptance does not validate this new route. The plugin remains PRE-ALPHA; the official CHIM catalog entry has not been submitted or approved.

Use one server-package route per CHIM server. The compatibility reference `cf5030f15781637498be86debe26fcf102f5690d` is evidence metadata, not an installation pin. MO2 may warn that the plain ZIP has no Skyrim game content; it contains CHIM server data for file sync.
