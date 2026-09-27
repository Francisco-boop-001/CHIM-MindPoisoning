# Mind Poisoning v0.1.0

Development candidate for HerikaServer. This plugin evaluates exact CHIM client `_speech` acknowledgements and applies bounded listener-affinity changes only for explicitly named, known NPC or Player subjects.

## Compatibility

Inspected compatibility reference: HerikaServer `cf5030f15781637498be86debe26fcf102f5690d`. This is a reference, not a deployment pin.

## Candidate limits

Live PostgreSQL writes and concurrency, provider behavior, and in-game behavior have not been verified. Treat this as an isolated-test candidate. Official CHIM catalog inclusion has not yet been submitted or approved; publication of the candidate does not imply catalog approval or installation.

## Downloads

Preferred CHIM Plugin Manager artifact: [`mind_poisoning.tar.gz`](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.0/mind_poisoning.tar.gz), attached to the [v0.1.0 prerelease](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.0). It contains one top-level `mind_poisoning/` directory; the installer strips that directory and expects `manifest.json` at the extracted package root.

The schema-4 `.dwpkg` is an optional, separate package-manager distribution route. It is not the catalog/Plugin Manager download path and does not provide a manual upload action in that UI.
