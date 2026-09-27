# CHIM Mind Poisoning

Version 0.1.1 development-candidate prerelease. Get the [repository archive](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.1/mind_poisoning.tar.gz) or [CHIM sync package](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.1/mind_poisoning-0.1.1.dwpkg) from the [release page](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.1). The [v0.1.0 prerelease](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.0) contains earlier source. Compatibility reference: `cf5030f15781637498be86debe26fcf102f5690d`; it is not a deployment pin. The official CHIM catalog entry has not been submitted or approved.

## Behavior

The hook evaluates only `_speech` acknowledgements. It binds to exactly one chat event by utterance ID and checks speaker, listener, explicit non-broadcast target, and active playthrough. The text used for subject extraction and judgment is the client-reported ACK speech. That is CHIM's acknowledged text report, not independent proof of audio playback. The logged event remains the identity and persistence anchor; no fuzzy text or tail matching selects an event.

Passive ACKs may be evaluated while the interaction switch is On and the request generation is current. Off and stale generations remain blocked, including a switch during the synchronous model request. Aborted, malformed, duplicate, unmatched, stale, broadcast, ambiguous, and Player-listener events are skipped. If the current event names the Player as a subject, ambiguous legacy Player relationship aliases are rejected before a paid model request and rechecked before commit; NPC-only events remain eligible.

The model evaluates explicitly named known NPCs or Player, up to 8 subjects and 12,000 bytes of speech. It returns a signed delta from -5 through +5, including zero; resulting affinity stays within -100 and +100. The update changes only the listener-to-subject affinity edge. It does not edit Skyrim relationship ranks or establish hearsay as shared world truth.

The prompt may include at most the last 8 relevant prior events for the same listener and playthrough, with up to 8 subject judgments per event. These are untrusted, bounded model notes, not verified claims or a cooldown. Repetition guidance cannot guarantee that a repeated claim will not change affinity again.

The hook also requires global relationship processing enabled and `NEVER_CLEAR_RELATIONSHIP_DATA=false`. It skips nonzero `lock_profile` and manual relationship locks; `relationships_locked` follows PHP `!empty` semantics. Only the configured `openrouterjson` or `openrouterjsoncached` connector is supported. The plugin selects no alternate connector, though the provider may use its own fallback. Calls are synchronous with a 12-second I/O timeout and 1024-token limit; the timeout is not a hard wall-clock deadline.

## Persistence and limits

Persistence uses the guarded `sql::$link` compatibility shim and retains one native connection for the transaction, advisory lock, row lock, relationship update, plugin ledger, and full NPC/history snapshot. Committed zero decisions also write the ledger and snapshot without changing affinity. Failed writes or snapshot verification roll back the transaction. The bounded ledger holds up to 128 event IDs and advances a numeric floor; ACKs at or below the floor are skipped, including unseen or out-of-order IDs.

The candidate has not been verified with live PostgreSQL writes or concurrency, a live provider, or in-game playback/save-load. Some upstream relationship writers do not take the shared advisory lock and can overwrite a later update. Core restore with `NEVER_CLEAR_RELATIONSHIP_DATA=true` preserves scores but rewinds plugin ledger state, so this mode disables the hook. Removing the plugin stops future evaluations; it does not undo stored affinity or prior snapshots.
