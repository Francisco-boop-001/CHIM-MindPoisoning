# World of Drama-Llama — Francisco's CHIM Plugins

Skyrim already has dragons, a civil war and Nazeem. Naturally, it also needed gossip with consequences and NPC conversations that are not automatically about you. You're welcome. Condolences.

**World of Drama-Llama** is a small family of CHIM server plugins for private scenes, spoken reflection, NPC promises and changing opinions. The emo llama supervises. Its qualifications remain disputed.

![Two characters whisper in a snowy landscape while an emo llama lurks at the side.](assets/dashboard-art-source.png)

## Pick your particular disaster

### Mind Poisoning 0.1.29 — BETA CANDIDATE

Gossip can change an NPC's opinion of the person being discussed. A listener can believe praise, resent a reported betrayal, doubt the speaker or change nothing. Player-origin claims and compatible Private Conversation reflections are supported; optional overhearing can include eligible witnesses. Effects go into CHIM's relationship data, while Skyrim's native relationship ranks remain separate. Our reputation-management qualifications amount to an unusually large sample of Skyrim gossip.

**Intimacy arrangements, without deleting the complicated feelings.** Once enabled, recognition can record an NPC's own current `casual`, `romantic` or explicit `none` report separately from affinity and emotional type. An NPC can be at **−15 / suspicious** and still report casual intimacy. No new main tag, automatic affection fee or reverse relationship is invented. The other person's agreement, exclusivity and willingness remain separate questions. The llama declines to judge their life choices.

Recognition is **off by default**. It uses exact addressed NPC speech or compatible registered reflection; wishes, invitations and third-party gossip are not grounds. Direct speech can add one synchronous configured-model request per eligible acknowledged line, including split replies. Reflection uses at most one request and can assess a fresh arrangement even when its affinity basis was already processed. Recognized child signals suppress only arrangement updates; ordinary affinity and emotional-type changes keep their existing rules. This is a narrow protective veto, not universal age verification, and missing optional metadata is not evidence of adulthood. Turning recognition off or pausing MP stops new arrangement context without erasing saved reports; a same-kind reaffirmation refreshes provenance but counts as no semantic change. Version 0.1.29 keeps apostrophes readable in NPC names in private arrangement context while retaining JSON escaping. Read the [arrangement guide](docs/intimacy-arrangements.md) for activation, cost, examples and limits. Apparently friends with benefits also needed configuration management.

The optional SHARMAT consumer in [upstream PR #7](https://github.com/Wondernuttz/Sharmat-Alpha/pull/7) has a sexual-eligibility switch that defaults off; crush-preservation policy remains under upstream discussion. A validated exact-pair arrangement may satisfy ordinary type/liking eligibility while other applicable gates and current decisions remain. This MP download does not update SHARMAT or prove an OStim scene will start. Private Conversation continues to own scenes; Sworn & Scorned owns promises and grievances.

**For server integrations:** the written-evidence API was introduced in 0.1.27, hardened in 0.1.28 and is unchanged in this release. Its bounded identity scan skips malformed unrelated rows while preserving exact valid conflicts, and an incomplete scan fails closed. Fixed preflight stops are logged as diagnostics, not applied interactions. The API lets a compatible server-side producer submit a confirmed message exposure for one NPC recipient's ordinary opinion evaluation. It does not verify who wrote or paid for the message, tell absent characters about it, or create an intimacy arrangement. Mind Poisoning does not include an inbox, delivery feature or producer; this release does not announce a public Correspondence companion. For once, the paperwork is the feature and the llama is not qualified to notarize it.

Arrangements follow the restored plugin timeline under **both** CHIM restore policies. Preserve mode may retain newer core affinity/type while restoring older arrangement state. The existing preserve-mode source fence and scope baseline remain; records are not reconstructed from affection or old notes. Follow the [restore guide](docs/restore-policy.md) and back up database/configuration before a real update. Turning recognition off is supported; downgrading older code over newer receipts may require backup recovery.

Use **Plugin Manager → Plugin Page → Interactions** to compare proposed and confirmed results; the [read-only dashboard](docs/dashboard.md) separates arrangement transitions from affinity/type. **Logs → Download filtered plugin log** exports useful diagnostics. The [player guide](docs/mind-poisoning.md) covers ordinary gameplay and reflection; there is no special Mind Poisoning conversation command.

The release remains **BETA CANDIDATE**. The 0.1.29 change has a retained focused fixture and syntax receipt; the 0.1.28 arrangement and written-evidence checks remain documented in their prior record. No native, provider, official-manager, real-model or gameplay check was rerun for this formatting fix. The controlled official-manager check used a development candidate, not 0.1.29; live 0.1.29 discovery, installation and gameplay remain unverified. See the [0.1.29 verification summary](docs/verification-v0.1.29.md), [0.1.28 verification summary](docs/verification-v0.1.28.md), [0.1.27 verification summary](docs/verification-v0.1.27.md) and [historical acceptance summary](docs/live-acceptance-summary.md). Live server, installed mods and game data were left untouched during this work. Publishing is not a maturity promotion.

[Recommended MO2 ZIP](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.29/mind_poisoning-0.1.29-mo2.zip) · [Release notes](docs/release-v0.1.29.md) · [Release and checksums](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/tag/mind_poisoning-v0.1.29) · [Source](https://github.com/Francisco-boop-001/CHIM-MindPoisoning) · [Player guide](docs/mind-poisoning.md) · [Arrangement guide](docs/intimacy-arrangements.md) · [Developer API](docs/integration-api.md) · [Written-evidence API](docs/written-evidence-api.md)

Companion versions below belong to this repository's retained snapshot; use the [main plugins page](https://github.com/Francisco-boop-001/CHIM-Plugins#pick-your-particular-disaster) for current companion releases.

### Private Conversation 0.1.16 — PRE-ALPHA

Stage a pair, a group of 2–4, a free scene, or one solo response. For example, choose Aela and Lydia on the Private Conversation page, arm the scene, then send a Standard-mode direction in Skyrim. Arming alone does not start dialogue. Voice-friendly “wrap up” and “end scene” phrases close scenes. Scene direction works without Mind Poisoning. When installed, Mind Poisoning can evaluate eligible dialogue acknowledgements and registered solo reflections for opinion effects; its judgments still come from dialogue, not scene-action execution. Scene scope does not enforce physical earshot or erase retained memories.

Scenes now support optional Personal, Physical and Intimate actions. All three are off by default and can be opted into per scene. Personal covers solo or shared activities, Physical covers scene-member brawls or surrender and is unavailable in solo scenes, and Intimate uses SHARMAT's gated actions. Targets stay within the scene; Attack and KillTarget are excluded. CHIM's action switch remains authoritative, and SHARMAT's consent and safety gates still apply to Intimate actions.

[Release and checksums](https://github.com/Francisco-boop-001/CHIM-PrivateConversation/releases/tag/private_conversation-v0.1.16) · [Source](https://github.com/Francisco-boop-001/CHIM-PrivateConversation) · [Tagged guide and installation](https://github.com/Francisco-boop-001/CHIM-PrivateConversation/blob/private_conversation-v0.1.16/README.md) · [Logging guide](https://github.com/Francisco-boop-001/CHIM-PrivateConversation/blob/private_conversation-v0.1.16/docs/logging-revision-2.md)

### Sworn & Scorned 0.1.5 — PRE-ALPHA

Sworn & Scorned tracks NPC-owned promises and grievances. For example, an NPC's promise to help another with a problem can remain as an issue; if it is open and unmuted, it may provide one short reminder in a later eligible conversation. The model chooses whether to mention it; no confrontation or recognition of a particular quest or item is guaranteed. Qualifying outcomes can also affect the NPC owner's CHIM opinion of the counterpart.

It starts disabled. Enable it and choose a cast of up to eight NPCs. PHP CLI workers are required. CHIM's relationship evaluator is the default; Jev's experimental decision evaluator is optional, off by default, and does not generate dialogue.

[Release and checksums](https://github.com/Francisco-boop-001/CHIM-SwornAndScorned/releases/tag/sworn_and_scorned-v0.1.5) · [Source](https://github.com/Francisco-boop-001/CHIM-SwornAndScorned) · [Tagged guide](https://github.com/Francisco-boop-001/CHIM-SwornAndScorned/blob/sworn_and_scorned-v0.1.5/README.md) · [Release notes](https://github.com/Francisco-boop-001/CHIM-SwornAndScorned/blob/sworn_and_scorned-v0.1.5/distribution/sworn_and_scorned-v0.1.5.md)

## How they fit together

These are separate CHIM server plugins; install only the ones you want. Private Conversation does not require Mind Poisoning for scene direction. With both installed, Mind Poisoning can evaluate eligible Private Conversation dialogue acknowledgements and registered solo reflections for opinion effects. Sworn & Scorned works with CHIM on its own; it can coexist with Mind Poisoning but does not send its lifecycle decisions through it.

None needs an extra ESP/ESL or Papyrus companion. Sworn & Scorned does require PHP CLI workers. Check each tagged guide for setup details. Opinion changes concern CHIM affinity, not Skyrim's vanilla relationship ranks.

## Current releases and downloads

Check the published release and its `SHA256SUMS.txt` before installing; a checksum verifies downloaded bytes, not CHIM sync. The 0.1.29 plain MO2 ZIP is the recommended route for MO2 users. It records its version and source in root `meta.ini` and contains `CHIM/server-plugins/mind_poisoning/0.1.29.dwpkg`, so no second download, manual unpacking, or package insertion is needed. MO2 may still show its Skyrim content warning, and metadata does not prove CHIM read or synchronized the package. The raw DWPkg is an advanced alternative for manual CHIM file sync; the server TAR is an advanced alternative for an existing manifest/channel install. The separate flat `chim-plugin.tar.gz` is the experimental topic-discovery route for a newer CHIM manager. A controlled official-manager install used a development candidate, not 0.1.29; live 0.1.29 discovery and gameplay remain unverified. GitHub's autogenerated Source code archives are not installers.

Pick one install route per plugin. Each release page lists its `SHA256SUMS.txt` file.

| Plugin | MO2 ZIP (recommended for MO2 users) | Advanced: CHIM DWPkg (manual file sync) | Advanced: server TAR (existing manifest/channel) |
| --- | --- | --- | --- |
| Mind Poisoning 0.1.29 | [Download](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.29/mind_poisoning-0.1.29-mo2.zip) | [Download](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.29/mind_poisoning-0.1.29.dwpkg) | [Download](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.29/mind_poisoning.tar.gz) |
| Private Conversation 0.1.16 | [Download](https://github.com/Francisco-boop-001/CHIM-PrivateConversation/releases/download/private_conversation-v0.1.16/private_conversation-0.1.16-mo2.zip) | [Download](https://github.com/Francisco-boop-001/CHIM-PrivateConversation/releases/download/private_conversation-v0.1.16/private_conversation-0.1.16.dwpkg) | [Download](https://github.com/Francisco-boop-001/CHIM-PrivateConversation/releases/download/private_conversation-v0.1.16/private_conversation.tar.gz) |
| Sworn & Scorned 0.1.5 | [Download](https://github.com/Francisco-boop-001/CHIM-SwornAndScorned/releases/download/sworn_and_scorned-v0.1.5/sworn_and_scorned-0.1.5-mo2.zip) | [Download](https://github.com/Francisco-boop-001/CHIM-SwornAndScorned/releases/download/sworn_and_scorned-v0.1.5/sworn_and_scorned-0.1.5.dwpkg) | [Download](https://github.com/Francisco-boop-001/CHIM-SwornAndScorned/releases/download/sworn_and_scorned-v0.1.5/sworn_and_scorned.tar.gz) |

Experimental CHIM topic-discovery route: flat [`chim-plugin.tar.gz`](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.29/chim-plugin.tar.gz). Use one Mind Poisoning route per server; see the [0.1.29 verification summary](docs/verification-v0.1.29.md) before choosing it. A controlled official-manager install used a development candidate, not 0.1.29; live 0.1.29 discovery and gameplay remain unverified.

## Install carefully

Use one route for each plugin. A clean Mind Poisoning install through one route creates one extension copy. Keep exactly one enabled Mind Poisoning MO2 package version. When both Mind Poisoning extension copies are present, CHIM's current bootstrap stops all CHIM conversation requests until one copy is removed. In an auditor's 0.1.21/0.1.21 reproduction, CHIM raised `Cannot redeclare ChimMindPoisoning\mindPoisoningEvaluateReflection` during `main.php` globals boot; the web Plugin Manager remained available.

For the Manager route, disable or remove the MO2 sync source before installing and do not load a save while it can resync. For MO2, update as **Replace**, never **Merge**, and do not click Mind Poisoning **Install/Update** in the web Plugin Manager. After any install or route change, verify exactly one of `ext/mind_poisoning` or `ext/CHIM-MindPoisoning` exists; if both remain, follow the linked recovery guide. Disabling or removing MO2 alone does not uninstall a server copy. Back up the database and config before folder cleanup; deleting extension code does not remove stored affinity or history from CHIM's database. Follow the [deployment and migration guide](docs/deployment-migration.md) for version-specific recovery.

For Mind Poisoning through MO2, launch Skyrim through MO2 with CHIM connected and load a save. On CHIM's Server Plugins page, open **Automatic Game Plugin Sync** and choose **Refresh Status**. Confirm `mind_poisoning` version `0.1.29` is installed before opening CHIM Plugin Manager → **Plugin Page**. The [player guide](docs/mind-poisoning.md) has the full steps and sync troubleshooting.

CHIM stores relationship data on the server. A separate Skyrim save or MO2 profile does not isolate that database, and removing a plugin does not undo its stored data. Use a separate CHIM server/database for testing.

## Support

For Mind Poisoning, open CHIM Plugin Manager → Plugin Page → Logs → Download filtered plugin log. For Private Conversation, follow its [tagged logging guide](https://github.com/Francisco-boop-001/CHIM-PrivateConversation/blob/private_conversation-v0.1.16/docs/logging-revision-2.md); for Sworn & Scorned, follow its [tagged guide](https://github.com/Francisco-boop-001/CHIM-SwornAndScorned/blob/sworn_and_scorned-v0.1.5/README.md). Each plugin has its own logging instructions.

For a useful report, include installed plugin versions, CHIM/client revision if known, relevant settings, reproduction steps, and expected versus observed behavior. Omit credentials and full private conversation dumps. Mind Poisoning's optional debug rationale can contain sensitive context, so review it before sharing.

Historical live observations are summarized in the [public acceptance summary](docs/live-acceptance-summary.md). They describe specific cases and versions, not general gameplay reliability for any plugin. No current gameplay acceptance is claimed here for Sworn & Scorned.

[Issues and support](https://github.com/Francisco-boop-001/CHIM-Plugins/issues)

Maintained by [Francisco](https://github.com/Francisco-boop-001), built for [CHIM by Dwemer Dynamics](https://github.com/Dwemer-Dynamics/CHIM). They made the platform. I brought the llama and made things awkward.
