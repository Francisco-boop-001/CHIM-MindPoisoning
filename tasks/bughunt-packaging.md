# Packaging and update-flow bug hunt

## Checklist

- [x] Read the current manifest/catalog, builder, matching tests, and prior manifest update-flow evidence.
- [x] Reproduce package-consumer mismatch hypotheses before changing code.
- [x] Remove the stale duplicated file-count gate while preserving payload and installed-byte checks.
- [x] Run the focused fixture/manager check and record exact results and limits.

## Initial hypotheses and counterexamples

1. **Old manager harness still assumes seven files.** The current explicit `SERVER_FILES` allowlist has 13 entries, while `tests/package_manager_check.php` still asserts a seven-file fixture. This may be a stale test gate rather than a shipped-package defect. Reproduce before editing.
2. **Versioned candidate URL may fail between manager and installer.** Prior `tasks/manifest-update-review.md` evidence already extracted the installed manager/installer helpers and exercised version token expansion with a local fetch stub. Treat that path as covered unless another consumer boundary contradicts it.
3. **Shared-repository catalog matching may select another plugin.** Prior evidence reproduced first-match-by-repository behavior in the installed Manager; the current catalog has one entry for this repository. This is a CHIM-core limitation, so do not alter core or invent a plugin-only workaround without a new concrete finding.

## Confirmed finding

`tests/package_manager_check.php` hard-coded a seven-file fixture expectation. The current builder's canonical `SERVER_FILES` has 13 entries, so the existing real-manager scratch harness rejected a correctly generated fixture before calling the manager. Reproduced with the fixture emitted by `python tests/test_package.py --manager-fixture`, then passed to the isolated manager harness:

```text
python tests/test_package.py --manager-fixture
wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/package_manager_check.php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/.package-manager-check/source/fixture.dwpkg
```

Red output:

```text
FAIL: Fixture server payload does not match the seven-file allowlist.
```

Replaced the duplicated count with a nonempty-payload guard. The canonical Python package tests own allowlist membership; the manager harness still uploads and installs with the actual manager class in isolated project scratch roots, compares installed files and bytes to the archive payload, rejects a checksum-tampered archive, verifies prior scratch state is preserved, and ensures migrations are not invoked.

## Checks

Commands and outputs:

```text
python tests/test_package.py --manager-fixture
K:\ActorwrightExchange\projects\CHIM-MindPoisoning\tests\.package-manager-check\source\fixture.dwpkg

wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/package_manager_check.php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/.package-manager-check/source/fixture.dwpkg
PASS: schema-4 fixture uploaded and installed through the pinned manager in scratch roots.
PASS: installed bytes match archive payload (13 files).
PASS: checksum-tampered archive rejected; prior payload and installed state preserved.
PASS: injected migration runner was not called.
```

The PHP run above passed after removing the stale count. The earlier red reproduction was the same harness and same fixture, before that edit.
The generated fixture directory was removed after verification; the harness also removed its own per-run scratch tree.

## Not newly changed

- The prior manifest/update-flow harness already exercised the actual installed manager/installer helper declarations with fixture-only fetches and covers candidate manifest lookup, `<version>` expansion, and matching plugin release URL. This hunt did not repeat that check or alter the manifest/catalog.
- The installed Manager's first-match-by-repository behavior remains a documented shared-core limitation. The current catalog has only one plugin for this repository; a plugin-only workaround or CHIM core edit is outside this task.
- No production consumer, manifest version, tag, release asset, database, provider, or installed plugin was modified or exercised.
