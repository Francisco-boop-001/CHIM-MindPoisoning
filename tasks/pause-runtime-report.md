# Operator pause control

## Behavior

The hook reads ENGINE_PATH/data/mind_poisoning.json at preflight and again after model validation, immediately before persistence. The file is operator-managed and outside the extension package. It must be a regular, non-symlink file no larger than 1024 bytes containing exactly one JSON property: {"enabled":true} or {"enabled":false}. Normal JSON whitespace (space, tab, CR, LF) is accepted; the key must use the literal spelling "enabled" rather than an escaped spelling.

An absent data/ directory or absent control file preserves the existing enabled behavior. The root must be an absolute, resolvable CHIM directory containing main.php; a valid data/ symlink to a readable directory is accepted. A dangling/non-directory/unreadable data path, invalid root, non-regular or symlinked control file, malformed JSON, extra/missing properties, non-boolean value, oversized file, or read failure fails closed with status control-invalid and reason pause_control_invalid (warning log).

When enabled is false, the hook returns status plugin-paused and reason plugin_paused. Both NPC ACK and Player input paths stop before model work when paused at preflight. If the file changes from absent/enabled to paused during model work, the second read prevents affinity, history, and ledger writes. Neither control contents nor filesystem paths are logged. Each read clears PHP's stat cache.

This is a sampled gate: a change after the second read can race with persistence. If an atomic rename happens while a file is already open, that read may finish on the prior file; the next gate reads the replacement. The pause control has no UI and has not been exercised against an installed CHIM service.

## Verification

RED before implementation:

    wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/runtime_test.php
    exit 1: pause assertion expected 'plugin-paused', got 'failed'

GREEN after implementation:

    wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/runtime_test.php
    Mind Poisoning persistence failed at snapshot-verification-failed.
    Mind Poisoning persistence failed at player-alias-ambiguous.
    runtime store checks passed
    exit 0

PHP lint passed for server/prerequest.php, server/logging.php, and tests/runtime_test.php (No syntax errors detected for each).

The runtime fixture uses temporary CHIM-shaped roots and an in-memory store. It covers paused NPC and Player preflight, enabled processing, enabled-to-paused and absent-to-paused transitions during model work, malformed and non-file controls, invalid roots, symlinked main/data paths, duplicate enabled keys in both value orders, formatted JSON whitespace, and secret/path exclusion from logs. No installed CHIM, live database, provider, or Skyrim game was used.

## Duplicate-key correction

PHP's JSON decoder keeps only the last value when an object repeats a key. The original object-property check therefore accepted duplicate enabled keys. The bounded control reader now recognizes only the documented fixed object shape, so neither duplicate order can pass.

RED before the correction:

    wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/runtime_test.php
    exit 1: duplicate-key control expected 'control-invalid', got 'committed'

GREEN after the correction:

    wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/runtime_test.php
    Mind Poisoning persistence failed at snapshot-verification-failed.
    Mind Poisoning persistence failed at player-alias-ambiguous.
    runtime store checks passed
    exit 0
