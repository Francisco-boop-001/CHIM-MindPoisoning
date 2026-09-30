# Mind Poisoning v0.1.12 candidate preparation

## Checklist

- [x] Review the accepted reflection implementation and focused source/fixture evidence; retain PRE-ALPHA maturity and the no-gameplay-proof boundary.
- [x] Align the nested server manifest, packaged server guide, contributor notes and candidate release notes on v0.1.12.
- [x] Verify candidate metadata, explicit server allowlist and package members; build deterministic DWPkg, repository archive and plain MO2 sync ZIP into `dist/release-v0.1.12`.
- [x] Record hashes, untouched historical artifacts and the limits of this local candidate. Do not publish or install it.

## Review basis

The new `server/reflection.php` API is opt-in and side-effect-free on load. The companion supplies an exact private registration and revalidation callback; Mind Poisoning verifies the matching native event, actor, utterance, sentinel and subtitle digest, then rechecks scope and dedupe guards through its existing model and transaction paths. Pair evaluation remains A→B about C (B owns the opinion of C); solo reflection is A→C (A owns the opinion), with `listener_id=null`.

Unchanged evidence is durably suppressed per current basis, including zero-delta results, with a bounded 32-subject token set independent of the 128-entry rolling ledger. Evidence excludes affinity and prior reflection output. Companion-registry integration evidence was independently reviewed in the PCV implementation, registry and bug-run records; it is separate from the Mind Poisoning release payload.

Previously captured focused checks passed: Mind Poisoning `reflection_test.php`, `runtime_test.php`, `store_logging_test.php`, dashboard attribution checks, and the PCV isolated registry fixture. The latest PCV registry check also covered the native CRLF utterance-ID case, exact output/ACK correlation, duplicate claim, stale revalidation, provider failure and optional-module failure containment. These reports use fake stores or isolated fixtures. No broad runtime suite is repeated for release-only edits.

## Release checks

The focused metadata check passed: `release metadata checks passed: manifest, PRE-ALPHA channel, candidate docs, and 17-file source allowlist`.

The existing `scripts/package.py` CLI built each format twice from an isolated copy at `dist/.release-v0.1.12-build/source`; the second output matched the first byte-for-byte. Its internal archive verification passed on each invocation. Final local candidate artifacts in `dist/release-v0.1.12` also passed DWPkg verification against the current source, repository-archive content verification, exact MO2 wrapper member/path and CRC checks, and checksum-file validation. The final gate confirmed the stage still matched all 17 allowlisted server files and the package builder, and every historical 0.1.11 asset plus the older legacy root tarball retained its pre-build SHA-256.

| Local candidate asset | Bytes | SHA-256 |
| --- | ---: | --- |
| `mind_poisoning-0.1.12.dwpkg` | 862872 | `d6eb07ef31976adba25d0f097ca769134c2f54de2cc50d3def50f0d51d1bef17` |
| `mind_poisoning.tar.gz` | 615842 | `3ac69f842cb210545eedd8fa7f98b77ec21a9df27cab0c6bbce56bc3272fa528` |
| `mind_poisoning-0.1.12-mo2.zip` | 617618 | `a158de777fb50a9f2e6b51214686ba18d7cd17e0706adaf592e9e13bd58005ec` |
| `SHA256SUMS.txt` | 278 | `6c110a0b5ebc02d97f027e3ee681dd038b2f8900a27dd2bb4bfb52a14a5d03b0` |

The candidate hashes above are for the working-tree snapshot. The lead will produce the authoritative clean-commit export and compare its bytes before any publication; line-ending normalization or later approved source changes may change these hashes.

Prior focused checks were reviewed from accepted reports and were not repeated during this release-only tranche: Mind Poisoning `reflection_test.php`, `runtime_test.php`, `store_logging_test.php`, and dashboard attribution fixtures passed; the PCV `reflection_registry_check.php` reported `PCV reflection registry checks passed.` These use fake stores or isolated fixtures, not a live DB/provider/game.

## Limits

No live database, provider, installed server, CHIM package import, Skyrim save/profile, game or audio playback was exercised. No tag, GitHub asset, catalog entry, installed plugin, database or modlist was changed. The root README was updated by the lead outside this slice and was left untouched here. The catalog repository-entry draft and publication remain outside this task; the official CHIM catalog entry has not been submitted or approved. Existing v0.1.11 tags and artifacts remain unchanged.

## Review

The candidate manifest remains schema 2 with the existing `candidate` channel and compatibility reference; it now labels its PRE-ALPHA maturity explicitly and retains the version-tokenized repository archive URL. Packaged and source-checkout guides distinguish pair listener ownership from solo actor ownership, describe exact ACK correlation and unchanged-basis skips, explain that a companion must invoke the API, and state the line-attempt/audio and fake-test limits. No runtime/package-builder code was edited.
