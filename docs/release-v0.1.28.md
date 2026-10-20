# Mind Poisoning 0.1.28 — BETA CANDIDATE

## A few fewer ways for the paperwork to lie

Mind Poisoning still changes CHIM opinions through identity-checked gossip and reflection. The optional intimacy feature records one NPC's own report separately from affinity and emotional type. In 0.1.28, recognized child signals veto only that arrangement lane; ordinary affinity, emotional type, gossip and reflection remain available. The guard mirrors limited SHARMAT-recognized names and bound server metadata. It is not universal age verification, and SHARMAT's native actor check remains the final scene safeguard.

Intimacy recognition remains off by default. Turning it off or pausing MP stops new arrangement context and classification but does not erase saved records. A repeat report can refresh provenance without being counted as a semantic arrangement change. One person's report still does not establish mutual agreement, exclusivity or current willingness. SHARMAT is separate: this release does not install or update it and makes no automatic compatibility promise.

## Written evidence and honest stops

The optional written-evidence API was introduced in 0.1.27 and remains a server-to-server interface for a compatible producer. Version 0.1.28 hardens its source scan: malformed unrelated rows do not poison valid identity checks, exact valid document/receipt/operation conflicts still block, and an incomplete scan fails closed after a cooperative three-second budget checked between synchronous reads. The deadline cannot interrupt a query already in flight.

The MAIN restore barrier now uses its helper's three-second wait setting for brief contention. Polling granularity means this is not a hard cap on total request time. Fixed written-evidence preflight diagnostics distinguish a stop before model or persistence work; they stay in diagnostics and do not appear as applied-interaction cards. No inbox, letter-delivery feature or producer is included. The llama is still not a notary, and it has misplaced the quill again.

## Status and evidence

This is a **BETA CANDIDATE**. Standard GitHub release classification supports CHIM topic discovery; it does not promote maturity. Focused deterministic and native checks passed, including source identity, restore-barrier, arrangement-child, same-kind reaffirmation, diagnostics-cap and preflight/dashboard controls. The native fixtures used disposable test state and no provider. They do not establish real-model interpretation, message delivery or reading, live 0.1.28 installation, discovery or gameplay. The controlled official-manager install used a development candidate, not the 0.1.28 release asset.

See the [verification summary](verification-v0.1.28.md), [player and installation guide](mind-poisoning.md), [deployment and migration guide](deployment-migration.md), [arrangement guide](intimacy-arrangements.md), and [written-evidence API contract](written-evidence-api.md). The API requires a compatible producer, which is not included.

## Install one route

After the release assets are published, verify the release's `SHA256SUMS.txt`. A checksum verifies downloaded bytes, not CHIM synchronization. Choose exactly one route per server:

- **MO2 users:** [plain MO2 ZIP](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.28/mind_poisoning-0.1.28-mo2.zip). Its version-matched DWPkg is already inside; update with **Replace**, not **Merge**.
- **Existing manifest/channel install:** [server TAR](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.28/mind_poisoning.tar.gz).
- **Advanced manual sync:** [versioned DWPkg](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.28/mind_poisoning-0.1.28.dwpkg).
- **Experimental topic discovery:** flat [`chim-plugin.tar.gz`](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.28/chim-plugin.tar.gz), for a CHIM manager that supports this route.

Back up the database and configuration before an update. A listing or checksum does not prove CHIM loaded the package. Follow the [duplicate-install recovery steps](deployment-migration.md#mind-poisoning-duplicate-install-recovery) if both extension folders exist.

## Release record

The tagged verification summary is a prepublication record. It does not claim that a source tag was rebuilt or that public asset bytes were checked. After publication and public-download verification, the mutable `main` copy of the verification summary may receive the final tag, asset and checksum receipt; this tagged file remains unchanged. `SHA256SUMS.txt` is the authoritative digest list for published assets.
