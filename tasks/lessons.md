# Lessons

- For a server-only CHIM plugin, inspect the user's intended Plugin Manager/catalog workflow before selecting a distribution artifact. The schema-4 `.dwpkg` sync path and repository tarball installer are different consumers; verify the selected consumer's archive layout and UI rather than describing a generic upload step.
- A repository hosting multiple plugins needs per-plugin manifest and release URLs. Do not use the repository-wide latest release as an implicit version selector for an individual plugin.
