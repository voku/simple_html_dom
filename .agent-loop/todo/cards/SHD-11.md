# SHD-11: Html5DomParser: enforce the explicit backend boundary

- **Ticket:** SHD-11
- **Lane:** DONE
- **Status:** Done
- **Domain:** parser
- **Created:** 2026-08-16T21:11:23+00:00
- **Updated:** 2026-08-16T22:59:20+00:00
- **Summary:** Html5DomParser is an explicit PHP 8.4 HTML5 backend choice, never silently becomes HtmlDomParser, and keeps bridge-sensitive protection minimal without changing HtmlDomParser preprocessing.
- **Format version:** 1

## Agent Task Brief
Completed under governed Run run:SHD-11:7eb9dd9a5be120d6. Html5DomParser is a separate strict parser class; unsupported runtimes and XML-bridge failures are explicit, keepBrokenHtml remains preprocessing rather than a backend selector, the copied compatibility suite pins intentional HTML5 differences, and the PR #146-style benchmark is part of validation. SHD-2 remains the bounded follow-up for repairing HTML5 trees that cannot be represented by the legacy DOMDocument bridge.
