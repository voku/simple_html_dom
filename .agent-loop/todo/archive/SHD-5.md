# SHD-5: agent-loop: projected skills hard-code vendor/bin/agent-loop

- **Ticket:** SHD-5
- **Lane:** BACKLOG
- **Status:** Backlog
- **Domain:** agent-loop-workflow
- **Created:** 2026-08-15T22:12:40+00:00
- **Updated:** 2026-08-17T10:35:00+00:00
- **Resolved upstream:** voku/agent-loop#138
- **Summary:** The dogfood run showed that projected guidance could name an executable path unavailable in isolated tool-project installs. voku/agent-loop#138 shipped repository-local CLI-path resolution and projection; this consumer now consumes that owner fix, and the card remains only as dogfood provenance.
- **Format version:** 1

## Agent Task Brief
See finding.2026-08-15.002 and voku/agent-loop#138. This repository added bin/agent-loop as a local wrapper so the projected command is usable here. Do not grow another consumer-specific command-path abstraction. The upstream issue owns resolving the repository-local agent-loop CLI once and rendering that resolved path into projected skills/subagents for isolated tool-project installs.

## Resolution
Resolved upstream by voku/agent-loop#138 and consumed by the installed agent-loop 0.16.x toolchain.
