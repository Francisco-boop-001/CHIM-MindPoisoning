# Repository package report

## Result

Added a deterministic repository `.tar.gz` output while preserving the no-argument schema-4 `.dwpkg` route. Final release docs/catalog name `mind_poisoning.tar.gz`; the GitHub repository slug remains unconfirmed.

- `dist/mind_poisoning.tar.gz`: 15,193 bytes; SHA-256 `0fd8eaf99fbb6aa40fae0a3f803dbbef41359b9ed55a8ea8debf7ee24d2ee569`
- `dist/mind_poisoning-0.1.0.dwpkg`: 67,519 bytes; SHA-256 `e4412e96748ed52f5c9c80e1f38657b029b2d6cde3a78737ef34a349794c8c5f`

The tar contains one top-level `mind_poisoning/` directory and exactly the seven allowlisted files. With `--strip-components=1`, `manifest.json` lands at the installed plugin root.

## Installer contract

Read-only inspection of `/var/www/html/HerikaServer/ui/server_plugin_installer.php` confirmed release assets are `<packageName>.tar.gz` (lines 127–130), extraction uses GNU tar `xvfz` and `--strip-components=1` (lines 151–162, 332–353), then reads `manifest.json` from the extraction root (lines 349–353). No installer entrypoint, endpoint, network access, or installed file was invoked or changed.

## Changes

- `scripts/package.py` adds `build_repository_archive` and `verify_repository_archive`, reusing `SERVER_FILES` and the existing manifest/source validation. It writes a fixed-metadata USTAR gzip with regular files only, then verifies exact paths, types, metadata, and source bytes before atomic publication.
- `python scripts/package.py` remains the schema-4 build. `python scripts/package.py --format repository-tar-gz` writes `dist/mind_poisoning.tar.gz`.
- Gzip header mtime is 0. Tar members use mtime 315532800 (1980-01-01 UTC). The first GNU tar attempt on project storage failed with `Cannot utime: Invalid argument` because DrvFs rejected epoch-zero file mtimes. The fixed 1980 timestamp kept rebuilds deterministic and allowed extraction.
- `tests/test_package.py` covers deterministic gzip bytes, exact layout, regular-file types, source equality, and the existing schema-4 allowlist/tamper checks. The existing missing-file test exercises shared `_read_entries` validation used by both builders.
- `tests/repository_tar_check.py` validates a fixture or supplied candidate, runs GNU tar with the installed extraction arguments in a unique project-local scratch root, compares all extracted bytes, and removes the scratch root.

## Verification

```text
python -m unittest discover -s tests -p test_package.py -v
test_build_is_deterministic_and_uses_only_the_allowlist ... ok
test_missing_allowlisted_payload_fails_before_writing ... ok
test_repository_tar_is_deterministic_and_contains_only_regular_payload_files ... ok
test_verifier_rejects_checksum_tampering ... ok
Ran 4 tests
OK
```

```text
python scripts/package.py --help
usage: package.py [-h] [--format {dwpkg,repository-tar-gz}]
--format {dwpkg,repository-tar-gz}
  package format (default: dwpkg)
```

After the README/catalog payload freeze, both final formats were rebuilt twice:

```text
python scripts/package.py
Built and verified dist\mind_poisoning-0.1.0.dwpkg (mind_poisoning 0.1.0, sha256 e4412e96748ed52f5c9c80e1f38657b029b2d6cde3a78737ef34a349794c8c5f)
python scripts/package.py
Built and verified dist\mind_poisoning-0.1.0.dwpkg (mind_poisoning 0.1.0, sha256 e4412e96748ed52f5c9c80e1f38657b029b2d6cde3a78737ef34a349794c8c5f)
schema-4 rebuild: PASS
python scripts/package.py --format repository-tar-gz
Built and verified dist\mind_poisoning.tar.gz (mind_poisoning 0.1.0, sha256 0fd8eaf99fbb6aa40fae0a3f803dbbef41359b9ed55a8ea8debf7ee24d2ee569)
python scripts/package.py --format repository-tar-gz
Built and verified dist\mind_poisoning.tar.gz (mind_poisoning 0.1.0, sha256 0fd8eaf99fbb6aa40fae0a3f803dbbef41359b9ed55a8ea8debf7ee24d2ee569)
repository tar rebuild: PASS
```

Actual candidate extraction and byte comparison:

```text
wsl.exe -d DwemerAI4Skyrim3 -e python3 /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/repository_tar_check.py /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/dist/mind_poisoning.tar.gz
PASS: tar (GNU tar) 1.34
PASS: GNU tar xvfz with --strip-components=1 extracted the candidate into scratch.
PASS: extracted bytes match the seven allowlisted source files; other files are excluded.
PASS: project-local scratch tree cleaned.
```

Both builder verifiers passed against current source bytes. The tar has exactly one directory plus seven regular files: `mind_poisoning/AGENTS.md` (2,393 bytes), `README.md` (2,819), `influence.php` (13,661), `manifest.json` (275), `model.php` (4,266), `prerequest.php` (8,744), and `store.php` (33,554). Gzip mtime is 0; all tar member mtimes are 315532800.

The earlier `.dwpkg` hash `4ab1c38bc30bd2c09876f9df47fd66853615eee33b154aade6ced3243d44b94f` (67,799 bytes) is superseded by the final packaging-doc-only README change. The rebuilt `.dwpkg` is 67,519 bytes with the hash above. Runtime PHP and `server/manifest.json` were not changed in this follow-up.

## Limits

The final `.dwpkg` was rebuilt and source-verified; the PHP package-manager harness was not rerun for this README-only payload change. GNU tar was tested directly; the installer itself was not bootstrapped or called. No GitHub publication, repository slug, deployment, or pin change was performed. Database writes/concurrency, provider behavior, and in-game behavior remain unverified by this packaging work.

## Owned files

- `scripts/package.py`
- `tests/test_package.py`
- `tests/repository_tar_check.py`
- `tasks/repository-package-report.md`
