# SHD-9: Use the HTML5 parser whenever it is enabled, instead of silently falling back

- **Ticket:** SHD-9
- **Lane:** BACKLOG
- **Status:** Backlog
- **Domain:** parser
- **Created:** 2026-08-16T20:20:17+00:00
- **Updated:** 2026-08-16T20:20:17+00:00
- **Summary:** When a caller enables the PHP >= 8.4 HTML5 parser, HtmlDomParser should use it. Today useKeepBrokenHtml() silently disables it, and an input the XML bridge cannot carry silently downgrades the whole document.
- **Next:** Plan the contract (workflow plan SHD-9)
- **Validation:** php vendor/bin/phpunit -c phpunit.xml
- **Priority:** 2
- **Format version:** 1

## Agent Task Brief
HtmlDomParser::createDOMDocument() guards the HTML5 branch with 'if ($this->useHtml5Parser && !$this->keepBrokenHtml)'. That guard was added defensively, not because the two features were shown to conflict. Measured evidence (2026-08-15, PHP 8.4.19): with the '!$this->keepBrokenHtml' condition removed and the HTML5 parser forced on, all five existing keepBrokenHtml tests pass unchanged (testBrokenHtmlAtTheBeginOfTheInput, testBrokenHtmlInTheMiddleOfTheInput, testHtmlWithSpecialCommentsAndKeepBrokenHtml, testHtmlWithSpecialCommentsAndKeepBrokenHtml2, HTML5DOMDocumentTest keepBrokenHtml case); the only failing test was HtmlDomParserHtml5Test::testKeepBrokenHtmlFallsBackToTheLegacyParser, which asserts the fallback itself. keepBrokenHtml replaces broken fragments with hashed text placeholders before parsing and restores them after serialization, and those placeholders survive HTML5 parsing. Decide the precedence deliberately, cover it with tests, and review the other silent fallbacks (SHD-2) with the same question: a caller who asked for the HTML5 parser should be able to tell whether they got it.
