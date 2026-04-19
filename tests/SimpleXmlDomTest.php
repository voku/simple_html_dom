<?php

use voku\helper\SimpleXmlDomBlank;
use voku\helper\SimpleXmlDomNodeBlank;
use voku\helper\XmlDomParser;

/**
 * @internal
 */
final class SimpleXmlDomTest extends \PHPUnit\Framework\TestCase
{
    public function testRssItemSelectorsExposeLinkAndPubDateText()
    {
        $xml = XmlDomParser::file_get_xml(__DIR__ . '/fixtures/lebensmittelwarnung.xml');
        $item = $xml->findOne('//item');

        static::assertSame('Example Produkt', $item->getElementByTagName('title')->text());
        static::assertSame('Wed, 03 Jan 2024 12:00:00 +0000', $item->getElementByTagName('pubDate')->text());
        static::assertSame('Wed, 03 Jan 2024 12:00:00 +0000', $item->find('pubDate', 0)->text());
        static::assertSame('https://example.com/produkt', $item->getElementByTagName('link')->text());
        static::assertSame('https://example.com/produkt', $item->findOne('link')->text());
        static::assertSame('https://example.com/produkt', $item('link', 0)->text());
        static::assertSame(['https://example.com/produkt'], $item->getElementsByTagName('link')->text());
    }

    public function testSimpleXmlDomPropertiesAttributesAndNavigation()
    {
        $xml = XmlDomParser::str_get_xml(
            '<root><item foo="bar"><title>First</title><pubDate>one</pubDate><link>https://first.example</link></item><item foo="baz"><title>Second</title></item></root>'
        );
        $item = $xml->findOne('item');
        $title = $item->firstChild();
        $link = $item->lastChild();

        static::assertSame('item', $item->tag);
        static::assertSame('bar', $item->foo);
        static::assertSame(['foo' => 'bar'], $item->attr);
        static::assertTrue(isset($item->tag));
        static::assertTrue(isset($item->foo));
        static::assertFalse(isset($item->missing));
        static::assertTrue($item->hasAttributes());
        static::assertTrue($item->hasAttribute('foo'));
        static::assertSame('bar', $item->getAttribute('foo'));
        static::assertSame(['foo' => 'bar'], $item->getAllAttributes());
        static::assertSame('<title>First</title><pubDate>one</pubDate><link>https://first.example</link>', $item->innerXml());
        static::assertSame('root', $item->parentNode()->tag);
        static::assertCount(3, $item->childNodes());
        static::assertSame('pubDate', $item->childNodes(1)->tag);
        static::assertSame('title', $item->first_child()->tag);
        static::assertSame('link', $item->last_child()->tag);
        static::assertSame('item', $item->nextSibling()->tag);
        static::assertSame('item', $item->nextNonWhitespaceSibling()->tag);
        static::assertNull($item->previousSibling());
        static::assertNull($item->previousNonWhitespaceSibling());
        static::assertSame('pubDate', $title->next_sibling()->tag);
        static::assertNull($title->previousSibling());
        static::assertSame('item', $title->parent()->tag);
        static::assertSame('pubDate', $link->previousNonWhitespaceSibling()->tag);
    }

    public function testSimpleXmlDomMutatorsUpdateXmlOutput()
    {
        $xml = XmlDomParser::str_get_xml('<root><item foo="bar"><title>Old</title></item></root>');
        $item = $xml->findOne('item');
        $item->setAttribute('foo', 'baz');
        $item->setAttribute('empty', '', true);

        static::assertSame('baz', $item->getAttribute('foo'));
        static::assertSame('', $item->getAttribute('empty'));
        static::assertTrue($item->hasAttribute('empty'));

        unset($item->foo);
        $item->removeAttribute('empty');

        static::assertFalse($item->hasAttribute('foo'));
        static::assertFalse($item->hasAttribute('empty'));

        $xml = XmlDomParser::str_get_xml('<root><item><title>Old</title></item></root>');
        $xml->findOne('title')->plaintext = 'Changed';
        static::assertSame('<root><item>Changed</item></root>', \trim($xml->xml()));

        $xml = XmlDomParser::str_get_xml('<root><item><title>Old</title></item></root>');
        $xml->findOne('item')->innertext = '<replacement>New</replacement>';
        static::assertSame('<root><item><replacement>New</replacement></item></root>', \trim($xml->xml()));

        $xml = XmlDomParser::str_get_xml('<root><item><title>Old</title></item></root>');
        $xml->findOne('title')->outertext = '<headline>New</headline>';
        static::assertSame('<root><item><headline>New</headline></item></root>', \trim($xml->xml()));

        $xml = XmlDomParser::str_get_xml('<root><item><title>Old</title></item></root>');
        $xml->findOne('title')->outertext = '';
        static::assertSame('<root><item></item></root>', \trim($xml->xml()));
    }

    public function testSimpleXmlDomNodeCollectionsExposeFindHelpers()
    {
        $xml = XmlDomParser::str_get_xml(
            '<root><item foo="bar"><title>First</title><pubDate>one</pubDate><link>https://first.example</link></item><item foo="baz"><title>Second</title></item></root>'
        );
        $items = $xml->getElementsByTagName('item');
        $titles = $items->findMulti('title');

        static::assertSame(2, $items->length);
        static::assertCount(2, $items);
        static::assertSame(['bar', 'baz'], $items->foo);
        static::assertSame(['First', 'Second'], $titles->text());
        static::assertSame(['First', 'Second'], $titles->plaintext);
        static::assertSame('First', $items->find('title', 0)->text());
        static::assertSame('First', $items->findOne('title')->text());
        static::assertSame('First', $items->findOneOrNull('title')->text());
        static::assertFalse($items->findOneOrFalse('missing'));
        static::assertFalse($items->findMultiOrFalse('missing'));
        static::assertNull($items->findOneOrNull('missing'));
        static::assertNull($items->findMultiOrNull('missing'));
    }

    public function testSimpleXmlDomMissingNodesReturnBlankWrappers()
    {
        $xml = XmlDomParser::str_get_xml('<root><item><title>Old</title></item></root>');
        $missing = $xml->findOne('missing');
        $missingList = $xml->findMulti('missing');

        static::assertInstanceOf(SimpleXmlDomBlank::class, $missing);
        static::assertSame('', $missing->text());
        static::assertNull($missing->attr);
        static::assertSame('', (string) $missing);
        static::assertNull($missing->findOneOrNull('missing'));
        static::assertNull($missing->findMultiOrNull('missing'));
        static::assertFalse($missing->findOneOrFalse('missing'));
        static::assertFalse($missing->findMultiOrFalse('missing'));

        static::assertInstanceOf(SimpleXmlDomNodeBlank::class, $missingList);
        static::assertCount(0, $missingList);
        static::assertSame(0, $missingList->length);
        static::assertSame([], $missingList->text());
        static::assertSame([], $missingList->innerHtml());
        static::assertSame([], $missingList->plaintext);
        static::assertSame([], $missingList->outertext);
        static::assertNull($missingList->find('missing', 0));
        static::assertNull($missingList->findOneOrNull('missing'));
        static::assertNull($missingList->findMultiOrNull('missing'));
        static::assertFalse($missingList->findOneOrFalse('missing'));
        static::assertFalse($missingList->findMultiOrFalse('missing'));
    }
}
