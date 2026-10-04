# Relationship compatibility handoff review — 2026-10-04

## Scope and evidence

Read-only assessment of MP a83da0a4d5e342e79aecc411afbbeff96363b7d1. No plugin implementation, PHP execution, database query, distro access, installation or publication. Sworn & Scorned server/store.php was read as integration evidence only. The supplied five empty-array rows and two duplicate-Player rows are the audit author's database observations; they were not independently queried in this task.

Pinned CHIM source was fetched as text from https://raw.githubusercontent.com/Dwemer-Dynamics/HerikaServer/cf5030f15781637498be86debe26fcf102f5690d/lib/relationship_manager.php . The browser fetch failed; the HTTPS PowerShell read succeeded. This verifies that historical source contract, not a newly inspected installed server.

## Findings

1. Empty relationships arrays: valid compatibility defect. prerequest.php:251,945,1164; reflection.php:476; store.php:881; dashboard_data.php:1000 reject the object-mode decoded empty array. Native writeNpc at store.php:1481 preserves the JSON array before the nested string-key jsonb_set at1491; changing PHP alone does not repair SQL. Accept only [] as an empty map, retain rejection of nonempty arrays and malformed values, and normalize the SQL base to an object. A shared narrow normalization boundary would avoid duplicated behavior. Do not broaden extended_data or ledger acceptance.

2. Duplicate Player relationship keys: valid compatibility defect. playerRelationshipKey at store.php:542 rejects duplicates, with pre-model and locked-save callers. This is an intentional old fail-closed policy, but core now supplies a deterministic compatibility rule. CHIM normalizeTargetName:186-213 canonicalizes Player aliases; normalizeRelationshipMap:277-331 picks the greater-weight record, retains the first on equal weight, and preserves custom_info. Weight:369-384 is absolute integer affinity plus type and descriptive-field weights. It is not affinity addition/averaging and does not merge every field from both records. Restrict normalization to Player edges, preserve unrelated edges and metadata, restore both the value and existence of PLAYER_NAME in finally, and recompute from the current owner row while locked. MP already obtains FOR UPDATE (store.php:1433) through the persistence read at746. Its edge-only write interface must support alias removal atomically with history and dedupe; setting Player without removing legacy keys is insufficient.

3. Required sibling consistency: influence.php:259-301 relationshipFor currently stops at the first matching Player alias. The model must see the same canonical edge chosen for persistence, and dashboardCurrent:994 must resolve it consistently. Applying core normalization only at save time leaves an incorrect model prior and an ambiguous dashboard. Preserve the distinction between duplicate relationship keys and genuine ambiguous NPC identity; this handoff does not authorize weakening subject/catalog collision safeguards.

## Corrections to the handoff

The functional conclusions are sound. The claim that empty-array failures always precede model calls is too broad: reflection.php validates this map before the model only when Player is a subject (475). A reflection about another NPC can reach reflection_model_started:544 and then fail during persistence. The general ACK path similarly has a conditional Player-subject check at prerequest.php:1163. Player-input and overhearing preflight check the map earlier. Duplicate aliases can also block Player-origin gossip about a third NPC because that path needs the Player credibility edge (prerequest.php:950).

## Proposed acceptance evidence if implementation is authorized later

- Retain the handoff's four cases: [] with NPC subject, [] with Player subject, two Player keys with expected core-weighted result, and [1] refusal.
- Cover addressed ACK, Player input, reflection and overhearing preflight, prompt context and dashboard readers; ensure malformed nonempty arrays are rejected before any paid request.
- Preserve winning edge metadata/custom_info and unrelated edges; check alias insertion/reordering and a change between preflight and locked persistence.
- Prove locked-save/history/dedupe atomicity and rollback, including alias removal and zero-change behavior, without mislabeling a maintenance normalization as an affinity change.
- Existing runtime_test.php:1352-1361 and player/store logging tests intentionally assert alias refusal and need reviewed replacement expectations. Keep catalog identity-collision tests.
- Use the existing disposable SQL fixture in the authorized test clone for actual jsonb writes. Memory-store checks alone cannot verify PostgreSQL's array-to-object path.

Judgment: fix both before expanding testing. These are bounded platform compatibility repairs supported by source evidence, not speculative modernization. No product code or release pin was changed by this review.