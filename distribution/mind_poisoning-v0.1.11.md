# Mind Poisoning v0.1.11 PRE-ALPHA

Development-candidate prerelease. The CHIM compatibility reference is unchanged. Use one installation route per CHIM server; do not combine Plugin Manager installation with an older MO2 file-sync copy.

## Changes

- NPC `_speech` acknowledgements normalize surrounding `utterance_id` characters with PHP's default `trim()` before the existing strict format validation. Invalid interior content remains rejected.
- Player-origin gossip is eligible only when the effective `CHIM_EXECUTION_MODE` is `STANDARD`, `WHISPER`, or `CLOSE`. Other or unknown modes are informational skips with reason `player-input-not-speech`; injected context alone does not count as Player speech.

## Downloads

- [Repository archive](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.11/mind_poisoning.tar.gz) for CHIM repository/Plugin Manager ingestion.
- [CHIM sync package](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.11/mind_poisoning-0.1.11.dwpkg) for server file sync.
- [Plain MO2 import ZIP](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.11/mind_poisoning-0.1.11-mo2.zip). MO2 may report its Skyrim content warning because this ZIP contains CHIM server files, not Skyrim gameplay assets.
- `SHA256SUMS.txt` contains package checksums.

## Verification and limits

Fresh package checks passed. Focused fixtures cover the ACK normalization and Player mode gate, but these two changes have not been verified by a live installation or gameplay. The prior user-reported Player-origin commits and skips remain documented in the [v0.1.10 live acceptance record](https://github.com/Francisco-boop-001/CHIM-Plugins/blob/mind_poisoning-v0.1.11/tasks/live-acceptance-v0.1.10.md); they do not test the v0.1.11 changes. Live installation, NPC-origin/audio delivery, broad database concurrency/failure behavior, and provider reliability remain unverified.
