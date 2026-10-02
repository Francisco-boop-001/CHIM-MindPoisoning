# Mind Poisoning 0.1.15 release plan

Authorization: the user explicitly requested incorporating the reviewed work, committing, pushing and publishing. This authorizes Mind Poisoning release metadata and remote publication, not installation or live database/provider/game changes.

- [x] Merge reviewed fix/reflection-reply-v2 into work/mind-poisoning and incorporate newer origin/main commits without overwriting unrelated dirty work.
- [x] Delegate and review only the necessary 0.1.15 PRE-ALPHA metadata/documentation changes; preserve Private Conversation's current source and release.
- [x] Verify the merged runtime and companion boundary using the smallest checks needed for the newly incorporated snapshot.
- [x] Build the three supported formats, commit/tag the exact source, rebuild from a clean tag and compare bytes/checksums.
- [x] Push immutable new tag; upload and download draft assets, verify them, publish, then advance canonical and migration-hub main refs.
- [x] Verify public assets/refs/update manifest, save release evidence and preserve unrelated edits.

Source reviewed: bf02d1ac332513d407d3646970c04ea9753a9a8d. Original branch base: 74ca8c97d30045822170477aad87824fb39b8222. Newer collection main observed: 34ad2a1; canonical Mind Poisoning main remains 74ca8c9. Existing published MP release is 0.1.14; 0.1.15 tag absent in both repositories. The guide web fetch was unavailable; use the existing inspected release checklist and deterministic builder without claiming a fresh guide verification.

Success: reviewed fixes reachable in the working branch and both publishing repositories; new immutable PRE-ALPHA release assets reproduce from their tag and public downloads match; update manifests point at available assets. No historical tag/asset replacement, PCV publication, modlist or installed-server change.

Review: all checks complete; source tag targets 4ac3b7713066216aeb37f4275ed907fa3bd99c19. Both public releases match all clean-tag files and checksum entries. Exact commands, hashes, preservation and runtime limits: release-v0.1.15.md.
