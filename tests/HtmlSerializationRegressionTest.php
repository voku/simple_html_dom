<?php

use voku\helper\HtmlDomParser;

/**
 * @internal
 */
final class HtmlSerializationRegressionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public function provideEdgeCaseDocumentHtml(): array
    {
        return [
            'custom non-html tag' => [
                '<custom-tag data-x="1"><span>A</span></custom-tag>',
                '<custom-tag data-x="1"><span>A</span></custom-tag>',
            ],
            'invalid html is normalized' => [
                '<div><span>alpha</div>',
                '<div><span>alpha</span></div>',
            ],
            'html5 implicit paragraph closing' => [
                '<p>one<p>two',
                '<p>one</p><p>two</p>',
            ],
            'chained paragraph roots' => [
                '<p>one</p><p>two</p><p>three</p>',
                '<p>one</p><p>two</p><p>three</p>',
            ],
        ];
    }

    /**
     * @return array<string, array{string, string, int, string, string}>
     */
    public function provideNodeBackedEdgeCases(): array
    {
        return [
            'custom non-html tag' => [
                '<custom-tag data-x="1"><span>A</span></custom-tag>',
                'custom-tag',
                0,
                '<custom-tag data-x="1"><span>A</span></custom-tag>',
                '<span>A</span>',
            ],
            'only p tag root' => [
                '<p>alpha</p>',
                'p',
                0,
                '<p>alpha</p>',
                'alpha',
            ],
            'only div tag root' => [
                '<div>alpha</div>',
                'div',
                0,
                '<div>alpha</div>',
                'alpha',
            ],
            'invalid html normalized div' => [
                '<div><span>alpha</div>',
                'div',
                0,
                '<div><span>alpha</span></div>',
                '<span>alpha</span>',
            ],
            'html5 implicit paragraph closing first p' => [
                '<p>one<p>two',
                'p',
                0,
                '<p>one</p>',
                'one',
            ],
            'html5 implicit paragraph closing second p' => [
                '<p>one<p>two',
                'p',
                1,
                '<p>two</p>',
                'two',
            ],
            'chained paragraph middle root' => [
                '<p>one</p><p>two</p><p>three</p>',
                'p',
                1,
                '<p>two</p>',
                'two',
            ],
        ];
    }

    /**
     * @dataProvider provideEdgeCaseDocumentHtml
     */
    public function testDocumentHtmlRoundTripsSerializationEdgeCases(string $html, string $expectedHtml)
    {
        static::assertSame($expectedHtml, HtmlDomParser::str_get_html($html)->html());
    }

    /**
     * @dataProvider provideNodeBackedEdgeCases
     */
    public function testNodeBackedHtmlHandlesSerializationEdgeCases(
        string $html,
        string $selector,
        int $index,
        string $expectedHtml,
        string $expectedInnerHtml
    ) {
        $document = HtmlDomParser::str_get_html($html);
        $element = $document->find($selector, $index);
        $parser = new HtmlDomParser($element->getNode());

        static::assertSame($expectedHtml, $parser->html());
        static::assertSame($expectedInnerHtml, $parser->innerHtml());
    }

    public function testHtmlDomParserConstructedFromExistingNodePreservesNestedMarkupWithoutInjectedNewlines()
    {
        $html = '<div class="mydiv"><div class="mydiv-item">A1</div><div class="mydiv-item"><span>B1</span><span>B2</span></div></div>';

        $document = HtmlDomParser::str_get_html($html);
        $element = $document->find('.mydiv-item', 1);
        $parser = new HtmlDomParser($element);

        static::assertSame(
            '<div class="mydiv-item"><span>B1</span><span>B2</span></div>',
            $parser->html()
        );
    }

    public function testElementHtmlPreservesWhitespaceWithoutExtraLineBreaks()
    {
        $html = '<div class="mydiv">
    <div class="mydiv-item">
        A:
        <span>A</span>
    </div>
    <div class="mydiv-item">
        B:
        <div><span>B1</span><span>B2</span></div>
    </div>
</div>';

        $document = HtmlDomParser::str_get_html($html);
        $outerElement = $document->find('.mydiv', 0);
        $nestedItem = $document->find('.mydiv-item', 1);
        $nestedItemHtml = '<div class="mydiv-item">
        B:
        <div><span>B1</span><span>B2</span></div>
    </div>';

        static::assertSame($html, $outerElement->html);
        static::assertSame($nestedItemHtml, $nestedItem->html);
        static::assertSame($nestedItemHtml, (new HtmlDomParser($nestedItem->getNode()))->html());
    }

    public function testHtmlDomParserConstructedFromSimpleHtmlDomPreservesNestedMarkup()
    {
        $html = '<div class="wrapper"><div class="target"><span>first</span><span>second</span></div></div>';

        $document = HtmlDomParser::str_get_html($html);
        $element = $document->find('.target', 0);
        $parser = new HtmlDomParser($element);

        static::assertSame(
            '<div class="target"><span>first</span><span>second</span></div>',
            $parser->html()
        );
    }

    public function testNodeBackedInnerHtmlPreservesChildrenFormatting()
    {
        $html = '<div class="target">before<span>middle</span><strong>after</strong></div>';

        $document = HtmlDomParser::str_get_html($html);
        $element = $document->find('.target', 0);
        $parser = new HtmlDomParser($element->getNode());

        static::assertSame('before<span>middle</span><strong>after</strong>', $parser->innerHtml());
    }

    public function testSerializeElementNodeDoesNotAppendTrailingNewline()
    {
        if (\PHP_VERSION_ID >= 80000) {
            static::markTestSkipped('serializeElementNodeForPhpLt8() is only used on PHP < 8.0.');
        }

        $document = HtmlDomParser::str_get_html(
            '<div><span>one</span><br><p>two</p><template id="card"><section><h2>Title</h2><p>Body</p></section></template></div>'
        );

        $serializeElementNodeForPhpLt8 = new \ReflectionMethod(HtmlDomParser::class, 'serializeElementNodeForPhpLt8');
        if (\PHP_VERSION_ID < 80100) {
            // This version check is only for Reflection behavior: private method
            // access still needs setAccessible() when PHP_VERSION_ID < 80100
            // (PHP 8.0 and earlier).
            $serializeElementNodeForPhpLt8->setAccessible(true);
        }

        $spanHtml = $serializeElementNodeForPhpLt8->invoke($document, $document->getElementByTagName('span')->getNode());
        $brHtml = $serializeElementNodeForPhpLt8->invoke($document, $document->getElementByTagName('br')->getNode());
        $pHtml = $serializeElementNodeForPhpLt8->invoke($document, $document->getElementByTagName('p')->getNode());

        static::assertSame(
            '<span>one</span><br><p>two</p>',
            $spanHtml . $brHtml . $pHtml
        );
        static::assertSame(
            '<template id="card"><section><h2>Title</h2><p>Body</p></section></template>',
            $serializeElementNodeForPhpLt8->invoke($document, $document->findOne('template')->getNode())
        );
    }

    public function testNodeBackedTextNodeHtmlPreservesTextVerbatim()
    {
        $document = HtmlDomParser::str_get_html('<div>before<span>middle</span>after</div>');
        $textNode = $document->find('div', 0)->getNode()->childNodes->item(0);

        static::assertSame('before', (new HtmlDomParser($textNode))->html());
    }
}
