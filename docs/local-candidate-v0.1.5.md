# v0.1.5 PRE-ALPHA installation

Mind Poisoning v0.1.5 is a PRE-ALPHA development-candidate prerelease. Use it only in an isolated test profile and server/database. The official CHIM catalog entry has not been submitted or approved.

Download the [corrected MO2 import wrapper](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.5-installer.1/mind_poisoning-0.1.5-mo2-installer.zip). It contains the unchanged package at `CHIM/server-plugins/mind_poisoning/0.1.5.dwpkg`. This wrapper is for MO2/file sync, not Skyrim's `Data` plugins, and not the repository tarball expected by the CHIM catalog installer.

The original `mind_poisoning-0.1.5-mo2.zip` remains on the v0.1.5 runtime release. The corrected FOMOD wrapper is a separate installer-only release; it preserves the same `.dwpkg` but does not suppress MO2's custom-content warning. See [MO2 wrapper correction](mo2-wrapper-correction.md) for the exact mapping and limits.

## Install through MO2

1. Use a separate MO2 profile and an isolated CHIM server/database. A separate game profile alone does not isolate server data.
2. In MO2, choose **Install a new mod from an archive** and select the downloaded `mind_poisoning-0.1.5-mo2-installer.zip` file.
3. Install it as a separate mod, enable it only in the test profile, and inspect its file tree. It must contain `CHIM/server-plugins/mind_poisoning/0.1.5.dwpkg`.
4. Start the isolated CHIM server, launch Skyrim through the test MO2 profile, and load a test save. CHIM client file sync is documented to run on SAVE LOAD; this project has not verified that behavior on an installed server.
5. From the Windows host, open the existing Plugin Manager page, for example `http://localhost:8081/HerikaServer/ui/server_plugins.php`, and confirm `mind_poisoning` 0.1.5 appears. Substitute the configured port and base path if they differ. The localhost path was checked with an isolated responder, not an installed CHIM server; live loading remains unverified. The plugin has no migrations.
6. Select **Plugin Page**. The dashboard route for this example base path is `http://localhost:8081/HerikaServer/ext/mind_poisoning/dashboard.php`. Installed dashboard authentication and live data display have not been tested.

## Manual file sync

If MO2 is not used, extract the one inner `.dwpkg` from the wrapper and copy it intact to:

```text
Data/CHIM/server-plugins/mind_poisoning/0.1.5.dwpkg
```

Keep the version filename and `.dwpkg` contents unchanged. The wrapper ZIP itself is not the file-sync package.

## Upgrade and limits

The v0.1.5 dashboard references `dashboard-art.webp`; the original source PNG is not in the new payload. An older v0.1.4 image can remain as an unused file if an installer overlays files rather than cleaning them. The available project evidence does not establish stale-file cleanup, and this candidate adds no cleanup code.

This is PRE-ALPHA. Local model/runtime/dashboard fixtures and package checks do not prove live PostgreSQL writes/concurrency, provider behavior, clean-server loading, dashboard authentication, or in-game/save-load behavior. Removing the plugin stops future processing but does not undo stored affinity changes.
