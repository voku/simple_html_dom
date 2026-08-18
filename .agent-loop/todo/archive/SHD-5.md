# SHD-5: agent-loop: projected skills hard-code vendor/bin/agent-loop

- **Ticket:** SHD-5
- **Lane:** BACKLOG
- **Status:** Backlog
- **Domain:** agent-loop-workflow
- **Created:** 2026-08-15T22:12:40+00:00
- **Updated:** 2026-08-17T10:35:00+00:00
- **Resolved upstream:** voku/agent-loop#138
- **Summary:** Skill and discipline text name an executable path that does not exist in every host. The owner-level portable CLI-path fix is tracked upstream; this consumer card remains only as dogfood provenance.
- **Format version:** 1

## Agent Task Brief
See finding.2026-08-15.002 and voku/agent-loop#138. This repository added bin/agent-loop as a local wrapper so the projected command is usable here. Do not grow another consumer-specific command-path abstraction. The upstream issue owns resolving the repository-local agent-loop CLI once and rendering that resolved path into projected skills/subagents for isolated tool-project installs.

## Resolution
Resolved upstream by voku/agent-loop#138 and consumed by the installed agent-loop 0.16.x toolchain.
