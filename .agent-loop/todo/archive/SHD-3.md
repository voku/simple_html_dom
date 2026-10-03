# SHD-3: HTML5 parser: decide fragment semantics for node string mutations

- **Ticket:** SHD-3
- **Lane:** BACKLOG
- **Status:** Backlog
- **Domain:** parser
- **Created:** 2026-08-15T22:12:40+00:00
- **Updated:** 2026-08-17T10:35:00+00:00
- **Resolved by:** voku/simple_html_dom#155
- **Summary:** `Html5DomParser` now preserves parser identity for context-sensitive `innerHtml` / `outerHtml` mutations by parsing fragments against the actual destination context where PHP's HTML5 fragment API is safe, while retaining the proven legacy mutation path for unsupported contexts.
- **Format version:** 1

## Agent Task Brief
`SimpleHtmlDom::replaceChildWithString()` and `replaceNodeWithString()` explicitly construct `new HtmlDomParser($string)`, and `getHtmlDomParser()` likewise returns a legacy `HtmlDomParser`. That keeps the existing mutation behavior stable after `Html5DomParser` became a separate class, but it also means a document parsed with HTML5 semantics can mutate nodes using legacy fragment parsing semantics.

Do not "fix" this by mechanically replacing those constructions with `Html5DomParser`: PHP's HTML5 parser builds complete documents, while correct fragment parsing is context-sensitive for elements such as `table` / `tbody` / `tr` / `td` and `select` / `option`.

First add focused regression fixtures that mutate nodes from an `Html5DomParser` document and expose a meaningful semantic difference. Then decide the smallest explicit contract: either document and test legacy fragment parsing as the intentional mutation behavior, or add a real context-aware HTML5 fragment path that preserves the originating parser semantics. No global parser mode and no implicit backend fallback.

## Resolution
Resolved by voku/simple_html_dom#155. `SimpleHtmlDom` asks the originating parser for a context-aware mutation fragment: `innerHtml` uses the current element as context and `outerHtml` uses its parent. `Html5DomParser` uses PHP >= 8.4 fragment parsing for contexts such as tables and selects, so implied tree construction is preserved instead of reparsing the replacement as an independent document.

Contexts with separate compatibility concerns remain intentionally on the legacy mutation path: `head` (legacy serialization/encoding behavior), `html` (document-wrapper synthesis), `template` (template-content storage), and namespaced foreign content. Focused regressions pin both the HTML5 fragment cases and these fallbacks. No global parser mode and no silent backend switch were introduced.
