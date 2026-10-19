# Mind Poisoning v0.1.26 — BETA CANDIDATE

## Friends with benefits. Administrative overhead without the affection bonus.

Skyrim relationships now have somewhere to put “we sleep together, but please stop calling this a romance.” Mind Poisoning can recognize an NPC's own current intimacy arrangement from supported dialogue or registered private reflection and keep it **separate from affinity and emotional relationship type**. The llama has not become a marriage counsellor. This is probably for the best.

Recognition is **off by default**. This is still an experimental **BETA CANDIDATE**. Standard GitHub release classification permits CHIM discovery; it does not promote maturity.

## Why a separate arrangement?

Liking someone, feeling romantic about them and having an intimacy arrangement are different facts. Replacing `suspicious` or `jealous` with a sexual label would discard the very complications we wanted to represent. Adding affection automatically would be equally misleading. This implementation records the NPC owner's current self-report as `casual`, `romantic`, or explicit `none`; unknown remains unknown. It never invents the reverse edge, exclusivity, mutual agreement or current willingness.

The classifier is instructed to reject wishes, invitations, hypotheticals, quotations, third-party gossip and Player claims as grounds for an NPC arrangement. Exact source identities, current scope, evidence excerpts, existing locks, pause/replay checks and atomic history writes still apply. An ending can update the arrangement without awarding the same reflection another affinity change.

## What to enable—and what it costs

Read the [arrangement guide](../docs/intimacy-arrangements.md) before enabling CHIM's general setting `mind_poisoning_intimacy_enabled`. Direct NPC speech can add **one synchronous configured-model call per eligible acknowledged line**, not per conversation. Split replies can therefore cost several calls. It does not multiply by witnesses. Registered reflection uses at most one evaluation, with the existing 4,096-output-token budget when opted in. A new valid source can call the model even when affinity evidence was already processed, to judge a new arrangement or ending; exact retries and no-subject reflections still skip. Turning recognition off stops new updates; it does not erase saved records.

The read-only dashboard distinguishes proposed, confirmed and uncertain arrangement outcomes. Prompts receive bounded owner-attributed context. State lives in the existing plugin namespace because the inspected CHIM relationship editor discards unknown fields on relationship edges. There is no new table or migration.

## SHARMAT contribution

[Upstream PR #7](https://github.com/Wondernuttz/Sharmat-Alpha/pull/7) includes an optional consumer for the new versioned, read-only arrangement API. Its independent `use_intimacy_arrangements` switch is also off by default. An active exact-pair arrangement may satisfy ordinary type/liking eligibility, even with negative affinity; the other applicable SHARMAT gates remain. Casual context protects against automatic crush conversion. Ended arrangements remove only that additional lane.

**This MP download does not contain a replacement SHARMAT release.** The consumer requires the contribution to be installed separately or merged/released upstream. A label does not make OStim start, establish the recipient's agreement or prove the native command succeeded.

## Reload, privacy and limits

Arrangements follow the restored plugin timeline under **both** CHIM restore policies. Preserve mode may keep newer core affinity/type while restoring older arrangement state. Missing/pruned source evidence or identity/scope uncertainty cannot grant the new action lane. The current map is bounded to 64 counterparts per owner; history remains bounded. No state is reconstructed from affinity or old notes.

Normal diagnostics use fixed codes and bounded metadata, not dialogue, prompts or source digests. Private reflection remains an actor-owned report, not public knowledge.

The [verification summary](../docs/verification-v0.1.26.md) separates deterministic PHP/source/storage evidence from the tests still requiring real model output and gameplay. No live server, installed mod, game data, provider quota or gaming distro was used for this work. The model can still misunderstand a joke; our tests cannot certify its social life.

## Install one package through one route

- **MO2 users:** install the [plain MO2 ZIP](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.26/mind_poisoning-0.1.26-mo2.zip), which already contains the DWPkg. Keep one enabled version; update with **Replace**, not **Merge**.
- **Existing CHIM Manager install:** continue that route; do not add an MO2 sync copy. The [server TAR](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.26/mind_poisoning.tar.gz) remains the explicit manifest/channel asset.
- **Advanced alternatives:** the raw versioned DWPkg is for manual file sync; `chim-plugin.tar.gz` is the flat topic-discovery asset for managers that support it. GitHub source archives are not installers.

Two extension copies can stop CHIM conversation requests. Disable an old MO2 sync source before changing routes, verify exactly one server copy, and follow the [migration guide](../docs/deployment-migration.md). Removing a package does not undo stored opinions. Back up database/configuration before a real update or policy change. Check `SHA256SUMS.txt`; matching bytes do not prove installation or gameplay.
