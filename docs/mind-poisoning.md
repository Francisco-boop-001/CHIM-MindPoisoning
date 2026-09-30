# Mind Poisoning

Mind Poisoning is a CHIM server plugin. It lets a listener's opinion of one named person shift a little after hearing another NPC praise or slander them. The listener can doubt the speaker or change nothing. Gossip remains gossip: this does not establish shared world truth or change Skyrim relationship ranks.

**Hypothetical example, not runtime evidence:** Aela tells Lydia that Farkas betrayed the group. Lydia might become slightly less fond of Farkas, remain unconvinced, or change nothing. The plugin does not decide that the accusation is true or change Lydia's affinity toward Aela.

## Status and download

The v0.1.10 PRE-ALPHA candidate provides a [repository archive](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.10/mind_poisoning.tar.gz) for catalog/Plugin Manager ingestion, a [CHIM sync package](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.10/mind_poisoning-0.1.10.dwpkg), and a [plain MO2 import ZIP](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.10/mind_poisoning-0.1.10-mo2.zip). Check the [v0.1.10 release page](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.10) for `SHA256SUMS.txt`. The official CHIM catalog entry has not been submitted or approved. The prior [v0.1.9](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.9) and earlier [v0.1.8](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.8), [v0.1.7](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.7), [v0.1.6](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.6), [v0.1.5](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.5), [v0.1.4](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.4), [v0.1.3](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.3), [v0.1.2](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.2), [v0.1.1](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.1), and [v0.1.0](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.0) contain older source.

The v0.1.10 source has focused fixtures for pause behavior, ACK diagnostics, and transaction wait bounds. These do not establish behavior against an installed CHIM service, live database writes/concurrency, a live provider, client ACK delivery, dashboard authentication, or in-game playback/save-load.

Version 0.1.7 adds a separate Player-origin input path to the NPC-origin speech acknowledgements in v0.1.6. It accepts four CHIM text-input request types, correlates the inserted eventlog row exactly, and evaluates the Player's submitted text for one route-selected NPC listener. This is not an audio acknowledgement. Structured logs omit dedicated Player name and speaker-ID fields; opt-in debug rationale may still quote a name or short game-text excerpt. Live CHIM game behavior remains unverified.

Version 0.1.8 added database-backed support when no CHIM Playthrough Saves profile is active, using explicit `unprofiled` shared-server scope, and shipped the dashboard's localhost navigation handoff. Existing v0.1.7 installs have schema-2 update metadata already. See the [dashboard guide](dashboard.md) and [developer guide](development.md) for access assumptions and limits.

Introduced in v0.1.9 and retained in v0.1.10, the operator pause is controlled from the CHIM server filesystem, not the read-only dashboard. A missing control file keeps it enabled; `{"enabled":false}` pauses new evaluations, and `{"enabled":true}` resumes them. Invalid state fails closed. The hook checks again after model validation, but a pause cannot cancel a provider call already in progress or undo stored affinity. See the [developer guide](development.md) for the exact format and timing limits.

## Requirements

- Test in a separate MO2 game profile and an isolated CHIM server/database. A separate MO2 profile alone does not isolate server data.
- CHIM itself works without Playthrough Saves. In v0.1.10, zero active profiles use an explicit `unprofiled` scope shared by the server database; exactly one active profile retains numeric isolation. Multiple, invalid, or ambiguous profile states skip evaluation. A disposable Skyrim save alone does not isolate this database-backed state.
- With CHIM auto-switch Off, there is no reliable unique Skyrim save ID. Source correlation and deduplication remain database-scoped; loading another game save without switching the database or profile does not automatically isolate its events.
- CHIM global relationship processing must be enabled. Passive acknowledgements may be processed only while interaction is On and the request generation is current; Off and stale generations remain blocked.
- `NEVER_CLEAR_RELATIONSHIP_DATA` must be `false`.
- `RELLLM_CONNECTOR` must identify a configured `openrouterjson` or `openrouterjsoncached` connector.
- Locked listener profiles and manual relationship locks are skipped.

The model call is synchronous and can delay the `_speech` acknowledgement. Its 12-second I/O timeout is not a hard wall-clock deadline.

The NPC-origin `_speech` path binds acknowledgements to one chat event by `utt_` ID, with matching speaker, listener, explicit non-broadcast target, and playthrough. The model uses the speech text reported by the client ACK; this is a client report, not independent proof of audio playback. No fuzzy text or event-tail matching selects the event. Aborted, unmatched, ambiguous, duplicate, broadcast, and Player-listener ACKs are skipped. If the current event names the Player as a subject, ambiguous legacy Player relationship aliases are rejected before the model call; NPC-only ACK events remain eligible.

The Player-origin input path, introduced in v0.1.7 and present in v0.1.10, accepts `inputtext`, `inputtext_s`, `ginputtext`, and `ginputtext_s`. It correlates the post-insert eventlog snapshot by the exact source tuple and uses `input_<positive rowid>` as its event key. One route-selected listener is the addressee; names in `source_people` may also include bystanders. Narrator, broadcast/everyone, ambiguous route, and uncertain identity cases are skipped. For Player-origin gossip, the Player and listener are excluded as subjects; only other explicitly named known NPCs are considered. This client-reported input text is not an audio ACK or proof of playback.

Names are first found by case-insensitive whole-word matching, so an NPC named May can appear as a candidate when the transcript uses “may” as an ordinary word. In v0.1.4, the existing model call must decide whether each candidate refers to a person; ordinary-word or uncertain uses should return `subject_mentioned=false` with zero delta. Missing or non-boolean mention decisions and false with a nonzero delta are rejected before persistence. This is model-based semantic judgment, not guaranteed entity recognition, so the model can still misclassify a mention. Lowercase names remain eligible and are not filtered by capitalization alone. False lexical candidates still count toward the cap of 8 subjects and may be sent in the same model call.

Each affinity change is between -5 and +5; affinity remains between -100 and +100. A zero change is valid. The prompt can include up to 8 relevant prior events from the same listener and playthrough, with up to 8 subject judgments per event. This bounded, untrusted context is not a cooldown or guaranteed repetition detector, so repeated claims can still influence affinity again. Committed decisions, including zero, retain full NPC/history snapshots.

## Install v0.1.10 PRE-ALPHA for an isolated test

Mind Poisoning is a CHIM server extension, not a Skyrim ESP. The MO2 option below supplies the server package; it does not install a game plugin. Test in a separate MO2 profile and isolated CHIM server/database.

Use one package source per CHIM server. Manual `.dwpkg` sync and an MO2 file-sync mod are alternatives; if Plugin Manager installs or updates the plugin, disable/remove any older MO2 sync source before the next SAVE LOAD so it cannot re-sync the older package.

For manual CHIM file sync, download [`mind_poisoning-0.1.10.dwpkg`](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.10/mind_poisoning-0.1.10.dwpkg), rename it to `0.1.10.dwpkg`, and keep its contents intact. Copy it to the CHIM data directory at:

```text
Data/CHIM/server-plugins/mind_poisoning/0.1.10.dwpkg
```

The published v0.1.7 [FOMOD import wrapper](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.7/mind_poisoning-0.1.7-mo2.zip) has a reported MO2/FOMOD Plus crash; its exact cause remains unresolved, so v0.1.8 through v0.1.10 do not recommend or include a FOMOD wrapper. The v0.1.10 [plain MO2 ZIP](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.10/mind_poisoning-0.1.10-mo2.zip) contains the package at this relative path:

```text
CHIM/server-plugins/mind_poisoning/0.1.10.dwpkg
```

Leave **CHIM** directly under the manual installer's **<data>** root. Do not set the inner CHIM folder as the data directory. MO2's Skyrim checker does not recognize CHIM server packages and may display “The content of <data> does not look valid.” Click **OK**, then **Ignore** in the **Continue?** prompt to retain this layout. Current [MO2 manual-installer source](https://github.com/ModOrganizer2/modorganizer-installer_manual/blob/master/src/installdialog.cpp) supports that override. This is a content-classification warning, not proof of a corrupt package; it does not establish that CHIM has synchronized successfully. Enable the resulting mod only in the isolated test profile. Native v0.1.10 installation has not been verified.

The published v0.1.3 manifest does not include `schema_version: 2`, so those installs do not meet the Plugin Manager's update eligibility gate. Install v0.1.10 once using file sync to add the schema-2 manifest; v0.1.5 through v0.1.9 installs already include it. Future version checks can then use the per-plugin candidate channel even if catalog lookup misses. The [repository archive](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.10/mind_poisoning.tar.gz) is for repository/catalog Plugin Manager ingestion, not manual `.dwpkg` sync. The official catalog entry has not been submitted or approved, so use direct file sync for initial installation; if you later switch to Plugin Manager, remove/disable the old MO2 file-sync source first.

Start and connect to the isolated CHIM test server, launch Skyrim through the separate MO2 profile, and load a test save. CHIM's documented client sync runs on SAVE LOAD; confirm the `mind_poisoning` 0.1.10 entry appears in Plugin Manager, then choose **Plugin Page**. From Windows/WSL, open CHIM through the configured `localhost` origin, retaining the port and base path (for example, `http://localhost:8081/HerikaServer/ui/server_plugins.php`). The localhost handoff was checked with an isolated responder; installed CHIM sync and dashboard access remain unverified. See the [dashboard guide](dashboard.md) for access rules and limits.

To stop future evaluations, remove the plugin from the CHIM server-side installation and remove its `.dwpkg` from the sync source, including the MO2 mod if used. Removing the plugin does not undo affinities already stored in CHIM.
