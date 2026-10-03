# Mind Poisoning v0.1.17 — PRE-ALPHA candidate

This source release adds opt-in NPC ACK overhearing. The `mind_poisoning_overhearing_enabled` setting is off by default; an administrator enables it with CHIM's supported `chimSetGeneralSetting` helper in the administrator-managed bootstrap. Mind Poisoning adds no setting-write endpoint. With the setting enabled, one NPC speech acknowledgement can evaluate the addressed listener plus up to four eligible additional NPCs from the exact source event's `eventlog.people` roster in one model call (maximum 4,096 tokens; ordinary addressed-only evaluation remains capped at 1,024). The capability marker is `\ChimMindPoisoning\MIND_POISONING_NPC_ACK_OVERHEARING_API_VERSION = 1`.

If more than four eligible extra listeners are present, the extras are skipped and addressed-listener evaluation continues normally. Each listener persists independently, so partial results are possible. Replay checks are sequential and do not reserve provider work across concurrent acknowledgements. Logs identify addressed versus overheard outcomes with `listener_role`, `addressed_listener_id`, and `batch_id`. Event roster membership does not prove audible playback or Private Conversation scene participation.

Published Mind Poisoning 0.1.16 retains its v2 full-reply API's 24-line cap. This 0.1.17 source tree carries the embedded Private Conversation 0.1.12 integration snapshot.

## Verification and evidence

Eight focused fixtures and eleven changed-file PHP syntax checks passed in the explicitly authorized disposable test clone. The fixtures cover the default-off/direct path, one-call batching, audience caps and identity checks, independent listener persistence, replay, failures and sanitized role-aware logs. They use an in-memory store and injected model behavior; native setting operations, live database durability, real provider behavior, installed CHIM behavior and game/audio delivery remain unverified.

## Candidate packages

Use one server-package route per CHIM server and replace older enabled packages instead of stacking them. Existing 0.1.13 installs still need the historical [CHIM-Plugins 0.1.14 bridge](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.14) before updates can move to this deployment repository. See the [deployment and migration guide](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/blob/mind_poisoning-v0.1.17/docs/deployment-migration.md) and [integration API contract](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/blob/mind_poisoning-v0.1.17/docs/integration-api.md).

- [Repository archive for Plugin Manager](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.17/mind_poisoning.tar.gz)
- [CHIM sync package](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.17/mind_poisoning-0.1.17.dwpkg)
- [Plain MO2 import ZIP](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.17/mind_poisoning-0.1.17-mo2.zip)
- [Release page and checksums](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/tag/mind_poisoning-v0.1.17)

The maturity remains PRE-ALPHA. These fixtures do not establish live database durability, provider behavior, installation, extension order, or game/audio behavior. The CHIM compatibility reference remains `cf5030f15781637498be86debe26fcf102f5690d`; it is not an installation pin. The official CHIM catalog entry has not been submitted or approved.
