# SHD-7: agent-loop: init scaffold seeds DEMO data into the real board

- **Ticket:** SHD-7
- **Lane:** BACKLOG
- **Status:** Backlog
- **Domain:** agent-loop-workflow
- **Created:** 2026-08-15T22:12:41+00:00
- **Updated:** 2026-08-17T10:35:00+00:00
- **Resolved upstream:** voku/agent-loop#151
- **Summary:** The dogfood run showed that real repository initialization received durable DEMO identity/work by default. voku/agent-loop#151 shipped the separation between real-project scaffold and explicit demo mode; this consumer now consumes that owner fix, and the card remains only as dogfood provenance.
- **Format version:** 1

## Agent Task Brief
See finding.2026-08-15.004 and voku/agent-loop#151. This repository had to replace the seeded DEMO board state with its real SHD prefix. Do not add another local scaffold cleanup script. The upstream issue owns explicit `--prefix=<PREFIX>` real-project initialization, empty-board scaffold semantics, and opt-in demo population while reusing agent-kanban's existing BoardConfig validation.

## Resolution
Resolved upstream by voku/agent-loop#151 and consumed by the installed agent-loop 0.16.x toolchain.
