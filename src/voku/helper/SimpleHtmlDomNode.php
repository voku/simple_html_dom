<?php

namespace voku\helper;

/**
 * Class SimpleHtmlDomNode
 *
 * @package FastSimpleHTMLDom
 * @property-read string outertext Get dom node's outer html
 * @property-read string plaintext Get dom node's plain text
 */
class SimpleHtmlDomNode extends \ArrayObject
{
  /**
   * @param $name
   *
   * @return string
   */
  public function __get($name)
  {
    switch ($name) {
      case 'innertext':
        return $this->innerHtml();
      case 'plaintext':
        return $this->text();
    }

    return null;
  }

  /**
   * @param string $selector
   * @param int    $idx
   *
   * @return SimpleHtmlDom|SimpleHtmlDomNode|null
   */
  public function __invoke($selector, $idx = null)
  {
    return $this->find($selector, $idx);
  }

  /**
   * @return mixed
   */
  public function __toString()
  {
    return $this->innerHtml();
  }

  /**
   * Find list of nodes with a CSS selector
   *
   * @param string $selector
   * @param int    $idx
   *
   * @return SimpleHtmlDomNode|SimpleHtmlDom|null
   */
  public function find($selector, $idx = null)
  {
    $elements = new self();
    foreach ($this as $node) {
      foreach ($node->find($selector) as $res) {
        $elements->append($res);
      }
    }

    if (null === $idx) {
      return $elements;
    } else {
      if ($idx < 0) {
        $idx = count($elements) + $idx;
      }
    }

    return (isset($elements[$idx]) ? $elements[$idx] : null);
  }

  /**
   * Get html of Elements
   *
   * @return string
   */
  public function innerHtml()
  {
    $text = '';
    foreach ($this as $node) {
      $text .= $node->outertext;
    }

    return $text;
  }

  /**
   * Get plain text
   *
   * @return string
   */
  public function text()
  {
    $text = '';
    foreach ($this as $node) {
      $text .= $node->plaintext;
    }

    return $text;
  }
}
