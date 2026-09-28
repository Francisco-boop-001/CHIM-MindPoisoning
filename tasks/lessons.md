# Lessons

- When a user rejects a UI as generic/AI-like and supplies a visual reference, treat the rejection as a design correction even if prior functionality and accessibility checks passed. Inspect the reference, rebuild the visual hierarchy around its materials and composition, use real integrated artwork when requested, and review desktop/mobile plus every explicit theme before handoff.

- In cross-shell byte checks, count carriage returns with `bytes([13])`; do not infer newline corruption from a shell-escaped `\r` search. Confirm actual byte values before asking owners to normalize files.

- When documenting stored model context, name every isolation boundary enforced by the code: listener, playthrough, and relevant subjects. Check the player guide against the implementation and technical guide.

- CHIM integration fixtures must represent the actual helper contract, including passive headers and interaction generations. A mocked global On/Off flag cannot establish callback eligibility.
- Keep event correlation separate from text representation: when CHIM logs context text but acknowledges client speech, bind the source by exact event/actors and make explicit which reported text drives subjects and evidence. Never infer delivery of a logged-only tail.

- Public READMEs must address prospective users first: purpose, a concrete example, current status, installation, requirements and support. Keep release procedures, verification ledgers, local machine paths and database internals in developer documentation. Use Francisco's dry, humorous, sarcastic voice in introductory copy while keeping instructions and limitations precise.

- For a server-only CHIM plugin, inspect the user's intended Plugin Manager/catalog workflow before selecting a distribution artifact. The schema-4 `.dwpkg` sync path and repository tarball installer are different consumers; verify the selected consumer's archive layout and UI rather than describing a generic upload step.
- A repository hosting multiple plugins needs per-plugin manifest and release URLs. Avoid repository-wide `latest`, and check catalog identity matching before adding a second plugin under the same `git_repo`; tag-specific assets do not fix a first-match-by-repository UI bug.
- Keep CHIM distribution gates separate: top-level `Data/CHIM` CSV imports, server code under `HerikaServer/ext/<plugin>`, the installed plugin manifest's `schema_version: 2` plus nonempty `git_repo` update gate, and this project's outer schema-4 `.dwpkg` envelope are different consumers. Verify the shipped manifest, matching channel fields, and intended installer; a passing archive check does not prove manager-update metadata is present or fixed.
- Resolve protected-repository identifiers with an explicit repository path (`git -C <repo> ...`, plus the required safe-directory setting when applicable). If changing directories fails, stop; never let the command fall through and report the caller checkout's `HEAD` as the protected repository revision.
- Use CHIM's per-plugin `NpcMaster` data methods for isolated NPC values, but do not treat a one-namespace statement as a transaction or history snapshot. Before combining plugin data, relationships, dedupe state, and history, verify same-connection transaction and rollback behavior on the target server.

- Before catalog submission, reread the current official submission checklist and upstream PR template, not just runtime consumer code. Supported custom manifest URLs/channels do not establish catalog-policy compliance. Verify repository-root manifest, required main channel, clean-server installation evidence, and maintainer discussion; keep technical compatibility and maintainer acceptance separate.

- When a PostgreSQL SELECT exposes an ID as text, qualify the underlying numeric column in ORDER BY; an unqualified output alias can silently change cap/tie selection to lexical order. Exercise bounded selection against real synthetic SQL data, including IDs with different digit lengths.
- Verify the actual Windows-to-WSL request source before treating host access as loopback. Preserve the access gate when a localhost route works; never infer safe access from private-range or default-gateway membership.
