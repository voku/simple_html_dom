# SHD-7: agent-loop: init scaffold seeds DEMO data into the real board

- **Ticket:** SHD-7
- **Lane:** BACKLOG
- **Status:** Backlog
- **Domain:** agent-loop-workflow
- **Created:** 2026-08-15T22:12:41+00:00
- **Updated:** 2026-08-17T10:35:00+00:00
- **Resolved upstream:** voku/agent-loop#151
- **Summary:** Real repository initialization currently receives durable DEMO identity/work by default. The owner-level separation of real-project scaffold from explicit demo mode is tracked upstream; this consumer card remains only as dogfood provenance.
- **Format version:** 1

## Agent Task Brief
See finding.2026-08-15.004 and voku/agent-loop#151. This repository had to replace the seeded DEMO board state with its real SHD prefix. Do not add another local scaffold cleanup script. The upstream issue owns explicit `--prefix=<PREFIX>` real-project initialization, empty-board scaffold semantics, and opt-in demo population while reusing agent-kanban's existing BoardConfig validation.

## Resolution
Resolved upstream by voku/agent-loop#151 and consumed by the installed agent-loop 0.16.x toolchain.
