# Core implementation report — 2026-10-04

## Scope

Changed only `server/influence.php` and `server/store.php` for core behavior. The shared relationship helpers are in `influence.php`; the `StoreDb` interface and PostgreSQL writer contract are in `store.php`. No tests, package metadata, runtime installs, or commits were changed by this owner.

## Caller and data-shape findings

- Native `PostgresStoreDb::npcById()` decodes `extended_data` in object mode. A stored relationship object and its object edges therefore arrive as `stdClass`; a stored empty JSON array remains `[]` at the relationship-map property.
- `promptNpcCopy()` converts object-shaped NPC context to associative PHP arrays before `relationshipFor()` reads it. The prompt helper must accept those trusted associative arrays while storage adapters reject nonempty PHP arrays at the raw map boundary.
- CHIM loads `lib/relationship_manager.php` from `ENGINE_PATH` in `context_pre`, after the plugin prerequest hook. Player canonicalization therefore loads that exact core helper lazily only when a Player read is needed. NPC-only persistence does not load or invoke it.
- `persistJudgments()` holds the listener owner row with `FOR UPDATE` inside the existing transaction before resolving and writing relationships. The affinity update, optional alias-key deletion, ledger update, and history snapshot remain inside this transaction.

## Implemented contract

- `storedRelationshipMap(mixed): ?array` accepts `stdClass` or exact `[]`; all nonempty PHP arrays, nulls, and scalars fail closed.
- `canonicalRelationshipMap(array, ?string): ?array` is the prompt-capable form. It delegates Player alias choice to CHIM's pinned `RelationshipManager` methods, keeps unrelated map entries untouched, binds any explicit player name (including `''`), and restores the prior `PLAYER_NAME` global in `finally`.
- `canonicalStoredRelationshipMap(array, ?string): ?array` uses the same implementation for raw stored maps, requiring each recognized Player alias edge to be `stdClass` before normalization. This keeps persisted edge-shape validation distinct from prompt-copy associative arrays.
- Both canonical helpers return `null` when the core helper cannot be loaded, required methods are unavailable, a Player edge has an invalid shape, or core normalization throws. `relationshipFor(..., 'Player')` raises `UnexpectedValueException` on a failed required canonical read rather than substituting neutral affinity.
- `StoreDb::writeNpc()` now accepts the optional fifth argument `array $relationshipKeysToRemove = []`. The PostgreSQL writer validates it as a list of unique, nonempty exact string keys, forbids removing canonical `Player`, and applies removals and edge upserts in the same `UPDATE`. Its SQL accepts an object relationship map, a missing map, or exact JSON `[]`; exact `[]` is initialized as `{}`. Other malformed relationship JSON shapes produce no updated row.

## Persistence behavior

When a nonzero Player judgment is applied, the locked current Player prior is selected by the pinned core normalizer. The canonical `Player` edge is updated, and any former alias keys are deleted in the same owner-row update as the new edge, plugin ledger, and timeline. Other relationship entries and `extended_data` properties are preserved. Player-speaker persistence also canonical-reads the locked owner map for revalidation, but does not rewrite Player aliases when the judgments only update NPC subjects. Zero-delta writes do not update or rewrite the relationship map.

## Verification status and failure modes

The test owner captured the pre-change public `persistJudgments()` RED in the approved test clone: empty NPC map and empty Player map returned `invalid`; duplicate Player keys returned `failed`; all three were expected to commit. The test owner owns post-change runtime, fixture, and isolated SQL verification. This core owner did not run the suite or enter WSL.

Expected fail-closed outcomes are: malformed stored map or stored Player edge returns the existing invalid/failed persistence path; a missing/broken core normalizer fails the canonical Player read; malformed native relationship JSON prevents the `UPDATE` from matching; a rejected write returns false so the existing caller rolls the transaction back. No post-change green result is claimed here.
