# SHD-5: agent-loop: projected skills hard-code vendor/bin/agent-loop

- **Ticket:** SHD-5
- **Lane:** BACKLOG
- **Status:** Backlog
- **Domain:** agent-loop-workflow
- **Created:** 2026-08-15T22:12:40+00:00
- **Updated:** 2026-08-15T22:12:40+00:00
- **Summary:** Skill and discipline text name an executable path that does not exist in every host; make/agent-loop.mk already solves this with AGENT_LOOP_BIN.
- **Format version:** 1

## Agent Task Brief
See finding.2026-08-15.002. This repository added bin/agent-loop as a wrapper so the documented command works. Upstream fix: render the host entrypoint into the projected skills.
