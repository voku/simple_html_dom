# SHD-8: agent-loop: returning to PLAN is blocked while a governed session is active

- **Ticket:** SHD-8
- **Lane:** BACKLOG
- **Status:** Backlog
- **Domain:** agent-loop-workflow
- **Created:** 2026-08-15T22:12:41+00:00
- **Updated:** 2026-08-17T10:35:00+00:00
- **Resolved upstream:** voku/agent-loop#152
- **Summary:** Canonical guidance says scope drift returns to PLAN, while an active governed Session must first be closed/superseded. The owner-level deterministic re-plan handoff is tracked upstream; this consumer card remains only as dogfood provenance.
- **Format version:** 1

## Agent Task Brief
See finding.2026-08-15.005 and voku/agent-loop#152. Preserve the useful Contract/Session/Run revision boundary: do not make one Session span two approved Contract revisions and do not hand-edit Run state in this repository. The upstream issue owns making the required close-old-Session -> revise Contract -> re-approve -> replacement Run/Session sequence explicit and actionable while preserving prior Run evidence.

## Resolution
Resolved upstream by voku/agent-loop#152 and consumed by the installed agent-loop 0.16.x toolchain.
