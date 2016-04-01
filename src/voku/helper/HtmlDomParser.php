<?php

namespace voku\helper;

use BadMethodCallException;
use DOMDocument;
use DOMXPath;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class HtmlDomParser
 *
 * @package voku\helper
 *
 * @property string      outertext Get dom node's outer html
 * @property string      innertext Get dom node's inner html
 * @property-read string plaintext Get dom node's plain text
 *
 * @method string outertext() Get dom node's outer html
 * @method string innertext() Get dom node's inner html
 * @method HtmlDomParser load() load($html) Load HTML from string
 * @method HtmlDomParser load_file() load_file($html) Load HTML from file
 *
 * @method static HtmlDomParser file_get_html() file_get_html($html) Load HTML from file
 * @method static HtmlDomParser str_get_html() str_get_html($html) Load HTML from string
 */
class HtmlDomParser
{
  /**
   * @var array
   */
  protected static $functionAliases = array(
      'outertext' => 'html',
      'innertext' => 'innerHtml',
      'load'      => 'loadHtml',
      'load_file' => 'loadHtmlFile',
  );

  /**
   * @var Callable
   */
  protected static $callback;

  /**
   * @var DOMDocument
   */
  protected $document;

  /**
   * @var string
   */
  protected $encoding = 'UTF-8';

  /**
   * @var bool
   */
  protected $isDOMDocumentCreatedWithoutHtml = false;

  /**
   * @var bool
   */
  protected $isDOMDocumentCreatedWithoutHtmlWrapper = false;

  /**
   * Constructor
   *
   * @param string|SimpleHtmlDom|\DOMNode $element HTML code or SimpleHtmlDom, \DOMNode
   */
  public function __construct($element = null)
  {
    $this->document = new DOMDocument('1.0', $this->getEncoding());

    if ($element instanceof SimpleHtmlDom) {
      $element = $element->getNode();
    }

    if ($element instanceof \DOMNode) {
      $domNode = $this->document->importNode($element, true);

      if ($domNode instanceof \DOMNode) {
        $this->document->appendChild($domNode);
      }

      return;
    }

    if ($element !== null) {
      $this->loadHtml($element);
    }
  }

  /**
   * @param $name
   * @param $arguments
   *
   * @return bool|mixed
   */
  public function __call($name, $arguments)
  {
    if (isset(self::$functionAliases[$name])) {
      return call_user_func_array(array($this, self::$functionAliases[$name]), $arguments);
    }

    throw new BadMethodCallException('Method does not exist: ' . $name);
  }

  /**
   * @param $name
   * @param $arguments
   *
   * @return HtmlDomParser
   */
  public static function __callStatic($name, $arguments)
  {
    if ($name == 'str_get_html') {
      $parser = new self();

      return $parser->loadHtml($arguments[0]);
    }

    if ($name == 'file_get_html') {
      $parser = new self();

      return $parser->loadHtmlFile($arguments[0]);
    }

    throw new BadMethodCallException('Method does not exist');
  }

  /**
   * @param $name
   *
   * @return string
   */
  public function __get($name)
  {
    switch ($name) {
      case 'outertext':
        return $this->html();
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
   * @return string
   */
  public function __toString()
  {
    return $this->html();
  }

  /**
   * does nothing (only for api-compatibility-reasons)
   *
   * @return bool
   */
  public function clear()
  {
    return true;
  }

  /**
   * create DOMDocument from HTML
   *
   * @param string $html
   *
   * @return \DOMDocument
   */
  private function createDOMDocument($html)
  {
    if (strpos($html, '<') === false) {
      $this->isDOMDocumentCreatedWithoutHtml = true;
    }

    if (strpos($html, '<html') === false) {
      $this->isDOMDocumentCreatedWithoutHtmlWrapper = true;
    }

    // set error level
    $internalErrors = libxml_use_internal_errors(true);
    $disableEntityLoader = libxml_disable_entity_loader(true);

    $sxe = simplexml_load_string($html);
    if (count(libxml_get_errors()) === 0) {
      $this->document = dom_import_simplexml($sxe)->ownerDocument;
    } else {
      $this->document->loadHTML('<?xml encoding="' . $this->getEncoding() . '">' . $html);

      // remove the "xml-encoding" hack
      foreach ($this->document->childNodes as $child) {
        if ($child->nodeType == XML_PI_NODE) {
          $this->document->removeChild($child);
        }
      }

      libxml_clear_errors();
    }

    // set encoding
    $this->document->encoding = $this->getEncoding();

    // restore lib-xml settings
    libxml_use_internal_errors($internalErrors);
    libxml_disable_entity_loader($disableEntityLoader);

    return $this->document;
  }

  /**
   * Callback function for preg_replace_callback use.
   *
   * @param  array $matches PREG matches
   *
   * @return string
   */
  protected function entityCallback(&$matches)
  {
    return mb_convert_encoding($matches[0], 'UTF-8', 'HTML-ENTITIES');
  }

  /**
   * Return SimpleHtmlDom by id.
   *
   * @param string $id
   *
   * @return SimpleHtmlDomNode|SimpleHtmlDomNode[]|SimpleHtmlDomNodeBlank
   */
  public function getElementById($id)
  {
    return $this->find("#$id", 0);
  }

  /**
   * Return SimpleHtmlDom by tag name.
   *
   * @param string $name
   *
   * @return SimpleHtmlDomNode|SimpleHtmlDomNode[]|SimpleHtmlDomNodeBlank
   */
  public function getElementByTagName($name)
  {
    return $this->find($name, 0);
  }

  /**
   * Returns Elements by id
   *
   * @param string   $id
   * @param null|int $idx
   *
   * @return SimpleHtmlDomNode|SimpleHtmlDomNode[]|SimpleHtmlDomNodeBlank
   */
  public function getElementsById($id, $idx = null)
  {
    return $this->find("#$id", $idx);
  }

  /**
   * Returns Elements by tag name
   *
   * @param string   $name
   * @param null|int $idx
   *
   * @return SimpleHtmlDomNode|SimpleHtmlDomNode[]|SimpleHtmlDomNodeBlank
   */
  public function getElementsByTagName($name, $idx = null)
  {
    return $this->find($name, $idx);
  }

  /**
   * Find list of nodes with a CSS selector.
   *
   * @param string $selector
   * @param int    $idx
   *
   * @return SimpleHtmlDom|SimpleHtmlDom[]
   */
  public function find($selector, $idx = null)
  {
    $xPathQuery = SelectorConverter::toXPath($selector);

    $xPath = new DOMXPath($this->document);
    $nodesList = $xPath->query($xPathQuery);
    $elements = new SimpleHtmlDomNode();

    foreach ($nodesList as $node) {
      $elements[] = new SimpleHtmlDom($node);
    }

    if (null === $idx) {
      return $elements;
    } else {
      if ($idx < 0) {
        $idx = count($elements) + $idx;
      }
    }

    if (isset($elements[$idx])) {
      return $elements[$idx];
    } else {
      return new SimpleHtmlDomNodeBlank();
    }
  }

  /**
   * @param string $content
   *
   * @return string
   */
  protected function fixHtmlOutput($content)
  {
    // INFO: DOMDocument will encapsulate plaintext into a paragraph tag (<p>),
    //          so we try to remove it here again ...
    if ($this->isDOMDocumentCreatedWithoutHtml === true) {
      $content = str_replace(
          array(
              "\n",
              '<p>', '</p>',
              "\n" . '<simpleHtmlDomP>', '<simpleHtmlDomP>', '</simpleHtmlDomP>',
              '<body>', '</body>',
              '<html>', '</html>'
          ),
          '',
          $content
      );

    } elseif ($this->isDOMDocumentCreatedWithoutHtmlWrapper === true) {
      $content = str_replace(
          array(
              "\n",
              "\n" . '<simpleHtmlDomP>', '<simpleHtmlDomP>', '</simpleHtmlDomP>',
              '<body>', '</body>',
              '<html>', '</html>'
          ),
          '',
          $content
      );
    }

    // replace html entities which represent UTF-8 codepoints.
    $content = preg_replace_callback("/&#\d{2,5};/", array($this, 'entityCallback'), $content);

    return urldecode(trim($content));
  }

  /**
   * @return DOMDocument
   */
  public function getDocument()
  {
    return $this->document;
  }

  /**
   * Get the encoding to use
   *
   * @return string
   */
  private function getEncoding()
  {
    return $this->encoding;
  }

  /**
   * @return bool
   */
  public function getIsDOMDocumentCreatedWithoutHtml()
  {
    return $this->isDOMDocumentCreatedWithoutHtml;
  }

  /**
   * @return bool
   */
  public function getIsDOMDocumentCreatedWithoutHtmlWrapper()
  {
    return $this->isDOMDocumentCreatedWithoutHtmlWrapper;
  }

  /**
   * Get dom node's outer html
   *
   * @return string
   */
  public function html()
  {
    if ($this::$callback !== null) {
      call_user_func_array($this::$callback, array($this));
    }

    if ($this->getIsDOMDocumentCreatedWithoutHtmlWrapper()) {
      $content = $this->document->saveHTML($this->document->documentElement);
    } else {
      $content = $this->document->saveHTML();
    }

    return $this->fixHtmlOutput($content);
  }

  /**
   * Get dom node's inner html
   *
   * @return string
   */
  public function innerHtml()
  {
    $text = '';

    foreach ($this->document->documentElement->childNodes as $node) {
      $text .= $this->fixHtmlOutput($this->document->saveHTML($node));
    }

    return $text;
  }

  /**
   * Load HTML from string
   *
   * @param string $html
   *
   * @return HtmlDomParser
   *
   * @throws InvalidArgumentException if argument is not string
   */
  public function loadHtml($html)
  {
    if (!is_string($html)) {
      throw new InvalidArgumentException(__METHOD__ . ' expects parameter 1 to be string.');
    }

    $this->document = $this->createDOMDocument($html);

    return $this;
  }

  /**
   * Load HTML from file
   *
   * @param string $filePath
   *
   * @return HtmlDomParser
   */
  public function loadHtmlFile($filePath)
  {
    if (!is_string($filePath)) {
      throw new InvalidArgumentException(__METHOD__ . ' expects parameter 1 to be string.');
    }

    if (!preg_match("/^https?:\/\//i", $filePath) && !file_exists($filePath)) {
      throw new RuntimeException("File $filePath not found");
    }

    try {
      $html = file_get_contents($filePath);
    } catch (\Exception $e) {
      throw new RuntimeException("Could not load file $filePath");
    }

    if ($html === false) {
      throw new RuntimeException("Could not load file $filePath");
    }

    $this->loadHtml($html);

    return $this;
  }

  /**
   * Save dom as string
   *
   * @param string $filepath
   *
   * @return string
   */
  public function save($filepath = '')
  {
    $string = $this->innerHtml();
    if ($filepath !== '') {
      file_put_contents($filepath, $string, LOCK_EX);
    }

    return $string;
  }

  /**
   * @param $functionName
   */
  public function set_callback($functionName)
  {
    $this::$callback = $functionName;
  }

  /**
   * Get dom node's plain text
   *
   * @return string
   */
  public function text()
  {
    return $this->document->textContent;
  }
}
