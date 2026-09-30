# v0.1.10 live acceptance progress

Session: 2026-09-30 UTC. Status: **PRE-ALPHA; four successful Player-origin cases observed.**

## Environment and evidence

The user reported v0.1.10 on the dashboard and ran the game as Hawke against their main CHIM database, explicitly accepting persistent relationship changes. Dashboard entries identify Shared server scope; no active playthrough profile isolates Skyrim saves. This was not an isolated clean-server installation test.

Evidence consists of user-pasted dashboard records and screenshots of the plugin dashboard and CHIM relationship editor. The assistant also queried Bruce's saved lock and affinity read-only immediately before the locked test: public.core_npc_master id 2905, lock_profile 0, relationships_locked true, affinity toward Lidia Sobieska 12. No raw log export or independent provider trace was captured in this record. The screenshots are supplied in the conversation, not bundled here.

## Observations

| UTC time | Input event | Listener / subject | Result | Corroboration |
| --- | --- | --- | --- | --- |
| 00:37:34 | input_1007000 | Lidia Sobieska / Bruce Wayne | committed; proposed -3, applied -3; 7 to 4 | Dashboard later read 4; refreshed CHIM editor showed 4, Neutral, Platonic. Player remained displayed at 100, Bonded, Romantic. Model 2124.9 ms; persistence 35.3 ms. |
| 00:43:35 | input_1007231 | Bruce Wayne / Lidia Sobieska | committed; proposed +2, applied +2; 10 to 12 | Dashboard and CHIM editor both showed 12, Acquaintance, Platonic. Earlier Lidia-to-Bruce current value remained 4 in the dashboard. Model 4166.5 ms; persistence 27.4 ms. |
| 00:57:45 | input_1007289 | Bruce Wayne / no subject | skipped; no-subjects | User submitted ordinary conversation without a named subject. No judgment values; model/persistence timings unavailable, consistent with the pre-model skip path. No independent whole-database before/after comparison. |
| 01:04:06 | input_1007351 | Bruce Wayne / intended subject Lidia Sobieska | skipped; locked | Lock and affinity 12 directly verified before the line. User's subsequent CHIM screenshot still showed Lidia at 12. No judgment values or model/persistence timings. |

Request IDs supplied: 5518f10a2624aafffad1d2d6 (slander), 0ed5da962beaa27a7760dc22 (praise), 8837fd58fda2eb036988f8ad (no subject), d8f43967c695b22ac2f25db9 (locked).

These observations support live Player-input routing, model evaluation and persisted negative/positive affinity changes for the two named cases, plus the no-subject and relationship-lock gates. They do not prove every relationship remained untouched, provider billing behavior, or future dialogue effects.

## NPC-origin attempt and stop point

The user could not make Bruce deliver the proposed accusation directly to Lidia. They tried Inject and chat as context instead. No matching Bruce-to-Lidia NPC acknowledgement/result was supplied; this does **not** count as an NPC-origin test. Director use was only suggested, not performed or validated. The user ended controlled testing and intends to observe natural gameplay.

Bruce was confirmed locked before the last test. Unlocking and saving was advised afterward, but completion was not confirmed. Do not assume his relationships are unlocked for subsequent gameplay.

## Readiness and remaining checks

Retain PRE-ALPHA. Replace the blanket claim of no live evaluation with this narrower record: two live Player-origin commits, two expected skips, with the evidence above. Historical release-time verification reports remain accurate for when they were written.

Not established live: NPC-origin correlation and evaluation; NPC gossip about Player/Hawke identity resolution; duplicate delivery protection; simultaneous requests; provider/database failures and recovery; in-flight pause/Off transitions; reload/profile restore behavior; long-session stability; clean-server installation. Use an isolated database for deliberate failure/replay/concurrency tests. A disposable Skyrim save alone does not isolate server state.

The dashboard warned its available log tail/record list was capped, so this is not an exhaustive traffic audit. Exporting logs was recommended, but no export was supplied. No code, installed settings, relationship values, release metadata or published assets were changed to document these results.
