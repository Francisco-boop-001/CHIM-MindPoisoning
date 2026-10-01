# Private Conversation reflection timing follow-up

## Scope

This follow-up covers the embedded Private Conversation reflection hooks and their optional Mind Poisoning API handshake. It does not edit standalone `CHIM-PrivateConversation`, native CHIM, packages, metadata, installed files, or live data. The prior logging revision and observer/importer work were preserved.

## Findings and changes

- Read-only CHIM source inspection shows the output ID/wire are available before `main.php` invokes `prepostrequest.php`; the response flush and semaphore release also happen before that hook. Core `postrequest.php` and extension `postrequest.php` follow it. Registration now runs from `prepostrequest.php`; the postrequest hook no longer repeats registration or emits a duplicate registration-skip event. A same-request late mode mismatch still installs the original solo relationship guard, skips registration, and records one bounded prepost skip.
- Moving registration earlier does **not** eliminate the ACK-first race. A client ACK can arrive before `prepostrequest.php` finishes. If the registry is missing or belongs to another utterance, PCV emits an informational `registration_missing` only after it revalidates an active solo scope and checks a structurally valid ACK from that actor to the Player. Malformed ACKs, other actors/listeners, pair scopes, and inactive scopes stay quiet. The diagnostic contains no raw utterance ID, actor/listener names, or dialogue. There is no pending-ACK queue or recovery guarantee; without a later exact ACK, evaluation does not happen.
- Mind Poisoning compatibility now requires `ChimMindPoisoning\\MIND_POISONING_REFLECTION_API_VERSION === 1` as well as the expected API functions and store class. A versionless/unsupported module returns the fixed `reflection_api_incompatible` diagnostic before store construction or provider work. Existing Mind Poisoning authorization and generic UUID acceptance remain unchanged.
- Output remains bound to the final complete `DEBUG_DATA.OUTPUT_LOG` line and its utterance ID. Earlier chunks are not joined, and `/` or `|` in the subtitle is rejected as `output_malformed` rather than rewritten.
- The unmatched-ACK scope validator validates externally shaped actor IDs without a string cast; an array-valued actor ID fails closed without emitting a PHP warning.

## Red-to-green evidence

The timing fixture first failed before the skip diagnostic was added:

```text
FAIL: A valid ACK before registration must be an informational scoped skip.
```

The caller-flow fixture initially failed while registration was still only in the later hook:

```text
FAIL: The prepostrequest hook did not register the current solo output while preserving the relationship guard.
```

The existing terminal/exception caller fixture also initially failed because its exception case still included `postrequest.php`:

```text
FAIL: registration exception logging was missing or exposed private text.
```

That exception-only subprocess now includes the prepost hook; its separate empty-output postrequest case remains unchanged.

The final focused runs used isolated temporary files and stubbed scope/API state; they did not use a live database or provider:

```powershell
wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/plugins/private_conversation/tests/reflection_hook_timing_check.php
wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/plugins/private_conversation/tests/scope_check.php
wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/plugins/private_conversation/tests/reflection_registry_check.php
wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/plugins/private_conversation/tests/postrequest_terminal_check.php
```

Results:

```text
PCV reflection hook timing and API compatibility checks passed.
scope_check.php: exit 0; six focused PASS lines. The deliberate logger-unavailable fixture also emits its fixed `directory_unavailable` diagnostic.
Mind Poisoning reflection registry checks passed.
PASS: empty-output postrequest is logged as hook observation only
```

The tests also verify that registration is attempted once before core postrequest, the postrequest callback does not register or duplicate the skip while AUTOCHAT remains active, a valid unmatched solo ACK makes no store/provider call, malformed/out-of-scope ACKs stay quiet, the versionless module fails closed, and malformed delimiters/output are rejected. Existing success and replay assertions in the registry fixture remain passing.

All changed PCV PHP sources and test fixtures passed `php -l` under WSL. The final `git diff --check` on the scoped source/test paths exited 0. Focused checks do not establish live CHIM playback, provider behavior, PostgreSQL durability, or that an early ACK is later replayed.

## Remaining limitation

The registration hook runs after output emission and flush. The fix makes a qualifying missing-registration ACK visible and safely skips it; it cannot recover an ACK that arrived first. A durable rendezvous/replay mechanism would require a separate design and authorization.
