# SHD-3: HTML5 mode: node-level string replacement uses fragment semantics the HTML5 parser does not have

- **Ticket:** SHD-3
- **Lane:** BACKLOG
- **Status:** Backlog
- **Domain:** parser
- **Created:** 2026-08-15T22:12:40+00:00
- **Updated:** 2026-08-15T22:12:40+00:00
- **Summary:** Setting innerHtml/outerHtml on a node builds a throwaway HtmlDomParser from the fragment string; with the HTML5 parser enabled by default that parser returns a complete document and the replacement loses content.
- **Format version:** 1

## Agent Task Brief
SimpleHtmlDom::replaceChildWithString() / replaceNodeWithString() create 'new HtmlDomParser($string)' and then take the children of documentElement. With the HTML5 parser that element is always <html>, so '<div>text2</div>' becomes '<div></div>'. Reproduce by running the suite with HtmlDomParser::useHtml5ParserByDefault(true) (HTML5DOMDocumentTest::testInnerHTML, testOuterHTML). Decide whether those paths should pin the legacy parser or use a real HTML5 fragment-parsing context.
