# Mind Poisoning 0.1.31 — Beta Candidate

Skyrim can now award one point of goodwill for a verified gift. I regret to report that the paperwork was the hard part.

- With Sealed Fates 0.1.0-dev.34, Mind Poisoning can grant **+1 affinity** for an original Player gift backed by the matching confirmed receipt when the recipient's judgment is nonnegative. Negative judgments remain possible. The gift does not change the sender's relationship type.
- An unverified or forged attachment claim earns the claimed sender no positive affinity and no type change. Text-only letters and judgments about other subjects keep their existing behavior.
- The gift checkbox is currently available in the **Sealed Fates web composer**. Install or update Mind Poisoning through one route. The MO2 ZIP contains the CHIM package. The other files serve alternate routes. See the [player guide](https://github.com/Francisco-boop-001/CHIM-MindPoisoning/blob/mind_poisoning-v0.1.31/docs/mind-poisoning.md).

**Verification.** A paired disposable PostgreSQL fixture exercised genuine and forged gifts, replay and re-exposure, the text-only control, negative judgments, the affinity cap, restore contention, rollback and retry, and rewind. The fixture used deterministic judgments with zero provider calls. The Windows PHP written-evidence fixture also exercises the +1 outcome through the evaluator. Live installation, provider behavior and gameplay remain unverified.

This standard GitHub release supports CHIM discovery. Mind Poisoning remains a **beta candidate**.