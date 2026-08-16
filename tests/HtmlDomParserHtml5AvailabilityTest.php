<?php

use voku\helper\HtmlDomParser;

/**
 * Runtime availability behavior for the opt-in HTML5 parser.
 *
 * @internal
 */
final class HtmlDomParserHtml5AvailabilityTest extends \PHPUnit\Framework\TestCase
{
    public function testOptInFallsBackWhenRuntimeDoesNotSupportHtml5Parser()
    {
        if (HtmlDomParser::isHtml5ParserSupported()) {
            static::markTestSkipped('This fallback is exercised only when the PHP 8.4 HTML5 parser is unavailable.');
        }

        $dom = (new HtmlDomParser())->useHtml5Parser();
        $dom->loadHtml('<div>x</div>');

        static::assertFalse($dom->getIsDOMDocumentCreatedWithHtml5Parser());
        static::assertSame('x', $dom->findOne('div')->text());
    }
}
