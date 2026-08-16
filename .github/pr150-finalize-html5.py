#!/usr/bin/env python3

from pathlib import Path
import re
import subprocess
import sys

ROOT = Path(__file__).resolve().parent.parent


def read(path: str) -> str:
    return (ROOT / path).read_text()


def write(path: str, content: str) -> None:
    (ROOT / path).write_text(content)


def replace_once(path: str, old: str, new: str) -> None:
    content = read(path)
    count = content.count(old)
    if count != 1:
        raise RuntimeError(f'{path}: expected one exact match, found {count}')
    write(path, content.replace(old, new, 1))


def regex_once(path: str, pattern: str, replacement: str) -> None:
    content = read(path)
    updated, count = re.subn(pattern, replacement, content, count=1, flags=re.S)
    if count != 1:
        raise RuntimeError(f'{path}: expected one regex match, found {count}')
    write(path, updated)


def prepare_card() -> None:
    path = '.agent-loop/todo/cards/SHD-11.md'
    content = read(path)
    content = re.sub(r'^# SHD-11:.*$', '# SHD-11: Html5DomParser: enforce the explicit backend boundary', content, count=1, flags=re.M)
    content = content.replace('- **Lane:** BACKLOG', '- **Lane:** READY')
    content = content.replace('- **Status:** Backlog', '- **Status:** Selected')
    content = re.sub(
        r'^- \*\*Summary:\*\*.*$',
        '- **Summary:** Html5DomParser must never silently become HtmlDomParser, and shared output cleanup must only reverse placeholders that the selected backend actually created.',
        content,
        count=1,
        flags=re.M,
    )
    content = re.sub(
        r'## Agent Task Brief\n.*\Z',
        '## Agent Task Brief\n'
        'Finish the separate-parser design from SHD-10. Choosing Html5DomParser is an explicit semantic choice, so a successful parse must come from the PHP HTML5 backend; unsupported runtimes and an XML bridge that cannot represent the normalized tree must fail visibly instead of silently switching parser semantics. Also fix SHD-11 proper: the HTML5 backend skips the legacy replaceToPreserveHtmlEntities() workaround, so output cleanup must restore only the broken-fragment placeholders that this backend actually created. Keep HtmlDomParser behavior unchanged, keep the public DOMDocument / DOMNode API, and validate with the copied compatibility suite plus the PR #146 benchmark method.\n',
        content,
        count=1,
        flags=re.S,
    )
    write(path, content)


def patch_html_dom_parser() -> None:
    path = 'src/voku/helper/HtmlDomParser.php'
    replace_once(
        path,
        '        return self::putReplacedBackToPreserveHtmlEntities($content, $putBrokenReplacedBack);',
        '        return $this->restoreOutputPlaceholders($content, $putBrokenReplacedBack);',
    )
    replace_once(
        path,
        '            $return = self::putReplacedBackToPreserveHtmlEntities($xml);',
        '            $return = $this->restoreOutputPlaceholders($xml, true);',
    )
    anchor = '''    protected function serializeDocumentWithoutHtmlWrapper(): string
    {
        return (string) $this->document->saveHTML($this->document->documentElement);
    }
'''
    addition = anchor + '''
    /**
     * Restore placeholders created by this parser backend before exposing output.
     *
     * HtmlDomParser runs replaceToPreserveHtmlEntities() before libxml parsing, so its
     * matching reverse transformation belongs here. Alternative backends can override this
     * hook when they create a smaller set of placeholders.
     *
     * @param string $content
     * @param bool   $putBrokenReplacedBack
     *
     * @return string
     */
    protected function restoreOutputPlaceholders(string $content, bool $putBrokenReplacedBack): string
    {
        return self::putReplacedBackToPreserveHtmlEntities($content, $putBrokenReplacedBack);
    }
'''
    replace_once(path, anchor, addition)


def patch_html5_dom_parser() -> None:
    path = 'src/voku/helper/Html5DomParser.php'

    replace_once(
        path,
        ''' * When the runtime is older than PHP 8.4, or the result cannot survive that bridge, the
 * libxml parser of "HtmlDomParser" produces the document instead. That keeps the parser
 * working, and "getHtml5ParserFallbackReason()" says when it happened.
''',
        ''' * Choosing this class is a strict parser choice. If the PHP 8.4 HTML5 backend is not
 * available, or the normalized tree cannot be represented by the legacy "\\DOMDocument"
 * bridge this library exposes, parsing throws instead of silently switching to libxml. Call
 * "isHtml5ParserSupported()" before selecting this class when an application supports older
 * runtimes, and choose "HtmlDomParser" explicitly when legacy semantics are the desired
 * fallback.
''',
    )

    regex_once(
        path,
        r'''    /\*\*\n     \* Fallback reason: the runtime is older than PHP 8\.4,.*?    const FALLBACK_XML_BRIDGE_FAILED = 'xml_bridge_failed';\n\n''',
        '',
    )

    regex_once(
        path,
        r'''    /\*\*\n     \* @var string\|null\n     \*/\n    protected \$html5ParserFallbackReason;\n\n''',
        '',
    )

    regex_once(
        path,
        r'''    /\*\*\n     \* Check why the current document was built by the libxml parser instead of the HTML5\n.*?    public function getHtml5ParserFallbackReason\(\): \?string\n    \{\n        return \$this->html5ParserFallbackReason;\n    \}\n\n''',
        '',
    )

    old_method = '''    protected function createDOMDocumentFromPreparedHtml(string $html)
    {
        $this->isDOMDocumentCreatedWithHtml5Parser = false;
        $this->html5ParserFallbackReason = null;

        if (!static::isHtml5ParserSupported()) {
            $this->html5ParserFallbackReason = self::FALLBACK_UNSUPPORTED_RUNTIME;

            return null;
        }

        $document = $this->createDOMDocumentViaHtml5Parser($html);

        if ($document === null) {
            $this->html5ParserFallbackReason = self::FALLBACK_XML_BRIDGE_FAILED;

            return null;
        }

        $this->isDOMDocumentCreatedWithHtml5Parser = true;

        return $document;
    }
'''
    new_method = '''    protected function createDOMDocumentFromPreparedHtml(string $html)
    {
        $this->isDOMDocumentCreatedWithHtml5Parser = false;

        if (!static::isHtml5ParserSupported()) {
            throw new \\RuntimeException(
                'Html5DomParser requires PHP >= 8.4 with "\\\\Dom\\\\HTMLDocument" and "\\\\Dom\\\\HTML_NO_DEFAULT_NS".'
            );
        }

        $document = $this->createDOMDocumentViaHtml5Parser($html);
        $this->isDOMDocumentCreatedWithHtml5Parser = true;

        return $document;
    }
'''
    replace_once(path, old_method, new_method)

    replace_once(
        path,
        '''     * @return \\DOMDocument|null <p>NULL if the result could not be carried through the XML
     *                           bridge.</p>
''',
        '''     * @throws \\RuntimeException <p>If the normalized HTML5 tree cannot be represented by
     *                           the legacy DOMDocument bridge.</p>
     *
     * @return \\DOMDocument
''',
    )

    replace_once(
        path,
        '''        if ($xml === false || $xml === '') {
            return null;
        }
''',
        '''        if ($xml === false || $xml === '') {
            throw new \\RuntimeException('Html5DomParser could not serialize the normalized HTML5 document for the DOMDocument bridge.');
        }
''',
    )

    replace_once(
        path,
        '''        $loaded = $document->loadXML($xml, \\LIBXML_NONET);

        \\libxml_clear_errors();
        \\libxml_use_internal_errors($internalErrors);

        if ($loaded === false) {
            return null;
        }
''',
        '''        $loaded = $document->loadXML($xml, \\LIBXML_NONET);
        $lastError = \\libxml_get_last_error();

        \\libxml_clear_errors();
        \\libxml_use_internal_errors($internalErrors);

        if ($loaded === false) {
            $detail = $lastError instanceof \\LibXMLError ? ' ' . \\trim($lastError->message) : '';

            throw new \\RuntimeException('Html5DomParser could not bridge the normalized HTML5 document into DOMDocument.' . $detail);
        }
''',
    )

    old_restore = '''    private function restoreXmlnsAttributes(\\DOMDocument $document, string $helper)
    {
        $xPath = new \\DOMXPath($document);
        $elements = $xPath->query('//*[@' . $helper . ']');

        if ($elements === false) {
            return;
        }

        foreach ($elements as $element) {
            if (!$element instanceof \\DOMElement) {
                continue;
            }

            $element->setAttribute('xmlns', $element->getAttribute($helper));
            $element->removeAttribute($helper);
        }
    }
'''
    new_restore = '''    private function restoreXmlnsAttributes(\\DOMDocument $document, string $helper)
    {
        // The helper is generated internally from a safe attribute name, and //*[] only selects elements.
        /** @var \\DOMNodeList<\\DOMElement> $elements */
        $elements = (new \\DOMXPath($document))->query('//*[@' . $helper . ']');

        foreach ($elements as $element) {
            $element->setAttribute('xmlns', $element->getAttribute($helper));
            $element->removeAttribute($helper);
        }
    }
'''
    replace_once(path, old_restore, new_restore)

    anchor = '''    public static function isHtml5ParserSupported(): bool
    {
'''
    method = '''    /**
     * Restore only placeholders that the HTML5 path actually created.
     *
     * The legacy parser protects entity/link syntax before libxml parsing; the HTML5 parser
     * does not need that workaround. Reversing those substitutions here would corrupt literal
     * strings such as "%5B%5B" or "%40" that came from the caller. keepBrokenHtml(), however,
     * runs before backend selection and does create dynamic broken-fragment placeholders, so
     * those still have to be restored.
     *
     * @param string $content
     * @param bool   $putBrokenReplacedBack
     *
     * @return string
     */
    protected function restoreOutputPlaceholders(string $content, bool $putBrokenReplacedBack): string
    {
        if (!$putBrokenReplacedBack || empty(self::$domBrokenReplaceHelper['tmp'])) {
            return $content;
        }

        return \\str_replace(
            self::$domBrokenReplaceHelper['tmp'],
            self::$domBrokenReplaceHelper['orig'],
            $content
        );
    }

'''
    replace_once(path, anchor, method + anchor)


def patch_availability_test() -> None:
    path = 'tests/Html5DomParserAvailabilityTest.php'
    content = '''<?php

use voku\\helper\\Html5DomParser;

/**
 * Runtime availability behavior of Html5DomParser.
 *
 * @internal
 */
final class Html5DomParserAvailabilityTest extends \\PHPUnit\\Framework\\TestCase
{
    public function testItFailsExplicitlyWhenTheRuntimeDoesNotSupportTheHtml5Parser()
    {
        if (Html5DomParser::isHtml5ParserSupported()) {
            static::markTestSkipped('This guard is exercised only when the PHP 8.4 HTML5 parser is unavailable.');
        }

        $this->expectException(\\RuntimeException::class);
        $this->expectExceptionMessage('Html5DomParser requires PHP >= 8.4');

        $dom = new Html5DomParser();
        $dom->loadHtml('<div>x</div>');
    }
}
'''
    write(path, content)


def patch_html5_tests() -> None:
    path = 'tests/Html5DomParserTest.php'
    content = read(path)

    old = '''    public function testXmlBridgeFailureFallsBackToLegacyParser()
    {
        $dom = new Html5DomParser();
        $dom->loadHtml('<div @foo="bar">x</div>');

        static::assertFalse($dom->getIsDOMDocumentCreatedWithHtml5Parser());
        static::assertSame('<div @foo="bar">x</div>', $dom->html());
        static::assertSame(
            Html5DomParser::FALLBACK_XML_BRIDGE_FAILED,
            $dom->getHtml5ParserFallbackReason()
        );
    }

    public function testNoFallbackReasonWithoutAFallback()
    {
        $dom = $this->html5('<div>x</div>');

        static::assertTrue($dom->getIsDOMDocumentCreatedWithHtml5Parser());
        static::assertNull($dom->getHtml5ParserFallbackReason());
    }

    public function testTheFallbackReasonIsResetPerDocument()
    {
        $dom = new Html5DomParser();
        $dom->loadHtml('<div @foo="bar">x</div>');
        static::assertSame(
            Html5DomParser::FALLBACK_XML_BRIDGE_FAILED,
            $dom->getHtml5ParserFallbackReason()
        );

        $dom->loadHtml('<div>x</div>');

        static::assertTrue($dom->getIsDOMDocumentCreatedWithHtml5Parser());
        static::assertNull($dom->getHtml5ParserFallbackReason());
    }
'''
    new = '''    public function testXmlBridgeFailureIsExplicitInsteadOfChangingParserSemantics()
    {
        $this->expectException(\\RuntimeException::class);
        $this->expectExceptionMessage('could not bridge the normalized HTML5 document');

        $dom = new Html5DomParser();
        $dom->loadHtml('<div @foo="bar">x</div>');
    }

    public function testLiteralLegacyProtectionTokensAreNotDecodedByHtml5Output()
    {
        $html = '<a href="/%5B%5B/%5D%5D/%7B%7B/%7D%7D/%40/%25">x</a>';
        $dom = $this->html5($html);

        static::assertSame($html, $dom->html());
        static::assertSame('/%5B%5B/%5D%5D/%7B%7B/%7D%7D/%40/%25', $dom->findOne('a')->getAttribute('href'));
    }
'''
    if content.count(old) != 1:
        raise RuntimeError(f'{path}: fallback test block expected once, found {content.count(old)}')
    content = content.replace(old, new, 1)
    content = content.replace('        static::assertNull($dom->getHtml5ParserFallbackReason());\n', '')
    write(path, content)


def patch_compatibility_test() -> None:
    path = 'tests/Html5DomParserCompatibilityTest.php'
    block = '''        // the sequences that the libxml path substitutes to protect them are resolved as well
        $html = \\str_replace(
            ['%5B%5B', '%5D%5D', '%7B%7B', '%7D%7D', '%40'],
            ['[[', ']]', '{{', '}}', '@'],
            $html
        );

'''
    replace_once(path, block, '')


def patch_benchmark() -> None:
    path = 'build/benchmark_html5_parser.php'
    pattern = r'''function runBenchmarkOnce\(string \$parserClass, string \$html, string \$selector, int \$iterations\): array\n\{.*?\n\}\n\n/\*\*\n \* @param array<string, class-string<HtmlDomParser>> \$parserClasses'''
    replacement = '''function runBenchmarkOnce(string $parserClass, string $html, string $selector, int $iterations): array
{
    if (\\function_exists('memory_reset_peak_usage')) {
        \\memory_reset_peak_usage();
    }
    \\gc_collect_cycles();

    $peakBefore = \\memory_get_usage();
    $documents = [];

    // Keep the PR #146 phase layout: build the whole parser batch first, then query it, then
    // serialize it. This makes parse / selector / serialization timing directly comparable to
    // that benchmark and keeps its peak-memory pressure visible instead of freeing each DOM
    // before the next iteration.
    $start = \\microtime(true);
    for ($i = 0; $i < $iterations; ++$i) {
        $dom = new $parserClass();
        $dom->loadHtml($html);
        $documents[] = $dom;
    }
    $parseSeconds = \\microtime(true) - $start;

    $matches = 0;
    $start = \\microtime(true);
    foreach ($documents as $dom) {
        $matches = \\count($dom->findMulti($selector));
    }
    $selectorSeconds = \\microtime(true) - $start;

    $length = 0;
    $start = \\microtime(true);
    foreach ($documents as $dom) {
        $length = \\strlen($dom->html());
    }
    $serializeSeconds = \\microtime(true) - $start;

    $peakBytes = \\max(0, \\memory_get_peak_usage() - $peakBefore);
    unset($documents);

    return [
        'parse_ms'     => $parseSeconds / $iterations * 1000,
        'selector_ms'  => $selectorSeconds / $iterations * 1000,
        'serialize_ms' => $serializeSeconds / $iterations * 1000,
        'total_ms'     => ($parseSeconds + $selectorSeconds + $serializeSeconds) / $iterations * 1000,
        'peak_bytes'   => $peakBytes,
        'matches'      => $matches,
        'length'       => $length,
    ];
}

/**
 * @param array<string, class-string<HtmlDomParser>> $parserClasses'''
    regex_once(path, pattern, replacement)


def patch_readme() -> None:
    path = 'README.md'
    old = '''When the runtime is older than PHP 8.4, or the result cannot be carried through the bridge (an
attribute name that is legal in HTML but not in XML, for example), `Html5DomParser` parses with
libxml instead of failing. That is never silent:

```php
Html5DomParser::isHtml5ParserSupported();       // false on PHP < 8.4
$dom->getIsDOMDocumentCreatedWithHtml5Parser(); // which parser built the current document
$dom->getHtml5ParserFallbackReason();           // null, or why the libxml parser was used
// Html5DomParser::FALLBACK_UNSUPPORTED_RUNTIME
// Html5DomParser::FALLBACK_XML_BRIDGE_FAILED
```
'''
    new = '''`Html5DomParser` is a strict parser choice. It does **not** silently switch back to
`HtmlDomParser`, because that would make the class name lie about the parsing semantics. On an
application that also runs on PHP < 8.4, check support before selecting the class:

```php
if (Html5DomParser::isHtml5ParserSupported()) {
    $dom = Html5DomParser::str_get_html($html);
} else {
    $dom = HtmlDomParser::str_get_html($html); // explicit application decision
}
```

Parsing throws a `RuntimeException` when the HTML5 backend is unavailable or when its normalized
tree cannot be represented by the legacy `\\DOMDocument` XML bridge (for example an HTML-valid
attribute name that XML cannot represent). Use `HtmlDomParser` explicitly if legacy parsing is
the intended fallback. A successful `Html5DomParser` parse therefore always means HTML5 tree
construction actually happened.
'''
    replace_once(path, old, new)


def patch_changelog() -> None:
    path = 'CHANGELOG'
    old = '''[PHP Simple HTML Dom - upcoming]
1: add "Html5DomParser": HTML5 parsing on PHP >= 8.4 via "\\Dom\\HTMLDocument", as its own class next to "HtmlDomParser" and with the same API
2: "Html5DomParser" -> "isHtml5ParserSupported()", "getIsDOMDocumentCreatedWithHtml5Parser()" and "getHtml5ParserFallbackReason()" report which parser produced a document and why
3: "HtmlDomParser" -> unchanged behavior; it only gained the protected extension points "createDOMDocumentFromPreparedHtml()" and "serializeDocumentWithoutHtmlWrapper()"
4: add "tests/Html5DomParserCompatibilityTest.php": the HtmlDomParser test suite run against Html5DomParser, with every HTML5 difference pinned
5: add "build/benchmark_html5_parser.php" to compare both parsers (interleaved samples, median, parse/selector/serialize phases, peak memory)
'''
    new = '''[PHP Simple HTML Dom - upcoming]
1: add "Html5DomParser": strict HTML5 parsing on PHP >= 8.4 via "\\Dom\\HTMLDocument", as its own class next to "HtmlDomParser" and with the same API layout
2: "Html5DomParser" -> never silently falls back to libxml; use "isHtml5ParserSupported()" before selecting it on older runtimes, and parsing throws if the HTML5 tree cannot cross the legacy DOMDocument bridge
3: "Html5DomParser" -> only reverses output placeholders its backend actually created, preserving literal legacy protection tokens such as "%5B%5B" and "%40"
4: "HtmlDomParser" -> unchanged behavior; it only gained protected backend / serialization extension points used by Html5DomParser
5: add "tests/Html5DomParserCompatibilityTest.php": the HtmlDomParser test suite run against Html5DomParser, with every intentional HTML5 difference pinned
6: add "build/benchmark_html5_parser.php" to compare both parsers using the PR #146 method (interleaved samples, discarded warm-up, median, phase timings, peak memory)
'''
    replace_once(path, old, new)


def patch() -> None:
    patch_html_dom_parser()
    patch_html5_dom_parser()
    patch_availability_test()
    patch_html5_tests()
    patch_compatibility_test()
    patch_benchmark()
    patch_readme()
    patch_changelog()

    for file in [
        'src/voku/helper/HtmlDomParser.php',
        'src/voku/helper/Html5DomParser.php',
        'tests/Html5DomParserAvailabilityTest.php',
        'tests/Html5DomParserTest.php',
        'tests/Html5DomParserCompatibilityTest.php',
        'build/benchmark_html5_parser.php',
    ]:
        subprocess.run(['php', '-l', file], cwd=ROOT, check=True)
    subprocess.run(['git', 'diff', '--check'], cwd=ROOT, check=True)


if len(sys.argv) != 2 or sys.argv[1] not in {'prepare', 'patch'}:
    raise SystemExit('usage: pr150-finalize-html5.py prepare|patch')

if sys.argv[1] == 'prepare':
    prepare_card()
else:
    patch()
