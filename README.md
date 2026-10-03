# World of Drama-Llama — Francisco's CHIM Plugins

Skyrim already has dragons, a civil war and Nazeem. Naturally, what it needed next was gossip with consequences and NPCs having conversations that are not automatically about you. You're welcome. Condolences.

**World of Drama-Llama** is my series of CHIM server plugins for character-driven drama: private scene direction, spoken reflection and individual opinions that can change through praise, slander or reconsideration. The emo llama supervises. Its qualifications remain disputed.

## Current releases

**[Mind Poisoning 0.1.17](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/tag/mind_poisoning-v0.1.17)** · **[Private Conversation 0.1.14](https://github.com/Francisco-boop-001/CHIM-PrivateConversation/releases/tag/private_conversation-v0.1.14)** — both **PRE-ALPHA**.

**New in Mind Poisoning 0.1.17:** optional overhearing lets the addressed NPC and up to four eligible additional NPCs consider the same gossip in one model call, with separate relationship results. It is **off by default**. Eavesdropping is now a group project; believing the gossip remains optional. [Release notes](distribution/mind_poisoning-v0.1.17.md) · [Downloads and installation](#quick-installation).

**New in Private Conversation 0.1.14:** in-game `end scene` and `wrap up:` commands, turns spread around groups, an optional scene card, turn-length guidance, and reliability fixes. Per-NPC SHARMAT conditions are preserved; intimate-scene interplay has source/offline checks only. [Release notes](distribution/private_conversation-v0.1.14.md).

![Two characters whisper in a snowy landscape while an emo llama lurks at the side.](assets/dashboard-art-source.png)

## Pick your particular disaster

| Plugin | Purpose | Candidate | Source | Guide |
| --- | --- | --- | --- | --- |
| **Mind Poisoning** | A speaks to B about C; B may revise an opinion of C. Compatible solo reflection lets A reconsider; optional, default-off overhearing evaluates up to four eligible NPCs alongside the addressed listener. | [0.1.17 PRE-ALPHA](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/tag/mind_poisoning-v0.1.17) · [release notes](distribution/mind_poisoning-v0.1.17.md) | [CHIM-MindPoisoning](https://github.com/Francisco-boop-001/CHIM-MindPoisoning) | [Player guide](docs/mind-poisoning.md) · [Developer guide](docs/development.md) · [Dashboard/logs](docs/dashboard.md) |
| **Private Conversation** | Pairs, 2–4 NPC groups, solo reflection and free scenes (nearest six; player excluded). Adds in-game `end scene` / `wrap up:`, turns spread in three-plus groups, a scene card, turn length and reliability fixes. SHARMAT-compatible. | [0.1.14 PRE-ALPHA](https://github.com/Francisco-boop-001/CHIM-PrivateConversation/releases/tag/private_conversation-v0.1.14) · [release notes](distribution/private_conversation-v0.1.14.md) | [CHIM-PrivateConversation](https://github.com/Francisco-boop-001/CHIM-PrivateConversation) | [Full guide, installation and examples](plugins/private_conversation/README.md) |

Both are **server extensions** using CHIM's existing client. These releases require no extra ESP/ESL or Papyrus companion and consume no Skyrim plugin slot. They are development candidates, not a promise that every LLM has finally learned social boundaries.

## Quick installation

Choose one route per plugin/server. **Replace older enabled packages; do not stack versions or mix competing sync and Plugin Manager sources.** Download version-specific assets and check that release's `SHA256SUMS.txt`.

| Plugin | Plain MO2 import | Manual CHIM sync | Repository/Plugin Manager archive |
| --- | --- | --- | --- |
| Mind Poisoning 0.1.17 | [MO2 ZIP](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.17/mind_poisoning-0.1.17-mo2.zip) | [DWPkg](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.17/mind_poisoning-0.1.17.dwpkg) | [Tar archive](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.17/mind_poisoning.tar.gz) |
| Private Conversation 0.1.14 | [MO2 ZIP](https://github.com/Francisco-boop-001/CHIM-PrivateConversation/releases/download/private_conversation-v0.1.14/private_conversation-0.1.14-mo2.zip) | [DWPkg](https://github.com/Francisco-boop-001/CHIM-PrivateConversation/releases/download/private_conversation-v0.1.14/private_conversation-0.1.14.dwpkg) | [Tar archive](https://github.com/Francisco-boop-001/CHIM-PrivateConversation/releases/download/private_conversation-v0.1.14/private_conversation.tar.gz) |

The MO2 ZIP contains `CHIM/server-plugins/<plugin>/<version>.dwpkg`. Keep `CHIM` directly under the data root. For manual sync, rename the raw DWPkg to `<version>.dwpkg` and place it at `Data/CHIM/server-plugins/<plugin>/<version>.dwpkg`; do not unpack it into Data. The tar archive is a separate server installer format with one wrapper directory stripped once. Detailed guides explain the routes and MO2's possible CHIM-only content warning.

After sync/install, verify the **installed** version in CHIM Plugin Manager and open **Plugin Page**. A download filename is not running-code evidence. Use an isolated CHIM server/database for deliberate tests; a disposable Skyrim save alone does not isolate relationships saved on the server.

Official CHIM catalog entries have not been submitted or approved. Each plugin now has its own deployment repository; `CHIM-Plugins` remains the historical collection and migration hub. See the [deployment and migration guide](docs/deployment-migration.md) before upgrading an existing install. Avoid `releases/latest`: it cannot tell which of our bad decisions you wanted.

## How to cause useful drama

### Private conversation

Choose Aela and Lydia on the Private Conversation page, exclude the player, and arm the scene. Then submit ordinary **Standard** input in-game:

> Aela brings up Nazeem's arrogance. Lydia considers whether the criticism is fair.

The browser stages settings; the game input starts the generation. The plugin converts that input into CHIM's `instruction` event, selects Aela's profile, replaces the current nearby/audience section and constrains the listener to Lydia. CHIM generates the dialogue and plays it through its normal pipeline. Further pair turns depend on CHIM rechat behavior. For an already active scene, a participant may still qualify when listed in the current close report, seen in a close report within the prior 60 seconds, or listed once in CHIM's wider “beings in range” report; starting a scene still requires close and AI-active evidence. This is the source contract, not a new live-behavior claim.

Your direction is not passed off as something Aela already said. With the player included instead, ordinary input remains player speech. With the player excluded, use scene directions rather than `Hello, Lydia`, unless you enjoy debugging an identity crisis you personally commissioned.

### Spoken reflection

Enable **Solo reflection**, choose Lydia and arm it. Enter:

> Lydia thinks aloud about her recent encounters with Nazeem and whether her judgment has been unfair.

The plugin selects Lydia, injects thinking-aloud guidance and fixes the listener to CHIM's `explicit_disable_rechat` marker. One generated response follows; another input can request another reflection. There is no new background monologue loop.

With Mind Poisoning 0.1.15 enabled, an exact registered output and matching native speech acknowledgement can lead to evaluation of **Lydia's own opinion of Nazeem**, grounded in her profile and relevant prior history. Repeating a reflection against unchanged evidence cannot endlessly farm the same solo change.

Mind Poisoning 0.1.15 introduced the opt-in v2 full-reply API with a cap of eight lines; Mind Poisoning 0.1.16 raises that cap to 24. Private Conversation 0.1.14 retains the required v1 API check and uses v2 only when its exact capability and evaluator are available and it can prove the reply grouping from that request's source rows. If grouping cannot be proven or the negotiated cap is exceeded, it falls back to evaluating the final line through v1. The imported PCV 0.1.14 README's `.14–.15` final-line-only summary is broader than MP 0.1.15's published v2 support; the imported README is kept byte-identical to its tag. Neither a final ACK nor emitted source rows prove that earlier lines were heard.

### Gossip with consequences

Mind Poisoning distinguishes the opinion owner:

- **Aela speaks to Lydia about Nazeem:** Lydia's opinion of Nazeem may change. Aela does not automatically receive the same update.
- **Lydia reflects about Nazeem:** Lydia may revise her own opinion.
- **The Player praises Farkas to Aela:** the separate Player-input path may affect Aela's opinion of Farkas.

These are illustrative directions and possible outcomes, not gameplay recordings. Praise, slander, balanced discussion and zero changes are legitimate outcomes. Nobody has to believe you. A resolvable gossip subject does not have to be one of the selected nearby speakers.

With overhearing enabled, one eligible NPC speech acknowledgement can evaluate the addressed listener plus up to four eligible NPCs from that source event's roster. It is off by default; roster membership is not proof that an NPC heard the line or participated in a Private Conversation scene. Sentinel-targeted PCV wrap-up lines have no addressed NPC and are skipped before the MP 0.1.17 witness batch; when SHARMAT pins a member listener instead, eligibility follows the actual source, ACK, and ordinary MP gates. Fixture results do not establish real provider, database, or gameplay behavior.

Changes are small validated affinity deltas, **-5 to +5**, bounded to **-100 to +100**. Locks, pause/Off state, uncertain identity, stale/duplicate evidence and failed evaluations prevent effects. CHIM's A↔B relationship processing is separate. These plugins do not set Skyrim relationship ranks, complete quests or convert an accusation into shared world truth. Bethesda did not ship a libel tribunal; neither do we.

## How it is coded

The existing root `server/` is Mind Poisoning. Private Conversation lives under [`plugins/private_conversation/`](plugins/private_conversation/). Package builders allowlist runtime files; source tests, previews and historical experiments do not become installed payload by accident.

Private Conversation uses PHP request/prompt/response hooks, local scoped state and a small browser polling script. It replaces current nearby context, switches the initial actor profile and constrains supported speaker/listener routes. CHIM owns generation, speech synthesis and client delivery. It reads CHIM's existing no-chat presence reports; browser reads never manufacture fresh reports.

Mind Poisoning uses exact event correlation, the configured relationship model connector, validated judgments and guarded persistence. Affinity, event ledger and history snapshot commit together. Model/network work stays outside transaction locks. The optional reflection APIs keep opinion ownership with the reflecting actor rather than inventing a second witness. `RequestLog` also offers an optional sanitized, request-local observer for companion diagnostics; its validated correlation IDs are intentional, while raw dialogue, prompts and debug rationale are excluded from the observer copy. See the [v0.1.17 release notes](distribution/mind_poisoning-v0.1.17.md), [developer guide](docs/development.md) and [PCV source map](plugins/private_conversation/README.md#how-it-is-coded) for details and failure limits.

## Limits, because marketing can sit down

- **Current scene scoping is not total prompt erasure.** Memories, history, profiles and other retained contributions can mention the player or absent people. The nearby/audience section is replaced; the whole character's past is not lobotomized.
- **No physical secrecy guarantee.** Doors, floors, earshot, vanilla greetings and native facing are not enforced. Director and a separate early rolemaster path are outside PCV's inspected Standard guards.
- **Presence is bounded evidence.** PCV polls every 15 seconds while visible. Activity observations expire at projected age 45 seconds. CHIM's broader scan/recent activity is not exact distance or instantaneous deactivation proof; ambiguous or stale evidence becomes unavailable.
- **Acknowledgement is not an audio recording.** Native `_speech` reports a line attempt. Exact matching authorizes the supported evaluation path; it does not prove hearing. PCV 0.1.14 can register the proven reply lines up to the negotiated API cap; otherwise solo reflection uses the final emitted line.
- **Server state is persistent.** Removing a plugin does not undo stored opinions. No active playthrough profile means shared scope; changing Skyrim saves alone does not establish isolation. Back up the server before experiments you want to reverse.
- **These are PRE-ALPHA.** Source/fixture/package checks and local browser artwork review are distinct from live installation, provider/database behavior and game acceptance.

The [0.1.10 live acceptance record](tasks/live-acceptance-v0.1.10.md) documents two user-reported **Player-origin** Mind Poisoning commits, **7→4** and **10→12**, plus expected no-subject and locked skips, corroborated through the dashboard and CHIM relationship editor. That evidence belongs to those cases. A separate [2026-10-02 clone report](tasks/reflection-reply-cap-2026-10-02.md) records user-reported pair-path affinity deltas of **-1, -1 and 0** for Lidia/Aela/Bruce Wayne, short-name resolution for Bruce, and no-subject skips. This supplied live-server evidence was not independently verified from logs and does not establish gameplay, solo full-reply behavior, or general NPC-origin reliability. Neither report validates clean installation or concurrency/failure recovery. Historical releases remain on the [collection repository](https://github.com/Francisco-boop-001/CHIM-Plugins/releases); new releases use the two deployment repositories above.

## Debugging and support

Mind Poisoning's **Plugin Page → Logs → Download filtered plugin log** provides a bounded export. Private Conversation 0.1.14 retains the protected **Logs** view and sanitized JSONL export; the private CLI entrypoint uses the same bounded reader. It also adds bounded recovery when a valid solo `_speech` acknowledgement reaches PCV before exact output registration: receipt metadata is capped at eight entries/8 KiB and 45 seconds from the original receipt time, then requires the exact registration and a unique native speech row. The separate effect claim may remain active for up to 600 seconds. A matching line ACK is not proof of playback, and process or transaction failures can still lose recovery. The page uses session CSRF protection but is not a login system: access requires exact loopback without forwarding headers or trusted server `REMOTE_USER`. This source check does not certify a deployed proxy topology. See its [logging section](plugins/private_conversation/README.md#logs-and-troubleshooting). Ordinary PCV logs omit prompts and dialogue; Mind Poisoning's optional debug rationale needs separate care before sharing.

Report installed versions, CHIM/client revision if known, exact settings, reproduction steps, expected/observed behavior and filtered logs. Omit credentials and full private conversation dumps. "It doesn't work" is a mood, not a bug report.

[Issues and support](https://github.com/Francisco-boop-001/CHIM-Plugins/issues) · [Mind Poisoning player guide](docs/mind-poisoning.md) · [Private Conversation guide](plugins/private_conversation/README.md)

Maintained by [Francisco](https://github.com/Francisco-boop-001), built for [CHIM by Dwemer Dynamics](https://github.com/Dwemer-Dynamics/CHIM). They made the platform. I brought the llama and made things awkward.
