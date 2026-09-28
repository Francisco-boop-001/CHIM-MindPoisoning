# MO2 FOMOD wrapper correction — 2026-09-27

## Change

- Added `build_mo2_fomod_archive` and CLI format `mo2-fomod-zip`.
- Built the distinct local artifact `dist/0.1.5/mind_poisoning-0.1.5-mo2-installer.zip`.
- Left the published `-mo2.zip`, v0.1.5 `.dwpkg`, runtime files, manifest version and release pins unchanged.
- Added a short local instruction note at `docs/mo2-wrapper-correction.md`.

The FOMOD `requiredInstallFiles` source and destination both equal `CHIM/server-plugins/mind_poisoning/0.1.5.dwpkg`. ZIP members are exactly `fomod/info.xml`, `fomod/ModuleConfig.xml`, and that package. The embedded `.dwpkg` is byte-identical to the existing v0.1.5 package.

The [upstream MO2 FOMOD installer source](https://github.com/ModOrganizer2/modorganizer-installer_fomod/blob/master/src/fomodinstallerdialog.cpp) parses each required file's source/destination and copies it to that destination. Its no-step install path uses an Install/Cancel dialog showing name/version; the small dialog hides the description. This source review was against the upstream repository, not a build matched to the installed MO2 version. The exact dialog was not opened because the user prohibited changing the modlist or installed state.

FOMOD mapping corrects the package extraction path; it does not clear MO2's post-install custom-content/missing-game-data flag for CHIM-only files. The inspected MO2 2.5.2 install-completion path does not automatically mark it valid; the warning may remain. No profile override was applied and no dummy game assets were added.

## Verification

Command:

```text
python tests/test_package.py PackageTests.test_mo2_sync_zip_is_deterministic_and_has_importable_path PackageTests.test_mo2_fomod_zip_is_deterministic_and_maps_exact_package_bytes
```

Output:

```text
..
----------------------------------------------------------------------
Ran 2 tests in 0.302s

OK
exit_code=0
```

Build command/output:

```text
python scripts/package.py --format mo2-fomod-zip
Built and verified dist\0.1.5\mind_poisoning-0.1.5-mo2-installer.zip (mind_poisoning 0.1.5, sha256 1f6ef69c8c8bba401d5041cc81f428bcdc981e8a918a17fb6c1041b3aa2b7cca)
```

Actual archive check: exact three members and FOMOD source/destination matched; extracted inner package passed `verify_archive` against current source, matched the existing `.dwpkg` byte-for-byte (853,911 bytes), and a second build matched the wrapper byte-for-byte. The published wrapper remained unchanged (SHA256 `0f3196bd4f64f4c7e3bbbbeda667fac342fd9984b8776fdcbabc1dc8484e76f2`).

New local wrapper: 697,754 bytes, SHA256 `1f6ef69c8c8bba401d5041cc81f428bcdc981e8a918a17fb6c1041b3aa2b7cca`. No MO2 GUI/install, profile/modlist mutation, CHIM execution, or publication was performed.
