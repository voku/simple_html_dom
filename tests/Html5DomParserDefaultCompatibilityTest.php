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
    /**
     * Prevent the copied HTML5 compatibility suite from silently missing future legacy tests.
     */
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

    /**
     * Representative markup where callers should observe the same selector/text results.
     *
     * @return array<string, array{0: string, 1: string, 2: string[]}>
     */
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
     * SHD-2 evidence: HTML-valid attribute names that XML cannot represent directly must
     * survive the legacy DOM bridge through the public wrapper API and serialization.
     *
     * @dataProvider xmlBridgeBoundaryProvider
     *
     * @param string $html
     * @param string $selector
     * @param string $attribute
     * @param string $expectedValue
     */
    public function testXmlBridgePreservesHtmlOnlyAttributeNames(
        string $html,
        string $selector,
        string $attribute,
        string $expectedValue
    ) {
        $this->requireHtml5Parser();

        $dom = Html5DomParser::str_get_html($html);
        $element = $dom->findOne($selector);

        static::assertTrue($element->hasAttribute($attribute));
        static::assertSame($expectedValue, $element->getAttribute($attribute));
        static::assertArrayHasKey($attribute, $element->getAllAttributes());
    }

    /**
     * HTML-valid attribute names that require placeholder transport through legacy DOM.
     *
     * @return array<string, array{0: string, 1: string, 2: string, 3: string}>
     */
    public function xmlBridgeBoundaryProvider()
    {
        return [
            'at-sign attribute' => ['<div @foo="bar">x</div>', 'div', '@foo', 'bar'],
            'comma attribute from horrible fixture class' => ['<font size="4" ,="" color="red">x</font>', 'font', ',', ''],
        ];
    }

    /**
     * SHD-3 evidence: a node originating from Html5DomParser currently exposes a legacy
     * HtmlDomParser for follow-up parsing. A future default switch must decide this boundary
     * deliberately rather than changing it as a side effect.
     */
    /**
     * Pin the current parser-context boundary for nodes created by Html5DomParser.
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

    /**
     * Existing HTML fixtures, including the historical malformed-attribute fixture.
     *
     * @return array<string, array{0: string}>
     */
    public function htmlFixtureCorpusProvider()
    {
        $fixtures = \glob(__DIR__ . '/fixtures/*.html');
        static::assertNotFalse($fixtures);

        $cases = [];
        foreach ($fixtures as $fixture) {
            $cases[\basename($fixture)] = [$fixture];
        }

        return $cases;
    }

    /**
     * Skip runtime-dependent evidence on PHP versions without the HTML5 backend.
     */
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
