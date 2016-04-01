<?php

namespace voku\helper;

/**
 * Class SimpleHtmlDomNodeBlank
 * 
 * @package voku\helper
 *
 * @property-read string outertext Get dom node's outer html
 * @property-read string plaintext Get dom node's plain text
 */
class SimpleHtmlDomNodeBlank extends \ArrayObject
{
  /**
   * @param $name
   *
   * @return string
   */
  public function __get($name)
  {
    return '';
  }

  /**
   * @param $name
   * @param $arguments
   *
   * @return string
   */
  public function __call($name, $arguments)
  {
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
    return null;
  }

  /**
   * @return string
   */
  public function __toString()
  {
    return $this->innerHtml();
  }

  /**
   * Get html of Elements
   *
   * @return string
   */
  public function innerHtml()
  {
    return '';
  }

  /**
   * Get plain text
   *
   * @return string
   */
  public function text()
  {
    return '';
  }
}
