# Private Conversation v0.1.5 — PRE-ALPHA candidate

Part of **World of Drama-Llama**. Direct two-NPC scenes or one-NPC reflection using CHIM's ordinary Standard path. This candidate adds logging revision 2 and three reviewed diagnostic fixes; source and isolated fixtures are not a live-server or gameplay guarantee.

## Included

- Pair and solo scene direction, existing background-presence feed, exact solo-output registration and correlated `_speech` acknowledgement remain the core behavior. The ACK is a line-attempt signal, not proof that audio played or was heard.
- A standalone **Logs** view runs before scene/CHIM dependency loading. It offers manual filtered reads and sanitized JSONL export through the shared bounded reader; the CLI remains available. Filters are fixed, and results are limited to 1–1000 matches. Normal filter/cap counts are distinct from malformed, oversized, unsupported or capped-record omissions.
- The viewer requires either an exact direct loopback address with no forwarding headers, or a nonempty trusted server-provided `REMOTE_USER`. Submitted reads, exports and client reports also require the session CSRF token. It is not a login system; deployed proxy/authentication topology must be independently trusted.
- Records distinguish routing preparation and terminal outcomes, presence available/known-empty/stale/missing/malformed/unavailable states, exact output registration, acknowledgement skips/errors and evaluation results. Timestamps are UTC. IDs and fixed reasons correlate evidence; they do not authenticate a person or prove generation, playback or hearing.
- Private JSONL storage is bounded to five 10 MiB segments with an 8 KiB entry cap, private directory/file permissions and nonblocking locking. Health reports the current request's write state, storage mode and categorized reader omissions. Failures use bounded fixed-code fallback messages in PHP's configured error log; temporary-directory cleanup, contention or disk failure can still lose history. `PCV_LOG_DIR` supports an administrator-provisioned private location.
- Browser refresh telemetry uses fixed failure codes only. The client suppresses duplicates within a failed episode and re-arms after successful recovery; the server throttles each session/code for 60 seconds. The report excludes free-form error bodies, URLs and stacks. Locked or disconnected browsers may be unable to deliver it.

## Reviewed fixes

- A malformed HTTP-200 refresh snapshot no longer partially replaces the actor selections or advances playthrough identity before rejection. ARM remains disabled until a valid eligibility refresh.
- An existing FIFO at the private logger lock path is rejected before opening, avoiding a request blocked on that path.
- In a fresh `_speech` ACK request, the optional observer is bound to the already validated configuration/event/utterance tuple before its first record is imported. A different event or utterance cannot relabel the acknowledged output.

## Mind Poisoning compatibility

Compatible Mind Poisoning 0.1.12 continues to support optional opinion effects. Published Mind Poisoning 0.1.13 adds the optional sanitized request-local observer that enables bounded unified **solo** model/persistence diagnostics in PCV. The 0.1.13 observer is not required for scene direction or the 0.1.12 effect path. Without it, PCV reports observer-unavailable and detailed outcomes remain in MP's diagnostics. Pair gossip has no verified PCV registration tuple or unified correlation API; inspect MP for those outcomes. Neither plugin changes native relationship ranks or turns allegations into shared world truth.

## Install

Choose one source for the server and **replace older packages; do not stack enabled versions or mix competing sync sources**. The plain MO2 ZIP contains `CHIM/server-plugins/private_conversation/0.1.5.dwpkg`. Manual sync uses `Data/CHIM/server-plugins/private_conversation/0.1.5.dwpkg`. The repository tar is for CHIM repository/Plugin Manager ingestion; strip its single `private_conversation/` wrapper once. Verify the installed version and downloaded assets against `SHA256SUMS.txt`. The [full guide](https://github.com/Francisco-boop-001/CHIM-Plugins/blob/private_conversation-v0.1.5/plugins/private_conversation/README.md) has the version-specific links and route details.

## Limits and evidence

Source and isolated PHP/Node fixtures reviewed the route, redaction, logger/reader bounds, scene/ACK paths and the fixes above. They do not verify installed CHIM, production authentication/proxy setup, live database/provider behavior, native client delivery, playback, hearing or gameplay. Background presence is broader than exact earshot and expires on a bounded observation lease. Retained history, profile or other prompt contributions can still refer to excluded people; there is no physical privacy guarantee. Temporary logs can be cleaned by the OS and diagnostics are best-effort, not tamper-evident or complete.

One narrow breadcrumb gap remains in source review: if the state directory disappears after a valid ACK is checked but before its second claim lock, evaluation fails closed as registration-missing, but the ACK breadcrumb may not be written. This was not established as a gameplay failure and no reflection semantics were changed for it.

The manifest retains schema version 2, the existing config endpoint and server compatibility reference. No new Papyrus/ESP/ESL companion is required. Official CHIM catalog approval and runtime certification are not implied.
