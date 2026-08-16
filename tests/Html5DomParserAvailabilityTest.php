<?php

use voku\helper\Html5DomParser;

/**
 * Runtime availability behavior of Html5DomParser.
 *
 * @internal
 */
final class Html5DomParserAvailabilityTest extends \PHPUnit\Framework\TestCase
{
    public function testItFallsBackWhenTheRuntimeDoesNotSupportTheHtml5Parser()
    {
        if (Html5DomParser::isHtml5ParserSupported()) {
            static::markTestSkipped('This fallback is exercised only when the PHP 8.4 HTML5 parser is unavailable.');
        }

        $dom = new Html5DomParser();
        $dom->loadHtml('<div>x</div>');

        static::assertFalse($dom->getIsDOMDocumentCreatedWithHtml5Parser());
        static::assertSame(Html5DomParser::FALLBACK_UNSUPPORTED_RUNTIME, $dom->getHtml5ParserFallbackReason());
        static::assertSame('x', $dom->findOne('div')->text());
        static::assertSame('<div>x</div>', $dom->html());
    }
}
