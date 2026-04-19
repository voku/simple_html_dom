<?php

use voku\helper\XmlDomParser;

/**
 * @internal
 */
final class XmlDomParserTest extends \PHPUnit\Framework\TestCase
{
    public function testXmlParserLoadersMagicAccessorsAndLookupHelpers()
    {
        $xmlFixture = __DIR__ . '/fixtures/test_xml.xml';

        $parser = new XmlDomParser();
        $parser->loadXmlFile($xmlFixture);

        static::assertStringContainsString('UNOB', $parser->plaintext);
        static::assertStringContainsString('<S_UNB>', $parser->innerHtml());
        static::assertStringContainsString('<S_UNB>', $parser->innerXml());

        $lookupParser = XmlDomParser::str_get_xml(
            '<root><item id="foobar" class="box"><span class="itemprop">content</span></item></root>'
        );

        static::assertSame('box', $lookupParser->getElementById('foobar')->class);
        static::assertSame('box', $lookupParser->getElementByClass('box')[0]->class);
        static::assertSame('span', $lookupParser->getElementByTagName('span')->tag);
        static::assertSame('foobar', $lookupParser->getElementsById('foobar', 0)->id);
        static::assertSame('itemprop', $lookupParser->getElementsByTagName('span', -1)->getAttribute('class'));
        static::assertInstanceOf(\voku\helper\SimpleXmlDomBlank::class, $lookupParser->getElementByTagName('missing'));
        static::assertInstanceOf(\voku\helper\SimpleXmlDomNodeBlank::class, $lookupParser->getElementsByTagName('missing'));
        static::assertInstanceOf(\voku\helper\SimpleXmlDomNodeBlank::class, $lookupParser->getElementsByTagName('missing', 0));
        static::assertSame('foobar', $lookupParser('#foobar', 0)->id);

        $tmpHtmlFile = \tempnam(\sys_get_temp_dir(), 'simple_html_dom_xml_html_');
        \assert($tmpHtmlFile !== false);
        \file_put_contents($tmpHtmlFile, '<root><item id="from-file" class="beta">loaded</item></root>');

        try {
            $htmlParser = new XmlDomParser();
            $htmlParser->loadHtmlFile($tmpHtmlFile);

            static::assertSame('loaded', $htmlParser->getElementById('from-file')->text());
        } finally {
            @\unlink($tmpHtmlFile);
        }
    }

    public function testXmlParserNamespaceAutoRegistrationAndFailurePaths()
    {
        $xml = <<<'XML'
<root xmlns:chap="http://example.org/chapter-title">
    <chapter id="1" class="chapter">
        <chap:title>Registered</chap:title>
    </chapter>
</root>
XML;

        $parser = (new XmlDomParser())
            ->autoRegisterXPathNamespaces()
            ->loadXml($xml);

        static::assertSame('Registered', $parser->findOne('//chap:title')->text());
        static::assertFalse($parser->findOneOrFalse('//chap:missing'));

        $missingPath = __DIR__ . '/fixtures/does-not-exist.xml';

        try {
            (new XmlDomParser())->loadXmlFile($missingPath);
            static::fail('Expected missing XML fixture to throw.');
        } catch (\RuntimeException $exception) {
            static::assertStringContainsString('not found', $exception->getMessage());
        }

        try {
            (new XmlDomParser())->loadHtmlFile($missingPath);
            static::fail('Expected missing HTML fixture to throw.');
        } catch (\RuntimeException $exception) {
            static::assertStringContainsString('not found', $exception->getMessage());
        }
    }

    public function testXmlParserInvalidStaticMethodThrows()
    {
        $this->expectException(\BadMethodCallException::class);

        XmlDomParser::unsupported_static_method('<root/>');
    }

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

    public function testXMLWithoutLoadingDtd()
    {
        $cxmlData = '<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE cXML SYSTEM "http://xml.cXML.org/schemas/cXML/1.2.024/cXML.dtd"><cXML payloadID="dsfsfsdds" timestamp="2022-12-21T15:35:02+01:00" xml:lang="en-US"><Header><From><Credential domain="NetworkId"><Identity>sdfdfsdf-123</Identity></Credential></From><To><Credential domain="NetworkId"><Identity>fsdfdsfdsfds-321</Identity></Credential></To><Sender><Credential domain="NetworkId"><Identity>fsdfdsfds-1234</Identity></Credential><UserAgent>vdmg: Moelleken, Lars (VDMG-Connect)</UserAgent></Sender></Header><Message><PunchOutOrderMessage><BuyerCookie/><PunchOutOrderMessageHeader operationAllowed="edit"><Total><Money currency="EUR">2.13</Money></Total></PunchOutOrderMessageHeader><ItemIn quantity="1"><ItemID><SupplierPartID>43423342</SupplierPartID></ItemID><ItemDetail><UnitPrice><Money currency="EUR">2.13</Money></UnitPrice><Description xml:lang="de">Stahlblech 10 mm 1200</Description><Classification domain="UNSPSC"/></ItemDetail></ItemIn></PunchOutOrderMessage></Message></cXML>';

        $xmlParser = new \voku\helper\XmlDomParser();
        $xmlParsed = $xmlParser->loadXml($cxmlData, LIBXML_NONET, false);

        $items = $xmlParsed->findMultiOrFalse('//ItemIn');
        static::assertSame('1', $items[0]->getAttribute('quantity'));
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

        $classname = $data->findOne('classname');
        static::assertSame('Closure', $classname->text());

        $classname = $data->findOneOrFalse('classname');
        static::assertSame('Closure', $classname->text());
    }

    public function testXmlFindOrNullWithNamespaces()
    {
        $xml = <<<'EOD'
<book xmlns:chap="http://example.org/chapter-title">
    <chapter id="1">
        <chap:title>Chapter 1</chap:title>
    </chapter>
</book>
EOD;

        $xmlParser = XmlDomParser::str_get_xml($xml);

        $chapters = $xmlParser->findMultiOrNull('chapter');
        static::assertNotNull($chapters);
        static::assertCount(1, $chapters);

        static::assertNull($xmlParser->findMultiOrNull('//chap:foo'));
        static::assertNull($xmlParser->findOneOrNull('//chap:foo'));
        static::assertSame('Chapter 1', $xmlParser->findOneOrNull('//chap:title')->text());

        if (\PHP_VERSION_ID >= 80000) {
            require_once __DIR__ . '/fixtures/php8_nullsafe_helpers.php';

            static::assertSame(
                'Chapter 1',
                \Tests\Fixtures\getXmlNullsafeTitle($xmlParser)
            );
            static::assertNull(
                \Tests\Fixtures\getXmlNullsafeMissingTitle($xmlParser)
            );
        }
    }

    public function testNestedXmlDomAndBlankFindOrNullPaths()
    {
        $xml = <<<'EOD'
<book xmlns:chap="http://example.org/chapter-title">
    <chapter id="1">
        <chap:title>Chapter 1</chap:title>
    </chapter>
</book>
EOD;

        $xmlParser = XmlDomParser::str_get_xml($xml);

        $chapter = $xmlParser->findOne('//chapter');
        $chapterTitles = $chapter->findMultiOrNull('//chap:title');
        static::assertNotNull($chapterTitles);
        static::assertCount(1, $chapterTitles);
        static::assertSame('Chapter 1', $chapter->findOneOrNull('//chap:title')->text());
        static::assertNull($chapter->findOneOrNull('//chap:foo'));

        $chapters = $xmlParser->findMulti('chapter');
        $titles = $chapters->findMultiOrNull('//chap:title');
        static::assertNotNull($titles);
        static::assertCount(1, $titles);
        static::assertSame('Chapter 1', $chapters->findOneOrNull('//chap:title')->text());
        static::assertNull($chapters->findMultiOrNull('//chap:foo'));
        static::assertNull($chapters->findOneOrNull('//chap:foo'));

        $blankElement = $xmlParser->findOne('//chap:foo');
        static::assertNull($blankElement->findMultiOrNull('//chap:title'));
        static::assertNull($blankElement->findOneOrNull('//chap:title'));

        $blankList = $xmlParser->findMulti('//chap:foo');
        static::assertNull($blankList->findMultiOrNull('//chap:title'));
        static::assertNull($blankList->findOneOrNull('//chap:title'));
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

    public function testTextNodeSelectorPreservesWhitespace()
    {
        $dom = (new voku\helper\XmlDomParser())->loadXml('<root><Foo> foo </Foo></root>');

        static::assertSame(' foo ', $dom->findOne('Foo')->findOne('text')->text());
        static::assertSame(' foo ', $dom->findOne('Foo text')->text());
    }

    public function testCommentNodeSelectorPreservesWhitespaceAndEntities(): void
    {
        $dom = (new voku\helper\XmlDomParser())->loadXml('<root><!--  &amp; <b>Hello, World!</b>  --></root>');
        $comment = $dom->findOne('comment');

        static::assertSame('  &amp; <b>Hello, World!</b>  ', $comment->text());
    }

    public function testCdataNodeSelectorPreservesWhitespaceAndEntities(): void
    {
        $dom = (new voku\helper\XmlDomParser())->loadXml('<root><![CDATA[  &amp; <b>Hello, World!</b>  ]]></root>');
        $textNode = $dom->findOne('text');

        static::assertSame('  &amp; <b>Hello, World!</b>  ', $textNode->text());
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
