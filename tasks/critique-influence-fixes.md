# MP-05 prior-judgment context

`server/influence.php` now passes the listener's existing `plugin_extended_data.mind_poisoning` ledger into the user payload. It reads the actual `stdClass` JSONB shape preserved by `promptNpcCopy`, requires the ledger playthrough to match the current event, and includes only prior judgments for current candidate tokens. It filters malformed entries and the current event, keeps the last eight relevant events, rejects nested entries with more than eight judgments, and bounds the stored utterance ID, reason, and evidence snippets to 120 UTF-8 bytes. No schema or persistence changes were made.

The system prompt treats prior entries as untrusted model judgments, not verified claims; it states that speaker attribution is unavailable and snippets may be truncated. It asks for a conservative zero on materially repeated allegations without new evidence while allowing new evidence or context to change the judgment. This is model guidance, not a cooldown or deterministic dedupe guarantee.

## Verification

- Test-first reproduction: before implementation, `tests/influence_test.php` failed because the prompt contained no `prior_judgments` history.
- Bound regression: with the per-entry eight-judgment check removed, the oversized-entry fixture displaced the expected latest relevant event IDs. Restoring the check produced the expected `[3..10]` sequence.
- Green: `wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/influence_test.php` printed `influence checks passed` (exit 0). Fixtures verify same-playthrough filtering, exclusion of other subjects, the last-eight bound, bounded snippets, absent speaker attribution, other-playthrough exclusion, and oversized-entry rejection.

No live provider, database, installer, or game calls were made. Semantic repetition detection depends on the model and the bounded excerpts; concurrent requests or incomplete history can still accumulate changes.
