<?php

namespace voku\helper;

/**
 * Class SimpleHtmlDomNode
 *
 * @package voku\helper
 *
 * @property-read string outertext <p>Get dom node's outer html.</p>
 * @property-read string plaintext <p>Get dom node's plain text.</p>
 */
class SimpleHtmlDomNode extends \ArrayObject implements SimpleHtmlDomNodeInterface
{
  /** @noinspection MagicMethodsValidityInspection */
  /**
   * @param string $name
   *
   * @return array|null
   */
  public function __get($name)
  {
    $name = strtolower($name);

    if ($this->count() > 0) {
      $return = array();

      foreach ($this as $node) {
        if ($node instanceof SimpleHtmlDom) {
          $return[] = $node->{$name};
        }
      }

      return $return;
    }

    return null;
  }

  /**
   * alias for "$this->innerHtml()" (added for compatibly-reasons with v1.x)
   */
  public function outertext()
  {
    $this->innerHtml();
  }

  /**
   * alias for "$this->innerHtml()" (added for compatibly-reasons with v1.x)
   */
  public function innertext()
  {
    $this->innerHtml();
  }

  /**
   * @param string $selector
   * @param int    $idx
   *
   * @return SimpleHtmlDomNode[]|SimpleHtmlDomNode|null
   */
  public function __invoke($selector, $idx = null)
  {
    return $this->find($selector, $idx);
  }

  /**
   * @return string
   */
  public function __toString()
  {
    $html = '';
    foreach ($this as $node) {
      $html .= $node->outertext;
    }

    return $html;
  }

  /**
   * Find list of nodes with a CSS selector.
   *
   * @param string $selector
   * @param int    $idx
   *
   * @return SimpleHtmlDomNode[]|SimpleHtmlDomNode|null
   */
  public function find($selector, $idx = null)
  {
    $elements = new self();
    foreach ($this as $node) {
      foreach ($node->find($selector) as $res) {
        $elements->append($res);
      }
    }

    // return all elements
    if (null === $idx) {
      return $elements;
    }

    // handle negative values
    if ($idx < 0) {
      $idx = count($elements) + $idx;
    }

    // return one element
    if (isset($elements[$idx])) {
      return $elements[$idx];
    }

    return null;
  }

  /**
   * Get html of elements.
   *
   * @return array
   */
  public function innerHtml()
  {
    $html = array();
    foreach ($this as $node) {
      $html[] = $node->outertext;
    }

    return $html;
  }

  /**
   * Get plain text.
   *
   * @return array
   */
  public function text()
  {
    $text = array();
    foreach ($this as $node) {
      $text[] = $node->plaintext;
    }

    return $text;
  }
}
