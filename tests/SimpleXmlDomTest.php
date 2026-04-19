<?php

use voku\helper\SimpleXmlDom;
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

    public function testSimpleXmlDomValueHelpersAndLookupAliases()
    {
        $xml = XmlDomParser::str_get_xml(
            '<root>'
            . '<input id="plain" type="text" value="alpha"/>'
            . '<input id="choice" type="radio" value="yes" checked="checked"/>'
            . '<textarea id="notes">body</textarea>'
            . '<select id="picker" checked="checked"><option value="one"/><option value="two"/></select>'
            . '<item id="first" class="alpha"><title>First</title></item>'
            . '<item id="second" class="beta"><title>Second</title></item>'
            . '</root>'
        );

        $textInput = $xml->getElementById('plain');
        $radio = $xml->getElementsById('choice', 0);
        $textarea = $xml->getElementByTagName('textarea');
        $select = $xml->getElementByTagName('select');

        static::assertSame('alpha', $textInput->val());
        $textInput->val('beta');
        static::assertSame('beta', $textInput->val());

        static::assertSame('yes', $radio->val());
        $radio->val('no');
        static::assertFalse($radio->hasAttribute('checked'));
        $radio->val('yes');
        static::assertTrue($radio->hasAttribute('checked'));

        static::assertSame('body', $textarea->val());
        $textarea->val('changed');
        static::assertSame('changed', $textarea->text());

        static::assertSame(['one', 'two'], $select->val());
        $select->val('two');
        static::assertFalse($select->getElementsByTagName('option', 0)->hasAttribute('selected'));
        static::assertSame('selected', $select->getElementsByTagName('option', -1)->getAttribute('selected'));

        static::assertStringContainsString('<option value="one"></option>', $select->innerHtml());
        static::assertStringContainsString('<option value="two" selected', $select->innerHtml());
        static::assertStringContainsString('<select id="picker" checked="checked">', $select->xml());
        static::assertStringContainsString('<option value="two" selected="selected"></option>', $select->xml());
        static::assertSame(['alpha'], $xml->getElementByClass('alpha')->class);
        static::assertSame('second', $xml->getElementsByTagName('item', -1)->getAttribute('id'));
        static::assertInstanceOf(SimpleXmlDomNodeBlank::class, $xml->getElementsByTagName('missing'));
        static::assertInstanceOf(SimpleXmlDomNodeBlank::class, $xml->getElementsByTagName('missing', 0));

        $textNode = new SimpleXmlDom($xml->getDocument()->createTextNode('loose'));
        static::assertNull($textNode->getAllAttributes());
        static::assertFalse($textNode->hasAttribute('id'));
        static::assertSame('', $textNode->getAttribute('id'));
    }

    public function testSimpleXmlDomCollectionConvenienceMethodsAndProtectedRename()
    {
        $xml = XmlDomParser::str_get_xml(
            '<root><item id="first"><title>First</title></item><item id="second"><title>Second</title></item></root>'
        );
        $items = $xml->getElementsByTagName('item');
        $titles = $items('title');

        static::assertCount(2, $titles);
        static::assertSame('', (string) $titles);
        static::assertSame(['', ''], $titles->innerHtml());
        static::assertSame(['', ''], $titles->innertext());
        static::assertSame(['', ''], $titles->outertext());
        static::assertSame('First', $items->findOne('title')->text());
        static::assertFalse($items->findOneOrFalse('missing'));
        static::assertFalse($items->findMultiOrFalse('missing'));
        static::assertInstanceOf(SimpleXmlDomNodeBlank::class, $items->findMulti('missing'));

        $renameXml = XmlDomParser::str_get_xml('<item><title>First</title></item>');
        $renameTarget = $renameXml->findOne('item');
        $renamer = new class($renameTarget->getNode()) extends SimpleXmlDom {
            public function renameNode(\DOMNode $node, string $name)
            {
                return $this->changeElementName($node, $name);
            }
        };

        $renamed = $renamer->renameNode($renameTarget->getNode(), 'entry');

        static::assertInstanceOf(\DOMElement::class, $renamed);
        static::assertSame('entry', $renamed->nodeName);
        static::assertSame('entry', $renameXml->findOne('entry')->tag);
        static::assertFalse($renamer->renameNode(new \DOMElement('free'), 'entry'));
    }

    public function testSimpleXmlDomUnknownMethodThrows()
    {
        $this->expectException(\BadMethodCallException::class);

        XmlDomParser::str_get_xml('<root><item/></root>')->findOne('item')->unknown_method();
    }

    public function testProtectedRenamePreservesAttributesAndSupportsNestedNodes()
    {
        $xml = XmlDomParser::str_get_xml(
            '<item id="first" class="alpha"><title code="x">First</title></item>'
        );
        $target = $xml->findOne('item');
        $renamer = new class($target->getNode()) extends SimpleXmlDom {
            public function renameNode(\DOMNode $node, string $name)
            {
                return $this->changeElementName($node, $name);
            }
        };

        $renamer->renameNode($target->getNode(), 'entry');

        $entry = $xml->findOne('entry');
        static::assertSame('first', $entry->getAttribute('id'));
        static::assertSame('alpha', $entry->getAttribute('class'));
        static::assertSame('First', $entry->findOne('title')->text());

        $nestedNode = $xml->getDocument()->getElementsByTagName('title')->item(0);
        static::assertInstanceOf(\DOMNode::class, $nestedNode);
        $renamer->renameNode($nestedNode, 'headline');

        $headline = $xml->findOne('headline');
        static::assertSame('x', $headline->getAttribute('code'));
        static::assertSame('First', $headline->text());
    }
}
