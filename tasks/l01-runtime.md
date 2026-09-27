# L-01 model failure reason evidence

## Root cause and fix

The adapter's known setup/empty-response failures were plain `RuntimeException`s, while the hook replaced every model exception with `model_request_failed`. `parseJudgments()` likewise threw generic `UnexpectedValueException`s for nine distinct response rejection branches, all collapsed to `judgment_validation_failed`. That made logs safe but erased actionable stage detail; the terminal severity also relied on a short list of literal warning reasons.

Added allowlisted `ModelRequestFailure` (`RuntimeException` subclass) and `JudgmentValidationFailure` (`UnexpectedValueException` subclass). The adapter gives stable codes to connector ID/runtime/row/driver/config/key checks and empty/non-string responses. All other adapter throwables use generic `model_request_failed`, with a fixed public message and original cause retained. The parser assigns fixed codes for response size, JSON, top-level schema, judgment schema, subject, delta, reason, evidence, and missing candidates. The hook reads only these plugin-owned typed properties; arbitrary exceptions remain generic. Existing hook statuses and global restoration are unchanged. Invalid model output keeps warning severity by `model_outcome=invalid`, without listing every parser code in the logger.

## Reproduction and verification

Before the implementation, focused red runs showed:

- `tests/model_test.php` exited 1: expected the stable invalid-connector reason, received no reason property.
- `tests/runtime_test.php` exited 1: expected `connector_api_key_missing`, received `model_request_failed` in the request summary.

After the fix, these WSL commands passed:

- `php -l` for `server/model.php`, `server/influence.php`, `server/prerequest.php`, `server/logging.php`, `tests/model_test.php`, and `tests/runtime_test.php` — no syntax errors.
- `php tests/model_test.php` — `model adapter checks passed`; includes the unavailable-connector child process, explicit setup/response reasons, and a provider exception whose text/code imitates the API-key failure.
- `php tests/influence_test.php` — `influence checks passed`; existing parser callers still catch `UnexpectedValueException`.
- `php tests/runtime_test.php` — `runtime store checks passed`; the two persistence failure diagnostics are expected injected fixtures. Table-driven checks assert each of the nine parser reasons in both `model_finished` and `request_finished`, warning severity, unchanged failed status, no affinity mutation, and no history snapshot. Typed connector/empty-response reasons remain errors; spoofed untyped exceptions stay generic and are not logged.
- `php tests/logging_test.php` — `logging checks passed`.
- `git diff --check` — passed; all six changed PHP files contain zero CR bytes.

## Limits

The checks use local PHP fixtures and test doubles. They do not make a provider request or exercise the installed HerikaServer runtime, live PostgreSQL, or in-game behavior. No protected server/mod files, live database, package artifact, release pin, or publication state was changed.
