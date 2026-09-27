# Lessons

- CHIM integration fixtures must represent the actual helper contract, including passive headers and interaction generations. A mocked global On/Off flag cannot establish callback eligibility.
- Keep event correlation separate from text representation: when CHIM logs context text but acknowledges client speech, bind the source by exact event/actors and make explicit which reported text drives subjects and evidence. Never infer delivery of a logged-only tail.

- Public READMEs must address prospective users first: purpose, a concrete example, current status, installation, requirements and support. Keep release procedures, verification ledgers, local machine paths and database internals in developer documentation. Use Francisco's dry, humorous, sarcastic voice in introductory copy while keeping instructions and limitations precise.

- For a server-only CHIM plugin, inspect the user's intended Plugin Manager/catalog workflow before selecting a distribution artifact. The schema-4 `.dwpkg` sync path and repository tarball installer are different consumers; verify the selected consumer's archive layout and UI rather than describing a generic upload step.
- A repository hosting multiple plugins needs per-plugin manifest and release URLs. Do not use the repository-wide latest release as an implicit version selector for an individual plugin.
