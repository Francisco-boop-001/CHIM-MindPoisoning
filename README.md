# World of Drama-Llama — Francisco's CHIM Plugins

Skyrim already has dragons, a civil war and Nazeem. Naturally, it also needed gossip with consequences and NPC conversations that are not automatically about you. You're welcome. Condolences.

**World of Drama-Llama** is a small family of CHIM server plugins for private scenes, spoken reflection, NPC promises and changing opinions. The emo llama supervises. Its qualifications remain disputed.

![Two characters whisper in a snowy landscape while an emo llama lurks at the side.](assets/dashboard-art-source.png)

## Pick your particular disaster

### Mind Poisoning 0.1.23 — BETA CANDIDATE

Mind Poisoning lets a CHIM character react to a claim about a named person. Aela can tell Lydia that Farkas kept his promise; Lydia may warm to him, treat the claim skeptically, or leave her opinion of him unchanged. Praise, criticism, and zero change are all possible, and the response belongs to the listener. Our reputation-management qualifications amount to an unusually large sample of Skyrim gossip.

Direct Player speech can also prompt checks about named NPCs. A compatible Private Conversation reflection evaluates the reflecting actor’s own opinion. Optional overhearing stays off by default; when enabled, it can consider up to four additional eligible NPCs alongside the addressed listener. Effects appear in CHIM relationship data, while Skyrim’s native rank remains separate. Optional relationship-type judgments remain available, with updates limited to one native CHIM type at a time.

Version 0.1.23 adds the opinion owner’s existing, bounded notes about the person being discussed to ordinary speech, reflection, and enabled overhearing. That can help with a complicated situationship, a lingering grudge, or one person expecting more than the other. The notes may be stale, incomplete, truncated, or one-sided. One person’s attraction or hope cannot establish a mutual or exclusive commitment. The context change adds no model call, relationship label, or saved relationship state; the listener can still disagree or make no change.

For a first check, follow the [ordinary interaction steps](docs/mind-poisoning.md#try-one-ordinary-interaction) and compare CHIM affinity and type before and after. Then check **Plugin Manager → Plugin Page → Interactions**; the [dashboard](docs/dashboard.md) is read-only, and its **Logs** tab can download filtered JSONL. Use the guide’s [Private Conversation reflection workflow](docs/mind-poisoning.md#solo-reflection-with-private-conversation) for a reflection—there is no special Mind Poisoning command.

0.1.23 remains a **BETA CANDIDATE**. Focused isolated PHP checks and syntax checks passed; they did not cover provider interpretation, cold installation, live-database persistence, or gameplay behavior. Our tests can inspect the plumbing; they have not held a conversation with Lydia. See the [candidate evidence summary](docs/beta-candidate-evidence.md) and [verification summary](docs/verification-v0.1.23.md).

[Recommended MO2 ZIP](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.23/mind_poisoning-0.1.23-mo2.zip) · [Release notes](distribution/mind_poisoning-v0.1.23.md) · [Release and checksums](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/tag/mind_poisoning-v0.1.23) · [Source](https://github.com/Francisco-boop-001/CHIM-MindPoisoning) · [Player guide](docs/mind-poisoning.md) · [Dashboard and logs](docs/dashboard.md) · [Developer guide](docs/development.md)

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

Check the published release and its `SHA256SUMS.txt` before installing; a checksum verifies downloaded bytes, not CHIM sync. The plain MO2 ZIP is the recommended route for MO2 users. It records its version and source in root `meta.ini` and contains `CHIM/server-plugins/mind_poisoning/0.1.23.dwpkg`, so no second download, manual unpacking, or package insertion is needed. MO2 may still show its Skyrim content warning, and metadata does not prove CHIM read or synchronized the package. The raw DWPkg is an advanced alternative for manual CHIM file sync; the server TAR is an advanced alternative for an existing manifest/channel install. The separate flat `chim-plugin.tar.gz` is the experimental topic-discovery route for a newer CHIM manager; that route remains unverified end to end. GitHub's autogenerated Source code archives are not installers.

Pick one install route per plugin. Each release page lists its `SHA256SUMS.txt` file.

| Plugin | MO2 ZIP (recommended for MO2 users) | Advanced: CHIM DWPkg (manual file sync) | Advanced: server TAR (existing manifest/channel) |
| --- | --- | --- | --- |
| Mind Poisoning 0.1.23 | [Download](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.23/mind_poisoning-0.1.23-mo2.zip) | [Download](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.23/mind_poisoning-0.1.23.dwpkg) | [Download](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.23/mind_poisoning.tar.gz) |
| Private Conversation 0.1.16 | [Download](https://github.com/Francisco-boop-001/CHIM-PrivateConversation/releases/download/private_conversation-v0.1.16/private_conversation-0.1.16-mo2.zip) | [Download](https://github.com/Francisco-boop-001/CHIM-PrivateConversation/releases/download/private_conversation-v0.1.16/private_conversation-0.1.16.dwpkg) | [Download](https://github.com/Francisco-boop-001/CHIM-PrivateConversation/releases/download/private_conversation-v0.1.16/private_conversation.tar.gz) |
| Sworn & Scorned 0.1.5 | [Download](https://github.com/Francisco-boop-001/CHIM-SwornAndScorned/releases/download/sworn_and_scorned-v0.1.5/sworn_and_scorned-0.1.5-mo2.zip) | [Download](https://github.com/Francisco-boop-001/CHIM-SwornAndScorned/releases/download/sworn_and_scorned-v0.1.5/sworn_and_scorned-0.1.5.dwpkg) | [Download](https://github.com/Francisco-boop-001/CHIM-SwornAndScorned/releases/download/sworn_and_scorned-v0.1.5/sworn_and_scorned.tar.gz) |

Experimental new CHIM topic-discovery route: flat [`chim-plugin.tar.gz`](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.23/chim-plugin.tar.gz). Use one Mind Poisoning route per server; see the [candidate evidence and compatibility notes](docs/beta-candidate-evidence.md) before choosing it. This route has not passed an end-to-end install check.

## Install carefully

Use one route for each plugin. A clean Mind Poisoning install through one route creates one extension copy. Keep exactly one enabled Mind Poisoning MO2 package version. When both Mind Poisoning extension copies are present, CHIM's current bootstrap stops all CHIM conversation requests until one copy is removed. In an auditor's 0.1.21/0.1.21 reproduction, CHIM raised `Cannot redeclare ChimMindPoisoning\mindPoisoningEvaluateReflection` during `main.php` globals boot; the web Plugin Manager remained available.

For the Manager route, disable or remove the MO2 sync source before installing and do not load a save while it can resync. For MO2, update as **Replace**, never **Merge**, and do not click Mind Poisoning **Install/Update** in the web Plugin Manager. After any install or route change, verify exactly one of `ext/mind_poisoning` or `ext/CHIM-MindPoisoning` exists; if both remain, follow the linked recovery guide. Disabling or removing MO2 alone does not uninstall a server copy. Back up the database and config before folder cleanup; deleting extension code does not remove stored affinity or history from CHIM's database. Follow the [deployment and migration guide](docs/deployment-migration.md) for version-specific recovery.

For Mind Poisoning through MO2, launch Skyrim through MO2 with CHIM connected and load a save. On CHIM's Server Plugins page, open **Automatic Game Plugin Sync** and choose **Refresh Status**. Confirm `mind_poisoning` version `0.1.23` is installed before opening CHIM Plugin Manager → **Plugin Page**. The [player guide](docs/mind-poisoning.md) has the full steps and sync troubleshooting.

CHIM stores relationship data on the server. A separate Skyrim save or MO2 profile does not isolate that database, and removing a plugin does not undo its stored data. Use a separate CHIM server/database for testing.

## Support

For Mind Poisoning, open CHIM Plugin Manager → Plugin Page → Logs → Download filtered plugin log. For Private Conversation, follow its [tagged logging guide](https://github.com/Francisco-boop-001/CHIM-PrivateConversation/blob/private_conversation-v0.1.16/docs/logging-revision-2.md); for Sworn & Scorned, follow its [tagged guide](https://github.com/Francisco-boop-001/CHIM-SwornAndScorned/blob/sworn_and_scorned-v0.1.5/README.md). Each plugin has its own logging instructions.

For a useful report, include installed plugin versions, CHIM/client revision if known, relevant settings, reproduction steps, and expected versus observed behavior. Omit credentials and full private conversation dumps. Mind Poisoning's optional debug rationale can contain sensitive context, so review it before sharing.

Historical live observations are summarized in the [public acceptance summary](docs/live-acceptance-summary.md). They describe specific cases and versions, not general gameplay reliability for any plugin. No current gameplay acceptance is claimed here for Sworn & Scorned.

[Issues and support](https://github.com/Francisco-boop-001/CHIM-Plugins/issues)

Maintained by [Francisco](https://github.com/Francisco-boop-001), built for [CHIM by Dwemer Dynamics](https://github.com/Dwemer-Dynamics/CHIM). They made the platform. I brought the llama and made things awkward.
