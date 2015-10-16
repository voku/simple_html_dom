<?php

namespace voku\helper;

/**
 * Website: http://sourceforge.net/projects/simplehtmldom/
 *
 * Acknowledge: Jose Solorzano (https://sourceforge.net/projects/php-html/)
 * Contributions by:
 *   Yousuke Kumakura (Attribute filters)
 *   Vadim Voituk (Negative indexes supports of "find" method)
 *   Antcs (Constructor with automatically load contents either text or file/url)
 *
 * all affected sections have comments starting with "PaperG"
 *
 * Paperg - Added case insensitive testing of the value of the selector.
 * Paperg - Added tag_start for the starting index of tags - NOTE: This works but not accurately.
 *  This tag_start gets counted AFTER \r\n have been crushed out, and after the remove_noice calls so it will not
 *  reflect the REAL position of the tag in the source, it will almost always be smaller by some amount. We use this
 *  to determine how far into the file the tag in question is.  This "percentage will never be accurate as the
 *  $dom->size is the "real" number of bytes the dom was created from. but for most purposes, it's a really good
 *  estimation. Paperg - Added the forceTagsClosed to the dom constructor.  Forcing tags closed is great for
 *  malformed html, but it CAN lead to parsing errors. Allow the user to tell us how much they trust the html. Paperg
 *  add the text and plaintext to the selectors for the find syntax.  plaintext implies text in the innertext of a
 *  node.  text implies that the tag is a text node. This allows for us to find tags based on the text they contain.
 *  Create find_ancestor_tag to see if a tag is - at any level - inside of another specific tag. Paperg: added
 *  parse_charset so that we know about the character set of the source document. NOTE:  If the user's system has a
 *  routine called get_last_retrieve_url_contents_content_type available, we will assume it's returning the
 *  content-type header from the last transfer or curl_exec, and we will parse that and use it in preference to any
 *  other method of charset detection.
 *
 * Found infinite loop in the case of broken html in restore_noise.  Rewrote to protect from that.
 * PaperG (John Schlick) Added get_display_size for "IMG" tags.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author     S.C. Chen <me578022@gmail.com>
 * @author     John Schlick
 * @author     Rus Carroll
 * @author     Lars Moelleken
 * @version    1.6.5
 */

/**
 * All of the Defines for the classes below.
 */
if (!defined('HDOM_TYPE_ELEMENT')) {
  define('HDOM_TYPE_ELEMENT', 1);
}
if (!defined('HDOM_TYPE_COMMENT')) {
  define('HDOM_TYPE_COMMENT', 2);
}
if (!defined('HDOM_TYPE_TEXT')) {
  define('HDOM_TYPE_TEXT', 3);
}
if (!defined('HDOM_TYPE_ENDTAG')) {
  define('HDOM_TYPE_ENDTAG', 4);
}
if (!defined('HDOM_TYPE_ROOT')) {
  define('HDOM_TYPE_ROOT', 5);
}
if (!defined('HDOM_TYPE_UNKNOWN')) {
  define('HDOM_TYPE_UNKNOWN', 6);
}
if (!defined('HDOM_QUOTE_DOUBLE')) {
  define('HDOM_QUOTE_DOUBLE', 0);
}
if (!defined('HDOM_QUOTE_SINGLE')) {
  define('HDOM_QUOTE_SINGLE', 1);
}
if (!defined('HDOM_QUOTE_NO')) {
  define('HDOM_QUOTE_NO', 3);
}
if (!defined('HDOM_INFO_BEGIN')) {
  define('HDOM_INFO_BEGIN', 0);
}
if (!defined('HDOM_INFO_END')) {
  define('HDOM_INFO_END', 1);
}
if (!defined('HDOM_INFO_QUOTE')) {
  define('HDOM_INFO_QUOTE', 2);
}
if (!defined('HDOM_INFO_SPACE')) {
  define('HDOM_INFO_SPACE', 3);
}
if (!defined('HDOM_INFO_TEXT')) {
  define('HDOM_INFO_TEXT', 4);
}
if (!defined('HDOM_INFO_INNER')) {
  define('HDOM_INFO_INNER', 5);
}
if (!defined('HDOM_INFO_OUTER')) {
  define('HDOM_INFO_OUTER', 6);
}
if (!defined('HDOM_INFO_ENDSPACE')) {
  define('HDOM_INFO_ENDSPACE', 7);
}
if (!defined('DEFAULT_TARGET_CHARSET')) {
  define('DEFAULT_TARGET_CHARSET', 'UTF-8');
}
if (!defined('DEFAULT_BR_TEXT')) {
  define('DEFAULT_BR_TEXT', "\r\n");
}
if (!defined('DEFAULT_SPAN_TEXT')) {
  define('DEFAULT_SPAN_TEXT', ' ');
}
if (!defined('MAX_FILE_SIZE')) {
  define('MAX_FILE_SIZE', 600000);
}

/**
 * Class HtmlDomParser
 *
 * @package voku\helper
 */
class HtmlDomParser
{

  /**
   * helper functions
   *
   * get html dom from file
   *
   * @param        $url
   * @param bool   $use_include_path
   * @param null   $context
   * @param int    $offset
   * @param int    $maxLen
   * @param bool   $lowercase
   * @param bool   $forceTagsClosed
   * @param string $target_charset
   * @param bool   $stripRN
   * @param string $defaultBRText
   * @param string $defaultSpanText
   *
   * @return bool|\voku\helper\SimpleHtmlDom
   */
  public static function file_get_html($url, $use_include_path = false, $context = null, $offset = -1, $maxLen = -1, $lowercase = true, $forceTagsClosed = true, $target_charset = DEFAULT_TARGET_CHARSET, $stripRN = true, $defaultBRText = DEFAULT_BR_TEXT, $defaultSpanText = DEFAULT_SPAN_TEXT)
  {
    // We DO force the tags to be terminated.
    $dom = new SimpleHtmlDom(null, $lowercase, $forceTagsClosed, $target_charset, $stripRN, $defaultBRText, $defaultSpanText);

    // For sourceforge users: uncomment the next line and comment the retreive_url_contents line 2 lines down if it is not already done.
    $contents = file_get_contents($url, $use_include_path, $context, $offset);

    // Paperg - use our own mechanism for getting the contents as we want to control the timeout.
    //$contents = retrieve_url_contents($url);

    if ($maxLen == -1) {
      $maxLen = MAX_FILE_SIZE;
    }

    if (empty($contents) || strlen($contents) > $maxLen) {
      return false;
    }

    // The second parameter can force the selectors to all be lowercase.
    $dom->load($contents, $lowercase, $stripRN);

    return $dom;
  }

  /**
   * get html dom from string
   *
   * @param        $str
   * @param bool   $lowercase
   * @param bool   $forceTagsClosed
   * @param string $target_charset
   * @param bool   $stripRN
   * @param string $defaultBRText
   * @param string $defaultSpanText
   *
   * @return bool|\voku\helper\SimpleHtmlDom
   */
  public static function str_get_html($str, $lowercase = true, $forceTagsClosed = true, $target_charset = DEFAULT_TARGET_CHARSET, $stripRN = true, $defaultBRText = DEFAULT_BR_TEXT, $defaultSpanText = DEFAULT_SPAN_TEXT)
  {
    $dom = new SimpleHtmlDom(null, $lowercase, $forceTagsClosed, $target_charset, $stripRN, $defaultBRText, $defaultSpanText);

    if (empty($str) || strlen($str) > MAX_FILE_SIZE) {
      $dom->clear();

      return false;
    }

    $dom->load($str, $lowercase, $stripRN);

    return $dom;
  }

  /**
   * dump html dom tree
   *
   * @param SimpleHtmlDomNode $node
   */
  public static function dump_html_tree($node)
  {
    $node->dump($node);
  }

}
