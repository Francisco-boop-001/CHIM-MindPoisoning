# Mind Poisoning 0.1.29 — verification and limits

## Release status — prepublication

This is the tagged prepublication record. The 0.1.29 tag, clean-tag builds, release assets, and public-download hashes have not yet been verified. Standard GitHub release classification is intended to retain the **BETA CANDIDATE** status while supporting CHIM topic discovery; it is not a maturity promotion. A later documentation-only commit on `main` may add the publication receipt without moving the product tag.

## Focused 0.1.29 change

The only behavior change is prompt-string presentation: an apostrophe in an NPC name remains readable in private arrangement context. Tag, ampersand, double-quote and control-character escaping remains. The retained context fixture covers both a readable `J'zargo` name and a hostile-name control. The source and test snapshot hashes are:

| File | SHA-256 |
| --- | --- |
| `server/intimacy.php` | `eebec2379641b7e4eb7a626b9e4cdd139c15db95421b57ca7bdcb3b1dc65bd60` |
| `tests/intimacy_context_test.php` | `ae40f8985f9a583afd2eaaae4d8ddf9bb9d8be36913298ac175f6643edf572ae` |

The retained focused run passed the intimacy state and context fixtures. Syntax checks passed for the changed source and test. The run used the source/test hashes above and unchanged `server/store.php` SHA-256 `269ba8e71ef78ced97f522fd98b21157c08d997acad3926d2e33bd7a7b4905f8`. This release did not rerun native integration, provider, official-manager, real-model, live-server or gameplay checks. The controlled manager install used a development candidate, not this release asset.

## Unchanged policy and scan evidence

Version 0.1.29 does not change intimacy recognition or child eligibility. Recognition remains off by default. Recognized child signals suppress only arrangement classification or exposure; ordinary affinity, emotional type, gossip and reflection remain available. The guard is limited to known recognized signals, is not universal age verification, and missing optional child metadata is not evidence of adulthood. A metadata query error inside the owned PostgreSQL transaction can still abort the atomic write; this release does not claim ordinary changes survive every metadata failure. A mid-request recognition toggle may cancel a reflection write. SHARMAT's native actor check remains the final scene safeguard.

The optional written-evidence API and identity scan are unchanged from 0.1.28. After `ANALYZE`, a retained synthetic baseline measured one scan over 50,000 marked rows among 200,000 total eventlog rows: 3,125 keyset pages of 16, 813.873 ms summed page-query time, and 1,450.581 ms total elapsed. That result used `server/store.php` SHA-256 `269ba8e71ef78ced97f522fd98b21157c08d997acad3926d2e33bd7a7b4905f8` and `server/written_evidence.php` SHA-256 `ef8afbd9c17f762a0816c7b344153851caeacb998e0967cc031c3f44f87cb843`. This is not a 0.1.29 performance change or workload guarantee: the pre-`ANALYZE` run reached the three-second budget at 20,000 markers after 1,536 rows, and no `EXPLAIN` was captured, so no production query-plan claim is supported. The cooperative three-second budget is checked between synchronous reads and cannot interrupt a query already in flight.

The MAIN restore barrier uses its helper's three-second wait setting. Polling granularity means this is not a hard cap on total request time. No new table or migration is included.

## Limits

Mind Poisoning remains a **BETA CANDIDATE**. Focused prompt formatting and syntax checks do not prove real-model interpretation, installed behavior, message delivery or reading, CHIM synchronization, discovery, or gameplay. The written-evidence API requires a compatible server-side producer, which this package does not include. See the [0.1.28 verification summary](verification-v0.1.28.md) for the prior release's retained deterministic and native checks; those checks were not rerun for 0.1.29.
