# Overheard gossip and PCV hub update — 2026-10-03

## Approved scope

The user approved the preceding design and asks for an evidence-led opinion on excessive defensive programming while work starts. Extend the existing NPC-acknowledgement flow: one bounded model call evaluates the addressed listener plus up to four additional NPC witnesses from the exact source event. Overhearing is off by default. Each NPC judges credibility in context; no mechanical delta reduction. Preserve Player-input and reflection APIs. Update the hub to the already-published PCV 0.1.12 immutable source and download links after source/package verification.

This is a bounded extension to existing evaluation and per-listener persistence, not a new job system or group transaction. Success means truthful per-listener outcomes, no duplicate paid evaluation after processing, exact witness authorization revalidated before writes, one call for a batch, and retained lock/scope/pause behavior. Roster membership is not audible-playback proof.

## Baseline and boundaries

- Branch `work/mind-poisoning`, HEAD `9eff9b280cb06526050467653b13f9563a4d93e2`; published MP pin `mind_poisoning-v0.1.16`, PRE-ALPHA.
- Embedded PCV snapshot is 0.1.8. Separate PCV dev checkout is 0.1.12 and read-only for this task. PCV 0.1.12 public prerelease metadata and supplied DWPkg digest were checked in the preceding read-only investigation.
- Protect dirty `plugins/private_conversation/tasks/logging-improvements-plan.md` exactly, SHA-256 `93125ca439851affdcb38aa59ccb08db5f16970022f101edc636b6ed6477c4bf`; preserve all untracked critique/submission/screenshot files.
- No install, provider/database call, gaming-distro command, commit/push/publication, old asset replacement or MP version/pin advancement. Runtime fixtures, if needed, use only an independent PHP runtime or the existing test clone under root AGENTS.md and its Windows preflight/shutdown rules.
- Windows-only preflight on this date reports all four listed WSL distros stopped. The current gaming VHD baseline is LastWriteTimeUtc `2026-10-03T14:08:41.6225615Z`, ticks `639266333216225615`, size `182184837120`; compare against this session's baseline, not yesterday's values. No distro was entered for this preflight.

## Ownership and increments

1. `hub_pcv12`: immutable PCV snapshot and current README/documentation links; verify tag/build/public asset correspondence before advancing the hub's PCV pin. Preserve protected dirty task bytes and disclose any snapshot exception. No MP PHP/test edits.
2. `overhearing_flow`: complete affected ACK/model/persistence/logging path and focused tests, with necessary packaging allowlist adjustments only. No hub/readme/version edits. Trace first, reuse existing parser and atomic per-listener save, implement the smallest complete flow.
3. `defensive_review`: baseline read-only assessment with concrete examples and counterarguments; then reuse for independent feature diff review. No parallel product edits.
4. Lead: plan/ledger, review each diff/call path and focused output, safe verification orchestration, return defects to owners, record final limitations. Lead writes no product code.

## Verification gates

- Hub: clean published tag source, exact included snapshot inventory, deterministic consumer package hashes matching actual published downloads; PCV0.1.12 remains PRE-ALPHA and MP0.1.16 unchanged.
- Feature: isolated OFF/direct compatibility, ON one-call multiple listeners, safe identities/exclusions, per-listener self-subject filtering, addressed-lock independence, replay, changed membership/setting/state under locks, malformed batch/no mutation, and truthful partial failure/uncertain commit.
- Relevant existing direct/Player/reflection and observer seams only where touched or the PCV snapshot changes integration consumers. Changed PHP syntax checks are a distinct gate. No broad suite without a concrete remaining risk.
- Review every changed diff, affected callers/failure paths and complete targeted outputs. Stop optional testing once these risks are covered.

## Review

Implementation verification is complete, as recorded below. The [defensive-programming assessment](defensive-programming-review-2026-10-03.md) found no basis for broad guard removal; the affected-path owner corrected concrete duplication and failure-classification issues. Most apparently repeated checks protect different trust or timing boundaries.

The new ACK fixture was copied into an unchanged export of baseline commit `9eff9b2`. It failed with exit 255 at the assertion that the addressed NPC and overhearer commit independently; no new-function dependency caused the failure. Full outputs are retained under ignored `dist/overhearing-2026-10-03/red.*.txt`. This is an isolated behavioral baseline, with an in-memory store and injected model function; no live provider or database was used. The clone was terminated and gaming VHD metadata stayed unchanged.

Hub verification is complete: all three PCV 0.1.12 packages and the checksum file match their actual public downloads. Lead independently compared the imported snapshot with the immutable tag export: 171 of 172 files match exactly; the protected dirty plan is the sole exception and retains its original SHA-256. Historical MP 0.1.16's observer fixture embeds PCV 0.1.8, but the current working-tree fixture dynamically loads the updated PCV 0.1.12 snapshot. Current fixture execution remains pending.

Supported opt-in setting source was read only from the disposable clone, with an explicit distro guard and clone shutdown afterward. `chimGetGeneralSettingBool` delegates to `chimGetGeneralSettingRow`, which performs an escaped fresh SELECT through the core adapter on each call; no cache exists in that getter chain at the inspected revision. The setter uses checked escaped upsert. Captured source: ignored `dist/overhearing-2026-10-03/settings-source.txt`. The first probe output was unavailable, and the initial capture omitted the preceding row helper; those evidence gaps were resolved with source-only captures. No PHP or database query was executed by these probes. The gaming VHD metadata still matches this session's baseline. Preserve this distinction between reading implementation and exercising settings on a live database.

A later source-only clone probe was refused by Windows preflight because `DwemerAI4Skyrim3-test` was already running outside this task. Neither CHIM distro was entered or stopped by that attempt. Windows-only state inspection confirmed gaming stopped and its VHD metadata unchanged; verification awaits an exclusive clone window. The user was asked to clarify availability while implementation/review continue.

The transaction compatibility concern was checked instead against public `lib/postgresql.class.php` at exact core revision `cf5030f15781637498be86debe26fcf102f5690d`, captured in ignored `postgresql-public-source.txt`. `fetchOne()` calls `re_connect()`, which retains a healthy native connection and reconnects only an unavailable/bad connection. No normal-path transaction break was demonstrated; existing native connection identity checks must remain. This is source evidence, not a database exercise.

## Final isolated verification

The user subsequently authorized the alternative test clone, expressly requiring the gaming distro on D: to remain untouched. The guarded Windows runner refused a running gaming distro or changed gaming VHD metadata, entered only `DwemerAI4Skyrim3-test`, and terminated only that clone afterward. No gaming-distro command, service administration, installation, provider call or real database exercise was performed.

Initial new-path runs exposed a real prompt-building defect: the reused ordinary builder expected the existing `promptNpcCopy()` conversion, while the new batch path passed raw JSON-backed listener data. The owner reused that conversion for each selected recipient; the new ACK fixture then passed. Review also returned and resolved Player-event lookup misrouting, addressed-listener preflight blocking extras, log-observer inheritance/reentrancy, redundant preflight reads, recipient cleanup attribution, parser/internal-failure classification, a transient callback-signature mismatch, and hidden unexpected preflight failures. The roster scan now stops once a fifth eligible extra establishes that the whole extra audience is over cap.

Final command, from this repository in PowerShell:

```powershell
& ./dist/overhearing-2026-10-03/run-isolated.ps1 -Fixtures @(
    'tests/overhearing_test.php',
    'tests/runtime_test.php',
    'tests/dashboard_data_test.php',
    'tests/logging_test.php',
    'tests/store_logging_test.php',
    'tests/reflection_reply_test.php',
    'tests/reflection_observer_test.php',
    'plugins/private_conversation/tests/reflection_full_reply_check.php'
)
```

All eight fixtures exited 0 using PHP 8.2.29. The runner also linted the eight changed server PHP files and three changed/new PHP test files; all eleven syntax checks exited 0. These are separate syntax and isolated execution gates.

| Check | Concrete evidence |
| --- | --- |
| Overhearing ACK | OFF preserves direct behavior; ON uses one injected model call; maximum six scene members produce five independent listener ledgers and terminal records, including a confirmed zero-change recipient. Identity/exclusion, over-cap fallback, post-model duplicate names, membership/delivery/setting/lock changes, replay, malformed batch, partial failure and uncertain commit are checked. Witness lookup exceptions produce fixed error records with diagnostics off, without exception text, while direct processing continues. |
| Existing runtime | Existing direct and Player behavior passes against the in-memory store. Stderr contains only the two expected injected persistence failures: `snapshot-verification-failed` and `player-alias-ambiguous`. |
| Dashboard data/view | Closed role/batch sanitization, recipient distinction and honest roster/playback wording pass. This is executed rendering/data evidence, not a browser screenshot. |
| Logging and store logging | Existing sanitization, lifecycle/observer containment, commit/cleanup diagnostic behavior pass. |
| Reflection reply | Existing v2 reply behavior passes. All 46 stderr lines parse as sanitized structured records for deliberate cases; no PHP error text occurred. |
| MP observer to PCV importer | Five cases pass against the dynamically loaded embedded PCV 0.1.12: changed commit, zero change, provider failure, unconfirmed commit and warning skip. |
| PCV full reply | Ordered lines `[101,104]` and a thirteen-line reply route through v2; missing/ambiguous anchors, other request, ordinary actor line, aborted line, incorrect final ordering and over-cap lines fall back. |

Full stdout/stderr/exit files and lint output are retained in ignored `dist/overhearing-2026-10-03/green/`. The eleven executed changed-file hashes match `final-source-freeze.json` after execution. Runtime receipt: fixture exit 0, test-clone stop exit 0, `GamingVhdUnchanged=true`. Final Windows-only WSL state confirms both CHIM distros stopped (unrelated Kali remains running); the gaming VHD retains ticks `639266333216225615` and size `182184837120`. A sandboxed enumeration was denied; the authorized Windows-only enumeration supplied the successful final state without entering any distro.

Owned-file `git diff --check` passes. Whole-repository whitespace checking reports only the pre-existing protected PCV plan's final blank line; its original hash remains intact, so that unrelated file was not normalized. Product manifest remains 0.1.16. Independent reviewer `defensive_review` inspected the frozen catch/cap/fixture changes and actual output and found no remaining blocker. Lead inspected every affected product diff, callers, persistence/cleanup paths and verification output; all returned defects were handled by the original owner. The shared speaker-copy failure caveat is documented in the defensive-programming assessment rather than represented as a newly fixed regression.

## Evidence limits and release status

The new feature is UNRELEASED and off by default. Published MP remains `mind_poisoning-v0.1.16` PRE-ALPHA; the hub source snapshot/link now points to the already-published PCV 0.1.12 after immutable source and public package verification. No install, commit, push, publication or MP release/version change was performed.

In-memory stores and injected model callbacks establish code behavior, not PostgreSQL durability, native settings execution, real provider batch correctness/latency or game playback. The exact roster is eligibility evidence, not proof of hearing or PCV scene provenance. Each listener commits independently, so partial outcomes are possible. Existing ledger checks prevent a processed sequential replay from making another model call; they do not reserve a provider call against concurrent in-flight duplicate ACKs. Reflection v1/v2 and Player-input contracts remain unchanged.
