# SHD-12: agent-loop: resume governed Run after Session pruning

- **Ticket:** SHD-12
- **Lane:** BACKLOG
- **Status:** Backlog
- **Domain:** workflow
- **Created:** 2026-08-16T22:59:30+00:00
- **Updated:** 2026-08-16T22:59:30+00:00
- **Summary:** A durable governed Run must resume after its pruneable bound Session disappears without requiring manual reconstruction of the old Session id.
- **Format version:** 1

## Agent Task Brief
Use finding.2026-08-16.001 as the regression evidence. Preserve the durable Run / pruneable Session boundary, but make resume deterministic: workflow approve or a dedicated resume path should recreate or governably replace missing working memory while keeping Contract, Run, Recall, validation and Learning lineage intact. Do not make Session durable merely to avoid the problem.
