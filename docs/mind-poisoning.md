# Mind Poisoning

Mind Poisoning is a CHIM server plugin. It lets a listener's opinion of one named person shift a little after hearing another NPC praise or slander them. The listener can doubt the speaker or change nothing. Gossip remains gossip: this does not establish shared world truth or change Skyrim relationship ranks.

**Hypothetical example, not runtime evidence:** Aela tells Lydia that Farkas betrayed the group. Lydia might become slightly less fond of Farkas, remain unconvinced, or change nothing. The plugin does not decide that the accusation is true or change Lydia's affinity toward Aela.

## Status and download

Version 0.1.0 is an experimental development candidate. Live database writes and concurrency, provider behavior, and in-game behavior have not been verified. The official CHIM catalog entry has not been submitted or approved, so it is not available from the catalog yet.

The [v0.1.0 GitHub prerelease](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.0) includes the [separate `.dwpkg` testing package](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.0/mind_poisoning-0.1.0.dwpkg). The `.tar.gz` asset is for CHIM's catalog/Plugin Manager route after catalog inclusion; that UI has no manual `.dwpkg` upload button. Official catalog submission and approval remain pending.

## Requirements

- Test in a separate MO2 game profile and an isolated CHIM server/database. A separate MO2 profile alone does not isolate server data.
- CHIM global relationship processing must be enabled and interactions must not be Off.
- `NEVER_CLEAR_RELATIONSHIP_DATA` must be `false`.
- `RELLLM_CONNECTOR` must identify a configured `openrouterjson` or `openrouterjsoncached` connector.
- Locked listener profiles and manual relationship locks are skipped.

The plugin only evaluates exact CHIM client `_speech` acknowledgements. It does not process aborted or unmatched speech. Each affinity change is between -5 and +5; affinity remains between -100 and +100. A zero change is valid.

## Isolated prerelease testing

This plugin is a CHIM server extension, not a Skyrim ESP. The MO2 option below supplies the server package; it does not install a game plugin.

Download `mind_poisoning-0.1.0.dwpkg` from the release link above, rename the file to `0.1.0.dwpkg`, and keep its contents intact. Copy it to the CHIM data directory at:

```text
Data/CHIM/server-plugins/mind_poisoning/0.1.0.dwpkg
```

Alternatively, create and enable a separate empty mod in the MO2 test profile with the intact package at this relative path:

```text
CHIM/server-plugins/mind_poisoning/0.1.0.dwpkg
```

Start and connect to the isolated CHIM test server, launch Skyrim through the separate MO2 profile, and load a test save. CHIM's documented sync runs on SAVE LOAD; confirm the `mind_poisoning` 0.1.0 entry appears in Plugin Manager. This file-sync procedure is documented by CHIM but has not been live-tested by this project.

To stop future evaluations, remove the plugin from the CHIM server-side installation and remove its `.dwpkg` from the sync source, including the MO2 mod if used. Removing the plugin does not undo affinities already stored in CHIM.
