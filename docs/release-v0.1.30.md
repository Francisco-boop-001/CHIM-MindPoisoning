# Mind Poisoning 0.1.30 — BETA CANDIDATE

## Twelve fixes and one unusually busy llama

This candidate repairs a dozen reported MP defects across written evidence, reflection storage, overhearing and the read-only dashboard. The llama remains unlicensed to notarize anything.

Written-evidence sender names now use the matching catalog identity, so a case-only spelling difference no longer creates a second relationship edge or bypasses its protected type. Existing noncanonical keys are left untouched; there is no historical relationship merge. A valid addressed listener now keeps its normal evaluation path when optional overhearing candidates are invalid, unavailable or over cap. The four-extra cap is checked against read-only preflight candidates before extra-recipient baselines are written. In preserve mode, this is intentionally all-or-none: even an extra that would later be fenced counts toward the cap.

An optional child-metadata query can fail without aborting the parent reflection transaction: the arrangement lane is refused while ordinary affinity, relationship type and receipt handling remain usable. Reflection history uses byte limits for source and people rows before its row limit. Cleanup evidence is scoped to the operation being reported, unresolved transaction/lock/barrier ownership is retained, and each evaluated recipient gets one final completion record. The connector ID check now follows the adapter's strict integer contract. Fenced `json` intimacy responses are parsed consistently with the existing influence parser.

The written-evidence API accepts a raw UTF-8 JSON envelope up to 65,536 bytes, including JSON escaping and metadata. Its decoded body limits remain 2,000 Unicode code points and 8,000 UTF-8 bytes. The API remains v1; this is a transport bound, not a larger letter allowance.

The read-only dashboard requires a well-formed local `Host` for unauthenticated loopback requests, while preserving authenticated remote access and the existing local handoff. Search checks field values rather than JSON keys and handles literal quotes and backslashes. The bounded log tail keeps a full first record when its cut falls at a line boundary.

Two inherited test fixtures are corrected too: the dashboard integration fixture copies `restore_policy.php` and tolerates rendered whitespace; the reflection observer test no longer expects persistence after a provider failure that never reached persistence. Those are harness repairs, not new product behavior.

## Compatibility and limits

Written-evidence API v1 and reflection APIs v1/v2 keep their current signatures and versions. Private Conversation is unchanged. The Department adapter uses the reader functions directly, but its current 0.1.29 version and seven source-hash pins intentionally do not authorize 0.1.30; its owner must review and re-pin before any deployed Department compatibility claim. This downstream review does not change MP's standalone release status.

Intimacy recognition remains off by default. This release adds no age policy, universal age verification, provider, gameplay feature or claim about real-model accuracy. PCV and SHARMAT are unchanged. Mind Poisoning remains a **BETA CANDIDATE**; standard-release classification supports CHIM discovery without promoting maturity.

## Verification snapshot — prepared before publication

Source and focused disposable-database checks passed. This prepublication snapshot includes no clean-tag package build or release-asset hashes; those checks are recorded in the [verification summary](verification-v0.1.30.md). The immutable tag copy preserves this snapshot. Once publication is verified, the mutable main copy and GitHub release body can carry the final asset receipt.

## Install one route

Before installing, verify the release's `SHA256SUMS.txt` and pick one route per server. A checksum verifies downloaded bytes; it does not prove CHIM synchronized the package.

- **Recommended for MO2:** [plain MO2 ZIP](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.30/mind_poisoning-0.1.30-mo2.zip), with the version-matched DWPkg included. Update with **Replace**, not **Merge**.
- **Existing manifest/channel install:** [server TAR](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.30/mind_poisoning.tar.gz).
- **Advanced manual CHIM sync:** [versioned DWPkg](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.30/mind_poisoning-0.1.30.dwpkg).
- **Experimental topic discovery:** flat [`chim-plugin.tar.gz`](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/releases/download/mind_poisoning-v0.1.30/chim-plugin.tar.gz), for a CHIM manager that supports the route.

Back up the database and configuration before updating. The [player and installation guide](mind-poisoning.md) and [duplicate-install recovery steps](deployment-migration.md#mind-poisoning-duplicate-install-recovery) cover route changes; installing both the MO2 and Plugin Manager copies is not a clever backup strategy.
