# Runtime critique fixes

## MP-02: Passive ACK interaction gate

The core interaction helper rejects requests when `X-CHIM-Passive` is `1`, when the installation switch is Off, or when the request generation is stale. The hook now reads the core request generation and current interaction state directly: passive ACKs may proceed only while the switch is enabled and the captured request generation still matches. It checks this before the model call and again before persistence. Off remains honored, and a switch/generation change during model evaluation prevents the write.

The fixture shim follows the core `chimInteractionBegin` / `chimInteractionState` contract. Cases cover passive+On/current generation, passive+Off, stale generation before evaluation, and Off/generation change inside the model callback. The latter verifies no listener or history mutation.

## MP-03: ACK text versus logged chat text

The hook still requires a unique eligible chat row for the exact `utterance_id`, matches the logged speaker and direct non-broadcast target, and retains event row data for transactional revalidation. It no longer requires the callback speech bytes to equal the eventlog context bytes. After the existing UTF-8 and 12,000-byte validation, trimmed callback speech is the event text used for subject detection, prompt construction, and evidence validation. Logged-only text, such as a truncated subtitle tail, is not evaluated.

The callback speech is the client ACK evidence correlated to the event ID; this does not prove that a human heard it. No fuzzy text matching was added. A transformed-ACK fixture confirms that the logged-only Player mention neither enters the prompt nor creates a Player candidate. A table-driven fixture confirms that wrong utterance IDs, speakers, and listeners remain rejected before the model call.

## MP-07: Ambiguous legacy Player aliases

When the listener mentions a Player subject, the hook now calls the existing `playerRelationshipKey` resolver before the model request. Ambiguous aliases return `listener-invalid` without a provider call or relationship-map mutation. The resolver remains in transactional persistence as a recheck. NPC-only subjects do not invoke the Player alias gate; their update preserves both existing aliases.

## Reproduction and verification

Before MP-02, a passive ACK with interaction enabled and matching generation returned `interaction-off` instead of reaching evaluation. Before MP-03, the exact-ID truncated ACK fixture returned `event-mismatch` because its text differed from the logged context. Before MP-07, dual Player aliases returned `failed` after the persistence ambiguity exception instead of being rejected before evaluation.

After the changes:

```text
$ wsl.exe -d DwemerAI4Skyrim3 --exec php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/runtime_test.php
Mind Poisoning persistence failed: Full listener history snapshot verification failed.
Mind Poisoning persistence failed: Multiple legacy Player relationship aliases are ambiguous.
Mind Poisoning hook failed: Response is not valid JSON.
runtime store checks passed
```

These three log lines are existing injected rollback/rejection fixtures. The runner exits 0. Both changed PHP files pass `php -l`, and `git diff --check -- server/prerequest.php tests/runtime_test.php` passes.

## Limits

Verification used the local in-memory `StoreDb` fixture. No protected server files were changed, and no live database transaction, provider request, client endpoint, package build, or in-game run was performed. This fixture does not establish that client ACK speech is audible text or prove PostgreSQL concurrency behavior.
