<?php

use voku\helper\HtmlDomParser;
use voku\helper\SimpleHtmlAttributes;
use voku\helper\SimpleHtmlDomBlank;
use voku\helper\SimpleHtmlDomNodeBlank;
use voku\helper\SimpleXmlDomBlank;
use voku\helper\SimpleXmlDomNodeBlank;
use voku\helper\XmlDomParser;

/**
 * @internal
 */
final class BlankDomCoverageTest extends \PHPUnit\Framework\TestCase
{
    public function testSimpleHtmlDomBlankAndNodeBlankPublicApi()
    {
        $blank = new SimpleHtmlDomBlank();
        $nodeBlank = new SimpleHtmlDomNodeBlank();

        static::assertSame('', $blank->html());
        static::assertSame('', $blank->innerHtml());
        static::assertSame('', $blank->innerXml());
        static::assertSame('', $blank->text());
        static::assertSame('', $blank->getTag());
        static::assertSame('', $blank->getAttribute('missing'));
        static::assertFalse($blank->hasAttribute('missing'));
        static::assertFalse($blank->hasAttributes());
        static::assertNull($blank->getAllAttributes());
        static::assertNull($blank->childNodes());
        static::assertNull($blank->firstChild());
        static::assertNull($blank->lastChild());
        static::assertNull($blank->nextSibling());
        static::assertNull($blank->nextNonWhitespaceSibling());
        static::assertNull($blank->previousSibling());
        static::assertNull($blank->previousNonWhitespaceSibling());
        static::assertNull($blank->val());
        static::assertTrue($blank->isRemoved());
        static::assertSame('', (string) $blank);
        static::assertSame('', $blank->html);
        static::assertSame('', $blank->innerhtml);
        static::assertSame('', $blank->innerhtmlkeep);
        static::assertSame('', $blank->plaintext);
        static::assertSame('', $blank->tag);
        static::assertNull($blank->attr);
        static::assertInstanceOf(SimpleHtmlAttributes::class, $blank->classlist);
        static::assertFalse(isset($blank->missing));
        static::assertTrue(isset($blank->text));
        static::assertInstanceOf(SimpleHtmlDomNodeBlank::class, $blank('div'));
        static::assertInstanceOf(SimpleHtmlDomNodeBlank::class, $blank->find('div'));
        static::assertInstanceOf(SimpleHtmlDomNodeBlank::class, $blank->findMulti('div'));
        static::assertFalse($blank->findMultiOrFalse('div'));
        static::assertInstanceOf(SimpleHtmlDomBlank::class, $blank->findOne('div'));
        static::assertFalse($blank->findOneOrFalse('div'));
        static::assertInstanceOf(SimpleHtmlDomNodeBlank::class, $blank->getElementByClass('foo'));
        static::assertInstanceOf(SimpleHtmlDomBlank::class, $blank->getElementById('foo'));
        static::assertInstanceOf(SimpleHtmlDomBlank::class, $blank->getElementByTagName('div'));
        static::assertInstanceOf(SimpleHtmlDomNodeBlank::class, $blank->getElementsById('foo'));
        static::assertInstanceOf(SimpleHtmlDomNodeBlank::class, $blank->getElementsByTagName('div'));
        static::assertInstanceOf(SimpleHtmlDomBlank::class, $blank->parentNode());
        static::assertInstanceOf(SimpleHtmlDomNodeBlank::class, $blank->getIterator());
        try {
            $blank->getHtmlDomParser();
            static::fail('Expected blank HTML parser creation to fail.');
        } catch (\Throwable $throwable) {
            static::assertInstanceOf(\Throwable::class, $throwable);
            static::assertStringContainsString('DOMNode', $throwable->getMessage());
        }

        $blank->outerhtml = '<div>ignored</div>';
        $blank->innerhtml = '<span>ignored</span>';
        $blank->innerhtmlkeep = '<span>ignored</span>';
        $blank->plaintext = 'ignored';
        $blank->classlist = 'ignored';
        $blank->dataFoo = 'bar';
        unset($blank->dataFoo);

        static::assertSame('', $blank->html());
        static::assertSame('', $blank->text());
        static::assertSame('', $blank->outertext());
        static::assertSame('', $blank->innertext());
        static::assertNull($blank->children());
        static::assertNull($blank->first_child());
        static::assertNull($blank->last_child());
        static::assertNull($blank->next_sibling());
        static::assertNull($blank->prev_sibling());
        static::assertInstanceOf(SimpleHtmlDomBlank::class, $blank->parent());

        $blank->delete();
        $blank->remove();

        static::assertNull($nodeBlank->find('div'));
        static::assertInstanceOf(SimpleHtmlDomNodeBlank::class, $nodeBlank->findMulti('div'));
        static::assertFalse($nodeBlank->findMultiOrFalse('div'));
        static::assertInstanceOf(SimpleHtmlDomBlank::class, $nodeBlank->findOne('div'));
        static::assertFalse($nodeBlank->findOneOrFalse('div'));
        static::assertSame([], $nodeBlank->innerHtml());
        static::assertSame([], $nodeBlank->innertext());
        static::assertSame([], $nodeBlank->outertext());
        static::assertSame([], $nodeBlank->text());
    }

    public function testSimpleXmlDomBlankAndNodeBlankPublicApi()
    {
        $blank = new SimpleXmlDomBlank();
        $nodeBlank = new SimpleXmlDomNodeBlank();

        static::assertSame('', $blank->xml());
        static::assertSame('', $blank->innerXml());
        static::assertSame('', $blank->innerHtml());
        static::assertSame('', $blank->text());
        static::assertSame('', $blank->getAttribute('missing'));
        static::assertFalse($blank->hasAttribute('missing'));
        static::assertFalse($blank->hasAttributes());
        static::assertNull($blank->getAllAttributes());
        static::assertNull($blank->childNodes());
        static::assertNull($blank->firstChild());
        static::assertNull($blank->lastChild());
        static::assertNull($blank->nextSibling());
        static::assertNull($blank->nextNonWhitespaceSibling());
        static::assertNull($blank->previousSibling());
        static::assertNull($blank->previousNonWhitespaceSibling());
        static::assertNull($blank->val());
        static::assertTrue($blank->isRemoved());
        static::assertSame('', (string) $blank);
        static::assertSame('', $blank->xml);
        static::assertSame('', $blank->plaintext);
        static::assertSame('', $blank->tag);
        static::assertNull($blank->attr);
        static::assertFalse(isset($blank->missing));
        static::assertTrue(isset($blank->text));
        static::assertInstanceOf(SimpleXmlDomNodeBlank::class, $blank('div'));
        static::assertInstanceOf(SimpleXmlDomNodeBlank::class, $blank->find('div'));
        static::assertInstanceOf(SimpleXmlDomNodeBlank::class, $blank->findMulti('div'));
        static::assertFalse($blank->findMultiOrFalse('div'));
        static::assertInstanceOf(SimpleXmlDomBlank::class, $blank->findOne('div'));
        static::assertFalse($blank->findOneOrFalse('div'));
        static::assertInstanceOf(SimpleXmlDomNodeBlank::class, $blank->getElementByClass('foo'));
        static::assertInstanceOf(SimpleXmlDomBlank::class, $blank->getElementById('foo'));
        static::assertInstanceOf(SimpleXmlDomBlank::class, $blank->getElementByTagName('div'));
        static::assertInstanceOf(SimpleXmlDomNodeBlank::class, $blank->getElementsById('foo'));
        static::assertInstanceOf(SimpleXmlDomNodeBlank::class, $blank->getElementsByTagName('div'));
        static::assertInstanceOf(SimpleXmlDomBlank::class, $blank->parentNode());
        static::assertInstanceOf(SimpleXmlDomNodeBlank::class, $blank->getIterator());
        try {
            $blank->getXmlDomParser();
            static::fail('Expected blank XML parser creation to fail.');
        } catch (\Throwable $throwable) {
            static::assertInstanceOf(\Throwable::class, $throwable);
            static::assertStringContainsString('DOMNode', $throwable->getMessage());
        }

        $blank->outerhtml = '<div>ignored</div>';
        $blank->innerhtml = '<span>ignored</span>';
        $blank->innerhtmlkeep = '<span>ignored</span>';
        $blank->plaintext = 'ignored';
        $blank->dataFoo = 'bar';
        unset($blank->dataFoo);

        static::assertSame('', $blank->xml());
        static::assertNull($blank->children());
        static::assertNull($blank->first_child());
        static::assertNull($blank->last_child());
        static::assertNull($blank->next_sibling());
        static::assertNull($blank->prev_sibling());
        static::assertInstanceOf(SimpleXmlDomBlank::class, $blank->parent());

        static::assertNull($nodeBlank->find('div'));
        static::assertInstanceOf(SimpleXmlDomNodeBlank::class, $nodeBlank->findMulti('div'));
        static::assertFalse($nodeBlank->findMultiOrFalse('div'));
        static::assertInstanceOf(SimpleXmlDomBlank::class, $nodeBlank->findOne('div'));
        static::assertFalse($nodeBlank->findOneOrFalse('div'));
        static::assertSame([], $nodeBlank->innerHtml());
        static::assertSame([], $nodeBlank->innertext());
        static::assertSame([], $nodeBlank->outertext());
        static::assertSame([], $nodeBlank->text());
    }
}
