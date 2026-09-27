# Mind Poisoning v0.1.1 — development-candidate prerelease

Mind Poisoning applies a small, uncertain opinion change when an NPC acknowledges hearing another NPC praise or criticize a named person. It does not establish that a claim is true or change Skyrim relationship ranks.

## Release assets

- [Repository archive](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.1/mind_poisoning.tar.gz) — repository/catalog Plugin Manager format.
- [CHIM sync package](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.1/mind_poisoning-0.1.1.dwpkg) — schema-4 package for CHIM server file sync.
- [Release page](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.1).

The CHIM catalog entry has not been submitted or approved. For manual testing, use the `.dwpkg` in an isolated CHIM server/database and game profile.

## Changes

- Bind processing to an exact `_speech` acknowledgement and chat-event ID, with matching speaker, listener, explicit direct target, and playthrough. Use the client-reported ACK speech as the text evidence; this is not independent proof of audio playback.
- Permit passive ACK evaluation only while interaction is On and the captured generation is current, checking again after the synchronous model call. Off and stale generations remain blocked.
- Reject ambiguous legacy Player relationship aliases before model evaluation when the ACK names the Player; retain commit-time identity checks. NPC-only events remain eligible.
- Add bounded prior-event context: at most the last 8 relevant same-playthrough events, each with up to 8 subject judgments. This is untrusted model guidance, not a deterministic cooldown or repetition detector.
- Fail closed on malformed present dedupe namespaces and Player-name changes during evaluation. Preserve exact-event dedupe, full NPC/history snapshots, and zero-decision snapshots.

## Verification and limits

PHP source and fixture checks passed. No live PostgreSQL writes or concurrency, provider request, client endpoint, or in-game playback/save-load test was performed. The client’s actual ACK text echo and passive-header usage remain unverified. The connector call is synchronous; its 12-second I/O timeout is not a hard wall-clock limit. Some upstream relationship writers do not use the shared advisory lock and can overwrite later updates. Removing the plugin stops future evaluations but does not undo stored affinities.
