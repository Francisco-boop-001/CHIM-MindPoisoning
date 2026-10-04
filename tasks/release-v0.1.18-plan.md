# Mind Poisoning 0.1.18 release

- [x] Review current source evidence and release metadata; preserve unrelated work.
- [x] Verify the reviewed index through a clean export, pinned Plugin Manager consumer check and deterministic packages.
- [x] Commit and tag; rebuild from the clean tag and compare every source file and release asset.
- [x] Independently review the release diff and evidence.
- [x] Push tags, verify downloaded draft assets, publish PRE-ALPHA releases and verify public downloads.
- [x] Advance both main branches only after publication; verify public manifests and protected files, and record receipt.

Scope: publish the verified relationship compatibility fixes and previously committed sentinel guard as 0.1.18. No installation, WSL execution, live database/provider, modlist, catalog or Discord action. Existing behavior/SQL evidence is in relationship-compatibility-2026-10-04/verification.md; this run checks release/export/consumer/package risks rather than repeating completed suites. Preserve Private Conversation 0.1.15 and all historical releases.

Review: Canonical and mirror PRE-ALPHA releases are public; source and all assets match clean-tag builds, draft/public downloads and main-source checks. Independent source/package review approved. Protected work and 0.1.17 assets/refs are preserved. No installation or WSL/live runtime operation. See [publication receipt](release-v0.1.18.md).
