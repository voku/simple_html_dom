<?php

namespace Tests;

use DOMNode;
use PHPUnit\Framework\TestCase;
use voku\helper\HtmlDomParser;
use voku\helper\SimpleHtmlDom;
use voku\helper\SimpleHtmlDomInterface;
use voku\helper\SimpleHtmlDomNode;
use voku\helper\SimpleHtmlDomNodeInterface;
use function file_exists;
use function file_get_contents;

/**
 * @internal
 */
final class SimpleHtmlDomTest extends TestCase
{
    /**
     * @param $filename
     *
     * @return string|null
     */
    protected function loadFixture($filename)
    {
        $path = __DIR__ . '/fixtures/' . $filename;
        if (file_exists($path)) {
            return file_get_contents($path);
        }

        return null;
    }

    public function testConstructor()
    {
        $html = '<input name="username" value="John">User name</input>';

        $document = new HtmlDomParser($html);
        $node = $document->getDocument()->documentElement;

        $element = new SimpleHtmlDom($node);

        static::assertSame('input', $element->tag);
        static::assertSame('User name', $element->plaintext);
        static::assertSame('username', $element->name);
        static::assertSame('John', $element->value);
    }

    public function testSetInput()
    {
        $html = '
        <input name="text" type="text" value="">Text</input>
        <textarea name="textarea"></textarea>
        <input name="checkbox" type="checkbox" value="3">Text</input>
        <select name="select" multiple>
          <option value="1" selected>1</option>
          <option value="2" selected>2</option>
          <option value="3">2</option>
        </select>
        ';

        $document = new HtmlDomParser($html);

        $inputs = $document->find('input, textarea');
        foreach ($inputs as $input) {
            static::assertNotSame('3', $input->val());

            $input->val('3');

            static::assertSame('3', $input->val(), 'tested:' . $input->html());
        }

        $expected = '<input name="text" type="text" value="3">Text
        <textarea name="textarea">3</textarea>
        <input name="checkbox" type="checkbox" value="3" checked>Text
        <select name="select" multiple>
          <option value="1" selected>1</option>
          <option value="2" selected>2</option>
          <option value="3">2</option>
        </select>';

        static::assertSame($expected, $document->html());
    }

    public function testGetNode()
    {
        $html = '<div>foo</div>';

        $document = new HtmlDomParser($html);
        $node = $document->getDocument()->documentElement;
        $element = new SimpleHtmlDom($node);

        static::assertInstanceOf(DOMNode::class, $element->getNode());
    }

    public function testDecodeShouldDecodeAttributes()
    {
        $expected = 'H&auml;agen-Dazs';

        $html = new HtmlDomParser();
        $html->load('<meta name="description" content="H&auml;agen-Dazs">');

        $description = $html->findOneOrFalse('meta[name="description"]');

        static::assertSame($expected, $description->getAttribute('content'));
        static::assertSame($description->getAttribute('content'), $description->content);
    }

    public function testFindInChildNode()
    {
        $html = '
        <div class="foo">
            <div class="class">
                <strong>1</strong>
                <div>
                    <strong>2</strong>
                </div>
            </div>
            <div class="class">
                <strong>3</strong>
                <div>
                    <strong>4</strong>
                </div>
            </div>
        </div>
        ';

        $d = HtmlDomParser::str_get_html($html);
        $div = $d->find('.class', 0);
        $v = $div->find('div strong', 0)->text();

        static::assertSame('1', $v);
    }

    public function testCommentWp()
    {
        $html = '
        <!-- wp:heading -->
        <h2 id="my-title">Level 2 title</h2>
        <!-- /wp:heading -->
        ';

        $d = new HtmlDomParser();
        $d->loadHtml($html);

        static::assertSame($html, $d->html());
    }

    public function testAppendPrependIssue()
    {
        $d = new HtmlDomParser();
        $d->loadHtml('<p>p1</p><p>p2</p>');
        $p = $d->find('p', 0);
        $p->outerhtml .= '<div>outer</div>';

        static::assertSame('<p>p1</p>
<div>outer</div><p>p2</p>', $d->html());
        static::assertSame('p1outerp2', $d->plaintext);
    }

    public function testReplaceText()
    {
        $html = '<div>foo</div>';
        $replace = '<h1>bar</h1>';
        $document = new HtmlDomParser($html);
        $node = $document->getDocument()->documentElement;
        $element = new SimpleHtmlDom($node);
        $element->plaintext = $replace;
        static::assertSame('<h1>bar</h1>', $document->outertext);
        static::assertSame($replace, $document->plaintext);
        static::assertSame('<h1>bar</h1>', $element->outertext);
        static::assertSame($replace, $element->plaintext);
        $element->plaintext = '';
        static::assertSame('', $document->outertext);
        static::assertSame('', $document->plaintext);
    }

    public function replaceNodeDataProvider()
    {
        return [
            [
                '<h1>bar</h1>',
            ],
            [
                '',
            ],
            [
                'foo',
            ],
        ];
    }

    /**
     * @dataProvider replaceNodeDataProvider
     *
     * @param string $replace
     */
    public function testReplaceNode($replace)
    {
        $html = '<div>foo</div>';

        $document = new HtmlDomParser($html);
        $node = $document->getDocument()->documentElement;
        $element = new SimpleHtmlDom($node);
        $element->outertext = $replace;

        static::assertSame($replace, $document->outertext);
        static::assertSame($replace, $element->outertext);

        $element->outertext = '';

        if ($replace !== '') {
            static::assertNotSame($replace, $document->outertext);
        }
    }

    public function testReplaceChild()
    {
        $html = '<div><p>foo</p></div>';
        $replace = '<h1>bar</h1>';

        $document = new HtmlDomParser($html);
        $node = $document->getDocument()->documentElement;
        $element = new SimpleHtmlDom($node);
        $element->innertext = $replace;

        static::assertSame('<div><h1>bar</h1></div>', $document->outertext);
        static::assertSame('<div><h1>bar</h1></div>', $element->outertext);
    }

    public function testGetDom()
    {
        $html = '<div><p>foo</p></div>';

        $document = new HtmlDomParser($html);
        $node = $document->getDocument()->documentElement;
        $element = new SimpleHtmlDom($node);

        static::assertInstanceOf(HtmlDomParser::class, $element->getHtmlDomParser());
    }

    /**
     * @dataProvider findTests
     *
     * @param string $html
     * @param string $selector
     * @param int    $count
     */
    public function testFind($html, $selector, $count)
    {
        $document = new HtmlDomParser($html);
        $element = new SimpleHtmlDom($document->getDocument()->documentElement);

        $elements = $element->find($selector);

        static::assertInstanceOf(SimpleHtmlDomNodeInterface::class, $elements);
        static::assertCount($count, $elements);

        foreach ($elements as $node) {
            static::assertInstanceOf(SimpleHtmlDomInterface::class, $node);
        }

        $elements = $element($selector);

        static::assertInstanceOf(SimpleHtmlDomNodeInterface::class, $elements);
    }

    /**
     * @return array
     */
    public function findTests()
    {
        $html = $this->loadFixture('test_page.html');

        return [
            [$html, '.fake h2', 0],
            [$html, 'article', 16],
            [$html, '.radio', 3],
            [$html, 'input.radio', 3],
            [$html, 'ul li', 35],
            [$html, 'fieldset#forms__checkbox li, fieldset#forms__radio li', 6],
            [$html, 'input[id]', 23],
            [$html, 'input[id=in]', 1],
            [$html, '#in', 1],
            [$html, '*[id]', 52],
            [$html, 'text', 640],
            [$html, 'comment', 3],
        ];
    }

    public function testGetElementById()
    {
        $html = $this->loadFixture('test_page.html');

        $document = new HtmlDomParser($html);
        $element = new SimpleHtmlDom($document->getDocument()->documentElement);

        $node = $element->getElementById('in');

        static::assertInstanceOf(SimpleHtmlDom::class, $node);
        static::assertSame('input', $node->tag);
        static::assertSame('input', $node->nodeName);
        static::assertSame('number', $node->type);
        static::assertSame('5', $node->value);
    }

    public function testGetElementByTagName()
    {
        $html = $this->loadFixture('test_page.html');

        $document = new HtmlDomParser($html);
        $element = new SimpleHtmlDom($document->getDocument()->documentElement);

        $node = $element->getElementByTagName('div');

        static::assertInstanceOf(SimpleHtmlDom::class, $node);
        static::assertSame('div', $node->tag);
        static::assertSame('top', $node->id);
        static::assertSame('page', $node->class);
    }

    public function testGetElementsByTagName()
    {
        $html = $this->loadFixture('test_page.html');

        $document = new HtmlDomParser($html);
        $element = new SimpleHtmlDom($document->getDocument()->documentElement);

        $elements = $element->getElementsByTagName('div');

        static::assertInstanceOf(SimpleHtmlDomNode::class, $elements);
        static::assertCount(16, $elements);

        foreach ($elements as $node) {
            static::assertInstanceOf(SimpleHtmlDom::class, $node);
        }
    }

    public function testChildNodes()
    {
        $html = '<div><p>foo</p><p>bar</p></div>';

        $document = new HtmlDomParser($html);
        $element = new SimpleHtmlDom($document->getDocument()->documentElement);

        $nodes = $element->childNodes();

        static::assertInstanceOf(SimpleHtmlDomNode::class, $nodes);
        static::assertCount(2, $nodes);

        foreach ($nodes as $node) {
            static::assertInstanceOf(SimpleHtmlDom::class, $node);
        }

        $node = $element->childNodes(1);

        static::assertInstanceOf(SimpleHtmlDom::class, $node);

        static::assertSame('<p>bar</p>', $node->outertext);
        static::assertSame('bar', $node->plaintext);

        $node = $element->childNodes(2);
        static::assertNull($node);
    }

    public function testChildren()
    {
        $html = '<div><p>foo</p><p>bar</p></div>';

        $document = new HtmlDomParser($html);
        $element = new SimpleHtmlDom($document->getDocument()->documentElement);

        $nodes = $element->children();

        static::assertInstanceOf(SimpleHtmlDomNode::class, $nodes);
        static::assertCount(2, $nodes);

        foreach ($nodes as $node) {
            static::assertInstanceOf(SimpleHtmlDom::class, $node);
        }

        $node = $element->children(1);

        static::assertInstanceOf(SimpleHtmlDom::class, $node);

        static::assertSame('<p>bar</p>', $node->outertext);
        static::assertSame('bar', $node->plaintext);
    }

    public function testFirstChild()
    {
        $html = '<div><p>foo</p><p></p></div>';

        $document = new HtmlDomParser($html);
        $element = new SimpleHtmlDom($document->getDocument()->documentElement);

        $node = $element->firstChild();

        static::assertInstanceOf(SimpleHtmlDom::class, $node);
        static::assertSame('<p>foo</p>', $node->outertext);
        static::assertSame('foo', $node->plaintext);

        $node = $element->lastChild();

        static::assertInstanceOf(SimpleHtmlDom::class, $node);
        static::assertSame('<p></p>', $node->outertext);
        static::assertSame('', $node->plaintext);

        static::assertNull($node->firstChild());
        static::assertNull($node->first_child());
    }

    public function testLastChild()
    {
        $html = '<div><p></p><p>bar</p></div>';

        $document = new HtmlDomParser($html);
        $element = new SimpleHtmlDom($document->getDocument()->documentElement);

        $node = $element->lastChild();

        static::assertInstanceOf(SimpleHtmlDom::class, $node);
        static::assertSame('<p>bar</p>', $node->outertext);
        static::assertSame('bar', $node->plaintext);

        $node = $element->firstChild();

        static::assertInstanceOf(SimpleHtmlDom::class, $node);
        static::assertSame('<p></p>', $node->outertext);
        static::assertSame('', $node->plaintext);

        static::assertNull($node->lastChild());
        static::assertNull($node->last_child());
    }

    public function testDataAttribute()
    {
        $html = '<div class="B(8px) Pos(a) C(white) Py(2px) Px(0) Ta(c) Bdrs(3px) Trstf(eio) Trsde(0.5) Arrow South Bdtc(i)::a Fw(b) Bgc($buy) Bdtc($buy)" data-test="rec-rating-txt" tabindex="0" aria-label="2.9 on a scale of 1 to 5, where 1 is Strong Buy and 5 is Sell" style="width: 30px; left: calc(47.5% - 15px);">2.9</div>';
        $document = new HtmlDomParser($html);

        $lall = $document->findOne('*[data-test="rec-rating-txt"]');

        static::assertSame('2.9', $lall->innerText());
    }

    public function testNextSibling()
    {
        $html = '<div><p>foo</p><p>bar</p></div>';

        $document = new HtmlDomParser($html);
        $element = new SimpleHtmlDom($document->getDocument()->documentElement);

        $node = $element->firstChild();
        $sibling = $node->nextSibling();

        static::assertInstanceOf(SimpleHtmlDom::class, $sibling);
        static::assertSame('<p>bar</p>', $sibling->outertext);
        static::assertSame('bar', $sibling->plaintext);

        $node = $element->lastChild();

        static::assertNull($node->nextSibling());
        static::assertNull($node->next_sibling());
    }

    public function testPreviousSibling()
    {
        $html = '<div><p>foo</p><p>bar</p></div>';

        $document = new HtmlDomParser($html);
        $element = new SimpleHtmlDom($document->getDocument()->documentElement);

        $node = $element->lastChild();
        $sibling = $node->previousSibling();

        static::assertInstanceOf(SimpleHtmlDom::class, $sibling);
        static::assertSame('<p>foo</p>', $sibling->outertext);
        static::assertSame('foo', $sibling->plaintext);

        $node = $element->firstChild();

        static::assertNull($node->previousSibling());
        static::assertNull($node->prev_sibling());
    }

    public function testParentNode()
    {
        $html = '<div><p>foo</p><p>bar</p></div>';

        $document = new HtmlDomParser($html);
        $element = $document->find('p', 0);

        $node = $element->parentNode();

        static::assertInstanceOf(SimpleHtmlDom::class, $node);
        static::assertSame('div', $node->tag);
        /** @noinspection PhpUndefinedFieldInspection */
        static::assertSame('div', $element->parent()->tag);
    }

    public function testHtml()
    {
        $html = '<div>foo</div>';
        $document = new HtmlDomParser($html);
        $element = new SimpleHtmlDom($document->getDocument()->documentElement);

        static::assertSame($html, $element->html());
        static::assertSame($html, $element->outerText());
        static::assertSame($html, $element->outertext);
        static::assertSame($html, (string) $element);
    }

    public function testInnerHtml()
    {
        $html = '<div><div>foo</div></div>';
        $document = new HtmlDomParser($html);
        $element = new SimpleHtmlDom($document->getDocument()->documentElement);

        static::assertSame('<div>foo</div>', $element->innerHtml());
        static::assertSame('<div>foo</div>', $element->innerText());
        /** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
        static::assertSame('<div>foo</div>', $element->innertext());
        static::assertSame('<div>foo</div>', $element->innertext);
    }

    public function testText()
    {
        $html = '<div>foo</div>';
        $document = new HtmlDomParser($html);
        $element = new SimpleHtmlDom($document->getDocument()->documentElement);

        static::assertSame('foo', $element->text());
        static::assertSame('foo', $element->plaintext);
    }

    public function testGetAllAttributes()
    {
        $attr = ['class' => 'post', 'id' => 'p1'];
        $html = '<html><div class="post" id="p1">foo</div><div>bar</div></html>';

        $document = new HtmlDomParser($html);

        $element = $document->find('div', 0);
        static::assertSame($attr, $element->getAllAttributes());

        $element = $document->find('div', 1);
        static::assertNull($element->getAllAttributes());
    }

    public function testGetAttribute()
    {
        $html = '<div class="post" id="p1">foo</div>';

        $document = new HtmlDomParser($html);
        $element = $document->find('div', 0);

        static::assertSame('post', $element->getAttribute('class'));
        static::assertSame('post', $element->class);
        static::assertSame('p1', $element->getAttribute('id'));
        static::assertSame('p1', $element->id);
    }

    public function testSetAttribute()
    {
        $html = '<div class="post" id="p1">foo</div>';

        $document = new HtmlDomParser($html);
        $element = $document->find('div', 0);

        $element->setAttribute('id', 'bar');
        $element->data = 'value';
        $element->class = null;

        static::assertSame('bar', $element->getAttribute('id'));
        static::assertSame('value', $element->getAttribute('data'));
        static::assertEmpty($element->getAttribute('class'));
        static::assertSame('<div id="bar" data="value">foo</div>', $element->html());
    }

    public function testHasAttribute()
    {
        $html = '<div class="post" id="p1">foo</div>';

        $document = new HtmlDomParser($html);
        $element = $document->find('div', 0);

        static::assertTrue($element->hasAttribute('class'));
        static::assertTrue(isset($element->id));
    }
}
