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

    public function testAmazonCxml()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE cXML SYSTEM "http://xml.cxml.org/schemas/cXML/1.2.024/cXML.dtd">
<cXML payloadID="312312312.452.5972@amazon.com" timestamp="2021-03-17T14:45:25.845Z">
  <Header>
    <From>
      <Credential domain="DUNS">
        <Identity>123456</Identity>
      </Credential>
      <Credential domain="NetworkId">
        <Identity>Amazon</Identity>
      </Credential>
    </From>
    <To>
      <Credential domain="NetworkId">
        <Identity>312312312</Identity>
      </Credential>
    </To>
    <Sender>
      <Credential domain="DUNS">
        <Identity>123456</Identity>
      </Credential>
      <Credential domain="NetworkId">
        <Identity>Amazon</Identity>
      </Credential>
      <UserAgent>Amazon LLC eProcurement Application</UserAgent>
    </Sender>
  </Header>
  <Message>
    <PunchOutOrderMessage>
      <BuyerCookie>1234567</BuyerCookie>
      <PunchOutOrderMessageHeader operationAllowed="create">
        <Total>
          <Money currency="EUR">0.39</Money>
        </Total>
        <Shipping>
          <Money currency="EUR">0.00</Money>
          <Description xml:lang="de-DE">Versandkosten (Versandsteuern ausgeschlossen).</Description>
        </Shipping>
        <Tax>
          <Money currency="EUR">0.07</Money>
          <Description xml:lang="de-DE">Steuern, einschließlich Steuer für Versand</Description>
        </Tax>
      </PunchOutOrderMessageHeader>
      <ItemIn quantity="1">
        <ItemID>
          <SupplierPartID>B000KJR1A8</SupplierPartID>
          <SupplierPartAcurrencyuxiliaryID>260-22222-111111,1</SupplierPartAcurrencyuxiliaryID>
        </ItemID>
        <ItemDetail>
          <UnitPrice>
            <Money currency="EUR">0.39</Money>
          </UnitPrice>
          <Description xml:lang="de-DE">Schneider Schreibgeräte Kugelschreiber K 15, Druckmechanik, M, Grün, Farbe des Schaftes: grün</Description>
          <UnitOfMeasure>EA</UnitOfMeasure>
          <Classification domain="UNSPSC">44121704</Classification>
          <ManufacturerPartID>3084</ManufacturerPartID>
          <ManufacturerName>Stella</ManufacturerName>
          <Extrinsic name="soldBy">Amazon</Extrinsic>
          <Extrinsic name="fulfilledBy">Amazon</Extrinsic>
          <Extrinsic name="category">OFFICE_PRODUCTS</Extrinsic>
          <Extrinsic name="subCategory">WRITING_INSTRUMENT</Extrinsic>
          <Extrinsic name="itemCondition">New</Extrinsic>
          <Extrinsic name="qualifiedOffer">true</Extrinsic>
          <Extrinsic name="UPC">NA</Extrinsic>
          <Extrinsic name="detailPageURL">https://www.amazon.de/dp/B000KJR1A8</Extrinsic>
          <Extrinsic name="ean">4052305136881</Extrinsic>
          <Extrinsic name="preference">default</Extrinsic>
        </ItemDetail>
        <Shipping>
          <Money currency="EUR">0.00</Money>
          <Description xml:lang="de-DE">Versandkosten (Versandsteuern ausgeschlossen).</Description>
        </Shipping>
        <Tax>
          <Money currency="EUR">0.07</Money>
          <Description xml:lang="de-DE">Steuern, einschließlich Steuer für Versand</Description>
          <TaxDetail category="vat" percentageRate="19.00" purpose="subtotalTax">
            <TaxAmount>
              <Money currency="EUR">0.07</Money>
            </TaxAmount>
          </TaxDetail>
        </Tax>
      </ItemIn>
    </PunchOutOrderMessage>
  </Message>
</cXML>';

        $xmlParser = new \voku\helper\XmlDomParser();

        // "Attempt to load network entity"
        $xml = \preg_replace('#cXML SYSTEM "http://xml.cxml.org/schemas/cXML/[\d.]*/cXML.dtd"#', 'cXML', $xml);

        $xmlParsed = $xmlParser->loadXml($xml);

        $items = $xmlParsed->findMultiOrFalse('//ItemIn');
        if ($items !== false) {
            foreach ($items as $item) {
                static::assertSame('1', $item->getAttribute('quantity'));
                static::assertSame('B000KJR1A8', $item->findOne('//SupplierPartID')->text());
                static::assertSame('Schneider Schreibgeräte Kugelschreiber K 15, Druckmechanik, M, Grün, Farbe des Schaftes: grün', $item->findOne('//ItemDetail //Description')->text());
            }
        }
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

    public function testXmlFindV2()
    {
        $xmlParser = new \voku\helper\XmlDomParser();
        $xmlParser->autoRemoveXPathNamespaces();

        $filename = __DIR__ . '/fixtures/test_xml_complex_v2.xml';
        $content = \file_get_contents($filename);

        $xml = $xmlParser->loadXml($content);
        $data = $xml->find('classsynopsisinfo');
        $classname = $data->find('classname');

        static::assertSame('Closure', $classname[0]->text());
    }

    public function testXmlFindV21()
    {
        $xmlParser = new \voku\helper\XmlDomParser();

        $filename = __DIR__ . '/fixtures/test_xml_complex_v2.xml';
        $content = \file_get_contents($filename);

        $xml = $xmlParser->loadXml($content);

        static::assertTrue(\strpos($xml->xml(), 'classname>Closure</classname>') !== false);
    }

    public function testXmlFindV3()
    {
        $xmlParser = new \voku\helper\XmlDomParser();
        $xmlParser->autoRemoveXPathNamespaces();
        $xmlParser->reportXmlErrorsAsException();

        $filename = __DIR__ . '/fixtures/test_xml_complex_v3.xml';
        $content = \file_get_contents($filename);

        $xml = $xmlParser->loadXml($content);
        $data = $xml->find('methodsynopsis');
        $types = $data->find('type');

        static::assertSame('int', $types[0]->text());

        // ---

        $xml = $xmlParser->loadXml($content);
        $data = $xml->find('methodsynopsis');
        $types = $data->find('descendant-or-self::type');

        static::assertSame('int', $types[0]->text());
    }

    public function testIssue63()
    {
        $dom = (new voku\helper\XmlDomParser())->loadHtml('<Foo> foo bar </Foo>');
        static::assertSame(' foo bar ', $dom->findOne('Foo')->innerXml());
        static::assertInstanceOf(\voku\helper\SimpleXmlDomBlank::class, $dom->findOne('Bar'));
        static::assertInstanceOf(\voku\helper\SimpleXmlDomBlank::class, $dom->findOne('Bar')->findOne('Bar'));
        static::assertInstanceOf(\voku\helper\SimpleXmlDomBlank::class, $dom->findOne('Bar')->findOne('Bar')->findOne('Bar'));
        static::assertInstanceOf(\voku\helper\SimpleXmlDomBlank::class, $dom->findOne('Bar')->findOne('Bar')->findOne('Bar')->findOne('Bar'));
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
