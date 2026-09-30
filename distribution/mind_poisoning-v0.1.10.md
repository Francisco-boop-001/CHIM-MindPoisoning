# Mind Poisoning v0.1.10 PRE-ALPHA

Development-candidate prerelease. The CHIM compatibility reference is unchanged. Use one installation route per CHIM server; do not combine Plugin Manager installation with an older MO2 file-sync copy.

## Changes

- Otherwise-valid NPC `_speech` acknowledgements with a missing, empty, or whitespace-only string `utterance_id` are skipped as info `untracked-speech` with reason `utterance_id_absent`. No correlation ID is inferred, and the model and persistence paths are not called.
- Wrong-type or malformed IDs remain warnings. Other required payload fields are validated before the benign untracked-speech skip, so malformed payloads retain their warning behavior.
- The user-supplied post-release shape-decoding analysis in `critique.md` correlates all 18 observed `invalid-payload` warnings, including the six previously noted, with valid ACK JSON lacking `utterance_id`. This release note attributes that finding to the report; it does not claim an independent re-decode.

## Downloads

- [Repository archive](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.10/mind_poisoning.tar.gz) for CHIM repository/Plugin Manager ingestion.
- [CHIM sync package](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.10/mind_poisoning-0.1.10.dwpkg) for server file sync.
- [Plain MO2 import ZIP](https://github.com/Francisco-boop-001/CHIM-Plugins/releases/download/mind_poisoning-v0.1.10/mind_poisoning-0.1.10-mo2.zip). MO2 may report its Skyrim content warning because this ZIP contains CHIM server files, not Skyrim gameplay assets.
- `SHA256SUMS.txt` contains package checksums.

## Verification and limits

The ACK classification correction passed the focused runtime and logging fixtures and touched PHP syntax checks. These checks do not establish live CHIM delivery or behavior. Live database writes/concurrency, provider behavior, deployed dashboard authentication, and in-game behavior have not been verified. Client-reported speech is not independent proof of audio delivery. The operator pause is sampled before and after model work rather than an atomic cancellation; database wait limits bound individual operations, not total request time.
