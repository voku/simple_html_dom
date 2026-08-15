# SHD-4: agent-loop: client hooks cannot find the package runtime in a tool-project install

- **Ticket:** SHD-4
- **Lane:** BACKLOG
- **Status:** Backlog
- **Domain:** agent-loop-workflow
- **Created:** 2026-08-15T22:12:40+00:00
- **Updated:** 2026-08-15T22:12:40+00:00
- **Summary:** The installed hooks resolve only <root>/vendor/autoload.php and otherwise treat <root>/src as the package source root, which fails when agent-loop is an isolated tool project.
- **Format version:** 1

## Agent Task Brief
See finding.2026-08-15.001. This repository works around it with a host-owned hook bundle in docs/agents/{claude,codex}-hooks that resolves tools/agent-loop/vendor/autoload.php first and exits 0 when the runtime is missing. Upstream fix: an ordered candidate list including a host-configured path, and a src/ fallback that verifies the directory really is the package.
