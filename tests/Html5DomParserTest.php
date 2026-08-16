<?php

use voku\helper\Html5DomParser;
use voku\helper\HtmlDomParser;

/**
 * Behavior of the opt-in HTML5 parser of PHP >= 8.4 ("\Dom\HTMLDocument").
 *
 * @internal
 */
final class Html5DomParserTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        if (!Html5DomParser::isHtml5ParserSupported()) {
            static::markTestSkipped('The HTML5 parser needs PHP >= 8.4 with "\Dom\HTMLDocument".');
        }
    }

    public function testTheParserIsChosenByClassNotByAFlag()
    {
        $html = '<table><tr><td>x</table>';

        $legacy = new HtmlDomParser($html);
        static::assertCount(0, $legacy->findMulti('tbody'));
        static::assertFalse(\method_exists($legacy, 'useHtml5Parser'));

        $html5 = new Html5DomParser($html);
        static::assertTrue($html5->getIsDOMDocumentCreatedWithHtml5Parser());
        static::assertCount(1, $html5->findMulti('tbody'));
    }

    public function testItIsAHtmlDomParser()
    {
        $dom = new Html5DomParser('<div>x</div>');

        static::assertInstanceOf(HtmlDomParser::class, $dom);
        static::assertInstanceOf(\voku\helper\DomParserInterface::class, $dom);
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

    public function testXmlnsSubstringWithoutXmlnsAttributeNeedsNoTransportMarker()
    {
        $dom = $this->html5('<div data-xmlns="caller-value">x</div>');
        $div = $dom->findOne('div');

        static::assertTrue($dom->getIsDOMDocumentCreatedWithHtml5Parser());
        static::assertSame('caller-value', $div->getAttribute('data-xmlns'));
        static::assertSame('<div data-xmlns="caller-value">x</div>', $dom->html());
    }

    public function testXmlBridgeFailureIsExplicitInsteadOfChangingParserSemantics()
    {
        $this->expectException(\RuntimeException::class);
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

    public function testKeepBrokenHtmlDoesNotDisableTheHtml5Parser()
    {
        $dom = new Html5DomParser();
        $dom->useKeepBrokenHtml(true);
        $dom->loadHtml('<script async src="cdnjs"></script></borken foo="lall"><table><tr><td>c</table>');

        // the broken fragment survives verbatim ...
        static::assertTrue($dom->getIsDOMDocumentCreatedWithHtml5Parser());
        static::assertStringContainsString('</borken foo="lall">', $dom->innerHtml);

        // ... and the same document still got HTML5 tree construction
        static::assertCount(1, $dom->findMulti('tbody'));
    }

    public function testKeepBrokenHtmlKeepsTheBrokenFragmentAtTheBeginOfTheInput()
    {
        $dom = new Html5DomParser();
        $dom->useKeepBrokenHtml(true);
        $dom->loadHtml('</script><script src="cdnjs"></script>');

        static::assertTrue($dom->getIsDOMDocumentCreatedWithHtml5Parser());
        static::assertSame('</script><script src="cdnjs"></script>', $dom->innerHtml);
    }

    /**
     * The preserved fragment is a text placeholder while the document is parsed, and HTML5
     * tree construction moves text out of a table - a browser does the same. The fragment is
     * kept, its position is not.
     */
    public function testKeepBrokenHtmlInsideATableMovesTheFragmentOutOfTheTable()
    {
        $html = '<table><tr><td>x</td></tr></borken foo="1"></table>';

        $legacy = new HtmlDomParser();
        $legacy->useKeepBrokenHtml(true);
        $legacy->loadHtml($html);
        static::assertSame($html, $legacy->innerHtml);

        $dom = new Html5DomParser();
        $dom->useKeepBrokenHtml(true);
        $dom->loadHtml($html);

        static::assertTrue($dom->getIsDOMDocumentCreatedWithHtml5Parser());
        static::assertSame(
            '</borken foo="1"><table><tbody><tr><td>x</td></tr></tbody></table>',
            $dom->innerHtml
        );
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
        $dom = new Html5DomParser();
        $dom->loadHtml(
            '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">'
            . '</head><body><p>' . \chr(0xE4) . '</p></body></html>'
        );

        static::assertSame('ä', $dom->findOne('p')->text());
    }

    public function testTheSameInstanceKeepsParsingWithHtml5()
    {
        $dom = new Html5DomParser();

        $dom->loadHtml('<table><tr><td>x</table>');
        static::assertTrue($dom->getIsDOMDocumentCreatedWithHtml5Parser());

        $dom->loadHtml('<ul><li>one<li>two</ul>');

        static::assertTrue($dom->getIsDOMDocumentCreatedWithHtml5Parser());
        static::assertSame('<ul><li>one</li><li>two</li></ul>', $dom->html());
    }

    public function testTheStaticEntryPointsReturnThisParser()
    {
        $dom = Html5DomParser::str_get_html('<table><tr><td>x</table>');

        static::assertInstanceOf(Html5DomParser::class, $dom);
        static::assertTrue($dom->getIsDOMDocumentCreatedWithHtml5Parser());
        static::assertCount(1, $dom->findMulti('tbody'));

        $fromFile = Html5DomParser::file_get_html(__DIR__ . '/fixtures/test_page.html');

        static::assertInstanceOf(Html5DomParser::class, $fromFile);
        static::assertTrue($fromFile->getIsDOMDocumentCreatedWithHtml5Parser());

        static::assertNotInstanceOf(
            Html5DomParser::class,
            HtmlDomParser::str_get_html('<table><tr><td>x</table>')
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
     * @return Html5DomParser
     */
    private function html5(string $html): Html5DomParser
    {
        $dom = new Html5DomParser();
        $dom->loadHtml($html);

        return $dom;
    }
}
