# SHD-11: Html5DomParser: fixHtmlOutput reverses a substitution the HTML5 path never applies

- **Ticket:** SHD-11
- **Lane:** BACKLOG
- **Status:** Backlog
- **Domain:** parser
- **Created:** 2026-08-16T21:11:23+00:00
- **Updated:** 2026-08-16T21:11:23+00:00
- **Summary:** fixHtmlOutput() always calls putReplacedBackToPreserveHtmlEntities(), but Html5DomParser skips the matching replaceToPreserveHtmlEntities(), so input that already contains %5B%5B, %7B%7B or %40 is rewritten on output.
- **Format version:** 1

## Agent Task Brief
Measured on 2026-08-16: applying replaceToPreserveHtmlEntities() in Html5DomParser for symmetry fixes testEditLinks and testMail2 but breaks testSimpleHtmlViaSimpleXmlLoadString, testGetHtmlInner and both XML-bridge fallback tests, so it was reverted. The asymmetry is currently absorbed by the normalizer in tests/Html5DomParserCompatibilityTest.php (the '%5B%5B' / '%40' rule). Real fix: make the reverse step conditional on the substitution having run, instead of making the HTML5 path run a libxml workaround it does not need.
