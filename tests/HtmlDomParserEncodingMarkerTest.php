<?php

use PHPUnit\Framework\TestCase;
use voku\helper\HtmlDomParser;

/**
 * @internal
 */
final class HtmlDomParserEncodingMarkerTest extends TestCase
{
    public function testSyntheticEncodingMarkerDoesNotLeakIntoHtml(): void
    {
        $parser = HtmlDomParser::str_get_html('<html><body><br></body></html>');

        static::assertSame(0, \substr_count($parser->html(), '<!--?xml encoding="UTF-8" ?-->'));
        static::assertSame(1, \count($parser->find('br')));
    }

    public function testRealEncodingLikeCommentIsNotDiscarded(): void
    {
        $parser = HtmlDomParser::str_get_html(
            '<html><body><!--?xml encoding="UTF-8" ?--><br></body></html>'
        );

        static::assertSame(1, \substr_count($parser->html(), '<!--?xml encoding="UTF-8" ?-->'));
    }
}
