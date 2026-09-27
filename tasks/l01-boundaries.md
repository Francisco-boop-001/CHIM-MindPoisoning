# L-01 logging boundary review

Read-only review of the model, influence, hook, and logger paths. No product, test, or user critique files were changed.

## Verified boundaries

### Connector/model request

`requestJudgments()` has eight distinct fixed message strings, but they are not eight equivalent known causes (`server/model.php:37-94`):

- Plugin-owned preconditions: invalid connector ID; connector class unavailable; connector row missing or mismatched; unsupported driver; missing model/URL; missing API key; empty response.
- `Judgment request failed.` is the wrapper for non-`RuntimeException` throwables. The adapter rethrows `RuntimeException` unchanged, so upstream/core errors can also carry arbitrary text internally. The hook discards every thrown message and logs only `model_request_failed` (`server/prerequest.php:350-364`). Preserve that generic fallback for arbitrary exceptions; never map their text or numeric exception codes into logs.
- The invalid-ID adapter branch is normally gated by the hook's earlier connector-enabled and positive-integer checks (`server/prerequest.php:181-189`). The user-visible hook case is `connector-invalid` with no model call. Keep direct adapter validation coverage separate from the ordinary ACK failure-code matrix.

### Judgment validation

`parseJudgments()` has nine distinct fixed rejection messages (`server/influence.php:349-420`): response too large, invalid JSON, top-level shape, item shape, unknown/duplicate subject, invalid delta, invalid reason, invalid evidence, and missing candidate coverage. The critique's five examples omit the size, item-shape, reason, and coverage branches, and combine the two schema levels. These are useful categories if the runtime owner assigns one stable code per rejection; evidence's empty/length/excerpt conditions can remain one code.

Two nearby branches are not ordinary model-output failures in the production hook: empty subjects return before calling the model (`server/prerequest.php:305-337`), and `validatedSubjects()` validates the internally generated subject map. Its `InvalidArgumentException` is currently inside the parser catch, but `findSubjects()` produces canonical tokens and shapes; no reachable bad caller was found. No change to that catch is recommended in this bounded review.

The hook's explicit non-string response branch (`prerequest.php:365-375`) is also not the normal adapter path: `requestJudgments(): string` either returns a string or throws. It remains relevant to the injected model-callable test seam.

## Severity and status contract

- Connector/model-call exceptions: `model_finished` error, request status `failed`, terminal severity error, generic safe `model_request_failed` unless the failure is a trusted explicit plugin condition.
- Invalid returned judgment data: `model_finished` warning. The existing return status remains `failed`, while terminal severity is warning because `RequestLog::finishLevel()` recognizes `model_outcome=invalid` only if the logger is updated for the new codes (currently it recognizes the generic `model_response_invalid` and `judgment_validation_failed`; `server/logging.php:174-189`). **Concrete acceptance point:** new per-cause reasons must not turn the terminal request record into error while the model event remains warning. The runtime owner has been notified and plans a `model_outcome` severity guard.
- Preflight skips such as `connector-invalid`, `no-subjects`, duplicate/floor, and interaction Off stay before paid model work and preserve their existing statuses/reasons. They must not be recategorized as connector/model failures.

## Acceptance cases for the runtime owner

1. Exercise every reachable plugin-owned connector guard through the model adapter and assert its stable safe reason; include the arbitrary connector/provider exception fallback with a secret sentinel and prove the sentinel is absent from every record. Assert model-call failures remain error severity and preserve `model_request_failed` fallback.
2. Assert the hook's invalid connector preflight returns `connector-invalid` (or the existing disabled result when disabled) and calls no model. Do not count the adapter's invalid-ID check as a normal ACK request path.
3. Exercise each chosen parser reason at least once and assert unchanged hook return status, `model_outcome=invalid`, warning-level `model_finished`, and warning-level terminal `request_finished`. Cover oversized response, malformed JSON, both schema levels, bad/duplicate subject, delta, reason, evidence, and incomplete coverage.
4. Keep a valid response and a non-string injected callback case to guard the adapter-versus-test-seam distinction. Do not claim that fixture output proves a live provider's error taxonomy.

No tests were run for this read-only review. Existing runtime fixtures cover generic provider failure, generic invalid JSON, and severity for the two current generic validation codes. During this review the runtime owner began adding direct model-adapter reason-code assertions in `tests/model_test.php`; those in-progress assertions have not been run and do not yet establish per-cause hook records or the proposed terminal severity mapping. No installed code, provider, database, or publication path was exercised.
