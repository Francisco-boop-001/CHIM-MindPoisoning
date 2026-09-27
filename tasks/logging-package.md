# Local structured logging package check

Status: the current source, including `server/logging.php`, has verification-only archives under `dist/logging-check/`. These are not published replacements. The published v0.1.1 assets under `dist/0.1.1/` were not targeted or overwritten. Manifest version remains `0.1.1`; no tag, compatibility pin, or catalog entry changed.

## Build and verification

Build command, from the repository root:

```sh
python -c "from pathlib import Path; from scripts.package import build_package, build_repository_archive; root=Path.cwd(); out=root / 'dist' / 'logging-check'; build_repository_archive(root, out / 'mind_poisoning.tar.gz'); build_package(root, out / 'mind_poisoning-0.1.1.dwpkg')"
```

Result: exit 0. Both existing verifier helpers were then run against the built files and frozen source. `verify_repository_archive` passed exact layout, required member metadata, and source-byte checks. `verify_archive` passed its exact allowlist, checksums, and source-byte checks. Separate reads confirmed `logging.php` is present and byte-equal to `server/logging.php` in both formats.

The raw-byte LF check used `bytes([13])` on all eight `SERVER_FILES`; each count was 0. No payload normalization or source edit was needed.

## Artifacts

- `dist/logging-check/mind_poisoning.tar.gz` — 22,997 bytes; SHA-256 `1d8155f4eb4bb70e337f7c7d2099e626ee9fdda8faa821085f8b095a9fa706cd`. The archive has nine entries: the `mind_poisoning/` directory plus `AGENTS.md`, `README.md`, `influence.php`, `logging.php`, `manifest.json`, `model.php`, `prerequest.php`, and `store.php` beneath it.
- `dist/logging-check/mind_poisoning-0.1.1.dwpkg` — 104,105 bytes; SHA-256 `0cdc05f5fd95c6de7a09873a83b3fdac66458610a41a2b066ca5b0345b79d1f7`. The archive has ten entries: `checksums.sha256`, outer `manifest.json`, and the eight files under `server/`, including `server/logging.php`.

The published v0.1.1 packages contain seven payload files. The local logging verification archives contain eight while retaining the unchanged manifest version; do not treat these local checks as a publication or compatibility-pin change.

## Test evidence and limits

`php tests/logging_test.php` in WSL (`DwemerAI4Skyrim3`) printed `logging checks passed`, exit 0. A prior `tests/store_logging_test.php` run overlapped the store owner's final-summary edit and failed at its `request_finished` assertion; the owner corrected that assertion, and the lead reported the final combined fixture suite green before source freeze. I did not rerun that suite during packaging.

These package checks do not establish live PostgreSQL writes or concurrency, provider behavior, client ACK behavior, or in-game/save-load acceptance. The logging addition remains local and unpublished.
