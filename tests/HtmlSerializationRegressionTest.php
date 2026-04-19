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
}
