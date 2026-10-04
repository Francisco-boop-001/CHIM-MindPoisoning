# Three-plugin hub overview — 2026-10-04

## Read-only sources

| Plugin | Published PRE-ALPHA tag | Release source commit | Guide |
| --- | --- | --- | --- |
| Mind Poisoning | mind_poisoning-v0.1.18 | 83908fe9e6a9962a51ccd3e409d2c586bc46e346 | [Player guide](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/blob/mind_poisoning-v0.1.18/docs/mind-poisoning.md) |
| Private Conversation | private_conversation-v0.1.15 | 5d003023e45058ec94ec4c22727552a352069e83 | [Tagged README](https://github.com/Francisco-boop-001/CHIM-PrivateConversation/blob/private_conversation-v0.1.15/README.md) |
| Sworn & Scorned | sworn_and_scorned-v0.1.5 | 828042f2af02d4526b98adce200ddd9c6ea565d6 | [Tagged README](https://github.com/Francisco-boop-001/CHIM-SwornAndScorned/blob/sworn_and_scorned-v0.1.5/README.md) |

GitHub release metadata confirmed non-draft prerelease state and three distribution formats plus checksums for each. Separate read-only researchers inspected the published PCV and S&S docs. The older local PCV 0.1.4 checkout and embedded snapshot are not treated as current published documentation. No sibling project was modified or pulled into this checkout.

PCV scene direction works without MP; its optional registered solo-reflection opinion effect uses MP. S&S independently computes promise/grievance lifecycle deltas in apply.php/transitions.php and writes its own affinity and ledger through store.php at its release revision. MP is not a hard S&S dependency. S&S starts disabled, uses a selected cast and PHP CLI workers; the default evaluator is CHIM's relationship connector, with Jev an optional experimental choice. These are source/documentation findings, not a new live integration test.

Scope: README landing page, task ledger and this evidence record only. Preserve artwork, MP 0.1.18 manifest/payload, PCV snapshot, unpublished critique/drafts/recovery material and historical tags/assets. Publish only to CHIM-Plugins origin/main; dedicated plugin repositories are read-only sources for this task. No package build, release creation, WSL, installed CHIM, live provider/database or modlist action.

## Verification and publication

The lead read the complete README and diff. The README owner corrected PCV physical-earshot/retained-memory limits, S&S example wording, the CHIM-versus-vanilla affinity boundary, historical pair-path attribution, logging directions, and the existing migration-guide link. Independent PCV review confirmed setup and identified the ordinary dialogue opinion path for inclusion alongside registered solo reflection. S&S source review confirmed no dedicated log-download viewer in 0.1.5, so the hub links its own guide instead of inventing a shared control.

Focused Windows checks passed: three public non-draft prerelease metadata records, all nine distinct download URLs exactly present in published asset inventories, seven relative file/image targets, five four-column table rows, and all seven protected-file SHA256 baselines. Scoped git diff --check passed; GitHub's Markdown API rendered the README in GFM mode. Raw release inventories, baseline hashes, readme-checks.json and rendered.html are retained under ignored dist/hub-overview-2026-10-04/.

Public hub main before this update: 00d4d4a1221676660f1b290e922a753c6885be4b. Documentation-only commit/push and public-byte verification are pending. No release pin advances: MP 0.1.18, PCV 0.1.15 and S&S 0.1.5 remain the published candidates. These checks verify documentation/source/link consistency, not viewport appearance or new runtime/gameplay behavior.
