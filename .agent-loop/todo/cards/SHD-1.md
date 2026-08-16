# SHD-1: PHP >= 8.4 HTML5 parsing via Dom\HTMLDocument

- **Ticket:** SHD-1
- **Lane:** VERIFY
- **Status:** In Test
- **Domain:** parser
- **Assignee:** claude
- **Created:** 2026-08-15T21:46:49+00:00
- **Updated:** 2026-08-15T22:13:14+00:00
- **Summary:** Add opt-in HTML5-conformant parsing through the PHP 8.4 Dom\HTMLDocument parser while keeping the libxml-based DOMDocument pipeline as default.
- **Next:** Review the pushed branch; follow-ups tracked as SHD-2 / SHD-3
- **Validation:** php vendor/bin/phpunit -c phpunit.xml
- **Priority:** 1
- **Format version:** 1

## Agent Task Brief
PHP 8.4 ships Dom\HTMLDocument, an HTML5-spec parser that is more forgiving than the XML-oriented DOMDocument and matches browser parsing. This library is built on \DOMDocument (public API returns \DOMDocument / \DOMNode), so the HTML5 parser must be additive and opt-in: no behavior change and no measurable cost for the default path. A previous attempt (PR #146) auto-bridged the modern document back into DOMDocument and lost more performance than it gained; the bridge cost is the core problem to solve or to scope out.
