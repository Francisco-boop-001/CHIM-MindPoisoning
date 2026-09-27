# Packaging and verification report

## Result

Implemented a stdlib-only deterministic schema-4 `.dwpkg` builder with a seven-file server allowlist. The outer `name` and `version` come from `server/manifest.json`; the root checksum file covers the outer manifest and every server payload file. The builder writes only within the project, verifies the archive before publishing it, and ignores unlisted files.

The Python packaging checks and real-manager fixture checks passed. After lead acceptance of the local runtime source, fixtures, and read-only SQL planning, the final development-candidate archive was built and passed the pinned manager harness using project-local scratch roots. No files were written to the installed server tree, F:, a database, or a provider; no deployment or pin advancement was performed.

## Source reference

- HerikaServer commit: `cf5030f15781637498be86debe26fcf102f5690d`
- Manager: `/var/www/html/HerikaServer/lib/plugin_package_manager.php`
- Manager SHA-256: `a35936ac82fe4bad92bedb514cc9b75353f663d21de07b88c9e4f1ec36e89e42`
- Focused fixture: `/var/www/html/HerikaServer/unittests/tests/PluginPackageManagerTest.php`
- Fixture SHA-256: `34a762a99f08054e0d737c1559ce97a29abfc9c133adca33adfcf4c03857748c`
- Reference environment: PHP 8.2.29 with `ZipArchive`; Python 3.12.4.
- Read-only source commands: `git -c safe.directory=/var/www/html/HerikaServer -C /var/www/html/HerikaServer rev-parse HEAD` and `sha256sum /var/www/html/HerikaServer/lib/plugin_package_manager.php /var/www/html/HerikaServer/unittests/tests/PluginPackageManagerTest.php`.

## Checks

Red baseline, before `scripts/package.py` existed:

```text
python -m unittest discover -s tests -p 'test_package.py' -v
ImportError: Failed to import test module: test_package
ModuleNotFoundError: No module named 'scripts.package'
Ran 1 test
FAILED (errors=1)
```

Green packaging tests:

```text
python -m unittest discover -s tests -p 'test_package.py' -v
test_build_is_deterministic_and_uses_only_the_allowlist ... ok
test_missing_allowlisted_payload_fails_before_writing ... ok
test_verifier_rejects_checksum_tampering ... ok
Ran 3 tests in 0.250s
OK
```

This fixture run generated a package under the project’s ignored test scratch directory:

```text
python tests/test_package.py --manager-fixture
K:\ActorwrightExchange\projects\CHIM-MindPoisoning\tests\.package-manager-check\source\fixture.dwpkg
```

The actual pinned manager was then called with explicit server, state, and upload roots under a unique `tests/.package-manager-check-run-*` directory. Its injected migration callback throws if called.

```text
wsl.exe -d DwemerAI4Skyrim3 -e php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/package_manager_check.php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/.package-manager-check/source/fixture.dwpkg
PASS: schema-4 fixture uploaded and installed through the pinned manager in scratch roots.
PASS: installed bytes match archive payload (7 files).
PASS: checksum-tampered archive rejected; prior payload and installed state preserved.
PASS: injected migration runner was not called.
```

The fixture scratch directory was removed after the check. `python --version` returned `Python 3.12.4`. The raw-byte line-ending command

```text
python -c "from pathlib import Path; files=['.gitattributes','.gitignore','README.md','scripts/package.py','server/manifest.json','server/README.md','server/AGENTS.md','tests/test_package.py','tests/package_manager_check.php']; bad=[p for p in files if b'\r' in Path(p).read_bytes()]; print('LF check:', 'PASS' if not bad else 'FAIL '+', '.join(bad))"
LF check: PASS
```

`git check-attr text eol -- .gitattributes .gitignore README.md scripts/package.py server/manifest.json server/README.md server/AGENTS.md tests/test_package.py tests/package_manager_check.php` reported `text: set`, `eol: lf` for PHP, Python, Markdown, and JSON source files.

## Package payload allowlist

The builder includes only:

- `server/AGENTS.md`
- `server/README.md`
- `server/influence.php`
- `server/manifest.json`
- `server/model.php`
- `server/prerequest.php`
- `server/store.php`

It excludes tests, tasks, root documentation, credentials, config, logs, reference copies, caches, and VCS files. Missing allowlisted files fail before an archive is written.

## Owned files

- `.gitattributes`
- `.gitignore`
- `README.md`
- `scripts/package.py`
- `server/AGENTS.md`
- `server/README.md`
- `server/manifest.json`
- `tests/package_manager_check.php`
- `tests/test_package.py`
- `tasks/package-report.md`

## Final candidate archive

- Path: `dist/mind_poisoning-0.1.0.dwpkg`
- Size: 67,799 bytes
- SHA-256: `4ab1c38bc30bd2c09876f9df47fd66853615eee33b154aade6ced3243d44b94f`
- Deterministic rebuild: `python scripts/package.py` was run twice; both runs printed the same SHA-256 and the byte hash remained unchanged.

Exact build output from both runs:

```text
Built and verified dist\mind_poisoning-0.1.0.dwpkg (mind_poisoning 0.1.0, sha256 4ab1c38bc30bd2c09876f9df47fd66853615eee33b154aade6ced3243d44b94f)
Built and verified dist\mind_poisoning-0.1.0.dwpkg (mind_poisoning 0.1.0, sha256 4ab1c38bc30bd2c09876f9df47fd66853615eee33b154aade6ced3243d44b94f)
First SHA256: 4ab1c38bc30bd2c09876f9df47fd66853615eee33b154aade6ced3243d44b94f
Second SHA256: 4ab1c38bc30bd2c09876f9df47fd66853615eee33b154aade6ced3243d44b94f
Deterministic rebuild: PASS
```

The builder's `verify_archive` compared every archive member byte-for-byte with the current source and checked the exact member list and hashes on each build. An independent archive inspection also returned `source_byte_verification: PASS`. The actual ZIP contains these nine sorted entries:

```text
checksums.sha256 674 sha256=8fad520aafddd26568f84aea7697f0faaabd368b92ab75e4db320301546d031c
manifest.json 119 sha256=a64ca28d74110043eada80b0b00769f1076b64a8296d1b94953b2cee7b019444
server/AGENTS.md 2393 sha256=c11acf57c602f53b89986ec165f5f57218e258af24403ab70b10c1b5bb485800
server/README.md 3099 sha256=99da1b57f5eff95bd7adcd03d367eebe8afbf758a68789f3070e942fbe022a1f
server/influence.php 13661 sha256=dff2b42a53b6f23b16c6d6141e0534e3b03345e5cc93e2231450c9f13b41b58a
server/manifest.json 275 sha256=c95081cb18215b212d143bc42673e5d81c9c25af37b4a7a6e20a480e3c4116f1
server/model.php 4266 sha256=c44ba6400fdc1734591be74b39f9ce0563bba8361e9b6748920937adbd34c20b
server/prerequest.php 8744 sha256=365bf314b77c44c4cb7d8b92f22813088a686acb198fbf7b2573d615ba1d9b6d
server/store.php 33554 sha256=009815953e99227a2350b0b995baf086bd0888efd75cd90c1a8e3ca5e6eebd52
```

The archive's seven server files were separately checked for required-file presence and raw CR bytes:

```text
python -c "from pathlib import Path; names=['AGENTS.md','README.md','influence.php','manifest.json','model.php','prerequest.php','store.php']; root=Path('server'); missing=[n for n in names if not (root/n).is_file()]; cr=[n for n in names if (root/n).is_file() and bytes([13]) in (root/n).read_bytes()]; print('missing:', missing); print('payload files with CR bytes:', cr)"
missing: []
payload files with CR bytes: []
```

The pinned package manager was run against the actual archive, not the earlier fixture archive. It used explicit server, state, upload, and temporary paths below `tests/.package-manager-check-run-*`; the injected migration callback throws if called. The harness prints “fixture” in its generic success text, but the command argument was the actual `dist` archive:

```text
wsl.exe -d DwemerAI4Skyrim3 -e php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/package_manager_check.php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/dist/mind_poisoning-0.1.0.dwpkg
PASS: schema-4 fixture uploaded and installed through the pinned manager in scratch roots.
PASS: installed bytes match archive payload (7 files).
PASS: checksum-tampered archive rejected; prior payload and installed state preserved.
PASS: injected migration runner was not called.
```

The manager fixture scratch root was cleaned after the check. Runtime source/fixture acceptance and read-only SQL planning are summarized in [`tasks/runtime-report.md`](runtime-report.md); the latest lead-run runtime suite exited 0 with expected failure-path logs followed by `runtime store checks passed`. No live PostgreSQL writes or concurrency, live provider call, or in-game behavior were verified. This archive is a development candidate for an isolated test environment only; do not deploy it or advance a server pin. Removing the plugin prevents future evaluations but does not undo affinities already stored in CHIM.
