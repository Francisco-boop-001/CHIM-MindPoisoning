# Influence evaluation report

## Files

- server/influence.php — pure subject resolution, fast-request messages, and strict judgment validation.
- tests/influence_test.php — framework-free outcome checks.
- tasks/influence-report.md — this evidence record.

## Existing relationship format

The installed /var/www/html/HerikaServer/lib/relationship_manager.php was read-only: normalizeTargetName (line 186) canonicalizes player aliases to Player; normalizeRelationshipMap (line 277) reads extended_data['relationships']; getRelationship (line 687) returns aff/type, defaulting to affinity 0 and type neutral. The module sends only these prior-view fields and never returns relationship-type edits.

## Red before implementation

Command:

    wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/influence_test.php

Exit code: 1.

Output:

    PHP Warning:  require(/mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/../server/influence.php): Failed to open stream: No such file or directory in /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/influence_test.php on line 4
    PHP Fatal error:  Uncaught Error: Failed opening required '/mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/../server/influence.php' (include_path='.:/usr/share/php') in /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/influence_test.php:4
    Stack trace:
    #0 {main}
      thrown in /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/influence_test.php on line 4

## Green after implementation

Command:

    wsl.exe -d DwemerAI4Skyrim3 -- php -l /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/server/influence.php

Exit code: 0. Output:

    No syntax errors detected in /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/server/influence.php

Command:

    wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/influence_test.php

Exit code: 0. Output:

    influence checks passed

The runner checks Player aliases and canonical name, Unicode boundaries, substring false positives, ambiguous duplicate names, overlapping names, self/listener exclusion, injection-supplied bogus targets, encoded untrusted prompt data, prior relationship context, bounded personality, positive/negative/zero judgments for multiple candidates, and malformed, invalid, duplicate, omitted, and oversized judgments.

## Legacy actual-player-name context

Player candidate context now also checks an optional event `player_name` against relationship keys when resolving the listener's prior relation and the speaker's bias. The regression fixture removes the canonical Player/Dragonborn aliases and supplies only `Dovah` edges, then verifies the prompt carries the configured listener affinity and speaker bias.

Commands:

    wsl.exe -d DwemerAI4Skyrim3 -- php -l /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/server/influence.php
    wsl.exe -d DwemerAI4Skyrim3 -- php -l /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/influence_test.php
    wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/influence_test.php

All three exited 0. The two lint commands reported no syntax errors; the fixture printed `influence checks passed`.

## Limits

Checks ran with PHP 8.2.29 against project files in WSL. No provider, database, installed-server write, or F: write was performed. These checks cover pure evaluation only; runtime hook and persistence behavior remain outside this task.
