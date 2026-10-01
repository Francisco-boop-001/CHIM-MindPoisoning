# Immutable source export verification — 2026-09-30

## Scope

Ran focused fixture checks against the Git archive export at
`/mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/dist/x-critique-fixes-2026-09-30/commit-source`
using WSL distro `DwemerAI4Skyrim3` and PHP 8.2.29. The parent task identified
the export as commit `80357f7`. No source files were edited. Fixture writes were
limited to the tests' isolated `/tmp` paths.

## Focused fixtures

Invocation (each `php` command was followed by a `printf` of its exit status):

```sh
wsl.exe -d DwemerAI4Skyrim3 -- bash -lc '
cd /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/dist/x-critique-fixes-2026-09-30/commit-source || exit 90
php tests/reflection_observer_test.php
printf "RESULT reflection_observer_test exit=%d\n" "$?"
php plugins/private_conversation/tests/reflection_hook_timing_check.php
printf "RESULT reflection_hook_timing_check exit=%d\n" "$?"
php plugins/private_conversation/tests/scope_check.php
printf "RESULT scope_check exit=%d\n" "$?"
php plugins/private_conversation/tests/reflection_registry_check.php
printf "RESULT reflection_registry_check exit=%d\n" "$?"
php plugins/private_conversation/tests/log_check.php
printf "RESULT log_check exit=%d\n" "$?"
php plugins/private_conversation/tests/postrequest_terminal_check.php
printf "RESULT postrequest_terminal_check exit=%d\n" "$?"
'
```

All six commands exited `0`:

- Reflection observer/importer: 5 cases passed (committed delta, zero change, provider failure, unconfirmed commit, warning skip).
- PCV reflection hook timing and API compatibility checks passed.
- `scope_check.php` passed its actor, audience, presence, routing, and failure-boundary assertions.
- PCV reflection registry checks passed.
- `log_check.php` passed schema/redaction, IDs, debug window, rotation, permissions, concurrent JSONL, unsafe-path rejection, and lock fallback.
- `postrequest_terminal_check.php` passed the empty-output hook-observation assertion.

## PHP syntax

Ran `php -l` for every PHP path changed in commit `80357f7`, from the export
root; the chained command exited `0` and each path reported `No syntax errors
detected`:

```sh
wsl.exe -d DwemerAI4Skyrim3 -- bash -lc '
cd /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/dist/x-critique-fixes-2026-09-30/commit-source && pwd && php -l server/reflection.php && php -l tests/reflection_observer_test.php && php -l tests/manifest_update_check.php && php -l plugins/private_conversation/server/diagnostics.php && php -l plugins/private_conversation/server/index.php && php -l plugins/private_conversation/server/log.php && php -l plugins/private_conversation/server/log_reader.php && php -l plugins/private_conversation/server/postrequest.php && php -l plugins/private_conversation/server/prepostrequest.php && php -l plugins/private_conversation/server/preprocessing.php && php -l plugins/private_conversation/server/reflection.php && php -l plugins/private_conversation/server/scope.php && php -l plugins/private_conversation/server/state.php && php -l plugins/private_conversation/tests/background_presence_check.php && php -l plugins/private_conversation/tests/diagnostics_check.php && php -l plugins/private_conversation/tests/log_check.php && php -l plugins/private_conversation/tests/postrequest_terminal_check.php && php -l plugins/private_conversation/tests/reflection_hook_timing_check.php && php -l plugins/private_conversation/tests/reflection_registry_check.php && php -l plugins/private_conversation/tests/scope_check.php && php -l plugins/private_conversation/tests/ui_check.php && php -l plugins/private_conversation/tests/ui_diagnostics_http_check.php
'
```

## Limits

These checks verify the archived source and its synthetic fixtures only. They
do not establish live CHIM database/provider behavior, installed-server
compatibility, or in-game behavior.
