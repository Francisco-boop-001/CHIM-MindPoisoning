# Final whole-branch review — reflection reply fixes

## Scope and evidence

Reviewed product range `74ca8c97d30045822170477aad87824fb39b8222..702a3281e54f9ccf449cf38625463d2ffe37ecfd` against `tasks/reflection-reply-fixes-2026-10-02.md` and its binding v2 contract. Read the complete changed product code and fixtures, shared matcher/evaluator/persistence callers, source parser, ledger, logger, actual StoreDb source queries, pinned embedded PCV importer/caller, focused task reviews/reports, and reply-contract evidence memo.

The report-only follow-up `ec3a9fe11bea10d77650658d24618437a98c930b` corrected the diagnostic attribution found during this review. Its corrected paragraph was inspected. The product assessment remains on the original reviewed range.

No product, test, PCV, Git state, live provider, database, installation, release or package mutation was performed. This report is the reviewer's sole output. No subagents were dispatched and no green fixture suites were repeated.

## Strengths

- **Compatibility is explicit.** `server/reflection.php:9-42` preserves the numeric v1 constant, signature and function, while exposing v2 separately through one shared runner. The embedded PCV capability check and call remain v1 (`plugins/private_conversation/server/reflection.php:156-158,647`); the documentation accurately identifies unreleased v2 and future caller adoption (`docs/integration-api.md:28-55`).
- **Listener and subject fixes retain identity checks.** `server/reflection.php:353-434` accepts the supported Player transport without the former catalog-name veto, while preserving exact ACK/source/sentinel and registered actor checks. `server/influence.php:107-270` derives the specified aliases, groups them with full names and Player identities, rejects ambiguous aliases, preserves longer unambiguous matches and excludes participants. `server/store.php:785-823` repeats matching for every selected NPC/Player token under the owner transaction before writes.
- **V2 validates a bounded complete registration.** `server/reflection.php:127-250,353-417,535-537` enforces exact tuple keys, ordered unique identities, final tuple equality, source body digests, sole explicit sentinel, permitted delivery states and inclusive text limits. Earlier ACK identity/body mismatches reject; a valid earlier ACK cannot invoke the model. All complete-registration callbacks retain the original v2 array.
- **Source and replay protection compose correctly.** `server/store.php:544-590,708-738,897-932,962-988` validates the captured source set, checks every member after the transaction callbacks, and records all members through the existing bounded ledger. Earlier entries remain reflection entries with empty judgments; the final one carries the decision. Zero decisions preserve replay protection, numeric floor checks apply to every member, and relationship/ledger/history updates share one transaction. Existing exact row reads use `FOR SHARE` (`server/store.php:1262-1290`); model work remains outside the transaction.
- **Diagnostics reuse established containment.** `server/reflection.php:46-93` constructs a logger only when omitted, retains a supplied observer and attempts one terminal summary. `server/logging.php:45-118,192-218,387-424` contains delivery failures and sanitizes records. `server/store.php:982-1054` distinguishes confirmed, unconfirmed and unattempted commit states and preserves exact underlying persistence reasons.
- **Fixtures test observable effects.** Alias race tests assert unchanged relationships, ledger and history, alongside successful full-name controls. Reply fixtures assert joined model input, all four callback checkpoints, all-member records, overlap/floor rejection, zero-decision replay and staged-write rollback. Diagnostic fixtures exercise default and supplied delivery, single terminal count, severity, redaction and sink failure.

## Issues

### Critical

None found.

### Important

None found.

### Minor

None outstanding. The Task 2 evidence report originally attributed `player-alias-ambiguous` to the new catalog-race fixture. Inspection of `tests/runtime_test.php:1019-1024` showed that output belongs to the existing duplicate Player relationship-edge case. This report-only attribution was returned to its owner and corrected at `ec3a9fe`; no product change or test rerun was needed.

## Verification assessment

- The task reports record red-to-green influence/listener/alias-race, reply-capability and omitted-logger checks, plus passing v1, v2, pinned observer/importer, ordinary runtime and changed-file syntax checks.
- The final ordinary runtime run is recorded at `cb534aa`; subsequent reviewed commits change evidence/tasks, not server/test behavior. Its two deliberate failure diagnostics are explained by their assertions.
- Read the saved unsuppressed stdout captures: all three success markers are present. Independently parsed the saved stderr captures: diagnostics has 0 records, v1 has 30 and v2 has 40; all 70 records have the MP schema/plugin identity and none contains PHP warning/notice/deprecation/fatal diagnostics. The focused Task 3 review also records its full sanitized-field inspection.
- `git diff --check 74ca8c9..702a328` passed during this review. This is whitespace evidence only.
- No new uncovered execution risk justified repeating previously green fixtures.

## Declined to judge

- **Companion v2 capture/adoption and immutable grouping enforcement:** current PCV remains a v1 caller and is read-only in this task. The future companion must prove its complete immutable ordered list, claim and scope at the documented callback checkpoints; individual row validity and increasing IDs cannot establish grouping.
- **Actual source-body/emitted-subtitle correspondence:** the available source evidence does not establish equality for every emitted line. V2 requires registered/source/final-ACK agreement and rejects disagreement; its real emitter must be verified before adoption.
- **Hearing and complete audio playback:** final native ACK and earlier emitted/spoken rows do not establish that every line was heard. The contract and consumer documentation state this ceiling.
- **Live provider judgment quality, latency and reliability:** fake-model fixtures establish validation and persistence behavior only; no provider calls were authorized.
- **Live PostgreSQL durability, restore behavior and concurrency with unrelated core writers/catalog changes:** reviewed the existing transaction, source row locks and revalidation paths, but in-memory fixtures cannot establish real isolation or all external writer behavior. This change preserves the existing store/locking contract.
- **Installed extension order, game behavior and deployment readiness:** no installed/runtime environment was changed or exercised. Source readiness does not certify a release or installation.
- **Logger construction failure by runtime fault injection:** the enclosing catch was inspected; sink failures are exercised. No new factory seam was warranted solely to force a constructor fault. Deliberate writes/output by a caller callback remain outside logger containment.
- **Publication, versions, packages and release pin advancement:** explicitly outside the requested implementation/review scope; none is authorized by this verdict.

## Ready to merge?

**Yes — for the reviewed source branch.**

The implementation meets the binding contract, retains the existing v1 consumer path and applies the shared alias/source/replay safeguards before durable changes. No actionable product defects or unresolved review findings remain; the documented companion and live-runtime limitations prevent interpreting this as deployment or end-to-end playback certification.


## Lead acceptance and boundary decisions

The lead reviewed each changed call path, every task diff and its verification output, returned concrete defects/evidence gaps to the same owners, and accepts the final source verdict. The reviewer attribution correction was independently checked against the named runtime and persistence cases.

Each declined judgment above is explicitly retained as a limitation:

- Companion adoption/grouping: reserve it for the PCV owner; changing PCV is outside the read-only boundary, and v2 must not be called without immutable complete registration.
- Subtitle correspondence: accept fail-closed hash comparison and require emitter conformance before live v2 use; do not relax matching to make integration appear successful.
- Hearing/playback: do not claim it from emitted rows or a final ACK; a future game acceptance run must establish it.
- Provider behavior: retain the fake-model evidence ceiling; no live credential or provider call is necessary to prove these source safeguards.
- PostgreSQL/external writers: retain the existing adapter and transaction contract; in-memory results are not durability or concurrency certification.
- Installed order/game/deployment: require a separately authorized deployment and acceptance run; this branch was never installed.
- Logger constructor fault: source catch inspection plus exercised sink/observer failures is sufficient for this small change; no extra factory abstraction solely for fault injection. Caller callback side effects remain caller-owned.
- Publication/pins: integration choice precedes any separately authorized release; manifests, packages and published pins are unchanged.

Scope checks: the original checkout remains on work/mind-poisoning with its prior unrelated dirty/untracked paths preserved. The isolated branch has no change to embedded PCV, manifest, packaging script or distribution. Whitespace checks pass. Server/test bytes after cb534aa are unchanged; later commits record evidence and plans only. Task reports preserve exact commands, red-to-green outcomes, unsuppressed diagnostics and limits. No broad suite reruns or live calls were added for this handoff.