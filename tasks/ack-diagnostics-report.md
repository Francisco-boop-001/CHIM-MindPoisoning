# ACK rejection diagnostics

Malformed `_speech` payloads retain their existing hook return statuses: invalid payloads return `invalid-payload`, while a non-string or over-16,384-byte raw body returns `oversized`. The request summary remains `skipped` and warning-level. Rejection reasons now use fixed codes for invalid JSON/root, missing or mistyped fields, field size/emptiness/UTF-8, and missing/mistyped/invalid `utterance_id`. The ID cases are distinct from other required fields. No raw payload, speech, speaker/listener name, or malformed ID is logged.

The runtime fixture verifies every reason returns before model work or persistence, leaves affinity/history unchanged, and does not leak a payload sentinel. Existing valid-route tests in the same fixture remain green. Logging's fixed warning set includes the new codes so these records keep their former severity.

## Verification

RED before the parser change:

```text
wsl.exe -d DwemerAI4Skyrim3 -- php /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning/tests/runtime_test.php
Mind Poisoning persistence failed at snapshot-verification-failed.
Mind Poisoning persistence failed at player-alias-ambiguous.
PHP Fatal error: ... invalid-json should log a fixed safe reason code.
expected: 'payload_json_invalid'
actual: 'invalid-payload'
exit 1
```

GREEN after the parser, safe warning classification, and focused fixture changes:

```text
wsl.exe -d DwemerAI4Skyrim3 -- bash -lc 'cd /mnt/k/ActorwrightExchange/projects/CHIM-MindPoisoning && php -l server/prerequest.php && php -l server/logging.php && php tests/runtime_test.php'
No syntax errors detected in server/prerequest.php
No syntax errors detected in server/logging.php
Mind Poisoning persistence failed at snapshot-verification-failed.
Mind Poisoning persistence failed at player-alias-ambiguous.
runtime store checks passed
exit 0
```

The two persistence lines are expected injected failure cases in the fixture. No installed server, database, or provider was used.

## Independent frozen-diff review

Reviewed `dist/audit-hardening-review/ack.diff` and the unchanged `handleSpeechAck()` → `evaluateInfluenceRequest()` → `finishInfluenceRequest()` path. No defect found. The new reasons are fixed constants that fit `sanitizeCode()`; they carry no payload fields. Existing status mapping remains unchanged (`invalid-payload` / `oversized` → skipped warning), and valid payloads still flow through the same trim, identity, exact utterance-ID correlation, model, and persistence path after parsing. The focused cases cover each new rejection, no model call, no affinity/history mutation, warning severity, and absence of the speech sentinel from logs. The recorded test output was reviewed but not rerun.
