# Mind Poisoning dashboard — UI design contract

**Status:** design only. Wait for the read-data/log-download contract and supported plugin page entry point to freeze before implementation. No core CHIM edits, hosted Sites service, or external UI dependency.

## Visual direction

Treat the page as a field journal or relationship ledger from Skyrim, not a marketing landing page. Use a warm-paper canvas (`#eee7d8`), ink text (`#272b28`), muted spruce for secondary navigation (`#52645a`), and restrained aged brass (`#92794c`) for active rules and small labels. Reserve a muted rust for errors. Establish contrast with color and text labels, never color alone.

Use a quiet serif for the page title and section headings (system Georgia/Cambria), with a familiar system sans-serif for controls and body copy. Use tabular numerals for affinity and timing. Fine rules, compact metadata, generous row spacing, and occasional paper-toned bands supply character. No gradients, floating card grid, oversized hero, emoji, decorative game assets, or promotional copy. No remote fonts or images.

## Page structure

- A compact masthead names **Mind Poisoning** and reports only data-source state the server actually returned. Do not imply a live connection if the read failed.
- Two navigable sections, **Interactions** and **Diagnostics**, are the only primary tabs. Use ordinary links or a native accessible tab pattern after route behavior is known; both must work without JavaScript.
- Filters are compact GET forms whose selected values remain in the URL. On narrow screens, stack them above the results rather than shrinking labels or values.
- The information layout is a ruled ledger, not repeated cards. Desktop uses labeled columns; mobile turns each row into a compact definition list with labels attached to every value.

### Interactions

Show a chronological, filterable list grouped by day. Each event can expand to its subject judgments. Use only values returned by the frozen data contract; the visual hierarchy keeps three distinct affinity facts side by side:

- **Proposed:** the validated model delta for a named subject. This is a suggestion, not a verified claim.
- **Applied:** the net before-to-after change only when persistence confirms a commit. Label unconfirmed commits as **Unconfirmed**; show rejected, skipped, or failed outcomes without suggesting that an update happened.
- **Current:** the affinity read at page-query time, with a small “current at …” timestamp. It is not the value immediately after the historical event.

For committed zero decisions, show `0 applied` and the resulting current affinity. Never infer a proposed delta from an applied change or current affinity. When a source value is absent, show **Unavailable** or an em dash with an accessible explanation, not a fabricated zero. Filters should cover time range, outcome, subject, and event/utterance ID where the backend supports them. Do not show raw speech or model prompts.

### Diagnostics

Use a dense, readable log table with timestamp, level, event/stage, outcome/reason, request ID, utterance ID, and elapsed/model/persistence time when present. Filters cover time, severity, outcome/reason, and correlated request or utterance ID. Keep safe reason codes visible as plain text; never render exception/provider messages, credentials, prompts, or raw speech. Debug rationale remains marked as untrusted and is not needed for the initial view.

Offer **Download filtered plugin log** as a bounded JSONL export of plugin records only. It must not return the whole shared CHIM log or adjacent core/Apache entries. If the native or fallback sink cannot be read safely, or the requested range is unavailable, explain that and disable the download. Preserve source timestamps and clearly label truncation/retention limits; do not imply a complete audit trail.

## States and accessibility

- A genuinely empty source says there are no recorded interactions/log entries; it is not populated with demo rows.
- No filter matches gets a distinct “No results for these filters” state and a one-action clear-filters link.
- A failed or inaccessible source gets an **Unavailable** state with a concise reason and retry path; it must not masquerade as empty data.
- A partial record labels missing proposed/applied/current values individually. An unconfirmed commit is never rendered as “not applied.”
- All functions work by keyboard. Use visible focus rings, semantic headings/forms/tables, explicit labels, meaningful link/button names, and status text announced on navigation or filter submission. Respect reduced-motion; no motion is needed for the design.
- Meet WCAG AA contrast for body text and controls. Do not rely on brass/spruce/rust alone to communicate active, success, or failure.

## Candidate implementation files

After the route and read-data contract freeze, prefer the smallest plugin-owned set:

1. `server/dashboard.php` — read-only controller and server-rendered page, using CHIM's existing authenticated request context.
2. `server/dashboard.css` — local CSS variables, responsive ledger layout, focus and status styles.
3. No JavaScript initially: native navigation, GET filters, and a download response cover the required interactions. Add a small local script only if a frozen contract demonstrates a keyboard- or workflow-critical behavior that native HTML cannot provide.
4. Update `scripts/package.py` and the focused package test only to include and verify shipped dashboard assets; add one focused read-only view/contract test if the endpoint has testable behavior.

Do not create a new login, database schema, provider call, daemon, or core route. Before coding, the lead must freeze the plugin page URL/auth boundary, authoritative source for proposed/applied/current values, bounded query/paging policy, and safe path/size/time policy for filtered log downloads. If CHIM does not expose a supported plugin-owned authenticated page route, stop for an integration decision rather than editing core.

## Visual verification after implementation

Use the in-app Browser following the `control-in-app-browser` skill. Run only an isolated local preview, not the installed CHIM service. Capture actual screenshots of both tabs at desktop (1440×900) and narrow mobile (390×844), with an empty state and clearly labeled test-only fixture rows. Verify tab/filter/download behavior, keyboard focus, readable overflow, and source-unavailable behavior. Fixture rows must never be seeded or exposed as production data. Attach the screenshots to the implementation review; no screenshot is claimed by this design-only task.
