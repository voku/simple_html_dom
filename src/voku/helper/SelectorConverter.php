<?php

declare(strict_types=1);

namespace voku\helper;

use Symfony\Component\CssSelector\CssSelectorConverter;

/**
 * Class SelectorConverter
 *
 * @package voku\helper
 */
class SelectorConverter
{
  protected static $compiled = array();

  /**
   * @param string $selector
   *
   * @return mixed|string
   *
   * @throws \RuntimeException
   */
  public static function toXPath(string $selector)
  {
    if (isset(self::$compiled[$selector])) {
      return self::$compiled[$selector];
    }

    // Select DOMText
    if ($selector === 'text') {
      return '//text()';
    }

    // Select DOMComment
    if ($selector === 'comment') {
      return '//comment()';
    }

    if (\strpos($selector, '//') === 0) {
      return $selector;
    }

    if (!\class_exists(CssSelectorConverter::class)) {
      throw new \RuntimeException('Unable to filter with a CSS selector as the Symfony CssSelector 2.8+ is not installed (you can use filterXPath instead).');
    }

    $converter = new CssSelectorConverter(true);

    $xPathQuery = $converter->toXPath($selector);
    self::$compiled[$selector] = $xPathQuery;

    return $xPathQuery;
  }
}
