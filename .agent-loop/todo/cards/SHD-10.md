# SHD-10: Extract the HTML5 parser into its own Html5DomParser class

- **Ticket:** SHD-10
- **Lane:** VERIFY
- **Status:** In Test
- **Domain:** parser
- **Assignee:** claude
- **Created:** 2026-08-16T20:52:35+00:00
- **Updated:** 2026-08-16T21:11:23+00:00
- **Summary:** Replace the useHtml5Parser() flag on HtmlDomParser with a separate Html5DomParser class, so the different parsing semantics are a visible choice of class instead of a hidden boolean.
- **Validation:** php vendor/bin/phpunit -c phpunit.xml
- **Priority:** 1
- **Format version:** 1

## Agent Task Brief
PHP itself put the HTML5 parser in a new class (\Dom\HTMLDocument) next to \DOMDocument instead of adding a mode to the old one. This library should do the same: HtmlDomParser keeps its libxml behavior unchanged, Html5DomParser extends it and swaps the parsing backend, and the API layout stays identical so callers only change the class name. The HTML5 semantics are breaking (entities decoded, always a complete document, boolean attributes serialized as attr=""), and a class name states that where a flag hides it. Copy the HtmlDomParser test suite against the new class to show what still holds and to pin every difference. Use the benchmark methodology from PR #146 (interleaved samples, median, parse/selector/serialize phases, peak memory) before finalizing.
