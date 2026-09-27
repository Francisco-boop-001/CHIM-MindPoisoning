# L-01 documentation correction

- Replaced `evidence-bound` manifest/catalog wording with identity-checked client-reported speech; this avoids implying that substring excerpt checks establish a claim's truth.
- Clarified in the developer guide that excerpt matching only checks presence in the client report, not semantic truth or audio playback.
- Recorded that safe failure-cause reporting is an unreleased local follow-up. Published v0.1.2 behavior and version/tag/catalog pins are unchanged; failure-code names are deferred until the runtime contract is frozen.

Checks: manifest and catalog JSON parse; targeted assertions verify candidate version, compatibility reference, and unchanged v0.1.2 tag pins; `git diff --check` passes. No runtime tests or package builds were run for this documentation-only change.
