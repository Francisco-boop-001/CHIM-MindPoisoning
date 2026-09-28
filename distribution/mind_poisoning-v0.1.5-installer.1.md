# Mind Poisoning v0.1.5-installer.1 PRE-ALPHA

Installer-only companion release for the v0.1.5 runtime package. Runtime version remains **0.1.5**; no plugin source or `.dwpkg` payload changed.

## Asset

- [MO2 FOMOD import wrapper](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.5-installer.1/mind_poisoning-0.1.5-mo2-installer.zip)
- [SHA256SUMS.txt](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.5-installer.1/SHA256SUMS.txt)

The wrapper maps the byte-identical v0.1.5 package to `CHIM/server-plugins/mind_poisoning/0.1.5.dwpkg`. The original v0.1.5 runtime release and its repository tarball, `.dwpkg`, and original MO2 wrapper are unchanged.

## Verification and limits

Focused packaging checks passed for the FOMOD mapping, deterministic output, exact inner package bytes, and package/source verification. No MO2 GUI install or CHIM client file-sync run was performed. MO2 may continue to flag the installed CHIM-only files as missing game data; the wrapper corrects extraction mapping and does not suppress that custom-content warning.

This remains PRE-ALPHA. Live CHIM database writes/concurrency, provider behavior, clean-server loading, client file sync, dashboard authentication, and in-game behavior are unverified. Use an isolated test profile and server/database.
