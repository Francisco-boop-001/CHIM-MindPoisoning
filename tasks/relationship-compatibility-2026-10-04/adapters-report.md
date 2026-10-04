# Relationship compatibility adapter review — 2026-10-04

## Scope and source status

This report covers the adapter changes in `server/prerequest.php`, `server/reflection.php`, and `server/dashboard_data.php`, plus the two assigned current-source paragraphs in `docs/development.md` and `docs/mind-poisoning.md`. The behavior described here is in the current unreleased checkout. It is not part of published Mind Poisoning v0.1.17; no version, tag, package, or metadata was changed.

The core helper contract is implemented in `server/influence.php`: `storedRelationshipMap()` accepts a stored `stdClass` or exact empty PHP array `[]`; all other array shapes, explicit nulls, scalars, and malformed roots return null. `canonicalStoredRelationshipMap()` applies the CHIM `RelationshipManager` normalizer to Player aliases at raw stored-data reads, while `canonicalRelationshipMap()` also supports trusted `promptNpcCopy()` arrays. Player-name global state is restored in `finally`; core load/normalization failures return null.

## Adapter paths and changes

- `inspectAckRecipient()` validates the addressed or overheard listener's raw relationship map before selecting that recipient for a model call. When the Player is a subject, it uses the strict stored-map canonicalizer. This covers the addressed and witness preflights in `evaluateOverhearingAck()`.
- `evaluateInfluenceRequest()` validates Player-origin listener maps before dedupe and model work, and canonicalizes the Player edge for the Player speaker even though Player is excluded from that route's subject set. For ordinary NPC ACKs, it validates the opinion owner's map after subject discovery and before model work; Player-subject ACKs also canonicalize the Player edge there.
- `evaluateReflection()` validates the opinion owner's map for every nonempty eligible reflection subject set, including NPC-only reflection, before building the model request. It canonicalizes the Player edge only when Player is a subject.
- `dashboardCurrent()` uses the strict stored-map gate for both current and historical reads. Player reads use the shared CHIM canonical edge. Only the canonical Player edge result is converted from the normalizer's array form to `stdClass`; malformed raw NPC array edges still return `invalid`. A missing map property and exact `[]` read as empty; explicit null remains invalid/unavailable.

The adapter gates use fixed existing failure reasons/states and do not log caught exception text. Raw preflight reads are read-only. Existing lock, profile/scope, event replay, subject/catalog identity, and reflection registration checks remain in their original request order. Missing `relationships` properties default to an empty object; explicit null and nonempty PHP arrays fail the strict gate.

The core persistence review found the lock and transaction order intact: listener advisory lock and transaction, opinion-owner reread with `FOR UPDATE`, actor/scope/catalog revalidation, then stored-map validation and Player canonicalization. A nonzero Player-subject update writes the canonical edge and removes old alias keys in the same guarded relationship update. Player-speaker-only and NPC-only writes do not clean Player aliases; zero Player delta does not trigger cleanup. Listener readback and history snapshot verification occur before commit, and the existing `finally` rolls back on failure.

## Existing boundary

Malformed third-party NPC speaker relationship maps can still yield neutral prompt-only speaker bias through the pre-existing `relationshipFor()` fallback. This task changes the opinion owner's stored map, which is the map read, mutated, and revalidated by the route. The fallback predates this diff and was left unchanged; it is not live-runtime evidence.

## Evidence and remaining verification

- The test owner captured baseline RED through the public persistence seam in the approved isolated test clone (PHP 8.2.29). The reported statuses were `empty NPC map: invalid`, `empty Player map: invalid`, and `duplicate Player keys: failed`, where each case expected `committed`.
- `git diff --check` on the three adapter files completed with exit code 0.
- The integrated GREEN run, the Player 50 → 47 dashboard readback, and isolated SQL/rollback assertions were still pending the test owner when this report was drafted. Do not treat this report as live CHIM, provider, or gameplay proof; append the exact focused-run output and limitations after it arrives.
