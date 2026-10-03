# SHD-2: HTML5 parser: bridge HTML-valid attribute names that XML cannot represent

- **Ticket:** SHD-2
- **Lane:** BACKLOG
- **Status:** Backlog
- **Domain:** parser
- **Created:** 2026-08-15T22:12:40+00:00
- **Updated:** 2026-08-17T10:35:00+00:00
- **Summary:** `Html5DomParser` is now a strict backend choice and throws when its HTML5-normalized tree contains attribute names that the legacy XML/`DOMDocument` transport cannot represent. The remaining task is to make that bridge case representable without silently switching parser semantics or discarding caller data.
- **Format version:** 1

## Agent Task Brief
`tests/fixtures/horrible.html` contains `<font size="4" ,="" color="...">`. `Dom\HTMLDocument` keeps the `,` attribute, while the current XML transport in `Html5DomParser::createDOMDocumentViaHtml5Parser()` rejects the serialized tree because `,` is not a valid XML attribute name. The final parser contract deliberately throws `RuntimeException` here instead of falling back to `HtmlDomParser`.

Reproduce that strict failure first. Then test the smallest bridge-only repair that preserves HTML5 parser identity and caller data, preferably paying extra work only after the initial `loadXML()` failure. A candidate is to park XML-invalid attribute names under collision-safe XML-valid placeholders before serialization and restore them after the `DOMDocument` bridge. Do not silently drop attributes and do not reintroduce libxml fallback.
