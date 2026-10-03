# SHD-2: HTML5 parser: bridge HTML-valid attribute names that XML cannot represent

- **Ticket:** SHD-2
- **Lane:** BACKLOG
- **Status:** Backlog
- **Domain:** parser
- **Created:** 2026-08-15T22:12:40+00:00
- **Updated:** 2026-08-17T10:35:00+00:00
- **Resolved by:** voku/simple_html_dom#154
- **Summary:** `Html5DomParser` preserves HTML-valid attribute names that the XML-backed legacy `DOMDocument` cannot represent directly by parking them under collision-safe XML-valid names only when the first XML bridge attempt fails, then restoring the public names through the wrapper and serialization APIs.
- **Format version:** 1

## Agent Task Brief
`tests/fixtures/horrible.html` contains `<font size="4" ,="" color="...">`. `Dom\HTMLDocument` keeps the `,` attribute, while legacy `DOMDocument` cannot create or round-trip that name directly. Preserve HTML5 parser identity and caller data without silently falling back to `HtmlDomParser`.

## Resolution
Resolved by voku/simple_html_dom#154. The normal HTML5 -> XML -> legacy DOM bridge remains unchanged for ordinary documents. After a genuine XML bridge failure, only un-namespaced attribute names that cannot round-trip through XML are moved to collision-safe internal attributes before retrying the same bridge. Parser-local mappings restore the original names through `SimpleHtmlDom` attribute access and HTML serialization. `horrible.html` is back in the cross-parser fixture corpus, helper collisions are covered, and mapped attributes can be read, updated, and removed.

The raw `getDocument()` result necessarily retains the XML-safe internal names because legacy `DOMDocument` physically cannot represent the original HTML-only names. No legacy parser fallback was introduced.
