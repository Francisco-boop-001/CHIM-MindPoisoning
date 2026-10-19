# Directed intimacy arrangements implementation plan

Goal: automatic NPC self-report recognition and separate scoped state, visible in prompts/diagnostics and optionally consumed by SHARMAT, without changing emotional type/affinity.
Spec: docs/superpowers/specs/2026-10-19-intimacy-arrangements-design.md

## Constraints and interface rulings

Standalone PHP 8.3.35 -n and synthetic fixtures only. No WSL/server/game/provider/real DB access. No core/OStim or companion product edits. Lead writes documentation/ops/review only. Preserve canonical dirty work and existing PR7. User delegated design selection and conditional implementation/publication; no repeated approval for that already authorized sequence.

The spec supersedes early design proposals: plugin namespace, not edge fields; dedicated default-off direct classifier, not multi-owner shared model coordination; no new per-record history ring; map cap64; no seeks/declines ladder or mutual agreement state. Latest accepted ongoing self-report remains until replaced or restored; no arbitrary time expiry, and it is never current willingness. The API separately rejects unavailable/pruned/wrong-scope/source/identity state. One speaker transaction atomically contains arrangement, receipt and snapshot; ordinary listener gossip is a separate existing operation, not a two-owner atomic promise.

API field `state` (not status): active|ended|none|unavailable; `kind` casual|romantic|null. Capability is exactly ChimMindPoisoning\MIND_POISONING_INTIMACY_API_VERSION=1; public function mindPoisoningReadIntimacyArrangement(int $ownerNpcId,string $targetSubject,?StoreDb $store=null):array. Two-argument callers remain the normal path. Constructor/read adapter may only read; no baseline/storage initialization in API. Missing optional arrangement map and omitted model field preserve legacy behavior. Malformed provided extension fails closed with fixed reason; it must not be silently treated as valid evidence. Store producer and consumer agree before fixtures use the API.

## Ownership and acceptance

### 1. MP state, persistence and capability — sharmat_build

Exclusive: server/store.php; NEW server/intimacy.php (bounded record/schema + read API); server/globals.php; server/logging.php; server/dashboard_data.php; server/dashboard_view.php; NEW server/context_pre.php (supported prompt registration); scripts/package.py allowlist only. Tests exclusively named tests/intimacy_state_test.php, tests/intimacy_dashboard_test.php, tests/intimacy_context_test.php. No evaluator/hook file edits. Reuse native writeNpc interface, owner/restore locks, full snapshot and ledger. Add optional arrangements map carried forward in same scope and discarded on new-scope baseline. Dedicated source_kind=intimacy selects opinion_owner_id=speaker and revalidates exact original source roles. Direct arrangement-only judgments may use delta0 and no type change; do not create/change core edge when arrangement alone changes.

- [ ] Establish RED against actual store/API for same scope/restore, owner mismatch, replay/order, null output, cap, write failure/rollback/uncertain commit and disabled baseline.
- [ ] Implement narrow shared map/transition/record helpers; agree signatures with evaluation owner first. No new StoreDb required methods or parameter changes.
- [ ] Add direct source owner path without weakening ordinary ACK/reflection checks, preserve missing/empty relationship compatibility and Player alias rules.
- [ ] Add fixed safe summaries/history/UI and supported actor-owned prompt injection (max8), no raw source text or write endpoints.
- [ ] GREEN isolated production-function fixtures, changed syntax, report exact inputs/substitutions/results and helpers/API to other owners.

### 2. MP recognition and source correlation — sharmat_tests

Exclusive: NEW server/intimacy_request.php; server/prerequest.php; server/influence.php; server/reflection.php; server/controls.php only if needed. Tests exclusively named tests/intimacy_request_test.php and tests/intimacy_reflection_test.php. No store, pureAPI, globals, logging, dashboard or packaging edits.

- [ ] RED actual directclassifier/source preflight with exact NPC target, literal/verified sourcePlayer route, ambiguous same-nameNPC rejection, sentinel/broadcast/Player-speaker rejection, null/current/ended/proposal/quoted/gossip model cases and replay-before-model.
- [ ] Implement global chimGetGeneralSettingBool opt-in mind_poisoning_intimacy_enabled=false, one extra existing-connector request per validated addressed source, strict fixed-target parser and fixed source metadata; model outside lock.
- [ ] Trigger speaker-owned path before ordinary ACK early returns while preserving ordinary flow/status. Don't echo; logging failure must not corrupt ACK.
- [ ] Piggyback registered reflection optional arrangement_change with status/evidence in existing per-subject judgment, one call/transaction and v1/v2 guards unchanged. No-subject/no-second-call unchanged.
- [ ] Coordinate exact event/judgment schema with state owner; full captured old record check under lock; GREEN and changed syntax with truthful semantic/provider limits.

### 3. SHARMAT consumer — sharmat_cleanup

Exclusive all SHARMAT files in projects/SHARMAT-NonRomantic-Compatibility only: common.php, functions.php, context_pre.php, nsfw_relationship.php, open_mode_policy.php, config_manager.php, config_section_reltypes.php, nsfw_data.php and NEW tests/arrangement_* fixtures. No MP edits. Existing source/test bodies remain unless required for supported fields.

- [ ] RED actual config/read/offer/schema/final filter: optionalAPI missing/old/off/unavailable, activecasual exactpair with negativeaffinity, ineligiblePlayer exclusion, unrelatedpair, endedfallback, currentrefusal/protection/catalog/combat/arousal/marriage constraints, legitimate builtin/custom controls.
- [ ] Add use_intimacy_arrangements=false in existing settings and normalized policy; old-client omission preserves current value. Validate version and API projection. Exact current actor/target IDs and current scope; never use fuzzy/recency targets.
- [ ] New exact-pair arrangement lane may replace ordinary type/liking qualification only when opted in; preserve distinct marriage/affair and all other current restrictions. Explicit ended removes only that lane, not permanent refusal. Re-read before dispatch; do not broadenPlayer viaNPC offer, and keep action-specific snapshots.
- [ ] Casual prompt/automatic-crush preservation independent of new sex-policy toggle. No state writes, type impersonation, reverse-edge inference or mutuality claims.
- [ ] GREEN actual production fixtures, changed syntax, review exact cross-plugin consumer with real MP API and synthetic DB, no new provider/DB/game calls.

## Dependency and review map

| Pair | Shared contract | Rule |
|---|---|---|
|1↔2|arrangement record/transition and source_kind=intimacy owner event|Agree exact helper and judgment/event fields before coding callers; no shared files|
|1↔3|public version1 API/result plus source/scope freshness|Spec signature/closed projection fixed; tests use actual MP readAPI for final integration|
|2↔3|speaker self-report semantics and ended fallback|No shared producer code or cross-plugin writes|
|all|defaultoff/cost/restore/tone|Document exactly, no successful gameplay claim|

After each owner GREEN, lead reviews diffs/callers/failure paths and actual outputs, returns defects to same owner. Reuse completed checks with unchanged inputs. Final review exchanges independent owner scopes after implementation, not before their code is frozen. Release docs/version work follows verified product acceptance; root handles exact commits, reproducible packages, clean-tag and public-byte verification and optional hub update. No unverified publishing to make progress. Preserve earlier tags/assets.
