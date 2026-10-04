# Reserved sentinel and reflection diagnosis — 2026-10-04

## Scope and outcome

The PCV 0.1.15 hub pin is separate from this unreleased MP source fix. MP release/version remains 0.1.17; no published package or tag was changed. No WSL distro, live database/provider, installation or game operation was performed.

The released addressed/witness lookup could accept a catalog NPC literally named `explicit_disable_rechat`. Two small guards in `server/prerequest.php` use the existing `sameActorName()` normalization to reject it before addressed identity resolution or additional-recipient selection. Selected actor names/IDs and the exact source are revalidated during persistence; persistence does not derive new recipients. The valid reflection source-target sentinel contract is unchanged.

## Focused evidence

- Ownership: delegated owner changed only `server/prerequest.php` and `tests/overhearing_test.php`; lead and independent reviewer inspected the diff, callers and save revalidation.
- Regression: direct sentinel/catalog collision failed before the direct guard; ordinary roster/catalog collision failed before the witness filter. With both guards, `Overhearing ACK tests passed.` (exit 0). Cases use uppercase transport names against a lowercase catalog row, check no sentinel model recipient/write, and preserve valid addressed/overheard writes.
- Exact native check (from repository root): `dist/hub-pcv15-2026-10-04/php-native/php.exe -n -d extension_dir=dist/hub-pcv15-2026-10-04/php-native/ext -d extension=mbstring tests/overhearing_test.php`.
- PHP `-n -l` passed for both changed files; scoped `git diff --check` passed. No optional broad suite was rerun.
- Portable PHP 8.5.11 CLI came from the [official Windows download](https://downloads.php.net/~windows/releases/archives/php-8.5.11-nts-Win32-vs17-x64.zip); SHA-256 verified as `0ea96e0d2b9b737a6036f05cf4e95c49313faa6d0f27bd97edb2742503f0c043`. It lives only in ignored scratch, with no PATH, server or system configuration changes.
- Sanitized transcript: `dist/hub-pcv15-2026-10-04/verification/sentinel-guards.txt`. Isolated Windows/PHP execution is not target-server durability, installed-game or provider proof.

## Reported live reflection: what is known

No exact registration/event export is available for the 2026-10-04 11:45:36Z incident. The newly published PCV `tasks/live-issues-2026-10-04.md` records that timestamp and the same two hypotheses, but no registration tuple count or catalog evidence resolving them. We did not enter the gaming distro or inspect its live catalog. Its exact evaluated text/path cannot be reconstructed from the available sanitized diagnostics.

Source contracts:

- Reflection v1 evaluates the exact acknowledged subtitle; v2 joins the registered source bodies in order.
- PCV falls back to v1 when it cannot prove compatible, uniquely anchored, valid full-reply grouping. Fourteen lines fit MP's current 24-line cap but do not establish that grouping succeeded.
- `findSubjects()` drops normalized aliases shared by multiple owners. A unique full canonical name can still match; duplicate full canonical names remain ambiguous.
- `reflection-no-subjects` precedes the model call. Current logs do not include the chosen evaluator version, registration line count or evaluated-text shape.
- Directions/prompt context are not spoken evidence. Pronouns alone do not identify the Player as a subject.

To discriminate the two reported hypotheses, capture only whether the registration passed to MP has `lines`, its tuple count, and the number of catalog owners mapping to `Bruce`. Do not share dialogue, hashes, claim tokens or credentials. With no registration/catalog evidence, neither final-line fallback nor alias ambiguity is established as the live cause.
