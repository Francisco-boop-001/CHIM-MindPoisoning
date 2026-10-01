# Cross-plugin fixes: lead source and release review

## Scope and source reconciliation

User authorized X-01 through X-06 fixes, commit, push and PRE-ALPHA publication. Product work is delegated to three existing gpt-6-luna Max owners with Ponytail FULL. Installed CHIM, modlist, standalone Private Conversation authoring source, live database/provider and official catalogue remain untouched.

During the initial inspection the shared checkout was at `adf741548462bf196c87a19edc0fde2e604d4e0c`, with accepted PCV logging changes pending. Remote `origin/main` subsequently advanced through `64a9ad4705fe1cba7384239f08f5d5e0e2bf3b4c` to `eb4f9e2` during independent PCV publication. GitHub confirms PCV v0.1.5 published at 2026-10-01T02:30:48Z. Its source and logging evidence are retained; this task does not reimplement that work. New fixes use MP v0.1.14 and PCV v0.1.6. The two proposed tags were absent when checked.

## Confirmed platform paths

CHIM reference `cf5030f15781637498be86debe26fcf102f5690d` remains the compatibility observation, not a universal guarantee. The manager matches the first repository-or-name catalogue entry; the installer lacks downloaded-name equality validation before replacement. Separate deployment repositories prevent these two plugins from colliding. A draft upstream report is saved, not sent.

Core flushes output before extension `prepostrequest` (main.php:2941), core postrequest (:2942) and extension postrequest (:2943). Earlier registration reduces a delay but cannot establish pre-emission registration. The existing solo relationship guard also disables MP's Player postrequest connector path for eligible solo requests; the alleged 2–4 second delay from that path is not established for this flow. Valid active early ACK misses need a fixed info diagnostic; unrelated/malformed ACKs remain quiet. No synthetic ACK or fuzzy recovery is allowed.

## Deployment decisions

`CHIM-MindPoisoning` and `CHIM-PrivateConversation` have been created as public deployment repositories. `CHIM-Plugins` remains the collection/migration route. MP's source retains the repository-contained versioned PCV integration fixture; PCV deployment source is exported from that prefix. Runtime packages contain only each plugin's explicit allowlist.

Old tags/assets remain immutable. MP v0.1.13 uses a version template at the collection URL, so the migration asset must be published there before main changes. PCV v0.1.4 and v0.1.5 use literal tag asset URLs; a one-time manual/file-sync migration is required, then v0.1.6's template/new identity supports later updates. Install through one route and never stack older sync packages.

## Verification status

Product diffs were reviewed against the independently published PCV 0.1.5 source, not only the older local HEAD. The earlier hook retains its existing relationship guard; new ACK diagnostics require a validated active solo scope and bounded matching actor-to-Player payload. API version 1 is checked before store/provider work. The observer fixture uses only the embedded source. Fatal-fixture cleanup runs after logger shutdown and removes only verified owned entries.

Returned defects were corrected by their original owners: duplicate registration diagnostics, a malformed actor-ID cast that could emit warnings, duplicated name normalization, the timing test's initially incorrect installed layout, the terminal exception test's stale caller, and cleanup ordering that could precede fatal terminal logging. Metadata review also returned stale CLI-only support text, inaccurate mutable-path wording and release-body-relative documentation links.

Focused red-to-green evidence is in x-api-test-evidence-2026-09-30.md and pcv-reflection-timing-follow-up-2026-09-30.md. An independent product reviewer found no remaining concrete defect in the assigned scope. Pending immutable source/tag exports, package equality, downloaded-asset checksums and public metadata verification. Static source checks and isolated fixtures will not be presented as live provider/database/playback proof. No official catalogue listing or maintainer message is included.

## Immutable source gate

Source fixes were committed as 80357f7; the normal merge eb741720bb78e23106bd4fa09f138a97e15a558e retained origin/main's published PCV 0.1.5 source and release evidence. All conflict resolutions were returned to their file owners. Lead reviewed the only additional merge changes (logging-guide accuracy and completed task history). Git diff --exit-code from 80357f7 to the merge over both plugins' server/tests/scripts exited 0, proving the tested immutable export and final product/check files are identical. A pre-existing extra EOF line in PCV's logging-improvements-plan remains unstaged; unrelated critique, draft and screenshot files are preserved.

Six focused PHP fixture executions and 22 changed-file PHP lints passed from the clean 80357f7 Git archive; exact commands and limits are in x-clean-source-verification-2026-09-30.md. Lead's final manifest_update_check.php passed all four actual-core helper cases without network or endpoint execution; package_check.py passed four tests. Initial clean-source builds verified MP's 17-file and PCV's 18-file allowlists, repository archives, schema-4 packages and exact single-DWPkg MO2 wrappers. Tag/download comparisons remain the publication gate.

WSL Git subtree exported PCV source 27a74f0a1a815040e518b8d130a97c98b1db78e3; its entire tree equals the reviewed embedded fixture tree df0221288e499e4aa08895596f4bde0de8eab6cb. Native Git's subtree helper is unavailable; the installed WSL facility was used without installing tools or changing CHIM.

## Package export mismatch caught before publication

The first clean-tag comparison passed all MP artifacts but failed PCV. No source, tags or assets had been pushed. The owner compared actual tar/DWPkg members: 17 text payloads had LF expanded to CRLF (log.php: 60,154 raw/initial bytes versus 61,474 exported bytes; exactly 1,320 line endings). The raw subtree blob matched the initial export. The standalone tree lacked the collection root's .gitattributes policy while Windows Git had core.autocrlf=true. Git source comparison alone had therefore not established exported-byte equality.

The root correction copies the existing six LF rules into plugins/private_conversation/.gitattributes; no global Git settings or runtime code change. Attribute resolution reports text=set/eol=lf for the affected source types. The two unpublished local candidate tags are superseded with source containing that policy. Fresh standalone/tag packages must equal the initial clean source bytes before any push. Historical public tags/assets remain unchanged.
