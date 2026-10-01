# World of Drama-Llama — Francisco's CHIM Plugins

Skyrim already has dragons, a civil war and Nazeem. Naturally, what it needed next was gossip with consequences and NPCs having conversations that are not automatically about you. You're welcome. Condolences.

**World of Drama-Llama** is my series of CHIM server plugins for character-driven drama: private scene direction, spoken reflection and individual opinions that can change through praise, slander or reconsideration. The emo llama supervises. Its qualifications remain disputed.

![Two characters whisper in a snowy landscape while an emo llama lurks at the side.](assets/dashboard-art-source.png)

## Pick your particular disaster

| Plugin | Purpose | Candidate | Guide |
| --- | --- | --- | --- |
| **Mind Poisoning** | A speaks to B about C; B may revise an opinion of C. Compatible solo reflection lets A reconsider an opinion of C. | [0.1.13 PRE-ALPHA](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.13) · [release notes](distribution/mind_poisoning-v0.1.13.md) | [Player guide](docs/mind-poisoning.md) · [Developer guide](docs/development.md) · [Dashboard/logs](docs/dashboard.md) |
| **Private Conversation** | Direct a two-NPC scene or one NPC thinking aloud, with current audience scoping and optional Mind Poisoning effects. | [0.1.4 PRE-ALPHA](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/private_conversation-v0.1.4) | [Full guide, installation and examples](plugins/private_conversation/README.md) |

Both are **server extensions** using CHIM's existing client. These releases require no extra ESP/ESL or Papyrus companion and consume no Skyrim plugin slot. They are development candidates, not a promise that every LLM has finally learned social boundaries.

## Quick installation

Choose one route per plugin/server. **Replace older enabled packages; do not stack versions or mix competing sync and Plugin Manager sources.** Download version-specific assets and check that release's `SHA256SUMS.txt`.

| Plugin | Plain MO2 import | Manual CHIM sync | Repository/Plugin Manager archive |
| --- | --- | --- | --- |
| Mind Poisoning 0.1.13 | [MO2 ZIP](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.13/mind_poisoning-0.1.13-mo2.zip) | [DWPkg](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.13/mind_poisoning-0.1.13.dwpkg) | [Tar archive](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.13/mind_poisoning.tar.gz) |
| Private Conversation 0.1.4 | [MO2 ZIP](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/private_conversation-v0.1.4/private_conversation-0.1.4-mo2.zip) | [DWPkg](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/private_conversation-v0.1.4/private_conversation-0.1.4.dwpkg) | [Tar archive](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/private_conversation-v0.1.4/private_conversation.tar.gz) |

The MO2 ZIP contains `CHIM/server-plugins/<plugin>/<version>.dwpkg`. Keep `CHIM` directly under the data root. For manual sync, rename the raw DWPkg to `<version>.dwpkg` and place it at `Data/CHIM/server-plugins/<plugin>/<version>.dwpkg`; do not unpack it into Data. The tar archive is a separate server installer format with one wrapper directory stripped once. Detailed guides explain the routes and MO2's possible CHIM-only content warning.

After sync/install, verify the **installed** version in CHIM Plugin Manager and open **Plugin Page**. A download filename is not running-code evidence. Use an isolated CHIM server/database for deliberate tests; a disposable Skyrim save alone does not isolate relationships saved on the server.

Official CHIM catalog entries have not been submitted or approved. This multi-plugin repository uses separate versioned releases and per-plugin manifest URLs. Avoid `releases/latest`: it cannot tell which of our bad decisions you wanted.

## How to cause useful drama

### Private conversation

Choose Aela and Lydia on the Private Conversation page, exclude the player, and arm the scene. Then submit ordinary **Standard** input in-game:

> Aela brings up Nazeem's arrogance. Lydia considers whether the criticism is fair.

The browser stages settings; the game input starts the generation. The plugin converts that input into CHIM's `instruction` event, selects Aela's profile, replaces the current nearby/audience section and constrains the listener to Lydia. CHIM generates the dialogue and plays it through its normal pipeline. Further pair turns depend on CHIM rechat behavior.

Your direction is not passed off as something Aela already said. With the player included instead, ordinary input remains player speech. With the player excluded, use scene directions rather than `Hello, Lydia`, unless you enjoy debugging an identity crisis you personally commissioned.

### Spoken reflection

Enable **Solo reflection**, choose Lydia and arm it. Enter:

> Lydia thinks aloud about her recent encounters with Nazeem and whether her judgment has been unfair.

The plugin selects Lydia, injects thinking-aloud guidance and fixes the listener to CHIM's `explicit_disable_rechat` marker. One generated response follows; another input can request another reflection. There is no new background monologue loop.

With Mind Poisoning 0.1.13 enabled, an exact registered output and matching native speech acknowledgement can lead to evaluation of **Lydia's own opinion of Nazeem**, grounded in her profile and relevant prior history. Repeating a reflection against unchanged evidence cannot endlessly farm the same solo change.

### Gossip with consequences

Mind Poisoning distinguishes the opinion owner:

- **Aela speaks to Lydia about Nazeem:** Lydia's opinion of Nazeem may change. Aela does not automatically receive the same update.
- **Lydia reflects about Nazeem:** Lydia may revise her own opinion.
- **The Player praises Farkas to Aela:** the separate Player-input path may affect Aela's opinion of Farkas.

These are illustrative directions and possible outcomes, not gameplay recordings. Praise, slander, balanced discussion and zero changes are legitimate outcomes. Nobody has to believe you. A resolvable gossip subject does not have to be one of the selected nearby speakers.

Changes are small validated affinity deltas, **-5 to +5**, bounded to **-100 to +100**. Locks, pause/Off state, uncertain identity, stale/duplicate evidence and failed evaluations prevent effects. CHIM's A↔B relationship processing is separate. These plugins do not set Skyrim relationship ranks, complete quests or convert an accusation into shared world truth. Bethesda did not ship a libel tribunal; neither do we.

## How it is coded

The existing root `server/` is Mind Poisoning. Private Conversation lives under [`plugins/private_conversation/`](plugins/private_conversation/). Package builders allowlist runtime files; source tests, previews and historical experiments do not become installed payload by accident.

Private Conversation uses PHP request/prompt/response hooks, local scoped state and a small browser polling script. It replaces current nearby context, switches the initial actor profile and constrains supported speaker/listener routes. CHIM owns generation, speech synthesis and client delivery. It reads CHIM's existing no-chat presence reports; browser reads never manufacture fresh reports.

Mind Poisoning uses exact event correlation, the configured relationship model connector, validated judgments and guarded persistence. Affinity, event ledger and history snapshot commit together. Model/network work stays outside transaction locks. The optional reflection API keeps opinion ownership with the reflecting actor rather than inventing a second witness. `RequestLog` also offers an optional sanitized, request-local observer for companion diagnostics; the observer excludes opt-in debug rationale while the normal sink retains its existing behavior. See the [v0.1.13 release notes](distribution/mind_poisoning-v0.1.13.md), [developer guide](docs/development.md) and [PCV source map](plugins/private_conversation/README.md#how-it-is-coded) for details and failure limits.

## Limits, because marketing can sit down

- **Current scene scoping is not total prompt erasure.** Memories, history, profiles and other retained contributions can mention the player or absent people. The nearby/audience section is replaced; the whole character's past is not lobotomized.
- **No physical secrecy guarantee.** Doors, floors, earshot, vanilla greetings and native facing are not enforced. Director and a separate early rolemaster path are outside PCV's inspected Standard guards.
- **Presence is bounded evidence.** PCV polls every 15 seconds while visible. Activity observations expire at projected age 45 seconds. CHIM's broader scan/recent activity is not exact distance or instantaneous deactivation proof; ambiguous or stale evidence becomes unavailable.
- **Acknowledgement is not an audio recording.** Native `_speech` reports a line attempt. Exact matching authorizes the supported evaluation path; it does not prove hearing. Solo currently registers only the last emitted chunk.
- **Server state is persistent.** Removing a plugin does not undo stored opinions. No active playthrough profile means shared scope; changing Skyrim saves alone does not establish isolation. Back up the server before experiments you want to reverse.
- **These are PRE-ALPHA.** Source/fixture/package checks and local browser artwork review are distinct from live installation, provider/database behavior and game acceptance.

The [0.1.10 live acceptance record](tasks/live-acceptance-v0.1.10.md) documents two user-reported **Player-origin** Mind Poisoning commits, **7→4** and **10→12**, plus expected no-subject and locked skips, corroborated through the dashboard and CHIM relationship editor. That evidence belongs to those cases. It does not validate current solo integration, general NPC-origin delivery, clean installation or concurrency/failure recovery. Prior releases remain available on [GitHub Releases](https://github.com/Francisco-boop-001/CHIM-Plugins/releases).

## Debugging and support

Mind Poisoning's **Plugin Page → Logs → Download filtered plugin log** provides a bounded export. Private Conversation has a private CLI reader with request/configuration filters; see its [logging section](plugins/private_conversation/README.md#logs-and-troubleshooting). Outcomes include timestamps, success/skip/failure status and fixed reasons. Ordinary PCV logs do not dump prompts or dialogue; Mind Poisoning's optional debug rationale needs separate care before sharing.

Report installed versions, CHIM/client revision if known, exact settings, reproduction steps, expected/observed behavior and filtered logs. Omit credentials and full private conversation dumps. "It doesn't work" is a mood, not a bug report.

[Issues and support](https://github.com/Francisco-boop-001/CHIM-Plugins/issues) · [Mind Poisoning player guide](docs/mind-poisoning.md) · [Private Conversation guide](plugins/private_conversation/README.md)

Maintained by [Francisco](https://github.com/Francisco-boop-001), built for [CHIM by Dwemer Dynamics](https://github.com/Dwemer-Dynamics/CHIM). They made the platform. I brought the llama and made things awkward.
