<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use voku\helper\HtmlDomParser;

final class HtmlSerializationRegressionTest extends TestCase
{
    public function testNestedElementHtmlPreservesWhitespaceWithoutExtraLineBreaks(): void
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

        $dom = HtmlDomParser::str_get_html($html);
        $nestedItem = $dom->find('.mydiv-item', 1);
        $nestedItemHtml = '<div class="mydiv-item">
        B:
        <div><span>B1</span><span>B2</span></div>
    </div>';

        static::assertSame($html, $dom->find('.mydiv', 0)->html);
        static::assertSame($nestedItemHtml, $nestedItem->html);
        static::assertSame($nestedItemHtml, (new HtmlDomParser($nestedItem->getNode()))->html());
    }
}
