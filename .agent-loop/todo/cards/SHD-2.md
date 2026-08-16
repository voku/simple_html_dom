# SHD-2: HTML5 mode: keep parsing when an attribute name is not a valid XML name

- **Ticket:** SHD-2
- **Lane:** BACKLOG
- **Status:** Backlog
- **Domain:** parser
- **Created:** 2026-08-15T22:12:40+00:00
- **Updated:** 2026-08-15T22:12:40+00:00
- **Summary:** The XML bridge of the HTML5 parser rejects documents with attribute names that HTML allows but XML forbids, so those documents silently use the legacy parser.
- **Format version:** 1

## Agent Task Brief
tests/fixtures/horrible.html contains <font size="4" ,="" color="...">. Dom\HTMLDocument keeps the ',' attribute, the XML transport in HtmlDomParser::createDOMDocumentViaHtml5Parser() cannot represent it ('error parsing attribute name'), loadXML() fails and the parser falls back to libxml - exactly for the broken real-world input the HTML5 parser exists for. Idea: pay for a repair pass only after a failed load (drop or park attribute names that do not match the XML Name production, re-serialize, retry once), so well-formed input keeps the fast path.
