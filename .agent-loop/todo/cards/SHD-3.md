# SHD-3: HTML5 parser: decide fragment semantics for node string mutations

- **Ticket:** SHD-3
- **Lane:** BACKLOG
- **Status:** Backlog
- **Domain:** parser
- **Created:** 2026-08-15T22:12:40+00:00
- **Updated:** 2026-08-17T10:35:00+00:00
- **Summary:** `SimpleHtmlDom` still parses `innerHtml` / `outerHtml` replacement strings with `HtmlDomParser` even when the node originated from `Html5DomParser`, so parser semantics do not currently propagate into string mutations. The old global HTML5-mode reproduction is gone, but the fragment-semantics boundary remains deliberate and unresolved.
- **Format version:** 1

## Agent Task Brief
`SimpleHtmlDom::replaceChildWithString()` and `replaceNodeWithString()` explicitly construct `new HtmlDomParser($string)`, and `getHtmlDomParser()` likewise returns a legacy `HtmlDomParser`. That keeps the existing mutation behavior stable after `Html5DomParser` became a separate class, but it also means a document parsed with HTML5 semantics can mutate nodes using legacy fragment parsing semantics.

Do not "fix" this by mechanically replacing those constructions with `Html5DomParser`: PHP's HTML5 parser builds complete documents, while correct fragment parsing is context-sensitive for elements such as `table` / `tbody` / `tr` / `td` and `select` / `option`.

First add focused regression fixtures that mutate nodes from an `Html5DomParser` document and expose a meaningful semantic difference. Then decide the smallest explicit contract: either document and test legacy fragment parsing as the intentional mutation behavior, or add a real context-aware HTML5 fragment path that preserves the originating parser semantics. No global parser mode and no implicit backend fallback.
