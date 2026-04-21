<?php

use voku\helper\HtmlDomParser;
use voku\helper\SimpleHtmlDom;

/**
 * @internal
 */
final class SimpleHtmlDomTest extends \PHPUnit\Framework\TestCase
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

        static::assertInstanceOf(\DOMNode::class, $element->getNode());
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

    public function testNestedFindOnManualWrapperScopesUnionSelectors()
    {
        $document = HtmlDomParser::str_get_html(
            '<html><body>body<!--body-comment--><img src="body.jpg"></body><footer>footer<!--footer-comment--><img src="footer.jpg"></footer></html>'
        );
        $element = new SimpleHtmlDom($document->getDocument()->documentElement);

        $body = $element->findOne('body');
        $nodes = $body->find('text, comment');

        static::assertCount(2, $nodes);
        static::assertSame(['body', 'body-comment'], $nodes->text());

        $body->findOne('img')->delete();

        static::assertSame(
            '<html><body>body<!--body-comment--></body><footer>footer<!--footer-comment--><img src="footer.jpg"></footer></html>',
            $document->outerHtml()
        );
    }

    public function testIssue63()
    {
        $dom = (new voku\helper\HtmlDomParser())->loadHtml('<div> foo bar </div>');
        static::assertSame('foo bar', $dom->findOne('div')->innerHtml());
        static::assertInstanceOf(\voku\helper\SimpleHtmlDomBlank::class, $dom->findOne('span'));
        static::assertInstanceOf(\voku\helper\SimpleHtmlDomBlank::class, $dom->findOne('span')->findOne('span'));
        static::assertInstanceOf(\voku\helper\SimpleHtmlDomBlank::class, $dom->findOne('span')->findOne('span')->findOne('span'));
        static::assertInstanceOf(\voku\helper\SimpleHtmlDomBlank::class, $dom->findOne('span')->findOne('span')->findOne('span')->findOne('span'));
    }

    public function testCommentWp()
    {
        $html = '
        <!-- wp:heading -->
        <h2 id="my-title">Level 2 title</h2>
        <!-- /wp:heading -->
        ';

        $d = new voku\helper\HtmlDomParser();
        $d->loadHtml($html);

        static::assertSame($html, $d->html());
    }

    public function testAppendPrependIssue()
    {
        $d = new voku\helper\HtmlDomParser();
        $d->loadHtml('<p>p1</p><p>p2</p>');
        $p = $d->find('p', 0);
        $p->outerhtml .= '<div>outer</div>';

        static::assertSame('<p>p1</p><div>outer</div><p>p2</p>', $d->html());
        static::assertSame('p1outerp2', $d->plaintext);
    }

    public function testMultiRootElements()
    {
        $html = '
<p>
	foo <code>bar</code>. ZIiiii  zzz <code>1.1</code> Lorem ipsum dolor sit amet, consectetur adipiscing elit.
</p>
						
<p>
	<h3>Vestibulum eget velit arcu.</h3>

	Vestibulum eget velit arcu. Phasellus eget scelerisque dui, nec elementum ante. <code>aoaoaoao</code>
</p>
';

        $d = new voku\helper\HtmlDomParser();
        $d->loadHtml($html);

        static::assertSame($html, $d->html());
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
            [
                '<p>bar</p>',
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

    public function testReplaceNodeWithParagraphWrapper()
    {
        $document = new HtmlDomParser('<div><span>foo</span><span>bar</span></div>');

        $elementsOrFalse = $document->findMultiOrFalse('span');

        static::assertNotFalse($elementsOrFalse);

        foreach ($elementsOrFalse as $element) {
            $element->outerhtml = '<p>' . $element->innerhtml . '</p>';
        }

        static::assertSame('<div><p>foo</p><p>bar</p></div>', $document->outertext);
        static::assertSame('foobar', $document->plaintext);
    }

    public function paragraphReplacementVariantProvider()
    {
        return [
            [
                '<p>foo</p><p>bar</p>',
                '<div><p>foo</p><p>bar</p></div>',
                'foobar',
            ],
            [
                '<p>foo<source src="a.mp4"></p>',
                '<div><p>foo<source src="a.mp4"></p></div>',
                'foo',
            ],
            [
                '<p>foo<wbr>bar</p>',
                '<div><p>foo<wbr>bar</p></div>',
                'foobar',
            ],
            [
                '<P>foo</P><P>bar</P>',
                '<div><P>foo</P><P>bar</P></div>',
                'foobar',
            ],
        ];
    }

    /**
     * @dataProvider paragraphReplacementVariantProvider
     *
     * @param string $replace
     * @param string $expectedHtml
     * @param string $expectedText
     */
    public function testReplaceNodeWithParagraphWrapperVariants($replace, $expectedHtml, $expectedText)
    {
        $document = new HtmlDomParser('<div><span>x</span></div>');
        $element = $document->findOne('span');

        $element->outerhtml = $replace;

        static::assertSame($expectedHtml, $document->outertext);
        static::assertSame($expectedText, $document->plaintext);
    }

    public function testGetDom()
    {
        $html = '<div><p>foo</p></div>';

        $document = new HtmlDomParser($html);
        $node = $document->getDocument()->documentElement;
        $element = new SimpleHtmlDom($node);

        static::assertInstanceOf(voku\helper\HtmlDomParser::class, $element->getHtmlDomParser());
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

        static::assertInstanceOf(voku\helper\SimpleHtmlDomNodeInterface::class, $elements);
        static::assertCount($count, $elements);

        foreach ($elements as $node) {
            static::assertInstanceOf(voku\helper\SimpleHtmlDomInterface::class, $node);
        }

        $elements = $element($selector);

        static::assertInstanceOf(voku\helper\SimpleHtmlDomNodeInterface::class, $elements);
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

        static::assertInstanceOf(voku\helper\SimpleHtmlDom::class, $node);
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

        static::assertInstanceOf(voku\helper\SimpleHtmlDom::class, $node);
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

        static::assertInstanceOf(\voku\helper\SimpleHtmlDomNode::class, $elements);
        static::assertCount(16, $elements);

        foreach ($elements as $node) {
            static::assertInstanceOf(voku\helper\SimpleHtmlDom::class, $node);
        }
    }

    public function testChildNodes()
    {
        $html = '<div><p>foo</p><p>bar</p></div>';

        $document = new HtmlDomParser($html);
        $element = new SimpleHtmlDom($document->getDocument()->documentElement);

        $nodes = $element->childNodes();

        static::assertInstanceOf(voku\helper\SimpleHtmlDomNode::class, $nodes);
        static::assertCount(2, $nodes);

        foreach ($nodes as $node) {
            static::assertInstanceOf(voku\helper\SimpleHtmlDom::class, $node);
        }

        $node = $element->childNodes(1);

        static::assertInstanceOf(voku\helper\SimpleHtmlDom::class, $node);

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

        static::assertInstanceOf(voku\helper\SimpleHtmlDomNode::class, $nodes);
        static::assertCount(2, $nodes);

        foreach ($nodes as $node) {
            static::assertInstanceOf(voku\helper\SimpleHtmlDom::class, $node);
        }

        $node = $element->children(1);

        static::assertInstanceOf(voku\helper\SimpleHtmlDom::class, $node);

        static::assertSame('<p>bar</p>', $node->outertext);
        static::assertSame('bar', $node->plaintext);
    }

    public function testFirstChild()
    {
        $html = '<div><p>foo</p><p></p></div>';

        $document = new HtmlDomParser($html);
        $element = new SimpleHtmlDom($document->getDocument()->documentElement);

        $node = $element->firstChild();

        static::assertInstanceOf(voku\helper\SimpleHtmlDom::class, $node);
        static::assertSame('<p>foo</p>', $node->outertext);
        static::assertSame('foo', $node->plaintext);

        $node = $element->lastChild();

        static::assertInstanceOf(voku\helper\SimpleHtmlDom::class, $node);
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

        static::assertInstanceOf(voku\helper\SimpleHtmlDom::class, $node);
        static::assertSame('<p>bar</p>', $node->outertext);
        static::assertSame('bar', $node->plaintext);

        $node = $element->firstChild();

        static::assertInstanceOf(voku\helper\SimpleHtmlDom::class, $node);
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

        static::assertInstanceOf(voku\helper\SimpleHtmlDom::class, $sibling);
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

        static::assertInstanceOf(voku\helper\SimpleHtmlDom::class, $sibling);
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

        static::assertInstanceOf(voku\helper\SimpleHtmlDom::class, $node);
        static::assertSame('div', $node->tag);
        /** @noinspection PhpUndefinedFieldInspection */
        static::assertSame('div', $element->parent()->tag);
    }

    public function testRootElementHasNoWrappedDocumentParent()
    {
        $document = HtmlDomParser::str_get_html('<html><body><div>ok</div></body></html>');

        static::assertNull($document->getElementByTagName('html')->parentNode());
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

    public function testIssue112()
    {
        $html = '<div class="woocommerce-variation single_variation">
            <div class="woocommerce-variation-description"></div>
            <div class="woocommerce-variation-price"></div>
            <div class="woocommerce-variation-availability"><p class="stock in-stock">30 in stock</p></div>
        </div>';

        $expected = '<div class="woocommerce-variation single_variation">
            <div class="woocommerce-variation-description"></div>
            <div class="woocommerce-variation-price"></div>
            <div class="woocommerce-variation-availability"><p class="stock in-stock">30 in stock</p></div>
        </div>';

        $document = new HtmlDomParser($html);
        $htmlNew = $document->html();
        static::assertSame($expected, $htmlNew);

        $availabilityHtml = $document->findOne('.woocommerce-variation-availability');
        static::assertSame('<p class="stock in-stock">30 in stock</p>', $availabilityHtml->innerHtml());
    }

    public function testLookupValueHelpersAndRemovedNodeState()
    {
        $document = HtmlDomParser::str_get_html(
            '<form>'
            . '<input id="plain" value="alpha">'
            . '<input id="choice" type="radio" value="yes" checked>'
            . '<textarea id="notes">body</textarea>'
            . '<select id="picker"><option value="one">One</option><option value="two" selected>Two</option></select>'
            . '<div id="first" class="alpha"><span>child</span></div>'
            . '<div id="second" class="beta"></div>'
            . '</form>'
        );
        $form = $document->findOne('form');

        $textInput = $form->getElementById('plain');
        $radio = $form->getElementsById('choice', 0);
        $textarea = $form->getElementByTagName('textarea');
        $select = $form->getElementByTagName('select');

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

        static::assertSame(['two'], $select->val());
        $select->val('two');
        static::assertFalse($select->getElementsByTagName('option', 0)->hasAttribute('selected'));
        static::assertSame('selected', $select->getElementsByTagName('option', -1)->getAttribute('selected'));

        static::assertSame(['alpha'], $form->getElementByClass('alpha')->class);
        static::assertSame('second', $form->getElementsByTagName('div', -1)->getAttribute('id'));
        static::assertInstanceOf(\voku\helper\SimpleHtmlDomNodeBlank::class, $form->getElementsByTagName('missing'));
        static::assertInstanceOf(\voku\helper\SimpleHtmlDomBlank::class, $form->getElementsByTagName('missing', 0));

        $corrupted = new SimpleHtmlDom(new \DOMElement('free'));
        $nodeProperty = new \ReflectionProperty(\voku\helper\AbstractSimpleHtmlDom::class, 'node');
        if (\PHP_VERSION_ID < 80100) {
            $nodeProperty->setAccessible(true);
        }
        $nodeProperty->setValue($corrupted, new \stdClass());
        static::assertTrue($corrupted->isRemoved());

        $renameDocument = HtmlDomParser::str_get_html('<div><span>child</span></div>');
        $renameTarget = $renameDocument->findOne('div');
        $renamer = new class($renameTarget->getNode()) extends SimpleHtmlDom {
            public function renameNode(\DOMNode $node, string $name)
            {
                return $this->changeElementName($node, $name);
            }
        };

        $renamed = $renamer->renameNode($renameTarget->getNode(), 'section');

        static::assertInstanceOf(\DOMElement::class, $renamed);
        static::assertSame('section', $renamed->nodeName);
        static::assertSame('section', $renameDocument->findOne('section')->tag);
        static::assertFalse($renamer->renameNode(new \DOMElement('free'), 'section'));
        static::assertNull((new SimpleHtmlDom(new \DOMElement('free')))->parentNode());
    }

    public function testSelectValReturnsNullWithoutSelectedOption()
    {
        $document = HtmlDomParser::str_get_html(
            '<form><select id="picker"><option value="one">One</option><option value="two">Two</option></select></form>'
        );

        static::assertNull($document->getElementById('picker')->val());
    }

    public function testSelectValSetterSupportsMultipleSelectedValues()
    {
        $document = HtmlDomParser::str_get_html(
            '<form><select id="picker" multiple><option value="one">One</option><option value="two">Two</option><option value="three">Three</option></select></form>'
        );

        $select = $document->getElementById('picker');
        $select->val(['one', 'three']);

        static::assertSame(['one', 'three'], $select->val());
        static::assertSame('selected', $select->getElementsByTagName('option', 0)->getAttribute('selected'));
        static::assertFalse($select->getElementsByTagName('option', 1)->hasAttribute('selected'));
        static::assertSame('selected', $select->getElementsByTagName('option', 2)->getAttribute('selected'));
    }

    public function testCheckboxAndRadioValSetterSupportArrayValues()
    {
        $document = HtmlDomParser::str_get_html(
            '<form>'
            . '<input id="checkbox" type="checkbox" value="one">'
            . '<input id="radio" type="radio" value="two" checked>'
            . '</form>'
        );

        $checkbox = $document->getElementById('checkbox');
        $radio = $document->getElementById('radio');

        $checkbox->val(['one', 'three']);
        $radio->val(['one', 'three']);

        static::assertTrue($checkbox->hasAttribute('checked'));
        static::assertFalse($radio->hasAttribute('checked'));
    }

    public function testProtectedRenamePreservesAttributesAndSupportsNestedNodes()
    {
        $document = HtmlDomParser::str_get_html(
            '<div id="first" class="alpha"><span data-role="x">child</span></div>'
        );
        $target = $document->findOne('div');
        $renamer = new class($target->getNode()) extends SimpleHtmlDom {
            public function renameNode(\DOMNode $node, string $name)
            {
                return $this->changeElementName($node, $name);
            }
        };

        $renamer->renameNode($target->getNode(), 'section');

        $section = $document->findOne('section');
        static::assertSame('first', $section->getAttribute('id'));
        static::assertSame('alpha', $section->getAttribute('class'));
        static::assertSame('child', $section->findOne('span')->text());

        $nestedNode = $document->getDocument()->getElementsByTagName('span')->item(0);
        static::assertInstanceOf(\DOMNode::class, $nestedNode);
        $renamer->renameNode($nestedNode, 'strong');

        $strong = $document->findOne('strong');
        static::assertSame('x', $strong->getAttribute('data-role'));
        static::assertSame('child', $strong->text());
    }
}
