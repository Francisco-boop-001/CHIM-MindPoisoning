# v0.1.1 local candidate package evidence

## Source and scope

The source candidate is the reviewed working tree based on `0a207700dbee57d9dff49ac05c7a3c3b42b42d43`, with the lead-reviewed runtime, influence, and documentation changes. The manifest is `mind_poisoning` version `0.1.1`, status `development_candidate`. Artifacts were written only under `dist/0.1.1/`; the older v0.1.0 artifacts were left intact. No tag, release, catalog submission, installation, or deployment pin was changed.

## Payload and source verification

Before building, the raw-byte scan covered the seven server payload files: `AGENTS.md`, `README.md`, `influence.php`, `manifest.json`, `model.php`, `prerequest.php`, and `store.php`. All had zero byte `0x0D`. An initial scan mistakenly searched for the literal two-character sequence `\r` and flagged two PHP files; the corrected scan using `bytes([13])` found zero raw CR bytes. No normalization or source edit was made in response to that false alarm.

Using the existing `scripts.package` builder and source verifiers:

```text
build_repository_archive(root, root / "dist/0.1.1/mind_poisoning.tar.gz")
build_package(root, root / "dist/0.1.1/mind_poisoning-0.1.1.dwpkg")
verify_repository_archive(tar_path, root)  -> PASS
verify_archive(dwpkg_path, root)           -> PASS
```

Both verifiers compare the archive payload with current source bytes. The lead independently verified both final archives against the exact current source; both passed.

| Artifact | Size | SHA-256 |
|---|---:|---|
| `dist/0.1.1/mind_poisoning.tar.gz` | 16,764 bytes | `7801d8aa888494b00748de679fb062459cd96f9866a9319a03f28cbfb9029d1c` |
| `dist/0.1.1/mind_poisoning-0.1.1.dwpkg` | 74,563 bytes | `ff095717aa03403f4366e19312074606061c063e3c9bec741fb31f638f1273da` |

The tar contains `mind_poisoning/` plus its seven payload files. The schema-4 ZIP contains `checksums.sha256`, package `manifest.json`, and the same seven payload files. No installer harness, extraction test, unchanged runtime suite, or live service test was repeated; those formats and install routes were unchanged.

## Limits

These are local development candidate artifacts, not a runtime-certified or published release. Live PostgreSQL writes/concurrency, provider behavior, client ACK echo behavior, in-game playback, and save/load remain unverified. The v0.1.1 URLs in the catalog template are planned targets only; the published v0.1.0 tag/assets remain unchanged.
