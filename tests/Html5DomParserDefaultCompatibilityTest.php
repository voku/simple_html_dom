<?php

use voku\helper\Html5DomParser;
use voku\helper\HtmlDomParser;

/**
 * Compatibility evidence for considering Html5DomParser as the default parser in a future
 * major release.
 *
 * These tests do not change parser selection. They pin the shared behavior we want to keep
 * and the two currently unresolved boundaries that must stay visible before a default switch.
 *
 * @internal
 */
final class Html5DomParserDefaultCompatibilityTest extends \PHPUnit\Framework\TestCase
{
    public function testCompatibilitySuiteTracksEveryLegacyParserTest()
    {
        $legacySource = \file_get_contents(__DIR__ . '/HtmlDomParserTest.php');
        $html5Source = \file_get_contents(__DIR__ . '/Html5DomParserCompatibilityTest.php');

        static::assertNotFalse($legacySource);
        static::assertNotFalse($html5Source);

        \preg_match_all('/public function (test[A-Za-z0-9_]+)\s*\(/', $legacySource, $legacyMatches);
        \preg_match_all('/public function (test[A-Za-z0-9_]+)\s*\(/', $html5Source, $html5Matches);

        $missing = \array_values(\array_diff($legacyMatches[1], $html5Matches[1]));
        \sort($missing);

        static::assertSame(
            [],
            $missing,
            'Every HtmlDomParser regression test must have an Html5DomParser compatibility counterpart.'
        );
    }

    /**
     * @dataProvider commonSelectorCompatibilityProvider
     *
     * @param string   $html
     * @param string   $selector
     * @param string[] $expectedTexts
     */
    public function testCommonSelectorResultsStayCompatible(string $html, string $selector, array $expectedTexts)
    {
        $this->requireHtml5Parser();

        $legacy = HtmlDomParser::str_get_html($html);
        $html5 = Html5DomParser::str_get_html($html);

        static::assertSame($expectedTexts, $this->textsForSelector($legacy, $selector));
        static::assertSame($expectedTexts, $this->textsForSelector($html5, $selector));
    }

    public function commonSelectorCompatibilityProvider()
    {
        return [
            'well-formed nested elements' => [
                '<div><p>one</p><p>two <strong>three</strong></p></div>',
                'p',
                ['one', 'two three'],
            ],
            'table tree construction' => [
                '<table><tr><td>one</td><td>two</td></tr></table>',
                'td',
                ['one', 'two'],
            ],
            'list items' => [
                '<ul><li>one</li><li>two</li></ul>',
                'li',
                ['one', 'two'],
            ],
            'select options' => [
                '<select><option value="1">one</option><option value="2">two</option></select>',
                'option',
                ['one', 'two'],
            ],
            'class and id selectors' => [
                '<main><article id="first" class="entry">one</article><article class="entry">two</article></main>',
                'article.entry',
                ['one', 'two'],
            ],
            'foreign content descendants' => [
                '<svg><g><text>one</text><text>two</text></g></svg>',
                'svg text',
                ['one', 'two'],
            ],
        ];
    }

    /**
     * SHD-2 evidence: these attribute names are valid input for the HTML parser but cannot
     * currently cross the XML transport used to expose the result as legacy DOMDocument.
     *
     * @dataProvider xmlBridgeBoundaryProvider
     *
     * @param string $html
     */
    public function testXmlBridgeBoundaryRemainsExplicit(string $html)
    {
        $this->requireHtml5Parser();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('could not bridge the normalized HTML5 document');

        Html5DomParser::str_get_html($html);
    }

    public function xmlBridgeBoundaryProvider()
    {
        return [
            'at-sign attribute' => ['<div @foo="bar">x</div>'],
            'comma attribute from horrible fixture class' => ['<font size="4" ,="" color="red">x</font>'],
        ];
    }

    /**
     * SHD-3 evidence: a node originating from Html5DomParser currently exposes a legacy
     * HtmlDomParser for follow-up parsing. A future default switch must decide this boundary
     * deliberately rather than changing it as a side effect.
     */
    public function testHtml5NodeParserContextCurrentlyFallsBackToLegacyParser()
    {
        $this->requireHtml5Parser();

        $dom = Html5DomParser::str_get_html('<div id="target"></div>');
        $target = $dom->findOne('#target');
        $parser = $target->getHtmlDomParser();

        static::assertInstanceOf(HtmlDomParser::class, $parser);
        static::assertNotInstanceOf(Html5DomParser::class, $parser);
    }

    /**
     * SHD-3 evidence for string mutations. HTML5 document parsing inserts tbody here, while
     * the current mutation path deliberately uses HtmlDomParser and therefore does not.
     */
    public function testInnerHtmlMutationCurrentlyUsesLegacyFragmentSemantics()
    {
        $this->requireHtml5Parser();

        $dom = Html5DomParser::str_get_html('<div id="target"></div>');
        $target = $dom->findOne('#target');

        $target->innerHtml = '<table><tr><td>x</td></tr></table>';

        static::assertCount(0, $target->findMulti('tbody'));
        static::assertSame('<table><tr><td>x</td></tr></table>', $target->innerHtml());
    }

    /**
     * Smoke-test the repository's accumulated HTML fixture corpus with both parsers.
     *
     * "horrible.html" is excluded here because SHD-2 deliberately pins its XML-invalid
     * attribute-name bridge failure above.
     *
     * @dataProvider htmlFixtureCorpusProvider
     *
     * @param string $fixture
     */
    public function testExistingHtmlFixtureCorpusLoadsWithBothParsers(string $fixture)
    {
        $this->requireHtml5Parser();

        $html = \file_get_contents($fixture);
        static::assertNotFalse($html);

        $legacy = HtmlDomParser::str_get_html($html);
        $html5 = Html5DomParser::str_get_html($html);

        static::assertInstanceOf(\DOMDocument::class, $legacy->getDocument());
        static::assertInstanceOf(\DOMDocument::class, $html5->getDocument());
        static::assertTrue($html5->getIsDOMDocumentCreatedWithHtml5Parser());
    }

    public function htmlFixtureCorpusProvider()
    {
        $fixtures = \glob(__DIR__ . '/fixtures/*.html');
        static::assertNotFalse($fixtures);

        $cases = [];
        foreach ($fixtures as $fixture) {
            if (\basename($fixture) === 'horrible.html') {
                continue;
            }

            $cases[\basename($fixture)] = [$fixture];
        }

        return $cases;
    }

    private function requireHtml5Parser(): void
    {
        if (!Html5DomParser::isHtml5ParserSupported()) {
            static::markTestSkipped('The HTML5 parser needs PHP >= 8.4 with "\\Dom\\HTMLDocument".');
        }
    }

    /**
     * @param HtmlDomParser $parser
     * @param string        $selector
     *
     * @return string[]
     */
    private function textsForSelector(HtmlDomParser $parser, string $selector): array
    {
        $texts = [];

        foreach ($parser->findMulti($selector) as $node) {
            $texts[] = $node->text();
        }

        return $texts;
    }
}
