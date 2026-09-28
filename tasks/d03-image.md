# D-03 image optimization and local install package

## Scope and plan

- Re-encode the existing poster; preserve the original PNG outside the runtime payload.
- Update the same-origin asset reference and explicit package allowlist; keep package root and server layout unchanged.
- Verify image bytes/path and archive membership; visually compare original and conversion.
- Prepare a local MO2-ready wrapper and concise CHIM file-sync/MO2 instructions. Build only after the source and runtime gates pass; do not publish or advance deployment pins.

## Initial evidence

- `server/dashboard-art.png` is a 1536×1024 RGB PNG, 3,615,533 bytes.
- `server/dashboard_view.php` references it as a same-origin static image; `scripts/package.py` includes it explicitly.
- The dashboard integration fixture copies and requests `dashboard-art.png`, and checks PNG magic/MIME; update it for the chosen format.
- Existing project evidence confirms extraction with `--strip-components=1`; it does not establish cleanup of stale files omitted by a later archive. A rename can leave an unused old PNG in existing installations; the updated view will reference only the WebP. No cleanup behavior is inferred.

## Implementation and evidence

- Re-encoded the existing poster with Pillow 12.1.0 WebP, quality 90, method 6; no composition regeneration or resizing.
- Runtime asset is `server/dashboard-art.webp` (649,836 bytes, 1536×1024 RGB), an 82.0% reduction from the PNG (2,965,697 bytes saved; pixel RMSE 4.001, PSNR 36.09 dB).
- The original is preserved byte-identically at `assets/dashboard-art-source.png` (SHA-256 `fbb3b5611bd3cfbd61167692ba3acccf32af01dbd02a901e1e45a7f2162a700ed`) and excluded from the explicit payload allowlist.
- Side-by-side visual review: `tasks/d03-side-by-side.png`. Lead reviewed and accepted the quality-90 conversion; no visible material change at dashboard scale.
- Updated dashboard view, preview/integration asset expectations, documentation asset reference, and package allowlist for WebP. Added package checks for WebP magic/source bytes and a deterministic MO2 wrapper archive at `CHIM/server-plugins/<name>/<version>.dwpkg`.
- Focused command: `python tests/test_package.py` — exit 0, 6 tests passed.
- Lead's final `php tests/dashboard_integration_test.php` composed check passed after the D-01/D-02 gates. It exercised local modules and a test image route; no installed CHIM server, live database/provider, or game was used.
- Updated the local-only v0.1.5 manifest and guide while preserving `schema_version: 2`, `status: development_candidate`, the `candidate` channel, compatibility reference, and public v0.1.4 links. No release pin or catalog pointer was advanced.
- Final local archives were source-verified with `verify_archive` and `verify_repository_archive`. The MO2 wrapper contains exactly `CHIM/server-plugins/mind_poisoning/0.1.5.dwpkg`; the embedded package was independently verified and byte-compared with the direct `.dwpkg`.

| Local artifact | Size | SHA-256 |
| --- | ---: | --- |
| `dist/0.1.5/mind_poisoning-0.1.5.dwpkg` | 854,348 bytes | `584bf21c9576a900e20937f2f8a7f5bf4febfe28c37d1559f3c3c226393d9fd8` |
| `dist/0.1.5/mind_poisoning.tar.gz` | 695,905 bytes | `1bb095bbe34d93bd21f81eb78b8e37c196ca65955a18c062b163e59ede404d1a` |
| `dist/0.1.5/mind_poisoning-0.1.5-mo2.zip` | 697,184 bytes | `e16182f476bae54b527dab158a29566f05659cea337ca66d2a1dd215fc507186` |

Checksums are also saved in `dist/0.1.5/SHA256SUMS`. All three payload formats contain the same 13 allowlisted server files, including the WebP poster and not the preserved source PNG. The MO2 wrapper is an import archive, not the repository tarball/catalog format. The candidate remains local and unpublished. The localhost route was tested only with an isolated responder; installed CHIM loading, MO2 sync, dashboard authentication/live data, database writes, provider calls, and in-game behavior remain unverified. Overlay cleanup for the old v0.1.4 PNG remains unknown; the view now references only WebP and no cleanup behavior was added.

## Checklist

- [x] Produce and review a standard WebP re-encode against the original.
- [x] Update the image path, explicit allowlist, and focused package checks; integration expectations updated.
- [x] Add local install instructions and build v0.1.5 artifacts after source freeze.
- [x] Record archive contents/hashes, visual review, and limits.

### Review

The accepted q90 WebP preserves the existing composition and has no material visible change at dashboard scale. Package verification proves archive membership and exact source-byte identity; it does not prove import through a live MO2/CHIM installation. No installed distribution/mod files, database, provider, deployment pin, or public release was changed.
