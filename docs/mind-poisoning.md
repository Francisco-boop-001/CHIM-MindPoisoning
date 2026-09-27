# Mind Poisoning

Mind Poisoning is a CHIM server plugin. It lets a listener's opinion of one named person shift a little after hearing another NPC praise or slander them. The listener can doubt the speaker or change nothing. Gossip remains gossip: this does not establish shared world truth or change Skyrim relationship ranks.

**Hypothetical example, not runtime evidence:** Aela tells Lydia that Farkas betrayed the group. Lydia might become slightly less fond of Farkas, remain unconvinced, or change nothing. The plugin does not decide that the accusation is true or change Lydia's affinity toward Aela.

## Status and download

The current development-candidate prerelease is v0.1.1: [release page](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.1), [repository archive](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.1/mind_poisoning.tar.gz), and [CHIM sync package](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.1/mind_poisoning-0.1.1.dwpkg). The repository archive is the catalog/Plugin Manager format; `.dwpkg` is the CHIM server file-sync format. The official CHIM catalog entry has not been submitted or approved. The older [v0.1.0 prerelease](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.0) does not contain these changes.

Local source and fixture checks passed. Live PostgreSQL writes/concurrency, provider behavior, client ACK behavior, and in-game playback/save-load remain unverified.

## Requirements

- Test in a separate MO2 game profile and an isolated CHIM server/database. A separate MO2 profile alone does not isolate server data.
- CHIM global relationship processing must be enabled. Passive acknowledgements may be processed only while interaction is On and the request generation is current; Off and stale generations remain blocked.
- `NEVER_CLEAR_RELATIONSHIP_DATA` must be `false`.
- `RELLLM_CONNECTOR` must identify a configured `openrouterjson` or `openrouterjsoncached` connector.
- Locked listener profiles and manual relationship locks are skipped.

The model call is synchronous and can delay the `_speech` acknowledgement. Its 12-second I/O timeout is not a hard wall-clock deadline.

The plugin evaluates exact `_speech` acknowledgements bound to one chat event by utterance ID, with matching speaker, listener, explicit non-broadcast target, and playthrough. The model uses the speech text reported by the client ACK; this is a client report, not independent proof of audio playback. No fuzzy text or event-tail matching selects the event. Aborted, unmatched, ambiguous, duplicate, broadcast, and Player-listener events are skipped. If the current event names the Player as a subject, ambiguous legacy Player relationship aliases are rejected before the model call; NPC-only events remain eligible.

Each affinity change is between -5 and +5; affinity remains between -100 and +100. A zero change is valid. The prompt can include up to 8 relevant prior events from the same playthrough, with up to 8 subject judgments per event. This bounded, untrusted context is not a cooldown or guaranteed repetition detector, so repeated claims can still influence affinity again. Committed decisions, including zero, retain full NPC/history snapshots.

## Install v0.1.1 for an isolated test

Mind Poisoning is a CHIM server extension, not a Skyrim ESP. The MO2 option below supplies the server package; it does not install a game plugin. Test in a separate MO2 profile and isolated CHIM server/database.

For manual CHIM file sync, download [`mind_poisoning-0.1.1.dwpkg`](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.1/mind_poisoning-0.1.1.dwpkg), rename it to `0.1.1.dwpkg`, and keep its contents intact. Copy it to the CHIM data directory at:

```text
Data/CHIM/server-plugins/mind_poisoning/0.1.1.dwpkg
```

Alternatively, create and enable a separate empty mod in the MO2 test profile with the intact package at this relative path:

```text
CHIM/server-plugins/mind_poisoning/0.1.1.dwpkg
```

The [repository archive](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.1/mind_poisoning.tar.gz) is for repository/catalog Plugin Manager ingestion, not a manual `.dwpkg` upload. The official catalog entry has not been submitted or approved, so use the direct `.dwpkg` path above for a local test.

Start and connect to the isolated CHIM test server, launch Skyrim through the separate MO2 profile, and load a test save. CHIM's documented sync runs on SAVE LOAD; confirm the `mind_poisoning` 0.1.1 entry appears in Plugin Manager. This file-sync procedure is documented by CHIM but has not been live-tested by this project.

To stop future evaluations, remove the plugin from the CHIM server-side installation and remove its `.dwpkg` from the sync source, including the MO2 mod if used. Removing the plugin does not undo affinities already stored in CHIM.
