# Written-subject prompt correction - unpublished

## Confirmed evidence and contract
The retained synthetic F1 configured-model response (ordinal 3, google/gemma-4-31b-it) returned composite subject `player or npc:930101`; its only candidate key was `npc:930101`. The published written prompt taught `player or npc:<id>` inside the JSON example. The unchanged parser rejected the response as `judgment_subject_invalid`, without an MP processing receipt. That is evidence of ambiguous prompt guidance, not proof that future provider responses will comply.

## Repair and review
The owner changed only buildWrittenEvidenceMessages output guidance/example and the existing focused test. The example now contains one actual supplied candidate key and explicitly requires exact map keys. No parser, persistence, receipt, model configuration, controls or public API changes. The evaluator still calls the model outside locks and parses against the original candidate map before saving. JSON encoding uses the existing throwing encoder; failures remain caught by the evaluator. Empty candidates produce an empty example, while ordinary evaluation skips model work for an empty candidate map.

## Verification
Owner RED, before the source edit: standalone Windows PHP fixture exited 1 with `The player-only prompt must require an exact supplied candidate key.` Owner GREEN and independent lead execution: `written evidence evaluator tests passed (103 checks)`, exit 0. Both changed PHP files pass `php.exe -n -l`, exit 0. `git diff --check` passes. Assertions cover Player-only, NPC-only and mixed maps and strict rejection of the captured composite token. These are synthetic prompt-contract/parser/evaluator checks, not a real-provider rerun.

Commands (repository working directory):
`K:/ActorwrightExchange/artifacts/php-8.3.35-windows-cli/runtime/php.exe -n -d extension=K:/ActorwrightExchange/artifacts/php-8.3.35-windows-cli/runtime/ext/php_mbstring.dll tests/written_evidence_test.php`
`K:/ActorwrightExchange/artifacts/php-8.3.35-windows-cli/runtime/php.exe -n -l server/written_evidence.php`
`K:/ActorwrightExchange/artifacts/php-8.3.35-windows-cli/runtime/php.exe -n -l tests/written_evidence_test.php`

## Frozen candidate and limits
candidate/server contains all 24 packaging-allowlisted runtime files. Exactly written_evidence.php differs byte-for-byte from base HEAD; all 23 others, including manifest.json, are unchanged. This is a distinct UNPUBLISHED test candidate retaining public version metadata, not the exact published payload. Apply only to the root coordinator's isolated stage and record its candidate hash. No release/version/tag/pin changes, commit, installation, provider call, WSL, live-server or game-data access occurred here. The original F1 remains a retained failure; root owns any authorized configured-model rerun.

Three similar legacy samples in influence.php remain outside this narrowly requested written-source F1 correction. They are a follow-up prompt risk, not evidence that those paths failed in this run. No fallback/retry or permissive subject parsing was added.

## Coordinator configured-model follow-up
After this candidate freeze, the coordinator ran the existing F1 source (event11) once with the corrected overlay. Read-only review of artifacts/chim-correspondence-offscreen-20261019/model-eval/F1-mp-fixed-run.txt confirms evaluation and durable status both processed/receipt_confirmed/zero_change. The original malformed-subject response remains retained. This supports this one corrected configured-model case, not a general reliability or gameplay claim. No provider was invoked by the MP owner.

## F3 neutral-evidence follow-up — unpublished v2
Confirmed retained response ordinal10 has a mentioned sender, delta0 and empty evidence; the strict parser correctly returned judgment_evidence_invalid. The staged MP source hash matched reviewed v1 (03ac5577...). The current saved actual-requests snapshot contained only ordinals1-4, so the lead does not claim independent inspection of the ordinal10 outbound prompt; the coordinator reports its source and the retained installed file confirms the v1 builder.

The owner clarified one existing prompt sentence: every judgment requires a short non-empty/non-whitespace exact original-body excerpt, including delta zero and subject_mentioned false. This aligns guidance with the unchanged parser. It does not establish actual authorship or invent evidence. Owner RED before source change failed the prompt assertion; owner and independent lead GREEN passed109checks, bothPHPsyntaxchecks anddiffcheck passed. Added parser cases keep empty/whitespace neutral evidence invalid and accept an exact nonempty neutral excerpt. No validation, persistence, config or retry changes.

Separate v2 freeze: artifacts/mp-written-evidence-prompt-v2-20261019; writerSHA0f86ca951b9646c90fae02aa1045a01a50ca55b5f24d2f3b28e5896e34a879dd; candidate-manifestSHA6d3b9828a03a97fee9f4d9c5e114e62ff82ac19fa91fb6b889b57cdb3e8bdc81. All23other runtime files match v1. Original F3 and v1 snapshot/dev.1 package remain preserved. Root owns any real configured-model rerun; no such success is claimed by these fixtures.

V2 package review: separate artifacts/mp-written-evidence-dwpkg-v2-20261019/staging/dist/mind_poisoning-0.1.27-written-subject.dev.2.dwpkg is built and owner/lead verified (SHA2567480e77e9cdcda838eaf789c5119f49b6092673a696995a54a6aa91dbbd3f29d). Both manifests dev.2;26members/24runtimefiles,checksums/CRC valid; onlyversion/description differfromfrozenv2. Exactinstalledfilemanifest andreceipt hashes sentroot. Previousdev.1 andpublicrelease remain unchanged. Noownerinstall/provider/WSLaction;root owns controlledrerun andfinalkitinstallation. Developer version logs unknown withunchangedlogger. Publicreleasepin remains0.1.27.

Coordinator v2 follow-up (retained read-only evidence): model-eval/F3-mp-fixed-v2-run.txt confirms source30 evaluation and durable after-status processed/receipt_confirmed/zero_change. mp-evidence-native-green-v2.txt ends with109checks passed. That Linux execution is the synthetic memory-store fixture, not a database-durability test. Originalordinal10 provider failure is retained. These support the corrected neutral case; no nonzero opinion effect or gameplay success is claimed.
