# Private Conversation v0.1.6 — PRE-ALPHA candidate

Version 0.1.6 tightens the optional solo-reflection link with Mind Poisoning and keeps the logging revision 2 feature set published in 0.1.5. It moves deployment to the dedicated [CHIM-PrivateConversation repository](https://github.com/Francisco-boop-001/CHIM-PrivateConversation).

## Reflection integration

- PCV checks `\ChimMindPoisoning\MIND_POISONING_REFLECTION_API_VERSION === 1` before model or database work. A missing or unsupported version fails closed with `reflection_api_incompatible`.
- Registration runs from `prepostrequest.php` after CHIM flushes output and releases request semaphores, before core and extension `postrequest.php`. The client ACK can still arrive first.
- A structurally valid ACK from the active solo actor to the Player without a matching registration is recorded as informational `registration_missing` only when PCV can revalidate the current solo scene. Unrelated, malformed, inactive, and pair-path ACKs stay quiet.
- There is no ACK queue or recovery guarantee. If the exact ACK does not arrive after registration completes, no opinion evaluation is performed.
- The output parser accepts the final complete `DEBUG_DATA.OUTPUT_LOG` line paired with its utterance ID. It does not join audio chunks; `/` and `|` are rejected as wire separators.

The Mind Poisoning observer is optional. If supported, PCV imports accepted sanitized reflection records while Mind Poisoning continues normal evaluation and sink delivery. The revision 2 importer and protected Logs view were already released in PCV 0.1.5; 0.1.6 does not introduce them.

## Install and evidence

PCV 0.1.4 and 0.1.5 manifests point to literal old-hub assets and cannot update themselves to this repository. Replace the package once using manual/file-sync 0.1.6 or an explicitly configured channel for `Francisco-boop-001/CHIM-PrivateConversation`, then verify the installed version and repository identity. See the [migration guide](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/blob/mind_poisoning-v0.1.14/docs/deployment-migration.md) and [logging guide](https://github.com/Francisco-boop-001/CHIM-PrivateConversation/blob/private_conversation-v0.1.6/docs/logging-revision-2.md); preserve historical assets.

The isolated registry/API and cross-boundary fixtures use local test seams. They do not establish live provider/database behavior, installed extension order, or audible/gameplay delivery. PCV remains **PRE-ALPHA**, and the official CHIM catalog entry has not been submitted or approved. Verify release checksums and use one server-package route at a time.
