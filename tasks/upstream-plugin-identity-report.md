# Draft upstream report: catalogue identity and package-name validation

Prepared for maintainer review; not sent or submitted. Source-only confirmation at installed HerikaServer `cf5030f15781637498be86debe26fcf102f5690d`; no production install reproduction.

## Observed mechanism

`ui/server_plugins.php:368-379` finds the first catalogue entry whose git_repo OR name matches the installed manifest. Two independently named plugins using one repository can therefore resolve to the same entry. The entry supplies update channels, while the installed name remains PACKAGE_NAME in the installer URL (`:463-475`, `:509`).

`ui/server_plugin_installer.php:64-83` trusts a supplied PLUGIN_ID, otherwise uses first name-or-repository match. `:349-361` verifies only that the extracted manifest parses as an array; it does not compare its name with PACKAGE_NAME. `:363-369` removes the target directory and moves the package there. A valid package for a different plugin can replace the selected plugin; duplicate PHP definitions may then break requests reaching the hooks.

## Suggested upstream checks

- Match an exact plugin name/identity before considering repository fallback; reject ambiguous repository-only matches and inconsistent ID/name pairs.
- Before replacing any existing directory, require the staged manifest name to equal the requested installed plugin name. Preserve the existing install on mismatch.
- Check one-entry and both-entry catalogues in both orders, two plugins sharing a repository, conflicting ID/name parameters, and a valid wrong-name archive.

## Author-side mitigation

Independent public deployment repository identities for Mind Poisoning and Private Conversation. Collection and legacy migration URLs remain separate from deployable plugin identity. Existing published tags/assets remain immutable. Catalogue submission is still pending clean-server acceptance and maintainer requirements.
