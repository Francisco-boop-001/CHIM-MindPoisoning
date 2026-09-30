# Mind Poisoning v0.1.9 PRE-ALPHA

Development-candidate prerelease. Compatibility reference remains `cf5030f15781637498be86debe26fcf102f5690d`; it is not a deployment pin. The official CHIM catalog entry has not been submitted or approved.

## Changes

- New malformed NPC `_speech` acknowledgements retain their existing skipped `invalid-payload`/`oversized` statuses, with fixed warning reason codes for invalid JSON/root, missing or mistyped fields, size/emptiness/UTF-8 errors, and missing/mistyped/invalid utterance IDs. Raw payloads, speech, names, and malformed IDs are not logged. Existing aggregate rejections cannot be diagnosed retroactively; the six previously observed cases remain unresolved.
- Adds an operator-managed `data/mind_poisoning.json` pause control outside the package. An absent file keeps the plugin enabled; `{"enabled":false}` stops new model work, and a fresh post-model read blocks persistence when the pause is observed. The bounded fixed-format file rejects duplicate keys and invalid shapes. This sampled gate is not an atomic cancellation and has no dashboard control.
- Adds transaction-local PostgreSQL limits of 1,000 ms for `lock_timeout` and 3,000 ms for `statement_timeout`, preserving smaller positive inherited values. They bound individual waits/statements, not total transaction or request time. The plugin does not infer timeout classes from PostgreSQL error text.

## Downloads

- [Repository archive](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.9/mind_poisoning.tar.gz) for CHIM repository/Plugin Manager ingestion.
- [CHIM sync package](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.9/mind_poisoning-0.1.9.dwpkg) for server file sync.
- [Plain MO2 import ZIP](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.9/mind_poisoning-0.1.9-mo2.zip). MO2 may report the Skyrim content warning because the ZIP contains CHIM server files, not Skyrim gameplay assets.
- `SHA256SUMS.txt` contains package checksums.

## Verification and limits

The runtime fixture covers fixed ACK rejection reasons, operator pause before model work and after model work, and the existing valid routes. A disposable PostgreSQL 15 fixture verified transaction-local timeout bounds, setting restoration, rollback, and lock release. These are source/fixture checks, not live installed CHIM database or concurrency proof.

No live CHIM service, provider request, end-to-end client delivery, or in-game test is claimed. Client-reported speech is not independent proof of audio delivery. The synchronous model call and non-atomic pause sampling remain. See the repository developer guide for installation and operating limits.

## Post-release clarification (documentation only; v0.1.9 tag and assets unchanged)

The release text above described six earlier `invalid-payload` acknowledgements as unresolved. A later shape-decoding analysis in the user-supplied `critique.md` report correlates all 18 observed `invalid-payload` warnings, including those six, with valid ACK JSON lacking `utterance_id`. This clarification attributes the finding to that report; it does not claim an independent re-decode here. Released v0.1.9 still reports an absent ID as warning `payload_utterance_id_missing`. The v0.1.10 correction skips only otherwise-valid absent or empty/whitespace-only string IDs as info `untracked-speech` / `utterance_id_absent`; wrong-type and malformed IDs remain warnings. That correction is not part of the v0.1.9 release; it is included in v0.1.10.
