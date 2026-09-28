# Francisco's CHIM Plugins

My plugins for CHIM and Skyrim. Apparently dragons, civil war, and Nazeem weren't enough.

## Mind Poisoning

The first plugin in this collection. Give Skyrim's gossip an actual consequence: NPCs can praise or badmouth a named NPC or the Player, nudging one listener's opinion. The listener may remain unconvinced—inconvenient for anyone expecting competent character assassination.

**Current candidate:** v0.1.6 is a PRE-ALPHA development-candidate prerelease. Get the [repository archive](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.6/mind_poisoning.tar.gz) for the catalog/Plugin Manager route, the [CHIM sync package](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.6/mind_poisoning-0.1.6.dwpkg) for server file sync, or the [MO2 import wrapper](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.6/mind_poisoning-0.1.6-mo2.zip). The [v0.1.6 release](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.6) includes `SHA256SUMS.txt`. Earlier [v0.1.5](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.5), [v0.1.4](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.4), [v0.1.3](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.3), [v0.1.2](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.2), [v0.1.1](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.1), and [v0.1.0](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.0) prereleases contain older source.

Source and fixture checks passed. Live database writes/concurrency, provider behavior, client ACK behavior, dashboard authentication, and in-game behavior have not been verified. The official CHIM catalog entry has not been submitted or approved.

Version 0.1.6 adds pausable five-second dashboard refresh with a 15-second request timeout; each poll waits until the prior request completes. It only reloads the read-only dashboard and retains the last good view after a failure. The MO2 wrapper remains a file-sync package and may still trigger MO2's custom-content warning. The model can still misclassify a mention. Existing v0.1.3 installs need one package sync because their installed manifest lacks the update schema; v0.1.5 installs already have it. The official CHIM catalog entry has not been submitted or approved. Live database writes/concurrency, provider behavior, client ACK delivery, deployed dashboard authentication, and in-game behavior remain unverified. See the [player guide](docs/mind-poisoning.md), [developer guide](docs/development.md), [dashboard guide](docs/dashboard.md), and [v0.1.6 release notes](distribution/mind_poisoning-v0.1.6.md) for details and limits.

- [Player guide](docs/mind-poisoning.md)
- [Developer guide](docs/development.md)
- [Issues and support](https://github.com/Francisco-boop-001/CHIM-Plugins/issues)
