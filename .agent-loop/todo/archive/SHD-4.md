# SHD-4: agent-loop: client hooks cannot find the package runtime in a tool-project install

- **Ticket:** SHD-4
- **Lane:** BACKLOG
- **Status:** Backlog
- **Domain:** agent-loop-workflow
- **Created:** 2026-08-15T22:12:40+00:00
- **Updated:** 2026-08-17T10:35:00+00:00
- **Resolved upstream:** voku/agent-loop#138
- **Summary:** The installed hooks resolve only <root>/vendor/autoload.php and otherwise treat <root>/src as the package source root, which fails when agent-loop is an isolated tool project. The owner-level fix is shipped and consumed here; this card remains only as dogfood provenance.
- **Format version:** 1

## Agent Task Brief
See finding.2026-08-15.001 and voku/agent-loop#138. This repository works around the defect with a host-owned hook bundle in docs/agents/{claude,codex}-hooks that resolves tools/agent-loop/vendor/autoload.php first and exits 0 when the runtime is missing. Do not add another simple_html_dom-specific runtime resolver. Follow the upstream issue, which owns portable isolated-tool runtime resolution and projected hook bootstrap behavior.

## Resolution
Resolved upstream by voku/agent-loop#138 and consumed by the installed agent-loop 0.16.x toolchain.
