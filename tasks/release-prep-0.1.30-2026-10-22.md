# 0.1.30 release preparation — read-only inspection

## Gate status

The initial blocker assessment in this read-only inspection is a historical snapshot and has been superseded by the current main release plan: the native `41+14` gate and ten focused Linux tests have passed, and C's final metadata work is underway. This note does not establish that the 0.1.30 tag or package builds are complete.

Department compatibility is a separate claim gate: its owner must review and re-pin the seven hashes before anyone claims deployed PRISMA supports 0.1.30. MP may be released independently; deployed PRISMA remains fail-closed while its old adapter/pin is in use.

## Four release formats

The four 0.1.29 release assets, confirmed in its release plan and retained assets, are:

1. DWPkg: `dist/mind_poisoning-0.1.30.dwpkg`
2. Repository/channel TAR: `dist/mind_poisoning.tar.gz`
3. Flat CHIM Plugin TAR: `dist/chim-plugin.tar.gz`
4. MO2 sync ZIP: `dist/0.1.30/mind_poisoning-0.1.30-mo2.zip`

From the root of each clean tag export, the exact builder commands are:

```powershell
python scripts\package.py --format dwpkg
python scripts\package.py --format repository-tar-gz
python scripts\package.py --format chim-plugin-tar-gz
python scripts\package.py --format mo2-sync-zip
```

`scripts/package.py` resolves the project root from its own file path and writes the paths above under that export's `dist`. It fixes archive timestamps/modes and verifies each output. The CLI and `tests/test_package.py` also support a fifth `mo2-fomod-zip` format; that format is not among the four published 0.1.29 assets and is excluded from this release set. Generate the sorted `SHA256SUMS.txt` separately from the four final assets.

## Package test gate

After the source freeze, run once from the clean export root:

```powershell
python tests\test_package.py
```

The `unittest` entry point contains eight tests: deterministic allowlist DWPkg, missing-payload rejection, checksum-tamper rejection, deterministic repository TAR, flat CHIM TAR, current WebP/hook packaging, deterministic MO2 sync ZIP, and deterministic MO2 FOMOD ZIP. The final FOMOD test remains part of the builder test suite even though FOMOD is not a release asset. No package tests were run during this inspection, per instruction.

## Minimal independent byte reproduction

After the immutable tag and full commit SHA exist, make two new exports from that same tag with the prior release's `git archive` approach (the 0.1.29 verifier's hardcoded version-specific main is not reusable as-is):

```powershell
$repo = 'K:\ActorwrightExchange\projects\CHIM-MindPoisoning-Written-Evidence'
$tag = 'mind_poisoning-v0.1.30'
$exportA = 'K:\ActorwrightExchange\artifacts\mp-release-0.1.30\clean-a'
$archiveA = 'K:\ActorwrightExchange\artifacts\mp-release-0.1.30\clean-a.tar'
New-Item -ItemType Directory -Force -Path $exportA | Out-Null
git -c safe.directory=$repo -C $repo rev-parse --verify "$tag^{commit}"
git -c safe.directory=$repo -C $repo archive --format=tar --output=$archiveA $tag
tar.exe -xf $archiveA -C $exportA
```

Repeat the same steps for fresh `clean-b` / `clean-b.tar` paths, creating the clean-b directory first. Verify both tag resolutions are the same full commit and both Git archive hashes match. Run the four builder commands from each export with the same `python.exe` and zlib runtime (record `python --version` and `python -c "import zlib; print(zlib.ZLIB_VERSION)"`). Compare each corresponding `dist` file with `fc.exe /b`; record byte length and SHA-256 for both copies. Only after equality should the four clean-A outputs be copied to the release staging area and the sorted checksum file be generated. This gives independent clean-source byte reproduction without using the dirty worktree as a package input.

This is a command plan only. No package build, package test, source/metadata edit, tag, or release action was performed.
