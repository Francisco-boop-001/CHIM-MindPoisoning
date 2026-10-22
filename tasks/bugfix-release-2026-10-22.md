# MP bug fixes and release — 2026-10-22

Base: public main d77c5eeee4fba372d8cfd70ae803ea9a9baf85bf (0.1.29). Review branch 8a1462a11c8db013852fd75154063e21ba27d8d9 changes only two tests and report. Work branch fix/mp-bughunt-20261022.

## Contract and safety
Fix verified MP defects 1–12 in tasks/bug-hunt-2026-10-22.md; PCV13–16 stay with PCV. Canonical identity must drive relationship reads/writes. Optional metadata failure must leave the parent transaction usable while retaining arrangement refusal. Bad extra witnesses cannot suppress a separately valid direct recipient. Preserve replay/scope/restore protections, truthful per-operation logs, bounded inputs, dashboard local/authenticated access, and existing API signatures. Coordinate PRISMA Host/auth transport before the access change. No semantic dedupe or new age policy. No provider, installed/live/game/SHARMAT changes; never enter gaming distro. TEST only after exclusive coordination; own synthetic fixtures/cluster only.

## Tasks and ownership
- [x] A: written_evidence.php + intimacy_request.php; written_evidence_test.php, written_store_test.php, intimacy_request_test.php. Findings1/8/9: canonical sender, fenced JSON parity, encoded versus decoded caps.
- [x] B: store.php only product; dedicated native/history/cleanup tests and reflection_observer_test.php. Findings3/4/5 plus review-branch observer fix. Prove transaction failure with PostgreSQL after lease; do not touch core or optional-table source.
- [x] C: dashboard.php + dashboard_data.php and dashboard tests. Findings10/11/12 plus review-branch dashboard test fixes. Host guard with PRISMA and legitimate local/authenticated access preserved.
- [x] D (A follow-up, sequential): prerequest.php + relevant model-ID helper only if required; overhearing/restore/runtime tests. Findings2/6/7; no overlap with B store.php or C dashboard files.
- [x] Lead: review each diff/callers/failure paths and RED/GREEN outputs; whole-change independent review by completed owner.
- [x] Integration: provider-free isolated Linux/Pg focused checks under exclusive TEST window; inherited tests reused only with verified input identity. Record genuine skipped gates.
- [ ] Release owner (sequential): 0.1.30 candidate metadata/docs/packages after source/test review; all formats from clean tag, independent reproduction, public download SHA checks; only then public main/update/hub pins. Preserve old tags/assets. Standard release BETA CANDIDATE per existing human choice; no maturity promotion.

## Plan interface review
| Pair/task | Interface or self-check | Decision |
|---|---|---|
| A/B | written subject -> store key | A canonicalizes resolved ID/name; B preserves storage API |
| B/D | store per-operation status and restore caller | B handles store lifetime; D preserves per-recipient baseline/replay |
| C/Department | dashboard HTTP Host/auth | wait for verified transport evidence, no broad subnet trust |
| A/D | exclusive same owner, different files | D starts after A, reused owner |
| all/release | runtime payload and version | source review first, pin after verified assets |
| A | caps/fences with raw input validation | bound raw transport separately, do not strip arbitrary prose |
| B | optional SQL failure | savepoint or equivalent supported atomic isolation; never swallow aborted tx |
| C | search/log cap | search values; bounded tail keeps complete first record |
| D | invalid extras | ordinary direct gates still required; don't weaken preservation |

## Review
Pending. No product/runtime claims yet.
Ownership ruling: B may also edit server/restore_policy.php if per-operation cleanup reporting requires its caller update. No other worker owns that file. Preserve unresolved cleanup state; reset-only masking is unacceptable.


Runtime ruling: standalone Windows PHP pure/in-memory/local fixture checks may proceed concurrently in exclusive worker fixture directories. Only WSL TEST/Linux/Pg needs the shared exclusive server window. No worker may start a distro or touch installed data without lead handoff.


Department coordination confirmed2026-10-22: no Department TEST reservation/queue; adapter uses direct PHP readers, not dashboardHTTP. It pins0.1.29 plus7hashes;0.1.30 will be refused until explicit owner review/repin. PCV/S&S lease availability pending. No Department code edits authorized here.


Runtime inventory stop: TEST has existing apache/PHP/Pg processes; MP stopped only its own lease holder before further checks. Windows fresh-state guard had a nonterminating PowerShell Replace overload error; fixed to explicit string overload plus ErrorActionPreference=Stop. Gaming confirmedStopped and VHD metadata retained unchanged. No native test or cluster start yet; identify remaining owner first.


Lead partial review: Acanonical sender/unchanged original envelope/protectedtype and fencedJSON+decodedcaps inspected; actual RED/GREEN outputs reviewed (234checks +store/intimacy seams). CHost/search/tail diff/callers and corrected schema-validboundaryRED reviewed; final8Windowsdashboardpasses plus explicitnative-recencyskip, hashes match. FinalLinuxstore/callerintegration pending; no releasegate passedyet. SF identified actualprivateclusterowner (/var/tmp/chim-sealed-fates-dev22-20261022:55466); MPawaitsSFrelease, no otherprocessstopped.


Native gate begun under exclusiveMPwindowafterSFrelease; ownPgmarker/systemIDverified. Automaticreview rejected workercommandaftername typo. Leadprovedactualscope viaAGENTS/freshinventory/originalJSON, restoredrunnerhashesafterworker misreadidentifier, and runsnativecommands directly (noalternate target/workaround). Stage0 RED historyinvalidrow; SQLbytefilterfix thenStage1 RED optionalmetadataabortedmixedwrite. B nowimplementsSAVEPOINT only; cleanupstagepending. GamingremainsStopped; preexistingTESTservicespreserved.


Lead D review: complete caller trace and six focused RED/GREEN outputs plus runtime/restore/overhearing suites inspected. Direct baseline/replay/locks remain required when extras are skipped. Accepted cap counts read-only preflight candidates before any extra baseline writes; document this conservative optional-extra tradeoff. Independent A/C/D source reviews are clean. Native stage2 still fails the mixed-reflection assertion; actual reason diagnosis is pending, so no isolation success claim yet.

Stage3 clarified earlier stages1/2: mixed-reflection failure is event-stale before transaction revalidation; these are fixture failures, NOT valid optional-query RED evidence. B must correct production dependency bootstrap, establish actual pre-fix SQL failure, then verify savepoint recovery. Retained all outputs. PCV requested concurrent pure-PHP use; MP retained procedure's exclusive window and PCV stays queued.

Corrected source-parser fixture now reaches the transaction: stage4 without savepoint fails write-listener-failed after transaction revalidation (genuine #3 RED). Stage5/6 with savepoint advance past write/read verification, then report verify-snapshot-failed. Standalone backupAndVerify smoke passes; stage label persists into later precommit helpers, so this is not established snapshot corruption. Full production-bootstrap dependencies (controls/interaction/pause seams) are being completed without changing guards. No release pin advanced.

Native stage7, using existing runtime fixture bootstrap and real reflection entrypoint, passes mixed reflection affinity/type/receipt plus arrangement suppression and a subsequent clean reflection, then reaches genuine #5 RED: retry-did-not-release-advisory-lock. Stages5/6 were missing precommit controls bootstrap, not a demonstrated snapshot defect. B now implements operation-local cleanup evidence and retained resource ownership. Existing parser/interaction/pause fixture seams are explicit substitutions; no provider/core/installed data was used.

Final source gate2026-10-22: native child/history/mixedreflection/cleanup41 and writtenidentity14 pass; ten focusedLinux suites pass, including all6POSIXobserver/importer cases. Initial isolated-runner missingpgsql/defaultscan duplicateextension failures retained as infra failures, corrected with task-localini+empty scan dir. All18changedPHP files lint clean, diffcheck clean. Lead inspected every productdiff/caller and independentA/B/C/D reviews; no open blocking source defect. Restorepolicy formatting-only poststage8 diff proven identical after in-memory formatting normalization; exact current formatting exercised by finalLinuxrestore/runtime suites. No actualSemaphoreManager failure retry/provider/gameplay/install claim. Only ownPgcluster+holder stopped, TEST handedPCV, preexistingservicesleft running, gamingnotentered and hostVHD size/mtime unchanged. No furthernativechecks queued. Releasegatepending.

Prepublication lead review complete: all12MPfindings have reviewed fixes and focusedproof; Cmetadata/player/operator/API/release docs reviewed including raw64KiB/decodedbodylimits, conservative4extra preflightcap, exactAPIversions andPRISMAdownstreamgate. Manifest/catalogdescription parity,57localMarkdown targets andsourcefingerprintfreeze verified. Producttests unchanged after finalintegration; release-only manifest/operatorREADME/docs are approved. Package8tests/clean-tag rebuild/draft+anonymousdownloads remain actualpublication gates. Oldtags/assets andPCV/SHARMAT/Departmentcode preserved.
