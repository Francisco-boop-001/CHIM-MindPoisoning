# Mind Poisoning v0.1.16 — PRE-ALPHA candidate

This release changes one limit in the opt-in v2 full-reply reflection API: the maximum ordered reply length increases from 8 lines to 24. Reflection API v1, v2 capability version 2, the eight-subject limit, and the joined-text limits of 2,000 Unicode code points and 8,000 bytes remain unchanged. The embedded Private Conversation 0.1.8 integration remains a v1 caller; it has not adopted v2.

## Verification and evidence

The isolated reply fixture accepts and evaluates a 13-line reply, accepts the 24-line boundary, rejects 25 lines before model work or state mutation, and rejects joined-text overflow without truncation or mutation. It uses in-memory store/model seams and does not call a live provider or database.

A separate user-reported live-server report for the 0.1.15 pair path records affinity deltas of -1, -1, and 0 for Lidia/Aela/Bruce Wayne, short-name resolution for Bruce, and no-subject skips. Those results were not independently verified from logs. They do not establish the 0.1.16 cap behavior, v2 solo integration, general NPC-origin reliability, audio playback, or gameplay acceptance. See the [reply-cap evidence report](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/blob/mind_poisoning-v0.1.16/tasks/reflection-reply-cap-2026-10-02.md).

## Candidate packages

Use one server-package route per CHIM server and replace older enabled packages instead of stacking them. Existing 0.1.13 installs need the historical [CHIM-Plugins 0.1.14 bridge](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.14) before updates can move to this deployment repository. See the tagged [deployment and migration guide](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/blob/mind_poisoning-v0.1.16/docs/deployment-migration.md) and [integration API contract](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/blob/mind_poisoning-v0.1.16/docs/integration-api.md).

- [Repository archive for Plugin Manager](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.16/mind_poisoning.tar.gz)
- [CHIM sync package](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.16/mind_poisoning-0.1.16.dwpkg)
- [Plain MO2 import ZIP](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.16/mind_poisoning-0.1.16-mo2.zip)
- [Release page and checksums](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/tag/mind_poisoning-v0.1.16)

The maturity remains PRE-ALPHA. These fixtures do not establish live database durability, provider behavior, installation, extension order, or game/audio behavior. The CHIM compatibility reference remains `cf5030f15781637498be86debe26fcf102f5690d`; it is not an installation pin. The official CHIM catalog entry has not been submitted or approved.
