# SHD-8: agent-loop: returning to PLAN is blocked while a governed session is active

- **Ticket:** SHD-8
- **Lane:** BACKLOG
- **Status:** Backlog
- **Domain:** agent-loop-workflow
- **Created:** 2026-08-15T22:12:41+00:00
- **Updated:** 2026-08-15T22:12:41+00:00
- **Summary:** The discipline says scope drift returns to PLAN, but 'workflow plan' fails while a session is active, so one task ends up split across two Runs.
- **Format version:** 1

## Agent Task Brief
See finding.2026-08-15.005. Either document the close-then-replan sequence in the discipline or add an explicit supersede path that keeps the session.
