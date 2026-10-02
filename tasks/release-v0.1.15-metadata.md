# Mind Poisoning 0.1.15 metadata and documentation handoff

## Result

Updated the Mind Poisoning release metadata and current-candidate documentation for 0.1.15. The merge snapshot was `492579ca1c1ce42e0cbceb23a8ef595f4dde62e9` on `work/mind-poisoning`; no commit, tag, build, push, or publication was performed by this metadata task.

## Files changed

- `server/manifest.json`: version `0.1.15`; retained schema version 2, candidate status/channel, repository, and compatibility reference. The description now identifies opt-in single-line and companion-registered full-reply reflection.
- `distribution/plugin_repository_entry.json`: updated only the MP description; preserved its existing entry/channel structure and candidate status.
- `README.md`: moved the MP candidate and package links to 0.1.15, retained the PCV 0.1.8 row, and states that the embedded PCV integration remains v1-only until a separate adoption change.
- `docs/deployment-migration.md`: current MP row now points to 0.1.15. Historical 0.1.14 bridge and source-check explanations remain unchanged.
- `docs/development.md`: current MP package links and current-candidate paragraph now point to 0.1.15. The old-hub 0.1.14 bridge and historical v0.1.14 API evidence remain identified as historical.
- `docs/integration-api.md`: marks the v2 API as included in 0.1.15 while retaining the v1 signature/contract. It states that embedded PCV 0.1.8 checks exactly v1 and requires a separate adoption change for v2.
- `server/README.md` and `server/AGENTS.md`: updated the release capability/published-status wording and preserved the v1 contract and companion boundary.
- `distribution/mind_poisoning-v0.1.15.md`: added PRE-ALPHA release notes with the exact v2 namespace/signature, caps and ledger behavior, callback trust boundary, tagged links to the deployment and integration contract, and stated verification limits.

The release notes describe the reviewed fixes: a reflection ACK accepts the configured Player name or supported Player/Dragonborn listener aliases even when an NPC catalog row shares that name, while foreign NPC names and `explicit_disable_rechat` remain invalid ACK listeners; pair and reflection subject matching share conservative aliases with ambiguity, Player, title, speaker, and listener exclusions and repeat identity matching under the actor lock; and reflection constructs the optional default logger when omitted, attempts a terminal summary when logging is available, and keeps expected skips informational while malformed ACK/source mismatches are warning-level rejections. Existing Player relationship-key alias handling was not presented as a new 0.1.15 fix.

Final review clarification: the under-lock subject/alias check rejects catalog changes only when they invalidate the selected subject or alias. A selected full canonical-name match remains committable when only its short alias becomes ambiguous. The release-note wording was narrowed accordingly; the existing focused fixtures cover both cases and were not rerun for this documentation-only correction.

## Verification

At the same merged HEAD, PowerShell parsed both JSON files and checked these release fields: MP version `0.1.15`, schema `2`, `development_candidate`, default channel `candidate`, and unchanged compatibility reference `cf5030f15781637498be86debe26fcf102f5690d`; the repository entry retained `mind_poisoning`, candidate status/channel, and `main` channel branch. The check also confirmed the v1/v2 API names and v2 parameter types in the release note, both documentation links pin to `mind_poisoning-v0.1.15`, the listed fixes are present, and current docs retain the PCV 0.1.8 v1-only boundary and historical 0.1.14 bridge.

PowerShell parsed the two JSON files and read the release note, API guide, README, and development guide with:

```powershell
$manifest = Get-Content server/manifest.json -Raw | ConvertFrom-Json
$entry = (Get-Content distribution/plugin_repository_entry.json -Raw | ConvertFrom-Json).plugins.'mind-poisoning'
$note = Get-Content distribution/mind_poisoning-v0.1.15.md -Raw
$api = Get-Content docs/integration-api.md -Raw
$readme = Get-Content README.md -Raw
$development = Get-Content docs/development.md -Raw
```

The check asserted the manifest/repository fields, API namespace and parameter types, tag-pinned guide links, reviewed-fix text, current PCV v1-only boundary, current MP 0.1.15 references, and historical bridge reference. It exited 0 with:

```text
PASS: both JSON files parse; MP manifest/channel/schema/status/reference and plugin repository entry channel/schema fields are consistent.
PASS: release note exact v1/v2 API namespace/signature, tag-pinned docs links and reviewed fixes are present; current docs retain PCV 0.1.8 v1-only/adoption boundary and historical MP 0.1.14 bridge.
```

Whitespace/scope command:

```powershell
git diff --check -- README.md distribution/plugin_repository_entry.json docs/deployment-migration.md docs/development.md docs/integration-api.md server/AGENTS.md server/README.md server/manifest.json
$lineNumber = 0
foreach ($line in Get-Content distribution/mind_poisoning-v0.1.15.md) { $lineNumber++; if ($line -match '[ \t]+$') { throw "Trailing whitespace in release note line $lineNumber" } }
```

Exit 0: `PASS: scoped tracked diff and new release note have no whitespace errors.` No product or PCV code was changed for this handoff. The merged-source runtime/reply/observer fixtures were not rerun; their exact commands and results are recorded in [merged-source verification](release-v0.1.15-merged-verification.md).

## Limits and handoff

The tagged URLs in the new release notes are intended to resolve after the release owner creates the tag. This report does not validate archive contents, tag assets, public URLs, live CHIM, PostgreSQL, provider calls, installation, or gameplay. The current embedded PCV 0.1.8 caller still uses v1; installing MP 0.1.15 alone does not enable full-reply reflection. The release owner can now review the scoped metadata/docs and build from the clean committed/tag export.
