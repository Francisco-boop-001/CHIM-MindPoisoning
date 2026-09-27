# Mind Poisoning v0.1.3

Development-candidate prerelease for HerikaServer. This version adds typed, allowlisted reasons for known connector/request and judgment-validation failures, and packages the read-only Interactions/Logs dashboard with the poster artwork and Day/Night theme.

## Compatibility

Inspected compatibility reference: HerikaServer `cf5030f15781637498be86debe26fcf102f5690d`. This is a reference, not a deployment pin. The official CHIM catalog entry has not been submitted or approved.

## Candidate limits

Source and fixture checks passed. Live PostgreSQL writes/concurrency, provider behavior, client ACK behavior, dashboard authentication, native log delivery, and in-game/save-load behavior remain unverified. The ACK text is client-reported speech, not independent proof of audio playback. Test only in an isolated CHIM server/database and separate game profile.

The feature remains bounded to exact `_speech` event identity and named subjects, up to 8 subjects per event and 12,000 bytes of speech. Deltas are integer -5 through +5; resulting affinity is clamped to -100 through +100. The synchronous model call uses the configured supported connector and a 12-second I/O timeout, not a hard wall-clock deadline. Removing the plugin stops future evaluations but does not undo stored affinity or history.

The dashboard is read-only and displays sanitized plugin records alongside separately queried current affinity. Its access guard and read path have isolated fixture coverage only; configure web-server authentication for remote access. It is not a complete audit trail.

## Downloads

Preferred CHIM Plugin Manager artifact: [`mind_poisoning.tar.gz`](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.3/mind_poisoning.tar.gz), attached to the [v0.1.3 prerelease](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/tag/mind_poisoning-v0.1.3). It contains one top-level `mind_poisoning/` directory; the installer strips that directory and expects `manifest.json` at the extracted package root.

The schema-4 [`mind_poisoning-0.1.3.dwpkg`](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.3/mind_poisoning-0.1.3.dwpkg) is a separate CHIM file-sync format, not the catalog/Plugin Manager download path.

## Verification

Focused local checks passed for model, influence, runtime, packaging, and dashboard integration. These use fixtures or isolated temporary roots; they do not establish live database transactions/concurrency, provider responses, client behavior, dashboard authentication in deployment, or in-game acceptance.
