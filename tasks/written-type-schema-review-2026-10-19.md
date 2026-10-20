# Written type-change schema review — 2026-10-19, unpublished dev3

## Defect and bounded contract
Retained hostile responseordinal17 proposed a string `type_change: betrayed`. Match by request shape and candidate, not equal ordinal: actual MP requestordinal18 supplied npc:940201/current_typeplatonic and an allowlist containingbetrayed. Its written system prompt mentioned optional types but did not explain the required non-null from/to object; the JSON example showed onlynull. The existing parser correctly failed closed with judgment_type_change_invalid. Coordinator reports source56 has the same class of failure; original sources and model failures remain retained.

## Change and review
Owner changes only the written prompt and existing focused test. Clarify null or an object with exactly string fieldsfrom/to; from matches the candidate current_type; to is from allowed_relationship_types; missing context requiresnull. Bare strings/lists are forbidden. The prior preserve-custom-types instruction remains. The lead removed an unrelated duplicate protected-type policy enumeration from the draft. No parser, persistence, receipt, typepreservation helper or domain policy was changed; no desired delta, coercion or retry mechanism was added.

The complete path remains source validation → exact candidate map/current type snapshot → configured model outside locks → strict parseJudgments → ordinary bounded atomic recipient-affinity/type/receipt/history write. Malformed output still returns failure before persistence. Valid object proposals are subject to the existing current-type and custom/protected-type checks when saving.

## Verification
Owner RED before wording change: prompt-contract assertion fails on missing exactfrom/to object instruction. Owner and independent lead GREEN: written_evidence_test.php passes128checks, exit0; both changed PHP files lint; diffcheck passes. New checks include valid builtin object parsing, bare type string refusal, missing-context guidance and genuine custom-type preservation. The pinned RelationshipManager fixture loads only after earlier no-helper/evaluator cases. Its helpers are actual retained core source at cf5030f15781637498be86debe26fcf102f5690d, not a replacement policy stub. These are synthetic store/model checks and do not prove a configured model will obey.

Windows command:
`K:/ActorwrightExchange/artifacts/php-8.3.35-windows-cli/runtime/php.exe -n -d extension=K:/ActorwrightExchange/artifacts/php-8.3.35-windows-cli/runtime/ext/php_mbstring.dll tests/written_evidence_test.php`

Root Linux frozen-fixture command:
`php -n -d extension=mbstring /mnt/k/ActorwrightExchange/artifacts/mp-written-type-schema-v3-20261019/candidate/tests/written_evidence_test.php`

## Freeze and limits
Source freeze: artifacts/mp-written-type-schema-v3-20261019. Writer SHA9017b3485b29810e4f9d48f096d54317df85c89ee8e4f46c1ace5f49977d8915; testSHAf09560f2173b555b26dc35de62941e1261185f8795fe4ce9eab08d4fa6007b02; helperSHA c68d8beb88d08c984846ff680d858116eb4c6735e5f1d7ae9ad44b094815dc56; candidate-manifestSHA70a4bf98eb8374a5d2568b32de6ae691d17a5e86448b0a87f3d86fee6db31587. All23other runtime files match dev2. Testhelper dependency is frozen beside the test and excluded from distribution payload. Coordinator exclusively owns any native or configured-model reruns. Publicpin0.1.27, originaldev1/dev2 artifacts and publicassets unchanged. No owner provider, WSL, database, install or live/game actions.

Affection evidence bookkeeping correction: coordinator reports two history rows after MP and S&S complete (one MP affinitycommit20→21 plus the S&S ledger/history write), not one total history row. Attribution must identify the MP change rather than assume all history snapshots belong to it. Gratitude zero remains contrary evidence for a nonzero-gratitude effect, not grounds to force a bonus.
Final package review: artifacts/mp-written-type-schema-dwpkg-v3-20261019/staging/dist/mind_poisoning-0.1.27-written-subject.dev.3.dwpkg SHA983134db0397e84472b70569a552ce8e00407146f848feb60e4ac8e0d3449fd2; owner and lead independently confirm26members,24runtimefiles,CRC/allchecksums, matchinginnerouterdev3 andonlystagedmanifestversion/description deltas. Test and required pinnedhelper are staged outsidepackage. ExactinstalledfilemanifestSHA5fd8c599798e7a1515df41cba8e6c972ec7366209d713afee72ade1ed2cc0a31;receiptSHA9db288ad84615910cda5ae89d8405c77843f114bab07ed029725ed26a3a141c3. Handoff and rootLinuxcommand delivered. No ownerruntime/provider/install/publication actions; publicpin0.1.27 and olderfrozenartifacts unchanged. Configuredmodel reruns remain root-owned and unverified by this package gate.
