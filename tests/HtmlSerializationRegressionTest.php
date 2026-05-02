<?php

use voku\helper\HtmlDomParser;

/**
 * @internal
 */
final class HtmlSerializationRegressionTest extends \PHPUnit\Framework\TestCase
{
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

    public function testSerializeElementNodeDoesNotAppendTrailingNewlines()
    {
        $document = HtmlDomParser::str_get_html(
            '<div><span>one</span><br><p>two</p><template id="card"><section><h2>Title</h2><p>Body</p></section></template></div>'
        );

        $serializeElementNodeForPhpLt8 = new \ReflectionMethod(HtmlDomParser::class, 'serializeElementNodeForPhpLt8');
        if (\PHP_VERSION_ID < 80100) {
            // This version check is only for Reflection behavior: private method
            // access still needs setAccessible() before PHP 8.1.
            $serializeElementNodeForPhpLt8->setAccessible(true);
        }

        static::assertSame(
            '<span>one</span><br><p>two</p>',
            $serializeElementNodeForPhpLt8->invoke($document, $document->getElementByTagName('span')->getNode())
            . $serializeElementNodeForPhpLt8->invoke($document, $document->getElementByTagName('br')->getNode())
            . $serializeElementNodeForPhpLt8->invoke($document, $document->getElementByTagName('p')->getNode())
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
