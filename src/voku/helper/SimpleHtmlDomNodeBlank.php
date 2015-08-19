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
   * dummy get
   *
   * @param $name
   *
   * @return bool|mixed|string
   */
  public function __get($name)
  {
    return '';
  }

  /**
   * dummy method
   *
   * @param $name
   * @param $arguments
   */
  public function __call($name, $arguments)
  {
  }

  /**
   * dummy clear
   */
  public function clear()
  {
  }
}
