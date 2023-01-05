<?php

use Voku\Helper\HtmlDomParser;

/**
 * @internal
 */
final class SimpleHtmlDomNodeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param $filename
     *
     * @return string|null
     */
    protected function loadFixture($filename)
    {
        $path = __DIR__ . '/fixtures/' . $filename;
        if (\file_exists($path)) {
            return \file_get_contents($path);
        }

        return null;
    }

    /**
     * @dataProvider findTests
     *
     * @param $html
     * @param $selector
     * @param $count
     */
    public function testFind($html, $selector, $count)
    {
        $document = new HtmlDomParser($html);
        $nodeList = $document->find('section');

        $elements = $nodeList->find($selector);

        static::assertInstanceOf(Voku\Helper\SimpleHtmlDomNodeInterface::class, $elements);
        static::assertCount($count, $elements);

        foreach ($elements as $node) {
            static::assertInstanceOf(Voku\Helper\SimpleHtmlDom::class, $node);
        }
    }

    /**
     * @return array
     */
    public function findTests(): array
    {
        $html = $this->loadFixture('test_page.html');

        return [
            [$html, '.fake h2', 0],
            [$html, 'article', 16],
            [$html, '.radio', 3],
            [$html, 'input.radio', 3],
            [$html, 'ul li', 9],
            [$html, 'fieldset#forms__checkbox li, fieldset#forms__radio li', 6],
            [$html, 'input[id]', 23],
            [$html, 'input[id=in]', 1],
            [$html, '#in', 1],
            [$html, 'text', 539],
            [$html, '*[id]', 51],
        ];
    }

    public function testInnerHtml()
    {
        $html = '<div><p>foo</p><p>bar</p></div>';
        $document = new HtmlDomParser($html);
        $element = $document->find('p');

        static::assertSame('<p>foo</p><p>bar</p>', (string) $element);
        static::assertSame(['<p>foo</p>', '<p>bar</p>'], $element->innerHtml());
        static::assertSame(['foo', 'bar'], $element->innertext);
    }

    public function testText()
    {
        $html = '<div><p>foo</p><p>bar</p></div>';
        $document = new HtmlDomParser($html);
        $element = $document->find('p');

        static::assertSame(['foo', 'bar'], $element->text());
        static::assertSame(['foo', 'bar'], $element->plaintext);
    }

    public function testNonText()
    {
        $html = '<div><p>foo</p><p>bar</p></div>';
        $document = new HtmlDomParser($html);
        $element = $document->find('span');

        static::assertInstanceOf(\Voku\Helper\SimpleHtmlDomNodeInterface::class, $element);
        static::assertInstanceOf(\Voku\Helper\SimpleHtmlDomNodeBlank::class, $element);
        static::assertSame([], $element->text());
        static::assertSame([], $element->plaintext);
    }

    public function testNonText0()
    {
        $html = '<div><p>foo</p><p>bar</p></div>';
        $document = new HtmlDomParser($html);
        $element = $document->find('span', 0);

        static::assertInstanceOf(\Voku\Helper\SimpleHtmlDomInterface::class, $element);
        static::assertInstanceOf(\Voku\Helper\SimpleHtmlDomBlank::class, $element);
        static::assertSame('', $element->class);
        static::assertSame('', $element->text());
        static::assertSame('', $element->plaintext);
    }

    public function testNonText1()
    {
        $html = '<div><p>foo</p><p>bar</p></div>';
        $document = new HtmlDomParser($html);
        $elements = $document->find('span', 1);

        static::assertCount(0, $elements);
        static::assertInstanceOf(\Voku\Helper\SimpleHtmlDomInterface::class, $elements);
        static::assertInstanceOf(\Voku\Helper\SimpleHtmlDomBlank::class, $elements);
        static::assertSame('', $elements->class);
        static::assertSame('', $elements->text());
        static::assertSame('', $elements->plaintext);
    }

    public function testGetFirstDomElement()
    {
        $html = '<div><p class="lall">foo</p><p>lall</p></div>';
        $document = new HtmlDomParser($html);
        $elements = $document->findMulti('p');

        static::assertCount(2, $elements);
        static::assertSame(['lall', ''], $elements->class);
        static::assertSame(['foo', 'lall'], $elements->text());
        static::assertSame(['foo', 'lall'], $elements->plaintext);
        static::assertSame('lall', $elements[0]->class);
        static::assertSame('foo', $elements[0]->text());
        static::assertSame('foo', $elements[0]->plaintext);

        // ---

        $html = '<div><p class="lall">foo</p><p>lall</p></div>';
        $document = new HtmlDomParser($html);
        $element = $document->find('p', 0);

        static::assertCount(1, $element);
        static::assertSame('lall', $element->class);
        static::assertSame('foo', $element->text());
        static::assertSame('foo', $element->plaintext);
    }
}
