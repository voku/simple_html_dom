<?php

namespace voku\helper;

/**
 * simple html dom node - blank
 *
 * @package voku\helper
 */
class SimpleHtmlDomNodeBlank
{
  /**
   * magic get
   *
   * @param $name
   *
   * @return bool|mixed|string
   */
  public function __get($name)
  {
    return '';
  }
}