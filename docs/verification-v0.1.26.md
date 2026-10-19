# Mind Poisoning 0.1.26 — verification and limits

## What changed

Automatic recognition records one NPC's current intimacy self-report independently of affinity and emotional type. It is default-off. The optional public read API is version 1; the separate SHARMAT consumer is submitted in upstream PR #7, not bundled into MP. This is still a **BETA CANDIDATE**, not a maturity promotion.

## Source boundary

MP started from `7c929739262698c8f59a4711139e976fe7b1e0ae` (0.1.25). The SHARMAT contribution extends PR #7 at `f7dc324425fc426cd78d42e34adb5e85fc2b83ea`, based on upstream `123c98d95ce7a54dada6ab34f91d2f4eabe3dffb` (3.1.9.10). The supported `character_bottom` injection slot and `herika_name` renderer context were additionally checked in retained HerikaServer dev `0ec590b42c8087860138bb4e9aab470049ff9a2b`; the fixture uses a registration seam rather than booting that server. Retained CHIM 3.5.1 revision `57f292415b008cfaf1a4531f0654d2563362ce70` supplies the restore baseline. Source review is pinned evidence; it does not promise compatibility with future upstream changes.

The inspected CHIM editor rebuilds core relationship edges from known fields. That counterexample ruled out attaching the new state to an arbitrary edge field. The implementation uses MP's existing plugin namespace and transaction/history path instead. No table, migration, worker or live installation was introduced.

## Deterministic checks

Windows standalone PHP 8.3.35 ran with `-n` and explicit bundled extensions. Fixtures used synthetic records, an in-memory StoreDb implementation and deterministic model responses. No WSL distro, native database, provider credentials, live server, installed mods or game data was used for these new checks.

From a clean source checkout, with a task-owned temporary directory:

```powershell
$php = '<standalone PHP 8.3.35>/php.exe'
$ext = '<standalone PHP 8.3.35>/ext'
$env:TEMP = '<task-owned existing temporary directory>'
$env:TMP = $env:TEMP
$env:PLAYER_STORE_TEST_PG_SOCKET = ''
$phpArgs = @('-n', '-d', "extension=$ext/php_mbstring.dll", '-d', "extension=$ext/php_pgsql.dll")
foreach ($test in @('intimacy_state_test', 'intimacy_context_test', 'intimacy_request_test', 'intimacy_reflection_test', 'intimacy_dashboard_test')) {
    & $php @phpArgs "tests/$test.php"
    if ($LASTEXITCODE -ne 0) { throw "$test failed" }
}
```

The new checks cover exact source/target binding, speaker ownership, Player alias collisions, null results and exact retries, locked owners, target-lock non-veto, existing core-edge preservation, state transitions and endings, stale-record compare-and-save, wrong scope/pruned source, bounded state, rollback/uncertain outcomes, source receipts beyond the bounded history window, private prompt context and escaped/sanitized dashboard output. Reflection checks cover a fresh arrangement ending with an already-processed affinity basis: zero delta and no type change are enforced in the model instruction, parser and store. The opted-in reflection call uses the existing 4096-token output budget; the disabled call retains its original default.

A failing direct-caller fixture first exposed missing speaker-owned scope baselining: it expected no model call for the baseline-triggering source but observed one. The fixed actual `handleSpeechAck()` route skips that source and allows the next eligible one. Other checks use malformed, mismatched, stale and failure-injected controls; they are not assertions that a real model interpreted dialogue correctly.

Ten affected existing suites passed with exit 0: `player_store_test`, `restore_policy_test`, `store_logging_test`, `logging_test`, `influence_test`, `relationship_type_judgments_test`, `reflection_test`, `reflection_reply_test`, `overhearing_test` and `dashboard_data_test`. Several include the inherited runtime fixtures. Their deliberate failure cases print `snapshot-verification-failed` and `player-relationship-normalization-failed`. Native PostgreSQL cases were skipped because no test socket was provided; Windows symlink checks were unavailable and reported skipped.

Final focused runs passed with exit 0 for context (including state), direct ACK, reflection and dashboard suites after the excerpt-bound and compare-and-save controls. The full-record mismatch control returned stale with zero writes; a distinct sibling-map update survived a successful target update. PHP syntax passed for all 17 server PHP files and five new PHP fixtures (22 files); the scoped whitespace check passed. The bounded context fixture reduced shared 8-by-24 source lookups from 192 to 24, and a later uncached public read detected a pruned source.

Packaging unit checks passed: `python -m unittest discover -s tests -p test_package.py` (8 tests). PHP syntax, source-package contents, clean rebuild and published-byte checks are separate gates, recorded below when completed.

## SHARMAT boundary

The companion fixture calls MP's production classifier, persistence and public read API before the SHARMAT reader. Model/network/database collaborators are synthetic; this is not a native OStim scene. SHARMAT's own fixtures exercise actual action availability, target/schema filtering and final dispatch seams. Existing applicable role, orientation, relationship, arousal, current-decision and native restrictions remain SHARMAT's responsibility. An arrangement alone neither sets an acceptance flag nor proves a recipient agreed. Player orientation is explicitly checked for the regular arrangement route; the inspected existing NPC-pair gate has no separate target-orientation predicate, and none is claimed here.

The new SHARMAT production-derived consumer fixture passed 95 checks with zero failures, settings round-trip passed 12, and the real MP producer-to-consumer fixture passed 9. Current existing OpenMode passed 55 assertions and the legacy offer/context/schema/postfilter fixture passed 82, including positive and negative CSV-group controls. Changed SHARMAT PHP and Node syntax checks passed. The new arrangement-specific fixture does not duplicate those legacy group-container cases.

The public reader is fresh. Offer generation may reuse a pair result only within its bounded action-building pass; final execution must read again so an ending between offer and dispatch cannot retain the additional lane. MP prompt construction may reuse the same source row within one bounded context build, without making the public API stale.

## Evidence limits and next gameplay check

- Deterministic outputs prove plumbing, parsing and state/policy behavior, not real-model recognition of tense, irony, wishes, quoted speech or current arrangements. No semantic quality score is claimed.
- New arrangement storage uses the existing adapter and transaction path. Its new cases have synthetic transaction evidence; the earlier 0.1.25 native restore proof is historical evidence, not a native 0.1.26 rerun.
- Sequential exact retries are covered. No new concurrent-ACK reservation or general crash/durability guarantee is claimed.
- Arrangements follow restored MP plugin state under both policies; preserved core affinity/type can be newer. Missing source rows cannot authorize the additional action lane.
- Older MP versions do not understand the new receipt kind. A code-only downgrade after new writes is not a supported rollback; retain and restore the matching database/configuration backup.
- The new feature has no installed-package, real-provider, Skyrim response or OStim playback proof. Standard GitHub release classification supports CHIM discovery and changes none of those limits.

A separately authorized gameplay test should establish and end a clearly current arrangement, inspect affinity/type remaining independent, compare each NPC's separate report, exercise a declined or ineligible action, and verify an ended arrangement cannot authorize a stale offer. Use a backup or isolated database. Do not infer mutual agreement from matching labels, or successful playback from an emitted command.

Publication gates are tracked in the task record and release checksums; package bytes establish distribution integrity, not installation or gameplay.
## Completed distribution checks

The immutable tag `mind_poisoning-v0.1.26` points to `43c87804875bad104fd955c344d5b300b9b9bcac`. All four installable archives from a clean commit checkout matched a second clean tag checkout byte for byte. Focused MP checks and SHARMAT consumer/settings/integration checks also passed from clean source exports, resolving the working-tree versus exported line-ending boundary.

All five uploaded draft assets matched the local files. After publication, each public URL was downloaded without authentication and matched again, including `SHA256SUMS.txt`. Main and the hub were advanced only after those public-download checks. These facts prove distribution integrity, not native installation or gameplay.

| Installable file | SHA-256 |
| --- | --- |
| `mind_poisoning-0.1.26-mo2.zip` | `278d982e46c84555cba82148dce9be4d2170a28da8e22d5269bea618daf76402` |
| `mind_poisoning-0.1.26.dwpkg` | `e7464767cc55b02ffafa02b5df6fcbd6f1c2472611a01af97036b77a8a0a9409` |
| `mind_poisoning.tar.gz` | `68c4f99b8281a6f254cd8a512a469289b22a87b66c33edac227174e0d5e0812e` |
| `chim-plugin.tar.gz` | `c7301ebbff61649bdbe672a83489b96601afb4496f7d094d9c71aa635bf371bf` |

[Published release and checksum file](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/tag/mind_poisoning-v0.1.26). SHARMAT's reviewed consumer commit is `d6c6da595eb961f338245d6e4d044fe6e86279f0` in [open upstream PR #7](https://github.com/Wondernuttz/Sharmat-Alpha/pull/7); submission is not maintainer approval or a SHARMAT release. The main plugins hub documentation was updated while preserving PCV 0.1.21 BETA and S&S 0.1.5. No live installation was performed.
