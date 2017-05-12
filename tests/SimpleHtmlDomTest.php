<?php

use voku\helper\HtmlDomParser;
use voku\helper\SimpleHtmlDom;

/**
 * Class SimpleHtmlDomTest
 */
class SimpleHtmlDomTest extends PHPUnit_Framework_TestCase
{
  /**
   * @param $filename
   *
   * @return null|string
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

    self::assertSame('input', $element->tag);
    self::assertSame('User name', $element->plaintext);
    self::assertSame('username', $element->name);
    self::assertSame('John', $element->value);
  }

  public function testGetNode()
  {
    $html = '<div>foo</div>';

    $document = new HtmlDomParser($html);
    $node = $document->getDocument()->documentElement;
    $element = new SimpleHtmlDom($node);

    self::assertInstanceOf('DOMNode', $element->getNode());
  }

  public function testReplaceNode()
  {
    $html = '<div>foo</div>';
    $replace = '<h1>bar</h1>';

    $document = new HtmlDomParser($html);
    $node = $document->getDocument()->documentElement;
    $element = new SimpleHtmlDom($node);
    $element->outertext = $replace;

    self::assertSame($replace, $document->outertext);
    self::assertSame($replace, $element->outertext);

    $element->outertext = '';

    self::assertNotEquals($replace, $document->outertext);
  }

  public function testReplaceChild()
  {
    $html = '<div><p>foo</p></div>';
    $replace = '<h1>bar</h1>';

    $document = new HtmlDomParser($html);
    $node = $document->getDocument()->documentElement;
    $element = new SimpleHtmlDom($node);
    $element->innertext = $replace;

    self::assertSame('<div><h1>bar</h1></div>', $document->outertext);
    self::assertSame('<div><h1>bar</h1></div>', $element->outertext);
  }

  public function testGetDom()
  {
    $html = '<div><p>foo</p></div>';

    $document = new HtmlDomParser($html);
    $node = $document->getDocument()->documentElement;
    $element = new SimpleHtmlDom($node);

    self::assertInstanceOf('voku\helper\HtmlDomParser', $element->getHtmlDomParser());
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

    self::assertInstanceOf('voku\helper\SimpleHtmlDomNode', $elements);
    self::assertCount($count, $elements);

    foreach ($elements as $node) {
      self::assertInstanceOf('voku\helper\SimpleHtmlDom', $node);
    }

    $elements = $element($selector);

    self::assertInstanceOf('voku\helper\SimpleHtmlDomNode', $elements);
  }

  /**
   * @return array
   */
  public function findTests()
  {
    $html = $this->loadFixture('test_page.html');

    $tests = array(
        array($html, '.fake h2', 0),
        array($html, 'article', 16),
        array($html, '.radio', 3),
        array($html, 'input.radio', 3),
        array($html, 'ul li', 35),
        array($html, 'fieldset#forms__checkbox li, fieldset#forms__radio li', 6),
        array($html, 'input[id]', 23),
        array($html, 'input[id=in]', 1),
        array($html, '#in', 1),
        array($html, '*[id]', 52),
        array($html, 'text', 462),
        array($html, 'comment', 3),
    );

    return $tests;
  }

  public function testGetElementById()
  {
    $html = $this->loadFixture('test_page.html');

    $document = new HtmlDomParser($html);
    $element = new SimpleHtmlDom($document->getDocument()->documentElement);

    $node = $element->getElementById('in');

    self::assertInstanceOf('voku\helper\SimpleHtmlDom', $node);
    self::assertSame('input', $node->tag);
    self::assertSame('number', $node->type);
    self::assertSame('5', $node->value);
  }

  public function testGetElementByTagName()
  {
    $html = $this->loadFixture('test_page.html');

    $document = new HtmlDomParser($html);
    $element = new SimpleHtmlDom($document->getDocument()->documentElement);

    $node = $element->getElementByTagName('div');

    self::assertInstanceOf('voku\helper\SimpleHtmlDom', $node);
    self::assertSame('div', $node->tag);
    self::assertSame('top', $node->id);
    self::assertSame('page', $node->class);
  }

  public function testGetElementsByTagName()
  {
    $html = $this->loadFixture('test_page.html');

    $document = new HtmlDomParser($html);
    $element = new SimpleHtmlDom($document->getDocument()->documentElement);

    $elements = $element->getElementsByTagName('div');

    self::assertInstanceOf('voku\helper\SimpleHtmlDomNode', $elements);
    self::assertCount(16, $elements);

    foreach ($elements as $node) {
      self::assertInstanceOf('voku\helper\SimpleHtmlDom', $node);
    }
  }

  public function testChildNodes()
  {
    $html = '<div><p>foo</p><p>bar</p></div>';

    $document = new HtmlDomParser($html);
    $element = new SimpleHtmlDom($document->getDocument()->documentElement);

    $nodes = $element->childNodes();

    self::assertInstanceOf('voku\helper\SimpleHtmlDomNode', $nodes);
    self::assertCount(2, $nodes);

    foreach ($nodes as $node) {
      self::assertInstanceOf('voku\helper\SimpleHtmlDom', $node);
    }

    $node = $element->childNodes(1);

    self::assertInstanceOf('voku\helper\SimpleHtmlDom', $node);

    self::assertSame('<p>bar</p>', $node->outertext);
    self::assertSame('bar', $node->plaintext);

    $node = $element->childNodes(2);
    self::assertNull($node);
  }

  public function testChildren()
  {
    $html = '<div><p>foo</p><p>bar</p></div>';

    $document = new HtmlDomParser($html);
    $element = new SimpleHtmlDom($document->getDocument()->documentElement);

    $nodes = $element->children();

    self::assertInstanceOf('voku\helper\SimpleHtmlDomNode', $nodes);
    self::assertCount(2, $nodes);

    foreach ($nodes as $node) {
      self::assertInstanceOf('voku\helper\SimpleHtmlDom', $node);
    }

    $node = $element->children(1);

    self::assertInstanceOf('voku\helper\SimpleHtmlDom', $node);

    self::assertSame('<p>bar</p>', $node->outertext);
    self::assertSame('bar', $node->plaintext);
  }

  public function testFirstChild()
  {
    $html = '<div><p>foo</p><p></p></div>';

    $document = new HtmlDomParser($html);
    $element = new SimpleHtmlDom($document->getDocument()->documentElement);

    $node = $element->firstChild();

    self::assertInstanceOf('voku\helper\SimpleHtmlDom', $node);
    self::assertSame('<p>foo</p>', $node->outertext);
    self::assertSame('foo', $node->plaintext);

    $node = $element->lastChild();

    self::assertInstanceOf('voku\helper\SimpleHtmlDom', $node);
    self::assertSame('<p></p>', $node->outertext);
    self::assertSame('', $node->plaintext);

    self::assertNull($node->firstChild());
    self::assertNull($node->first_child());
  }

  public function testLastChild()
  {
    $html = '<div><p></p><p>bar</p></div>';

    $document = new HtmlDomParser($html);
    $element = new SimpleHtmlDom($document->getDocument()->documentElement);

    $node = $element->lastChild();

    self::assertInstanceOf('voku\helper\SimpleHtmlDom', $node);
    self::assertSame('<p>bar</p>', $node->outertext);
    self::assertSame('bar', $node->plaintext);

    $node = $element->firstChild();

    self::assertInstanceOf('voku\helper\SimpleHtmlDom', $node);
    self::assertSame('<p></p>', $node->outertext);
    self::assertSame('', $node->plaintext);

    self::assertNull($node->lastChild());
    self::assertNull($node->last_child());
  }

  public function testNextSibling()
  {
    $html = '<div><p>foo</p><p>bar</p></div>';

    $document = new HtmlDomParser($html);
    $element = new SimpleHtmlDom($document->getDocument()->documentElement);

    $node = $element->firstChild();
    $sibling = $node->nextSibling();

    self::assertInstanceOf('voku\helper\SimpleHtmlDom', $sibling);
    self::assertSame('<p>bar</p>', $sibling->outertext);
    self::assertSame('bar', $sibling->plaintext);

    $node = $element->lastChild();

    self::assertNull($node->nextSibling());
    self::assertNull($node->next_sibling());
  }

  public function testPreviousSibling()
  {
    $html = '<div><p>foo</p><p>bar</p></div>';

    $document = new HtmlDomParser($html);
    $element = new SimpleHtmlDom($document->getDocument()->documentElement);

    $node = $element->lastChild();
    $sibling = $node->previousSibling();

    self::assertInstanceOf('voku\helper\SimpleHtmlDom', $sibling);
    self::assertSame('<p>foo</p>', $sibling->outertext);
    self::assertSame('foo', $sibling->plaintext);

    $node = $element->firstChild();

    self::assertNull($node->previousSibling());
    self::assertNull($node->prev_sibling());
  }

  public function testParentNode()
  {
    $html = '<div><p>foo</p><p>bar</p></div>';

    $document = new HtmlDomParser($html);
    $element = $document->find('p', 0);

    $node = $element->parentNode();

    self::assertInstanceOf('voku\helper\SimpleHtmlDom', $node);
    self::assertSame('div', $node->tag);
    /** @noinspection PhpUndefinedFieldInspection */
    self::assertSame('div', $element->parent()->tag);
  }

  public function testHtml()
  {
    $html = '<div>foo</div>';
    $document = new HtmlDomParser($html);
    $element = new SimpleHtmlDom($document->getDocument()->documentElement);

    self::assertSame($html, $element->html());
    self::assertSame($html, $element->outerText());
    self::assertSame($html, $element->outertext);
    self::assertSame($html, (string)$element);
  }

  public function testInnerHtml()
  {
    $html = '<div><div>foo</div></div>';
    $document = new HtmlDomParser($html);
    $element = new SimpleHtmlDom($document->getDocument()->documentElement);

    self::assertSame('<div>foo</div>', $element->innerHtml());
    self::assertSame('<div>foo</div>', $element->innerText());
    /** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
    self::assertSame('<div>foo</div>', $element->innertext());
    self::assertSame('<div>foo</div>', $element->innertext);
  }

  public function testText()
  {
    $html = '<div>foo</div>';
    $document = new HtmlDomParser($html);
    $element = new SimpleHtmlDom($document->getDocument()->documentElement);

    self::assertSame('foo', $element->text());
    self::assertSame('foo', $element->plaintext);
  }

  public function testGetAllAttributes()
  {
    $attr = array('class' => 'post', 'id' => 'p1');
    $html = '<html><div class="post" id="p1">foo</div><div>bar</div></html>';

    $document = new HtmlDomParser($html);

    $element = $document->find('div', 0);
    self::assertSame($attr, $element->getAllAttributes());

    $element = $document->find('div', 1);
    self::assertNull($element->getAllAttributes());
  }

  public function testGetAttribute()
  {
    $html = '<div class="post" id="p1">foo</div>';

    $document = new HtmlDomParser($html);
    $element = $document->find('div', 0);

    self::assertSame('post', $element->getAttribute('class'));
    self::assertSame('post', $element->class);
    self::assertSame('p1', $element->getAttribute('id'));
    self::assertSame('p1', $element->id);
  }

  public function testSetAttribute()
  {
    $html = '<div class="post" id="p1">foo</div>';

    $document = new HtmlDomParser($html);
    $element = $document->find('div', 0);

    $element->setAttribute('id', 'bar');
    $element->data = 'value';
    unset($element->class);

    self::assertSame('bar', $element->getAttribute('id'));
    self::assertSame('value', $element->getAttribute('data'));
    self::assertEmpty($element->getAttribute('class'));
  }

  public function testHasAttribute()
  {
    $html = '<div class="post" id="p1">foo</div>';

    $document = new HtmlDomParser($html);
    $element = $document->find('div', 0);

    self::assertTrue($element->hasAttribute('class'));
    self::assertTrue(isset($element->id));
  }
}
