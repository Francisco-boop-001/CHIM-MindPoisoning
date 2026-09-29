# Mind Poisoning v0.1.8 PRE-ALPHA

Development-candidate prerelease. Compatibility reference remains `cf5030f15781637498be86debe26fcf102f5690d`; this is not a deployment pin. The official CHIM catalog entry has not been submitted or approved.

## Changes

- Supports CHIM without an active Playthrough Saves profile, including servers without the optional profile table. These events use an explicit shared-server database scope. One active profile retains profile isolation; ambiguous or invalid profile state is rejected. Duplicate protection and the commit-time context checks remain in place.
- The dashboard labels shared-server history accurately. Without an active profile, different Skyrim saves using the same server database are not automatically isolated.
- Plugin Page requests a one-time localhost handoff for otherwise denied Windows/WSL navigation. The existing loopback or web-server authentication requirement remains unchanged.
- The MO2 download is now a plain ZIP using the existing CHIM file-sync layout. It contains no FOMOD installer, ESP, or game assets. The reported v0.1.7 FOMOD crash remains unresolved; MO2 may still display its custom-content warning.

## Downloads and installation

- [Plain MO2 ZIP](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.8/mind_poisoning-0.1.8-mo2.zip): retain `CHIM` directly under the installer's `<data>` root. If MO2 shows its content warning, choose **OK**, then **Ignore**. Replace the older Mind Poisoning package; do not stack versions. Native MO2 installation of this release remains unverified.
- [CHIM sync package](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.8/mind_poisoning-0.1.8.dwpkg): for manual sync, rename to `0.1.8.dwpkg` and place under `Data/CHIM/server-plugins/mind_poisoning/`.
- [Repository archive](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.8/mind_poisoning.tar.gz): for CHIM repository/Plugin Manager ingestion, with one top-level `mind_poisoning/` directory.
- [SHA256SUMS.txt](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.8/SHA256SUMS.txt): checksums for the three packages.

## Verification and remaining limits

Focused runtime, logging, dashboard, HTTP, integration, manifest-update and package checks passed. An isolated PostgreSQL fixture exercised shared and explicit profiles, missing profile metadata, ambiguous profiles and ledger isolation. These checks do not establish live CHIM database writes/concurrency, provider behavior, client delivery, deployed authentication or in-game acceptance.

Six observed acknowledgements were rejected as `invalid-payload`; their cause remains unresolved. This release does not claim to fix those rejections. The model call remains synchronous, and model judgments can misclassify mentions or leave affinity unchanged.

Test in an isolated CHIM server/database and a disposable game save. A separate MO2 profile alone does not isolate server data. Removing the plugin stops future evaluations but does not undo stored relationships or history.
