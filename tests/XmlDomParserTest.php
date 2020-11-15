<?php

use voku\helper\XmlDomParser;

/**
 * @internal
 */
final class XmlDomParserTest extends \PHPUnit\Framework\TestCase
{
    public function testXml()
    {
        $filename = __DIR__ . '/fixtures/test_xml.xml';
        $filenameExpected = __DIR__ . '/fixtures/test_xml_expected.xml';

        $xml = XmlDomParser::file_get_xml($filename);
        $xmlExpected = \str_replace(["\r\n", "\r", "\n"], "\n", \file_get_contents($filenameExpected));

        // object to sting
        static::assertSame(
            $xmlExpected,
            \str_replace(["\r\n", "\r", "\n"], "\n", (string) $xml)
        );
    }

    public function testError()
    {
        $this->expectException(InvalidArgumentException::class);

        $content = '<xml>broken xml<foo</xml>';

        $xmlParser = new \voku\helper\XmlDomParser();
        $xmlParser->reportXmlErrorsAsException();
        $xmlParser->loadXml($content);
    }

    public function testErrorWithoutException()
    {
        $content = '<xml>broken xml<foo</xml>';

        $xmlParser = new \voku\helper\XmlDomParser();
        $xmlParser->reportXmlErrorsAsException(false);
        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        @$xmlParser->loadXml($content);

        static::assertSame('', $xmlParser->xml());
    }

    public function testXmlFind()
    {
        $xmlParser = new \voku\helper\XmlDomParser();
        $xmlParser->autoRemoveXPathNamespaces();
        $xmlParser->setCallbackBeforeCreateDom(
            static function (string $str, \voku\helper\XmlDomParser $xmlParser) {
                return \str_replace('array', 'arrayy', $str);
            }
        );
        $xmlParser->setCallbackXPathBeforeQuery(
            static function (string $cssSelectorString, string $xPathString, \DOMXPath $xPath, \voku\helper\XmlDomParser $xmlParser) {
                return $cssSelectorString === 'methodsynopsis' ? '//methodsynopsis' : $xPathString;
            }
        );

        $filename = __DIR__ . '/fixtures/test_xml_complex.xml';
        $content = \file_get_contents($filename);

        $xml = $xmlParser->loadXml($content);
        $data = $xml->find('methodsynopsis');
        $types = $data->find('type.union type');

        static::assertSame('arrayy', $types[0]->text());
        static::assertSame('false', $types[1]->text());
    }

    public function testXmlReplace()
    {
        $filename = __DIR__ . '/fixtures/test_xml.xml';
        $filenameExpected = __DIR__ . '/fixtures/test_xml_replace_expected.xml';

        $xml = XmlDomParser::file_get_xml($filename);
        $xmlExpected = \str_replace(["\r\n", "\r", "\n"], "\n", \file_get_contents($filenameExpected));

        $xml->replaceTextWithCallback(static function ($oldValue) {
            if (!\trim($oldValue)) {
                return $oldValue;
            }

            return \htmlspecialchars($oldValue, \ENT_XML1);
        });

        // object to sting
        static::assertSame(
            $xmlExpected,
            \str_replace(["\r\n", "\r", "\n"], "\n", (string) $xml)
        );
    }

    public function testXmlWithNamespace()
    {
        $xml = <<<'EOD'
<book xmlns:chap="http://example.org/chapter-title">
    <title>My Book</title>
    <chapter id="1">
        <chap:title>Chapter 1</chap:title>
        <para>Donec velit. Nullam eget tellus vitae tortor gravida scelerisque.
            In orci lorem, cursus imperdiet, ultricies non, hendrerit et, orci.
            Nulla facilisi. Nullam velit nisl, laoreet id, condimentum ut,
            ultricies id, mauris.</para>
    </chapter>
    <chapter id="2">
        <chap:title>Chapter 2</chap:title>
        <para>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Proin
            gravida. Phasellus tincidunt massa vel urna. Proin adipiscing quam
            vitae odio. Sed dictum. Ut tincidunt lorem ac lorem. Duis eros
            tellus, pharetra id, faucibus eu, dapibus dictum, odio.</para>
    </chapter>
</book>
EOD;

        $xmlParser = XmlDomParser::str_get_xml($xml);

        static::assertSame('Chapter 1', $xmlParser->findOne('//chap:title')->getNode()->textContent);

        $chapters = $xmlParser->findMulti('chapter');
        static::assertSame(2, $chapters->count());
        static::assertCount(2, $chapters);

        static::assertFalse($xmlParser->findOneOrFalse('//chap:foo'));

        static::assertFalse($xmlParser->findMultiOrFalse('foo'));

        $foo = $xmlParser->findMulti('foo');
        static::assertSame(0, $foo->count());
        static::assertCount(0, $foo);
    }
}
