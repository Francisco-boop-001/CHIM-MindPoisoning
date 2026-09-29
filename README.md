# Francisco's CHIM Plugins

My plugins for CHIM and Skyrim. Apparently dragons, civil war, and Nazeem weren't enough.

## Mind Poisoning

The first plugin in this collection. Give Skyrim's gossip an actual consequence: NPCs or the Player can praise or badmouth a named NPC, nudging one listener's opinion. The listener may remain unconvinced—inconvenient for anyone expecting competent character assassination.

**Current candidate:** v0.1.7 is a PRE-ALPHA development-candidate prerelease. Get the [repository archive](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.7/mind_poisoning.tar.gz) for the catalog/Plugin Manager route, the [CHIM sync package](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.7/mind_poisoning-0.1.7.dwpkg) for server file sync, or the [MO2 import wrapper](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.7/mind_poisoning-0.1.7-mo2.zip). The [v0.1.7 release](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.7) includes `SHA256SUMS.txt`. Earlier [v0.1.6](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.6), [v0.1.5](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.5), [v0.1.4](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.4), [v0.1.3](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.3), [v0.1.2](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.2), [v0.1.1](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.1), and [v0.1.0](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.0) prereleases contain older source.

Source and fixture checks passed. Live database writes/concurrency, provider behavior, client ACK behavior, dashboard authentication, and in-game behavior have not been verified. The official CHIM catalog entry has not been submitted or approved.

Version 0.1.7 adds Player-origin gossip from CHIM's supported input-text requests. It correlates the post-insert eventlog row and routes to one listener, separately from NPC `_speech` acknowledgements. Structured logs omit dedicated Player name and speaker-ID fields; opt-in debug rationale may still quote names or a short game-text excerpt. The model can still misclassify a mention. Existing v0.1.3 installs need one package sync because their installed manifest lacks the update schema; v0.1.5 and v0.1.6 installs already have it. The official CHIM catalog entry has not been submitted or approved. Live database writes/concurrency, provider behavior, client input delivery, deployed dashboard authentication, and in-game behavior remain unverified. See the [player guide](docs/mind-poisoning.md), [developer guide](docs/development.md), [dashboard guide](docs/dashboard.md), and [v0.1.7 release notes](distribution/mind_poisoning-v0.1.7.md) for details and limits.

- [Player guide](docs/mind-poisoning.md)
- [Developer guide](docs/development.md)
- [Issues and support](https://github.com/Francisco-boop-001/CHIM-Plugins/issues)
