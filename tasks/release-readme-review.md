# Release README review: 0.1.12 / 0.1.4

## Verdict

Approved for factual content and tone. I found no blocking documentation defect in the MP root README or the nested Private Conversation README. The copy is informative, playful, and explicit about the PRE-ALPHA boundary.

## Confirmed against the candidate source

- The linked release tags/assets consistently identify Mind Poisoning 0.1.12 and Private Conversation 0.1.4; both manifests carry those versions. The root guide describes one install route per plugin, the MO2/DWPkg/tar layouts, installed-version verification, and catalog status. Local relative links in both READMEs resolve.
- The scene flow distinguishes staging from gameplay: ARM stages settings, an ordinary Standard input triggers generation, and END is staged for the next eligible Standard input. Pair rechat remains CHIM-controlled; solo produces one response and blocks continuation. PCV source implements the excluded-player conversion to `instruction` and preserves ordinary player speech when the player is included (`plugins/private_conversation/server/scope.php`, input preparation and audience snapshot; `plugins/private_conversation/server/preprocessing.php`). A and B remain the selected generated speakers; including the player adds the player as a participant in the audience.
- Both documents correctly separate current audience/nearby-context replacement from retained memories, history, profiles and other prompt contributions. They also exclude physical secrecy, exact earshot, vanilla greetings, Director/early rolemaster handling, and model compliance guarantees.
- Opinion ownership and acknowledgement limits are accurate: A→B about C assigns B’s opinion of C; solo A about C assigns A’s own opinion. Solo requires a registered exact last output and its matching `_speech` acknowledgement; acknowledgement is a line-attempt signal, not proof of audio playback or hearing. Examples are explicitly hypothetical, and zero/negative/positive results are allowed.
- The diagnostic guidance is appropriately bounded: PCV logging is described as CLI-only and omitting raw prompts/dialogue, with IDs and bounded exception metadata possible; MP optional rationale is called out for extra care. The docs do not present source/package checks as live gameplay acceptance.
- Tone meets the requested voice without obscuring the instructions: the jokes are frequent but the install, workflow, safety limits and troubleshooting remain easy to find.

## Non-blocking evidence limit

The candidate tree does not include CHIM core implementation for the in-game **Stop All Dialogue** control. The PCV page and README both identify that as CHIM’s control, while describing END separately as staged; I found no contrary evidence. Its immediate-stop behavior is therefore external to this source-only review rather than independently established here.

## Scope

Read-only review of `projects/CHIM-MindPoisoning/README.md` and the copied `projects/CHIM-MindPoisoning/plugins/private_conversation/README.md`, checked against candidate manifests and PCV request/scope source. No product files or packages were changed; no live CHIM, provider, database or game calls were made.
