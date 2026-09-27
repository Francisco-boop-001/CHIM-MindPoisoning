# Repository distribution docs report

## Result

Updated the project README to describe the planned shared CHIM plugin home under Francisco-boop-001, with Mind Poisoning first. The repository name remains a placeholder until confirmed. The existing source layout is retained; no plugin moves or empty scaffolds were added.

The Plugin Manager route is documented as the primary distribution path: a public, tag-pinned GitHub prerelease asset named `mind_poisoning.tar.gz`. The catalog example defaults to a `Development candidate` channel and retains `status: development_candidate`. It pins the source manifest and release asset to the same plugin-specific tag. Each new release must advance both URLs together; no `releases/latest` URL is used.

## Catalog and installer evidence

Read-only inspection of `/var/www/html/HerikaServer/ui/data/plugin_repository.json` found a top-level `plugins` map keyed by plugin id. Entries use `name`, `git_repo`, `github_url`, `default_channel`, and named `channels`; channel settings accept `label`, `branch`, `manifest_url`, `package_source`, `package_urls`, `archive_strip_components`, and `allow_force`.

Read-only inspection of `/var/www/html/HerikaServer/ui/server_plugin_installer.php` confirmed that explicit `manifest_url` and `package_urls` are used in preference to generated branch/latest defaults. The installer downloads a configured package URL, extracts tar archives with GNU tar, defaults `archive_strip_components` to 1, and requires `manifest.json` at the extracted package root. The configured explicit release asset avoids an archive or release from another plugin being selected through `releases/latest`.

One inspected multi-plugin catalog limitation remains upstream: `/var/www/html/HerikaServer/ui/server_plugins.php:368-379` returns the first repository-entry match when either `manifestRepo` matches `git_repo` or the entry `name` matches. A later plugin entry sharing this repository can match the first plugin's `git_repo` before reaching its own exact package name. The installer fallback at `/var/www/html/HerikaServer/ui/server_plugin_installer.php:64-83` has a similar repository-or-name fallback, although an explicit `PLUGIN_ID` takes precedence there. The installed UI can supply the wrong id first; CHIM must prioritize exact package identity before a second same-repository catalog entry is offered. Tag-pinned asset URLs do not resolve that identity ambiguity. No upstream code was changed.

The catalog's `status` is informational to the inspected channel normalizer; it is not an install safety gate. The entry is therefore clearly labeled `Development candidate`, uses no stable channel, and leaves the manifest's existing `development_candidate` status intact.

## Publication state and limits

The exact GitHub repository name is pending user confirmation. `REPOSITORY_NAME` must be replaced before the entry is submitted. The root README directs maintainers to publish the source tree, preserving `server/manifest.json`, to the confirmed repository before tagging, and to mark the first GitHub release as a prerelease. The example URLs are not claimed to exist. No live repository, release, authoritative catalog entry, or installed catalog file was created or changed; the local catalog snippet is documentation only. Publication requires a public repository and unauthenticated access to both tag-pinned URLs.

The docs identify `python scripts/package.py --format repository-tar-gz` as the repository archive build command and describe the expected `mind_poisoning/` top-level folder and single-component strip. Per packaging ownership, this task did not build or inspect the final tar archive; package content and tar verification remain with the packaging task. No live provider, database, installed-file write, or publication action was performed.

## Checks

- `python -m json.tool distribution/plugin_repository_entry.json` — passed (`catalog JSON valid`).
- Raw-byte scan of `README.md`, `server/README.md`, `distribution/plugin_repository_entry.json`, and this report — all four contained zero CR bytes, matching `.gitattributes` LF policy.
- `git diff --check` — exited 0 with no whitespace errors.

## Owned files

- `README.md`
- `server/README.md`
- `distribution/plugin_repository_entry.json`
- `tasks/repository-docs-report.md`
