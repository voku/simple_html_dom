<?php

namespace voku\helper;

/**
 * Class SimpleHtmlDomNodeBlank
 *
 * @package voku\helper
 *
 * @property-read string outertext <p>Get dom node's outer html.</p>
 * @property-read string plaintext <p>Get dom node's plain text.</p>
 */
class SimpleHtmlDomNodeBlank extends \ArrayObject implements SimpleHtmlDomNodeInterface
{
  /** @noinspection MagicMethodsValidityInspection */
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
   * @return null
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
    return '';
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
   * @param string $selector
   * @param null   $idx
   *
   * @return null
   */
  public function find($selector, $idx = null)
  {
    return null;
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
