# Mind Poisoning project instructions

## Protected runtime

- Never execute commands in the gaming WSL distro `DwemerAI4Skyrim3`, including PHP, read-only inspection, or tests. Booting that distro can start services and write its VHD even when a fixture uses isolated data.
- Never use an unspecified/default WSL distro. For PHP, use a separate PHP installation or explicitly target the existing disposable `DwemerAI4Skyrim3-test` clone.
- Before using the test clone, follow `../CHIM-PrivateConversation-dev/docs/live-server-testing.md`. Check distro states through Windows WSL management commands without entering the gaming distro. Do not run the clone alongside the gaming server: WSL2 instances share networking.
- Do not create/export a clone from the gaming distro, start CHIM services, invoke `/etc/start_env`, install plugins, or call live providers/databases for isolated fixture checks.
- Preserve installed CHIM, the modlist, Private Conversation code, other contributors' changes, and historical release assets. Use scoped tests and report their actual evidence limits.

## Ownership and review

Product changes are delegated to the responsible owner. Review the affected callers, failure paths, diff, and verification output before declaring completion. New source changes do not advance a published version or pin without a separate release gate.
