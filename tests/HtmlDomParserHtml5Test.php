<?php

use voku\helper\HtmlDomParser;

/**
 * Behavior of the opt-in HTML5 parser of PHP >= 8.4 ("\Dom\HTMLDocument").
 *
 * @internal
 */
final class HtmlDomParserHtml5Test extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        if (!HtmlDomParser::isHtml5ParserSupported()) {
            static::markTestSkipped('The HTML5 parser needs PHP >= 8.4 with "\Dom\HTMLDocument".');
        }
    }

    protected function tearDown(): void
    {
        if (HtmlDomParser::isHtml5ParserSupported()) {
            HtmlDomParser::useHtml5ParserByDefault(false);
        }
    }

    public function testIsOptInAndOffByDefault()
    {
        $dom = new HtmlDomParser('<table><tr><td>x</table>');

        static::assertFalse($dom->getIsDOMDocumentCreatedWithHtml5Parser());
        static::assertCount(0, $dom->findMulti('tbody'));
    }

    public function testUseHtml5ParserIsFluent()
    {
        $dom = new HtmlDomParser();

        static::assertSame($dom, $dom->useHtml5Parser());
        static::assertSame($dom, $dom->useHtml5Parser(false));
    }

    public function testImpliedTableSectionIsCreated()
    {
        $dom = $this->html5('<table><tr><td>x</table>');

        static::assertTrue($dom->getIsDOMDocumentCreatedWithHtml5Parser());
        static::assertSame('<table><tbody><tr><td>x</td></tr></tbody></table>', $dom->html());
        static::assertCount(1, $dom->findMulti('tbody'));
        static::assertSame('x', $dom->findOne('table tbody tr td')->text());
    }

    public function testParagraphsAreAutoClosedInsteadOfNested()
    {
        $dom = $this->html5('<p>a<p>b');

        static::assertSame('<p>a</p><p>b</p>', $dom->html());
        static::assertCount(2, $dom->findMulti('p'));
        static::assertCount(0, $dom->findMulti('p p'));
    }

    public function testListItemsAreAutoClosed()
    {
        $dom = $this->html5('<ul><li>one<li>two</ul>');

        static::assertSame('<ul><li>one</li><li>two</li></ul>', $dom->html());
        static::assertCount(2, $dom->findMulti('ul > li'));
    }

    public function testMisnestedFormattingTagsAreRecoveredLikeInABrowser()
    {
        $dom = $this->html5('<b>1<p>2</b>3</p>');

        static::assertSame('<b>1</b><p><b>2</b>3</p>', $dom->html());
    }

    public function testTagNamesAreLowercased()
    {
        $dom = $this->html5('<DIV CLASS="x">a</DIV>');

        static::assertSame('<div class="x">a</div>', $dom->html());
        static::assertCount(1, $dom->findMulti('div.x'));
    }

    public function testSelectorsWorkWithoutNamespaceHandling()
    {
        $dom = $this->html5('<div id="a" class="c"><span data-x="1">t</span></div>');

        static::assertSame('t', $dom->findOne('#a .c span, span')->text());
        static::assertSame('1', $dom->findOne('div span')->getAttribute('data-x'));
        static::assertSame('', $dom->getDocument()->documentElement->namespaceURI ?? '');
    }

    public function testXmlnsAttributeSurvivesTheXmlTransportWithoutNamespacingElements()
    {
        $dom = $this->html5('<div xmlns="urn:example"><span>x</span></div>');
        $div = $dom->findOne('div');

        static::assertTrue($dom->getIsDOMDocumentCreatedWithHtml5Parser());
        static::assertSame('urn:example', $div->getAttribute('xmlns'));
        static::assertSame('', $div->getNode()->namespaceURI ?? '');
        static::assertSame('x', $dom->findOne('div span')->text());
        static::assertSame('<div xmlns="urn:example"><span>x</span></div>', $dom->html());
    }

    public function testXmlnsBridgePreservesCallerOwnedTransportAttribute()
    {
        $dom = $this->html5('<div xmlns="urn:example" data-simplevokuxmlns="caller-value"><span>x</span></div>');
        $div = $dom->findOne('div');

        static::assertTrue($dom->getIsDOMDocumentCreatedWithHtml5Parser());
        static::assertSame('urn:example', $div->getAttribute('xmlns'));
        static::assertSame('caller-value', $div->getAttribute('data-simplevokuxmlns'));
        static::assertSame('', $div->getNode()->namespaceURI ?? '');
        static::assertSame('x', $dom->findOne('div span')->text());
    }

    public function testDocumentStaysALegacyDomDocument()
    {
        $dom = $this->html5('<div>x</div>');

        static::assertInstanceOf(\DOMDocument::class, $dom->getDocument());
        static::assertInstanceOf(\DOMNode::class, $dom->findOne('div')->getNode());
    }

    public function testFragmentIsNotWrappedInTheGeneratedDocumentElements()
    {
        $dom = $this->html5('<div>foo</div><div>bar</div>');

        static::assertSame('<div>foo</div><div>bar</div>', $dom->html());
    }

    public function testCommentOnlyInputIsPreserved()
    {
        $dom = $this->html5('<!--<p>Hello, World!</p>-->');

        static::assertSame('<!--<p>Hello, World!</p>-->', $dom->html());
    }

    public function testScriptContentIsPreservedVerbatim()
    {
        $dom = $this->html5('<div><script>var a = 1 < 2 && "</div>";</script></div>');

        static::assertSame('<div><script>var a = 1 < 2 && "</div>";</script></div>', $dom->html());
    }

    public function testForeignContentIsPreserved()
    {
        $dom = $this->html5('<div><svg viewBox="0 0 1 1"><circle r="1"/></svg></div>');

        static::assertCount(1, $dom->findMulti('svg'));
        static::assertSame('0 0 1 1', $dom->findOne('svg')->getAttribute('viewBox'));
    }

    public function testEncodingIsDetectedFromTheDocumentLikeInABrowser()
    {
        $dom = (new HtmlDomParser())->useHtml5Parser();
        $dom->loadHtml(
            '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">'
            . '</head><body><p>' . \chr(0xE4) . '</p></body></html>'
        );

        static::assertSame('ä', $dom->findOne('p')->text());
    }

    public function testKeepBrokenHtmlFallsBackToTheLegacyParser()
    {
        $dom = (new HtmlDomParser())->useHtml5Parser();
        $dom->useKeepBrokenHtml(true);
        $dom->loadHtml('<p>a</p>');

        static::assertFalse($dom->getIsDOMDocumentCreatedWithHtml5Parser());
    }

    public function testTheParserFlagIsResetWhenTheParserIsTurnedOffAgain()
    {
        $dom = (new HtmlDomParser())->useHtml5Parser();
        $dom->loadHtml('<table><tr><td>x</table>');
        static::assertTrue($dom->getIsDOMDocumentCreatedWithHtml5Parser());

        $dom->useHtml5Parser(false);
        $dom->loadHtml('<table><tr><td>x</table>');

        static::assertFalse($dom->getIsDOMDocumentCreatedWithHtml5Parser());
        static::assertCount(0, $dom->findMulti('tbody'));
    }

    public function testTheDefaultAppliesToTheStaticEntryPoints()
    {
        HtmlDomParser::useHtml5ParserByDefault(true);

        $dom = HtmlDomParser::str_get_html('<table><tr><td>x</table>');

        static::assertTrue($dom->getIsDOMDocumentCreatedWithHtml5Parser());
        static::assertCount(1, $dom->findMulti('tbody'));

        HtmlDomParser::useHtml5ParserByDefault(false);

        static::assertFalse(
            HtmlDomParser::str_get_html('<table><tr><td>x</table>')->getIsDOMDocumentCreatedWithHtml5Parser()
        );
    }

    public function testEmptyInputDoesNotBreakTheParser()
    {
        $dom = $this->html5('');

        static::assertSame('', $dom->html());
    }

    public function testTextOnlyInputIsNotWrappedInAParagraph()
    {
        $dom = $this->html5('foo');

        static::assertSame('foo', $dom->html());
    }

    /**
     * @param string $html
     *
     * @return HtmlDomParser
     */
    private function html5(string $html): HtmlDomParser
    {
        $dom = new HtmlDomParser();
        $dom->useHtml5Parser();
        $dom->loadHtml($html);

        return $dom;
    }
}
