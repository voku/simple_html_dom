# SHD-11: Html5DomParser: enforce the explicit backend boundary

- **Ticket:** SHD-11
- **Lane:** READY
- **Status:** Selected
- **Domain:** parser
- **Created:** 2026-08-16T21:11:23+00:00
- **Updated:** 2026-08-16T21:11:23+00:00
- **Summary:** Html5DomParser must never silently become HtmlDomParser, and its minimal bridge-sensitive input protection must pair with the existing shared inverse cleanup without changing HtmlDomParser preprocessing.
- **Format version:** 1

## Agent Task Brief
Finish the separate-parser design from SHD-10. Choosing Html5DomParser is an explicit semantic choice, so a successful parse must come from the PHP HTML5 backend; unsupported runtimes and an XML bridge that cannot represent the normalized tree must fail visibly instead of silently switching parser semantics. Also fix SHD-11 proper: protect only percent escapes and the Google AMP marker before the HTML5 backend. Keep HtmlDomParser preprocessing order unchanged and keep the existing shared inverse cleanup authoritative for DOM mutations and broken-fragment placeholders. Keep HtmlDomParser behavior unchanged, keep the public DOMDocument / DOMNode API, and validate with the copied compatibility suite plus the PR #146 benchmark method.
