# Dashboard poster package review

## Result

The frozen dashboard payload includes `server/dashboard-art.png` in both supported archives. The package verifier matched each archive against the current allowlist and source bytes; no test fixtures are included.

## Archive evidence

- `dist/poster-check/mind_poisoning-poster-review.dwpkg`: 15 ZIP entries; SHA-256 `b5fa6fab7bb4332af3e73f4508f9e632888f3edf8b016b90a2a9b743accf7a10`.
- `dist/poster-check/mind_poisoning-poster-review.tar.gz`: 14 tar entries; SHA-256 `044aaeeb6015e3ea339c772faf59a7ce9000c0b8df015bde86cf6087814f3970`.
- Both contain all 13 allowlisted server files. The tar has the `mind_poisoning/` prefix required by the installer. The poster asset is 3,615,533 bytes, SHA-256 `fbb3b5611bd3cfbd61167692ba3accf32af01dbd02a901e1e45a7f2162a700ed`.

## Checks

- `py -3.12` called `verify_archive` and `verify_repository_archive`; both passed against the final source tree. Explicit member checks confirmed the complete allowlist and absence of `tests/` entries.
- `tests/repository_tar_check.py` passed with GNU tar 1.34: `xvfz --strip-components=1` extracted the candidate, all 13 payload files matched source, and scratch was cleaned.
- The dashboard preview self-test passed for poster loading, day/night themes, escaping, accessible navigation, filters, values, and source states.
- Static review found theme selection retained across navigation, forms, and downloads. No blocking renderer or packaging issue remains.

These checks establish archive contents and local preview behavior only. No live CHIM server, database, model provider, or Skyrim runtime was exercised.
