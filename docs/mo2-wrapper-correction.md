# MO2 wrapper correction for v0.1.5

The corrected wrapper is distributed as a separate installer-only companion release: [v0.1.5-installer.1](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.5-installer.1). Download [`mind_poisoning-0.1.5-mo2-installer.zip`](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.5-installer.1/mind_poisoning-0.1.5-mo2-installer.zip). The original `mind_poisoning-0.1.5-mo2.zip` on the v0.1.5 runtime release remains unchanged. Runtime version remains 0.1.5; this companion release changes packaging only.

The local build path is `dist/0.1.5/mind_poisoning-0.1.5-mo2-installer.zip`.

The new ZIP adds FOMOD metadata and maps the unchanged package to:

```text
CHIM/server-plugins/mind_poisoning/0.1.5.dwpkg
```

In MO2, choose **Install a new mod from an archive** and select the `-mo2-installer.zip`. The FOMOD configuration has no selectable options; reviewed upstream MO2 FOMOD source uses a small Install/Cancel dialog showing its name and version in this case (the description is hidden). Cancel before installation if the package is not intended for that profile. The exact UI was not opened or tested against the installed MO2 version in this task.

After installation, inspect the mod tree and confirm the path above. This is CHIM server content, not Skyrim game content. FOMOD fixes the extraction destination; it does not suppress MO2's missing-game-data/custom-content flag. The inspected MO2 2.5.2 install-completion path does not automatically mark this content valid, so the warning may remain. No profile override was applied and no dummy ESPs, scripts, or other game files were added.

Use only with an isolated PRE-ALPHA test profile and CHIM server/database. The wrapper and package were verified from source locally, but no MO2 install, live CHIM server, database, provider, or in-game behavior was tested.
