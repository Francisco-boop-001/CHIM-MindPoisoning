# Mind Poisoning 0.1.18 independent source review — 2026-10-04

## Status

Initial review complete; the clean-export, Plugin Manager consumer, and package proofs are pending. This is not the final release approval.

## Scope and reviewed revisions

Compared published tag `mind_poisoning-v0.1.17` (`bd5f5ae341f41e8762dc658f6c68a5b7f27cc18c`) with source HEAD `a83da0a4d5e342e79aecc411afbbeff96363b7d1` and the current relationship-fix working tree. The review was read-only except for this report and the fixture provenance clarification in `tests/fixtures/README.md`. No PHP tests, WSL commands, live services, providers, or databases were run for this review; the prior focused results below are attributed to `tasks/relationship-compatibility-2026-10-04/verification.md`.

## Source findings

The Mind Poisoning behavior delta since 0.1.17 has two parts:

1. Commit `a83da0a` excludes the reserved `explicit_disable_rechat` marker from both addressed ACK recipient resolution and the overhearer roster. Its test includes a same-named NPC catalog row, including case variation. This closes a real reserved-marker/catalog-collision path.
2. The current candidate repairs empty stored relationship maps and canonical Player aliases across preflight, Player credibility/model context, reflection, dashboard reads, and locked persistence. The strict stored-map boundary accepts a `stdClass` or exactly `[]`; explicit `null` and nonempty PHP arrays fail closed. The pinned CHIM `RelationshipManager` normalizer selects only the Player edge, with `PLAYER_NAME` restored afterward. A nonzero Player-subject write recalculates from the locked row and removes legacy aliases in the same persistence transaction; NPC-only, Player-speaker-only, and zero-delta work does not rewrite aliases.

The affected source paths are `server/influence.php`, `server/store.php`, `server/prerequest.php`, `server/reflection.php`, and `server/dashboard_data.php`. The PostgreSQL writer guards malformed JSON relationship shapes and turns an exact empty array into an object when an actual relationship write occurs. The existing verification record covers request/model behavior in MemoryStore and direct `PostgresStoreDb::writeNpc` commit/rollback behavior. It explicitly does not claim a full PostgreSQL `persistJudgments` pipeline, native CHIM dispatch, provider, installed-order, audio, or gameplay proof.

I found no blocking source regression in these reviewed paths. The distinction between malformed Player relationship values and genuine NPC catalog identity ambiguity remains intact. The source tests use the actual pinned CHIM normalization methods rather than a copied weighting implementation.

## Fixture provenance and line endings

The fixture file’s fetched-byte SHA-256 is `4497443C9480C7D7FF5D5DEABCEC9AD5AB4CDEC6F28F1FD044F89594D71E06AB`. It contains 1,315 CRLF and 50 LF line endings. Because `.gitattributes` specifies `*.php text eol=lf`, Git stores the normalized 55,640-byte content with SHA-256 `C68D8BEB88D08C984846FF680D858116EB4C6735E5F1D7AE9AD44B094815DC56` and blob ID `868b2ea4cb7ebf5b2069c1b3a7303fec028472c8`. Both identities are recorded in `tests/fixtures/README.md`; the fetched hash must not be described as the clean-export blob hash. The fixture is test-only and is not in the runtime package allowlist.

## Metadata and PRE-ALPHA assessment

The current draft manifest is version `0.1.18`, retains the `candidate` channel and `development_candidate` status, and uses a version-token URL that resolves to the versioned `mind_poisoning-v0.1.18` release. The draft release notes and current documentation describe the canonical Player and empty-map behavior while distinguishing the earlier v0.1.10 live acceptance and v0.1.15 user-reported observations from this candidate's isolated checks. The official CHIM catalog entry is not submitted or approved; no current catalog-policy review is claimed.

The pinned CHIM `docs/custom-plugins.md` and `docs/building.md` were not present in the local core snapshot, and the official web fetch returned a cache miss. The lead's pinned Plugin Manager check proves schema-2 manifest/update metadata resolution against the inspected consumer, including candidate-channel and entry-order matching; it does not prove archive extraction, installation, or current catalog-policy compliance.

These specific fixes can ship as PRE-ALPHA without an installed/live validation claim. The prior record supports focused PHP behavior, an isolated PostgreSQL direct-writer transaction check, syntax checks, and whitespace checks. It does not establish installed CHIM, provider, player-database, audio, or in-game behavior. Keep those limits in the published notes. The versioned links and candidate artifacts are not considered verified public assets by this review.

## Release gates still pending

- Verify the reviewed source through a clean Git export and the pinned Plugin Manager consumer check.
- Build repository archive, CHIM sync package, and MO2 ZIP using the deterministic builder; inspect allowlisted members, checksums, and format-specific layout.
- Rebuild from the clean release tag and compare source files and package bytes with the upload candidates, then verify downloaded assets and public release state.
- Stage only the explicit Mind Poisoning release allowlist. The shared worktree contains unrelated Private Conversation changes, audit/recovery files, images, and other task material that must not enter this release.

The builder scripts are unchanged since v0.1.17. A lead-owned release plan and metadata checklist remain authoritative for those pending gates; this report does not replace their package/publication receipts.

## Final review and publication record

The independent reviewer subsequently approved the frozen source and packages for PRE-ALPHA publication after checking the clean tag, source equality, four asset hashes/internal checksums, format-specific layouts and clean-export transcript. Lead-owned draft/public download and public-state/main gates then passed. See the [publication receipt](release-v0.1.18.md). These gates do not establish live installation, provider, player database or gameplay behavior.
