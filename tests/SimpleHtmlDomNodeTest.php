<?php

use voku\helper\HtmlDomParser;

/**
 * Class SimpleHtmlDomNodeTest
 */
class SimpleHtmlDomNodeTest extends \PHPUnit\Framework\TestCase
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

    self::assertInstanceOf('voku\helper\SimpleHtmlDomNode', $elements);
    self::assertCount($count, $elements);

    foreach ($elements as $node) {
      self::assertInstanceOf('voku\helper\SimpleHtmlDom', $node);
    }
  }

  /**
   * @return array
   */
  public function findTests()
  {
    $html = $this->loadFixture('test_page.html');

    $tests = [
        [$html, '.fake h2', 0],
        [$html, 'article', 16],
        [$html, '.radio', 3],
        [$html, 'input.radio', 3],
        [$html, 'ul li', 9],
        [$html, 'fieldset#forms__checkbox li, fieldset#forms__radio li', 6],
        [$html, 'input[id]', 23],
        [$html, 'input[id=in]', 1],
        [$html, '#in', 1],
        [$html, 'text', 390],
        [$html, '*[id]', 51],
    ];

    return $tests;
  }

  public function testInnerHtml()
  {
    $html = '<div><p>foo</p><p>bar</p></div>';
    $document = new HtmlDomParser($html);
    $element = $document->find('p');

    self::assertSame('<p>foo</p><p>bar</p>', (string)$element);
    self::assertSame(['<p>foo</p>', '<p>bar</p>'], $element->innerHtml());
    self::assertSame(['foo', 'bar'], $element->innertext);
  }

  public function testText()
  {
    $html = '<div><p>foo</p><p>bar</p></div>';
    $document = new HtmlDomParser($html);
    $element = $document->find('p');

    self::assertSame(['foo', 'bar'], $element->text());
    self::assertSame(['foo', 'bar'], $element->plaintext);
  }

  public function testGetFirstDomElement()
  {
    $html = '<div><p class="lall">foo</p><p>lall</p></div>';
    $document = new HtmlDomParser($html);
    $element = $document->find('p');

    self::assertSame(['lall', ''], $element->class);
    self::assertSame(['foo', 'lall'], $element->plaintext);
  }
}
