<?php

use voku\helper\Html5DomParser;

/**
 * Runtime availability behavior of Html5DomParser.
 *
 * @internal
 */
final class Html5DomParserAvailabilityTest extends \PHPUnit\Framework\TestCase
{
    public function testItFailsExplicitlyWhenTheRuntimeDoesNotSupportTheHtml5Parser()
    {
        if (Html5DomParser::isHtml5ParserSupported()) {
            static::markTestSkipped('This guard is exercised only when the PHP 8.4 HTML5 parser is unavailable.');
        }

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Html5DomParser requires PHP >= 8.4');

        $dom = new Html5DomParser();
        $dom->loadHtml('<div>x</div>');
    }
}
