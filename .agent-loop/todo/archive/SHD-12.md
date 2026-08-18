# SHD-12: agent-loop: resume governed Run after Session pruning

- **Ticket:** SHD-12
- **Lane:** BACKLOG
- **Status:** Backlog
- **Domain:** workflow
- **Created:** 2026-08-16T22:59:30+00:00
- **Updated:** 2026-08-17T10:35:00+00:00
- **Resolved upstream:** voku/agent-loop#181 (owner capability: voku/agent-session#15)
- **Summary:** The dogfood run showed that a durable governed Run could outlive its pruneable bound Session while approval/resume created a new Session ID and correctly refused to rebind the existing Run. voku/agent-session#15 and voku/agent-loop#181 shipped exact-ID rehydration; this consumer now consumes and replays that owner fix, and the card remains only as dogfood provenance.
- **Format version:** 1

## Agent Task Brief
Use finding.2026-08-16.001, voku/agent-loop#181, and voku/agent-session#15 as the regression chain. Preserve the durable Run / pruneable Session boundary and the existing same-Session invariant. The owner-level fix is for `workflow approve` to rehydrate the exact Session ID already bound to the existing same-Contract Run when that Session has been pruned, using an agent-session capability that can create the historical exact ID. Do not make Session durable, weaken Run identity checks, or reconstruct a different Session locally in simple_html_dom.

## Resolution
Resolved upstream by voku/agent-session#15 and voku/agent-loop#181; this consumer now locks agent-session 0.6.1 and agent-loop 0.16.6 and replays the exact-ID prune/resume regression.
