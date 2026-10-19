# Mind Poisoning 0.1.27 — BETA CANDIDATE

## A letter enters the relationship paperwork

Skyrim already has gossip. Mind Poisoning 0.1.27 adds a narrow server-side route for a compatible plugin to submit a confirmed written-message exposure for one NPC recipient's ordinary opinion evaluation. We have not taught Mind Poisoning to write letters, deliver them, or prove who held the quill. The llama is not a notary.

The written-evidence API is optional and developer-facing. A compatible producer must create the source record and call the versioned API; this package includes no inbox, authoring or delivery feature, and no producer. The recipient's normal bounded affinity/type rules apply. The event does not authenticate the claimed author or payer, notify absent characters, establish intimacy, or turn the text into spoken dialogue. A compatible source must pass strict marker, envelope, identity, scope and replay checks before evaluation. See the [written-evidence API contract](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/blob/mind_poisoning-v0.1.27/docs/written-evidence-api.md) for the exact boundary.

Other characters still have feelings the paperwork cannot settle. Optional intimacy recognition remains off by default and stores an NPC's own current report separately from affinity and emotional type. One NPC's report does not establish the other person's agreement, exclusivity or current willingness. The separate SHARMAT consumer is not bundled or updated by this release.

## Status and evidence

This release remains a **BETA CANDIDATE**. GitHub uses a standard release classification so supported CHIM topic discovery can see the package; that classification does not promote maturity. Deterministic fixtures and a bounded native integration fixture passed. They do not prove real-model interpretation, delivery or reading in a live game, or gameplay. The [verification summary](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/blob/mind_poisoning-v0.1.27/docs/verification-v0.1.27.md) gives the retained checks and limits.

No compatible public producer is supplied with this package. The integration fixture used a development candidate, which is evidence about the MP adapter seam, not an announcement that a separate Correspondence release is available.

## Install one route

After the versioned release assets are published, check their `SHA256SUMS.txt` before installing. A checksum verifies downloaded bytes, not a successful CHIM sync.

- **MO2 users:** use the [plain MO2 ZIP](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.27/mind_poisoning-0.1.27-mo2.zip). It contains the version-matched DWPkg at `CHIM/server-plugins/mind_poisoning/0.1.27.dwpkg`; do not add a second package. Update with **Replace**, not **Merge**.
- **Existing CHIM manifest/channel install:** use the [server TAR](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.27/mind_poisoning.tar.gz).
- **Advanced manual sync:** use the [versioned DWPkg](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.27/mind_poisoning-0.1.27.dwpkg).
- **Experimental topic discovery:** the flat [`chim-plugin.tar.gz`](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.27/chim-plugin.tar.gz) is for a CHIM manager that supports the `chim-plugin` topic. A development-candidate package passed a controlled official-manager install check; this does not verify the 0.1.27 release asset, live discovery install or gameplay.

Use one route per server, keep one enabled copy, and back up the database and configuration before an update. A package listing or checksum does not prove the server loaded it. Follow the [player installation and recovery guide](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/blob/mind_poisoning-v0.1.27/docs/mind-poisoning.md) and [migration guide](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/blob/mind_poisoning-v0.1.27/docs/deployment-migration.md).

## Release record

The tagged verification summary records what was known before publication. After the assets are published and their public bytes are checked, the final receipt is recorded on mutable `main` at `docs/verification-v0.1.27.md`; the release's `SHA256SUMS.txt` is the authoritative asset digest list. The versioned source note does not claim that the pre-publication checks prove a published download.
