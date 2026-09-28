# Francisco's CHIM Plugins

My plugins for CHIM and Skyrim. Apparently dragons, civil war, and Nazeem weren't enough.

## Mind Poisoning

The first plugin in this collection. Give Skyrim's gossip an actual consequence: NPCs can praise or badmouth a named NPC or the Player, nudging one listener's opinion. The listener may remain unconvinced—inconvenient for anyone expecting competent character assassination.

**Current candidate:** v0.1.4 is the development-candidate prerelease. Download the [repository archive](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.4/mind_poisoning.tar.gz) for the catalog/Plugin Manager route or the [CHIM sync package](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.4/mind_poisoning-0.1.4.dwpkg) for server file sync. The [release page](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.4) has both assets. Earlier [v0.1.3](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.3), [v0.1.2](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.2), [v0.1.1](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.1), and [v0.1.0](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.0) prereleases are older source.

Source and fixture checks passed. Live database writes/concurrency, provider behavior, client ACK behavior, dashboard authentication, and in-game behavior have not been verified. The official CHIM catalog entry has not been submitted or approved.

Version 0.1.4 adds a model check for ordinary-word name matches, corrects the dashboard's empty-log state, and includes Plugin Manager metadata for later versioned updates. The model can still misclassify a mention. Existing v0.1.3 installs need one package sync first because their installed manifest lacks the update schema; after that, the Manager can use the installed manifest's channel even though the official catalog entry is not yet approved. See the [developer guide](docs/development.md), [dashboard guide](docs/dashboard.md), and [v0.1.4 release notes](distribution/mind_poisoning-v0.1.4.md) for details and limits.

- [Player guide](docs/mind-poisoning.md)
- [Developer guide](docs/development.md)
- [Issues and support](https://github.com/Francisco-boop-001/CHIM-Plugins/issues)
