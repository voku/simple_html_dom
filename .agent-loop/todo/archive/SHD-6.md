# SHD-6: agent-loop: shipped router describes commands the released CLI does not have

- **Ticket:** SHD-6
- **Lane:** BACKLOG
- **Status:** Backlog
- **Domain:** agent-loop-workflow
- **Created:** 2026-08-15T22:12:41+00:00
- **Updated:** 2026-08-17T10:35:00+00:00
- **Resolved upstream:** voku/agent-loop#153
- **Summary:** The installed 0.16.3 host guidance named command routes unavailable from the same installed CLI. voku/agent-loop#153 shipped the installed release/projection consistency gate; this consumer now consumes that owner fix, and the card remains only as dogfood provenance.
- **Format version:** 1

## Agent Task Brief
See finding.2026-08-15.003 and voku/agent-loop#153. The relevant defect is release-internal drift between projected host instructions and the installed command surface, not something simple_html_dom should patch locally. Keep this repository's release-accurate instructions bounded, and let the upstream installed-release dogfood prove projected router/skill routes against the same candidate CLI without another hand-maintained command list.

## Resolution
Resolved upstream by voku/agent-loop#153 and consumed by the installed agent-loop 0.16.x toolchain.
