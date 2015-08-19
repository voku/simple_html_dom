<?php

namespace voku\helper;

/**
 * simple html dom node
 *
 * PaperG - added ability for "find" routine to lowercase the value of the selector.
 * PaperG - added $tag_start to track the start position of the tag in the total byte index
 *
 * @property string alt
 * @property string class
 * @property string name
 * @property string src
 * @property string checked
 * @property string outertext
 * @property string innertext
 *
 * @package voku\helper
 */
class SimpleHtmlDomNode
{
  public $nodetype = HDOM_TYPE_TEXT;

  /**
   * @var string
   */
  public $tag = 'text';

  /**
   * @var array
   */
  public $attr = array();

  /**
   * @var SimpleHtmlDomNode[]
   */
  public $children = array();

  /**
   * @var SimpleHtmlDomNode[]
   */
  public $nodes = array();

  /**
   * @var SimpleHtmlDomNode
   */
  public $parent = null;

  /**
   * The "info" array - see HDOM_INFO_... for what each element contains.
   *
   * @var array
   */
  public $_ = array();

  /**
   * @var int
   */
  public $tag_start = 0;

  /**
   * @var SimpleHtmlDom|null
   */
  private $dom = null;

  /**
   * @param $dom
   */
  public function __construct($dom)
  {
    $this->dom = $dom;
    $dom->nodes[] = $this;
  }

  /**
   * Returns true if $string is valid UTF-8 and false otherwise.
   *
   * @param mixed $str String to be tested
   *
   * @return boolean
   */
  public static function is_utf8($str)
  {
    return UTF8::is_utf8($str);
  }

  public function __destruct()
  {
    $this->clear();
  }

  // clean up memory due to php5 circular references memory leak...

  public function clear()
  {
    unset($this->dom);
    unset($this->nodes);
    unset($this->parent);
    unset($this->children);
  }

  /**
   * magic - toString
   *
   * @return string
   */
  public function __toString()
  {
    return $this->outertext();
  }

  /**
   * get dom node's outer text (with tag)
   *
   * @return string
   */
  public function outertext()
  {
    if ($this->tag === 'root') {
      return $this->innertext();
    }

    // trigger callback
    if ($this->dom && $this->dom->callback !== null) {
      call_user_func_array($this->dom->callback, array($this));
    }

    if (isset($this->_[HDOM_INFO_OUTER])) {
      return $this->_[HDOM_INFO_OUTER];
    }

    if (isset($this->_[HDOM_INFO_TEXT])) {
      return $this->dom->restore_noise($this->_[HDOM_INFO_TEXT]);
    }

    // render begin tag
    if ($this->dom && $this->dom->nodes[$this->_[HDOM_INFO_BEGIN]]) {
      /** @noinspection PhpUndefinedMethodInspection */
      $ret = $this->dom->nodes[$this->_[HDOM_INFO_BEGIN]]->makeup();
    } else {
      $ret = '';
    }

    // render inner text
    if (isset($this->_[HDOM_INFO_INNER])) {
      // If it's a br tag...  don't return the HDOM_INNER_INFO that we may or may not have added.
      if ($this->tag != 'br') {
        $ret .= $this->_[HDOM_INFO_INNER];
      }
    } else {
      if ($this->nodes) {
        foreach ($this->nodes as $n) {
          $ret .= $this->convert_text($n->outertext());
        }
      }
    }

    // render end tag
    if (isset($this->_[HDOM_INFO_END]) && $this->_[HDOM_INFO_END] != 0) {
      $ret .= '</' . $this->tag . '>';
    }

    return $ret;
  }

  /**
   * get dom node's inner html
   *
   * @return string
   */
  public function innertext()
  {
    if (isset($this->_[HDOM_INFO_INNER])) {
      return $this->_[HDOM_INFO_INNER];
    }

    if (isset($this->_[HDOM_INFO_TEXT])) {
      return $this->dom->restore_noise($this->_[HDOM_INFO_TEXT]);
    }

    $ret = '';
    foreach ($this->nodes as $n) {
      $ret .= $n->outertext();
    }

    return $ret;
  }

  /**
   * PaperG - Function to convert the text from one character set to another if the two sets are not the same.
   *
   * @param $text
   *
   * @return string
   */
  public function convert_text($text)
  {
    global $debug_object;

    if (is_object($debug_object)) {
      /** @noinspection PhpUndefinedMethodInspection */
      $debug_object->debug_log_entry(1);
    }

    $converted_text = $text;

    $sourceCharset = '';
    $targetCharset = '';

    if ($this->dom) {
      $sourceCharset = strtoupper($this->dom->_charset);
      $targetCharset = strtoupper($this->dom->_target_charset);
    }

    if (is_object($debug_object)) {
      /** @noinspection PhpUndefinedMethodInspection */
      $debug_object->debug_log(3, 'source charset: ' . $sourceCharset . ' target charaset: ' . $targetCharset);
    }

    if (!empty($sourceCharset) && !empty($targetCharset) && strcasecmp($sourceCharset, $targetCharset) != 0) {
      // Check if the reported encoding could have been incorrect and the text is actually already UTF-8
      if (
          (strcasecmp($targetCharset, 'UTF-8') == 0)
          &&
          UTF8::is_utf8($text)
      ) {
        $converted_text = $text;
      } else {
        UTF8::checkForSupport(); // polyfill for "iconv"
        $converted_text = iconv($sourceCharset, $targetCharset, $text);
      }
    }

    // Lets make sure that we don't have that silly BOM issue with any of the utf-8 text we output.
    if ((strcasecmp($targetCharset, 'UTF-8') != 0)) {
      $converted_text = UTF8::removeBOM($converted_text);
    }

    return $converted_text;
  }

  /**
   *  dump node's tree
   *
   * @param bool $show_attr
   * @param int  $deep
   */
  public function dump($show_attr = true, $deep = 0)
  {
    $lead = str_repeat('	', $deep);

    echo $lead . $this->tag;
    if ($show_attr && count($this->attr) > 0) {
      echo '(';
      foreach ($this->attr as $k => $v) {
        echo "[$k]=>\"" . $this->$k . '", ';
      }
      echo ')';
    }
    echo "\n";

    if ($this->nodes) {
      foreach ($this->nodes as $c) {
        $c->dump($show_attr, $deep + 1);
      }
    }
  }

  /**
   * Debugging function to dump a single dom node with a bunch of information about it.
   *
   * @param bool $echo
   * @param      $node
   *
   * @return string|void
   */
  public function dump_node($echo = true, $node)
  {
    $string = $this->tag;

    if (count($this->attr) > 0) {
      $string .= '(';
      foreach ($this->attr as $k => $v) {
        $string .= "[$k]=>\"" . $this->$k . '", ';
      }
      $string .= ')';
    }

    if (count($this->_) > 0) {
      $string .= ' $_ (';
      foreach ($this->_ as $k => $v) {
        if (is_array($v)) {
          $string .= "[$k]=>(";
          foreach ($v as $k2 => $v2) {
            $string .= "[$k2]=>\"" . $v2 . '", ';
          }
          $string .= ')';
        } else {
          $string .= "[$k]=>\"" . $v . '", ';
        }
      }
      $string .= ')';
    }

    if (isset($this->text)) {
      $string .= ' text: (' . $this->text . ')';
    }

    $string .= " HDOM_INNER_INFO: '";
    if (isset($node->_[HDOM_INFO_INNER])) {
      $string .= $node->_[HDOM_INFO_INNER] . "'";
    } else {
      $string .= ' NULL ';
    }

    $string .= ' children: ' . count($this->children);
    $string .= ' nodes: ' . count($this->nodes);
    $string .= ' tag_start: ' . $this->tag_start;
    $string .= "\n";

    if ($echo) {
      echo $string;

      return '';
    } else {
      return $string;
    }
  }

  /**
   * function to locate a specific ancestor tag in the path to the root.
   *
   * @param $tag
   *
   * @return \voku\helper\SimpleHtmlDomNode
   */
  public function find_ancestor_tag($tag)
  {
    global $debug_object;
    if (is_object($debug_object)) {
      /** @noinspection PhpUndefinedMethodInspection */
      $debug_object->debug_log_entry(1);
    }

    // Start by including ourselves in the comparison.
    $returnDom = $this;

    while (null !== $returnDom) {
      if (is_object($debug_object)) {
        /** @noinspection PhpUndefinedMethodInspection */
        $debug_object->debug_log(2, 'Current tag is: ' . $returnDom->tag);
      }

      if ($returnDom->tag == $tag) {
        break;
      }

      $returnDom = $returnDom->parent;
    }

    return $returnDom;
  }

  /**
   * build node's text with tag
   *
   * @return string
   */
  public function makeup()
  {
    // text, comment, unknown
    if (isset($this->_[HDOM_INFO_TEXT])) {
      return $this->dom->restore_noise($this->_[HDOM_INFO_TEXT]);
    }

    $ret = '<' . $this->tag;
    $i = -1;

    foreach ($this->attr as $key => $val) {
      ++$i;

      // skip removed attribute
      if ($val === null || $val === false) {
        continue;
      }

      $ret .= $this->_[HDOM_INFO_SPACE][$i][0];
      //no value attr: nowrap, checked selected...
      if ($val === true) {
        $ret .= $key;
      } else {
        switch ($this->_[HDOM_INFO_QUOTE][$i]) {
          case HDOM_QUOTE_DOUBLE:
            $quote = '"';
            break;
          case HDOM_QUOTE_SINGLE:
            $quote = '\'';
            break;
          default:
            $quote = '';
        }
        $ret .= $key . $this->_[HDOM_INFO_SPACE][$i][1] . '=' . $this->_[HDOM_INFO_SPACE][$i][2] . $quote . $val . $quote;
      }
    }
    $ret = $this->dom->restore_noise($ret);

    return $ret . $this->_[HDOM_INFO_ENDSPACE] . '>';
  }

  /**
   * magic unset
   *
   * @param $name
   */
  public function __unset($name)
  {
    if (isset($this->attr[$name])) {
      unset($this->attr[$name]);
    }
  }

  /**
   * Function to try a few tricks to determine the displayed size of an img on the page.
   * NOTE: This will ONLY work on an IMG tag. Returns FALSE on all other tag types.
   *
   * @author  John Schlick
   * @version April 19 2012
   * @return array an array containing the 'height' and 'width' of the image on the page or -1 if we can't figure it
   *               out.
   */
  public function get_display_size()
  {
    $width = -1;
    $height = -1;

    if ($this->tag !== 'img') {
      return false;
    }

    // See if there is aheight or width attribute in the tag itself.
    if (isset($this->attr['width'])) {
      $width = $this->attr['width'];
    }

    if (isset($this->attr['height'])) {
      $height = $this->attr['height'];
    }

    // Now look for an inline style.
    if (isset($this->attr['style'])) {
      // Thanks to user gnarf from stackoverflow for this regular expression.
      $attributes = array();
      preg_match_all("/([\w-]+)\s*:\s*([^;]+)\s*;?/", $this->attr['style'], $matches, PREG_SET_ORDER);
      foreach ($matches as $match) {
        $attributes[$match[1]] = $match[2];
      }

      // If there is a width in the style attributes:
      if (isset($attributes['width']) && $width == -1) {
        // check that the last two characters are px (pixels)
        if (strtolower(substr($attributes['width'], -2)) == 'px') {
          $proposed_width = substr($attributes['width'], 0, -2);
          // Now make sure that it's an integer and not something stupid.
          if (filter_var($proposed_width, FILTER_VALIDATE_INT)) {
            $width = $proposed_width;
          }
        }
      }

      // If there is a width in the style attributes:
      if (isset($attributes['height']) && $height == -1) {
        // check that the last two characters are px (pixels)
        if (strtolower(substr($attributes['height'], -2)) == 'px') {
          $proposed_height = substr($attributes['height'], 0, -2);
          // Now make sure that it's an integer and not something stupid.
          if (filter_var($proposed_height, FILTER_VALIDATE_INT)) {
            $height = $proposed_height;
          }
        }
      }

    }

    // Future enhancement:
    // Look in the tag to see if there is a class or id specified that has a height or width attribute to it.

    // Far future enhancement
    // Look at all the parent tags of this image to see if they specify a class or id that has an img selector that specifies a height or width
    // Note that in this case, the class or id will have the img subselector for it to apply to the image.

    // ridiculously far future development
    // If the class or id is specified in a SEPARATE css file thats not on the page, go get it and do what we were just doing for the ones on the page.

    $result = array(
        'height' => $height,
        'width'  => $width,
    );

    return $result;
  }

  /**
   * get all attributes
   *
   * @return array
   */
  public function getAllAttributes()
  {
    return $this->attr;
  }

  /**
   * get attribute
   *
   * @param $name
   *
   * @return bool|mixed|string
   */
  public function getAttribute($name)
  {
    return $this->__get($name);
  }

  /**
   * magic get
   *
   * @param $name
   *
   * @return bool|mixed|string
   */
  public function __get($name)
  {
    if (isset($this->attr[$name])) {
      return $this->convert_text($this->attr[$name]);
    }

    switch ($name) {
      case 'outertext':
        return $this->outertext();
      case 'innertext':
        return $this->innertext();
      case 'plaintext':
        return $this->text();
      case 'xmltext':
        return $this->xmltext();
      default:
        return array_key_exists($name, $this->attr);
    }
  }

  /**
   * magic set
   *
   * @param $name
   * @param $value
   *
   * @return mixed
   */
  public function __set($name, $value)
  {
    global $debug_object;

    if (is_object($debug_object)) {
      /** @noinspection PhpUndefinedMethodInspection */
      $debug_object->debug_log_entry(1);
    }

    switch ($name) {
      case 'outertext':
        return $this->_[HDOM_INFO_OUTER] = $value;
      case 'innertext':
        if (isset($this->_[HDOM_INFO_TEXT])) {
          return $this->_[HDOM_INFO_TEXT] = $value;
        }

        return $this->_[HDOM_INFO_INNER] = $value;
    }

    if (!isset($this->attr[$name])) {
      $this->_[HDOM_INFO_SPACE][] = array(
          ' ',
          '',
          '',
      );
      $this->_[HDOM_INFO_QUOTE][] = HDOM_QUOTE_DOUBLE;
    }

    $this->attr[$name] = $value;

    return '';
  }

  /**
   * get dom node's plain text
   *
   * @return string
   */
  public function text()
  {
    if (isset($this->_[HDOM_INFO_INNER])) {
      return $this->_[HDOM_INFO_INNER];
    }

    switch ($this->nodetype) {
      case HDOM_TYPE_TEXT:
        return $this->dom->restore_noise($this->_[HDOM_INFO_TEXT]);
      case HDOM_TYPE_COMMENT:
        return '';
      case HDOM_TYPE_UNKNOWN:
        return '';
    }
    if (strcasecmp($this->tag, 'script') === 0) {
      return '';
    }

    if (strcasecmp($this->tag, 'style') === 0) {
      return '';
    }

    $ret = '';
    // In rare cases, (always node type 1 or HDOM_TYPE_ELEMENT - observed for some span tags, and some p tags) $this->nodes is set to NULL.
    // NOTE: This indicates that there is a problem where it's set to NULL without a clear happening.
    // WHY is this happening?
    if (null !== $this->nodes) {
      foreach ($this->nodes as $n) {
        $ret .= $this->convert_text($n->text());
      }

      // If this node is a span... add a space at the end of it so multiple spans don't run into each other.  This is plaintext after all.
      if ($this->tag == 'span') {
        $ret .= $this->dom->default_span_text;
      }
    }

    return $ret;
  }

  /**
   * xmltext
   *
   * @return mixed|string
   */
  public function xmltext()
  {
    $ret = $this->innertext();
    $ret = str_ireplace('<![CDATA[', '', $ret);
    $ret = str_replace(']]>', '', $ret);

    return $ret;
  }

  /**
   * set attribute
   *
   * @param $name
   * @param $value
   */
  public function setAttribute($name, $value)
  {
    $this->__set($name, $value);
  }

  /**
   * has attribute
   *
   * @param $name
   *
   * @return bool
   */
  public function hasAttribute($name)
  {
    return $this->__isset($name);
  }

  /**
   * magic isset
   *
   * @param $name
   *
   * @return bool
   */
  public function __isset($name)
  {
    switch ($name) {
      case 'outertext':
        return true;
      case 'innertext':
        return true;
      case 'plaintext':
        return true;
    }

    //no value attr: nowrap, checked selected...
    return (array_key_exists($name, $this->attr)) ? true : isset($this->attr[$name]);
  }

  /**
   * remove attribute
   *
   * @param $name
   */
  public function removeAttribute($name)
  {
    $this->__set($name, null);
  }

  /**
   * get element by id
   *
   * @param $id
   *
   * @return array|null
   */
  public function getElementById($id)
  {
    return $this->find("#$id", 0);
  }

  /**
   * find elements by css selector
   * PaperG - added ability for find to lowercase the value of the selector.
   *
   * @param          $selector
   * @param null|int $idx
   * @param bool     $lowercase
   *
   * @return SimpleHtmlDomNode|SimpleHtmlDomNode[]|array|null
   */
  public function find($selector, $idx = null, $lowercase = false)
  {
    $selectors = $this->parse_selector($selector);

    if (($count = count($selectors)) === 0) {
      return array();
    }
    $found_keys = array();

    // find each selector
    for ($c = 0; $c < $count; ++$c) {

      // The change on the below line was documented on the sourceforge code tracker id 2788009
      // used to be: if (($levle=count($selectors[0]))===0) return array();
      if (($levle = count($selectors[$c])) === 0) {
        return array();
      }

      if (!isset($this->_[HDOM_INFO_BEGIN])) {
        return array();
      }

      $head = array($this->_[HDOM_INFO_BEGIN] => 1);

      // handle descendant selectors, no recursive!
      for ($l = 0; $l < $levle; ++$l) {
        $ret = array();
        foreach ($head as $k => $v) {
          $n = ($k === -1) ? $this->dom->root : $this->dom->nodes[$k];
          //PaperG - Pass this optional parameter on to the seek function.
          $n->seek($selectors[$c][$l], $ret, $lowercase);
        }
        $head = $ret;
      }

      foreach ($head as $k => $v) {
        if (!isset($found_keys[$k])) {
          $found_keys[$k] = 1;
        }
      }
    }

    // sort keys
    ksort($found_keys);

    $found = array();
    foreach ($found_keys as $k => $v) {
      $found[] = $this->dom->nodes[$k];
    }

    // return nth-element or array
    if (null === $idx) {
      return $found;
    } elseif ($idx < 0) {
      $idx = count($found) + $idx;
    }

    return (isset($found[$idx])) ? $found[$idx] : null;
  }

  /**
   * parse_selector
   *
   * @param $selector_string
   *
   * @return array
   */
  protected function parse_selector($selector_string)
  {
    global $debug_object;
    if (is_object($debug_object)) {
      /** @noinspection PhpUndefinedMethodInspection */
      $debug_object->debug_log_entry(1);
    }

    // pattern of CSS selectors, modified from mootools
    // Paperg: Add the colon to the attrbute, so that it properly finds <tag attr:ibute="something" > like google does.
    // Note: if you try to look at this attribute, yo MUST use getAttribute since $dom->x:y will fail the php syntax check.
    // Notice the \[ starting the attbute?  and the @? following?  This implies that an attribute can begin with an @ sign that is not captured.
    // This implies that an html attribute specifier may start with an @ sign that is NOT captured by the expression.
    // farther study is required to determine of this should be documented or removed.
    //		$pattern = "/([\w-:\*]*)(?:\#([\w-]+)|\.([\w-]+))?(?:\[@?(!?[\w-]+)(?:([!*^$]?=)[\"']?(.*?)[\"']?)?\])?([\/, ]+)/is";
    $pattern = "/([\w-:\*]*)(?:\#([\w-]+)|\.([\w-]+))?(?:\[@?(!?[\w-:]+)(?:([!*^$]?=)[\"']?(.*?)[\"']?)?\])?([\/, ]+)/is";
    preg_match_all($pattern, trim($selector_string) . ' ', $matches, PREG_SET_ORDER);

    if (is_object($debug_object)) {
      /** @noinspection PhpUndefinedMethodInspection */
      $debug_object->debug_log(2, 'Matches Array: ', $matches);
    }

    $selectors = array();
    $result = array();

    foreach ($matches as $m) {
      $m[0] = trim($m[0]);
      if ($m[0] === '' || $m[0] === '/' || $m[0] === '//') {
        continue;
      }
      // for browser generated xpath
      if ($m[1] === 'tbody') {
        continue;
      }

      list($tag, $key, $val, $exp, $no_key) = array(
          $m[1],
          null,
          null,
          '=',
          false,
      );
      if (!empty($m[2])) {
        $key = 'id';
        $val = $m[2];
      }
      if (!empty($m[3])) {
        $key = 'class';
        $val = $m[3];
      }
      if (!empty($m[4])) {
        $key = $m[4];
      }
      if (!empty($m[5])) {
        $exp = $m[5];
      }
      if (!empty($m[6])) {
        $val = $m[6];
      }

      // convert to lowercase
      if ($this->dom->lowercase) {
        $tag = strtolower($tag);
        $key = strtolower($key);
      }
      //elements that do NOT have the specified attribute
      if (isset($key[0]) && $key[0] === '!') {
        $key = substr($key, 1);
        $no_key = true;
      }

      $result[] = array(
          $tag,
          $key,
          $val,
          $exp,
          $no_key,
      );

      if (trim($m[7]) === ',') {
        $selectors[] = $result;
        $result = array();
      }
    }

    if (count($result) > 0) {
      $selectors[] = $result;
    }

    return $selectors;
  }

  /**
   * seek for given conditions
   *
   * PaperG - added parameter to allow for case insensitive testing of the value of a selector.
   *
   * @param      $selector
   * @param      $ret
   * @param bool $lowercase
   */
  protected function seek($selector, &$ret, $lowercase = false)
  {
    global $debug_object;
    if (is_object($debug_object)) {
      /** @noinspection PhpUndefinedMethodInspection */
      $debug_object->debug_log_entry(1);
    }

    list($tag, $key, $val, $exp, $no_key) = $selector;

    // xpath index
    if ($tag && $key && is_numeric($key)) {
      $count = 0;
      foreach ($this->children as $c) {
        if ($tag === '*' || $tag === $c->tag) {
          if (++$count == $key) {
            $ret[$c->_[HDOM_INFO_BEGIN]] = 1;

            return;
          }
        }
      }

      return;
    }

    $end = (!empty($this->_[HDOM_INFO_END])) ? $this->_[HDOM_INFO_END] : 0;
    if ($end == 0) {
      $parent = $this->parent;
      while (!isset($parent->_[HDOM_INFO_END]) && $parent !== null) {
        $end -= 1;
        $parent = $parent->parent;
      }
      $end += $parent->_[HDOM_INFO_END];
    }

    for ($i = $this->_[HDOM_INFO_BEGIN] + 1; $i < $end; ++$i) {
      /* @var SimpleHtmlDomNode $node */
      $node = $this->dom->nodes[$i];

      $pass = true;

      if ($tag === '*' && !$key) {
        if (in_array($node, $this->children, true)) {
          $ret[$i] = 1;
        }
        continue;
      }

      // compare tag
      if ($tag && $tag != $node->tag && $tag !== '*') {
        $pass = false;
      }
      // compare key
      if ($pass && $key) {
        if ($no_key) {
          if (isset($node->attr[$key])) {
            $pass = false;
          }
        } else {
          if (($key != 'plaintext') && !isset($node->attr[$key])) {
            $pass = false;
          }
        }
      }
      // compare value
      if ($pass && $key && $val && $val !== '*') {

        // If they have told us that this is a "plaintext" search then we want the plaintext of the node - right?
        if ($key == 'plaintext') {
          // $node->plaintext actually returns $node->text();
          $nodeKeyValue = $node->text();
        } else {
          // this is a normal search, we want the value of that attribute of the tag.
          $nodeKeyValue = $node->attr[$key];
        }
        if (is_object($debug_object)) {
          /** @noinspection PhpUndefinedMethodInspection */
          $debug_object->debug_log(2, 'testing node: ' . $node->tag . ' for attribute: ' . $key . $exp . $val . ' where nodes value is: ' . $nodeKeyValue);
        }

        //PaperG - If lowercase is set, do a case insensitive test of the value of the selector.
        if ($lowercase) {
          $check = $this->match($exp, strtolower($val), strtolower($nodeKeyValue));
        } else {
          $check = $this->match($exp, $val, $nodeKeyValue);
        }
        if (is_object($debug_object)) {
          /** @noinspection PhpUndefinedMethodInspection */
          $debug_object->debug_log(2, 'after match: ' . ($check ? 'true' : 'false'));
        }

        // handle multiple class
        if (!$check && strcasecmp($key, 'class') === 0) {
          foreach (explode(' ', $node->attr[$key]) as $k) {
            // Without this, there were cases where leading, trailing, or double spaces lead to our comparing blanks - bad form.
            if (!empty($k)) {
              if ($lowercase) {
                $check = $this->match($exp, strtolower($val), strtolower($k));
              } else {
                $check = $this->match($exp, $val, $k);
              }
              if ($check) {
                break;
              }
            }
          }
        }
        if (!$check) {
          $pass = false;
        }
      }
      if ($pass) {
        $ret[$i] = 1;
      }
      unset($node);
    }
    // It's passed by reference so this is actually what this function returns.
    if (is_object($debug_object)) {
      /** @noinspection PhpUndefinedMethodInspection */
      $debug_object->debug_log(1, 'EXIT - ret: ', $ret);
    }
  }

  /**
   * match
   *
   * @param $exp
   * @param $pattern
   * @param $value
   *
   * @return bool|int
   */
  protected function match($exp, $pattern, $value)
  {
    global $debug_object;
    if (is_object($debug_object)) {
      /** @noinspection PhpUndefinedMethodInspection */
      $debug_object->debug_log_entry(1);
    }

    switch ($exp) {
      case '=':
        return ($value === $pattern);
      case '!=':
        return ($value !== $pattern);
      case '^=':
        return preg_match('/^' . preg_quote($pattern, '/') . '/', $value);
      case '$=':
        return preg_match('/' . preg_quote($pattern, '/') . '$/', $value);
      case '*=':
        if ($pattern[0] == '/') {
          return preg_match($pattern, $value);
        }

        return preg_match('/' . $pattern . '/i', $value);
    }

    return false;
  }

  /**
   * get elements by id
   *
   * @param      $id
   * @param null $idx
   *
   * @return array|null
   */
  public function getElementsById($id, $idx = null)
  {
    return $this->find("#$id", $idx);
  }

  /**
   * get element by tag name
   *
   * @param $name
   *
   * @return array|null
   */
  public function getElementByTagName($name)
  {
    return $this->find($name, 0);
  }

  /**
   * get elements by tag name
   *
   * @param      $name
   * @param null $idx
   *
   * @return array|null
   */
  public function getElementsByTagName($name, $idx = null)
  {
    return $this->find($name, $idx);
  }

  /**
   * parent node
   *
   * @return null
   */
  public function parentNode()
  {
    return $this->parent();
  }

  /**
   * returns the parent of node
   *
   * If a node is passed in, it will reset the parent of the current node to that one.
   *
   * @param null $parent
   *
   * @return null
   */
  public function parent($parent = null)
  {
    // I am SURE that this doesn't work properly.
    // It fails to unset the current node from it's current parents nodes or children list first.
    if ($parent !== null) {
      $this->parent = $parent;
      $this->parent->nodes[] = $this;
      $this->parent->children[] = $this;
    }

    return $this->parent;
  }

  /**
   * child nodes
   *
   * @param int $idx
   *
   * @return array|null
   */
  public function childNodes($idx = -1)
  {
    return $this->children($idx);
  }

  /**
   * returns children of node
   *
   * @param int $idx
   *
   * @return array|null
   */
  public function children($idx = -1)
  {
    if ($idx === -1) {
      return $this->children;
    }

    if (isset($this->children[$idx])) {
      return $this->children[$idx];
    }

    return null;
  }

  /**
   * first child
   *
   * @return null
   */
  public function firstChild()
  {
    return $this->first_child();
  }

  /**
   * returns the first child of node
   *
   * @return null
   */
  public function first_child()
  {
    if (count($this->children) > 0) {
      return $this->children[0];
    }

    return null;
  }

  /**
   * last child
   *
   * @return null
   */
  public function lastChild()
  {
    return $this->last_child();
  }

  /**
   * returns the last child of node
   *
   * @return null
   */
  public function last_child()
  {
    if (($count = count($this->children)) > 0) {
      return $this->children[$count - 1];
    }

    return null;
  }

  /**
   * next sibling
   *
   * @return null
   */
  public function nextSibling()
  {
    return $this->next_sibling();
  }

  /**
   * returns the next sibling of node
   *
   * @return null
   */
  public function next_sibling()
  {
    if ($this->parent === null) {
      return null;
    }

    $idx = 0;
    $count = count($this->parent->children);
    while ($idx < $count && $this !== $this->parent->children[$idx]) {
      ++$idx;
    }

    if (++$idx >= $count) {
      return null;
    }

    return $this->parent->children[$idx];
  }

  /**
   * previous sibling
   *
   * @return null
   */
  public function previousSibling()
  {
    return $this->prev_sibling();
  }

  /**
   * returns the previous sibling of node
   *
   * @return null|\voku\helper\SimpleHtmlDomNode
   */
  public function prev_sibling()
  {
    if ($this->parent === null) {
      return null;
    }

    $idx = 0;
    $count = count($this->parent->children);
    while ($idx < $count && $this !== $this->parent->children[$idx]) {
      ++$idx;
    }

    if (--$idx < 0) {
      return null;
    }

    return $this->parent->children[$idx];
  }

  /**
   * has child nodes
   *
   * @return bool
   */
  public function hasChildNodes()
  {
    return $this->has_child();
  }

  /**
   * verify that node has children
   *
   * @return bool
   */
  public function has_child()
  {
    return !empty($this->children);
  }

  /**
   * node name
   *
   * @return string
   */
  public function nodeName()
  {
    return $this->tag;
  }

  /**
   * append child
   *
   * @param SimpleHtmlDomNode $node
   *
   * @return mixed
   */
  public function appendChild($node)
  {
    $node->parent($this);

    return $node;
  }

}
