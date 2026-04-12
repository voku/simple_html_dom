<?php

use PHPUnit\Framework\TestCase;
use voku\helper\HtmlDomParser;
use voku\helper\XmlDomParser;

final class HtmlEntityDecodeFallbackTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testHtmlFallsBackWhenUtf8RawurldecodeThrowsForDefaultSerialization()
    {
        $this->requireThrowingUtf8Stub();

        $dom = HtmlDomParser::str_get_html('<div class="notice">Tea &amp; biscuits</div>');

        static::assertSame('<div class="notice">Tea &amp; biscuits</div>', $dom->html());
        static::assertSame('<div class="notice">Tea &amp; biscuits</div>', $dom->findOne('div')->html());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testHtmlFallsBackWhenUtf8RawurldecodeThrowsForMultiDecodeSerialization()
    {
        $this->requireThrowingUtf8Stub();

        $dom = HtmlDomParser::str_get_html('<div data-note="A &amp; B">Tea &amp; biscuits</div>');

        static::assertSame('<div data-note="A &amp; B">Tea &amp; biscuits</div>', $dom->html(true));
        static::assertSame('Tea &amp; biscuits', $dom->findOne('div')->innerHtml(true));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testXmlFallsBackWhenUtf8RawurldecodeThrows()
    {
        $this->requireThrowingUtf8Stub();

        $xml = XmlDomParser::str_get_xml('<root><item>A &amp; B</item></root>');

        static::assertSame('<root><item>A &amp; B</item></root>', \rtrim($xml->xml(), "\n"));
        static::assertSame('A &amp; B', $xml->findOne('item')->innerXml());
    }

    private function requireThrowingUtf8Stub(): void
    {
        if (\class_exists('\voku\helper\UTF8', false)) {
            $this->markTestSkipped('The UTF8 helper was already loaded before this test could install the throwing stub.');
        }

        require_once __DIR__ . '/fixtures/ThrowingUtf8Stub.php';
    }
}
