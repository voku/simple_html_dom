<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use voku\helper\HtmlDomParser;

final class HtmlSerializationRegressionTest extends TestCase
{
    public function testNestedElementHtmlDoesNotIntroduceFormattingNewlines(): void
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

        static::assertSame($html, $dom->find('.mydiv', 0)->html);
        static::assertSame(
            '<div class="mydiv-item">
        B:
        <div><span>B1</span><span>B2</span></div>
    </div>',
            $dom->find('.mydiv-item', 1)->html
        );
    }
}
