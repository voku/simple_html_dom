<?php

declare(strict_types=1);

namespace voku\helper;

/**
 * @property-read string outerText <p>Get dom node's outer html (alias for "outerHtml").</p>
 * @property-read string outerHtml <p>Get dom node's outer html.</p>
 * @property-read string innerText <p>Get dom node's inner html (alias for "innerHtml").</p>
 * @property-read string innerHtml <p>Get dom node's inner html.</p>
 * @property-read string plaintext <p>Get dom node's plain text.</p>
 *
 * @method string outerText() <p>Get dom node's outer html (alias for "outerHtml()").</p>
 * @method string outerHtml() <p>Get dom node's outer html.</p>
 * @method string innerText() <p>Get dom node's inner html (alias for "innerHtml()").</p>
 * @method HtmlDomParser load() load($html) <p>Load HTML from string.</p>
 * @method HtmlDomParser load_file() load_file($html) <p>Load HTML from file.</p>
 * @method static HtmlDomParser file_get_html() file_get_html($html, $libXMLExtraOptions = null) <p>Load HTML from file.</p>
 * @method static HtmlDomParser str_get_html() str_get_html($html, $libXMLExtraOptions = null) <p>Load HTML from string.</p>
 */
class HtmlDomParser
{
    /**
     * @var array
     */
    protected static $functionAliases = [
        'outertext' => 'html',
        'outerhtml' => 'html',
        'innertext' => 'innerHtml',
        'innerhtml' => 'innerHtml',
        'load'      => 'loadHtml',
        'load_file' => 'loadHtmlFile',
    ];

    /**
     * @var string[][]
     */
    protected static $domLinkReplaceHelper = [
        'orig' => ['[', ']', '{', '}'],
        'tmp'  => [
            '____SIMPLE_HTML_DOM__VOKU__SQUARE_BRACKET_LEFT____',
            '____SIMPLE_HTML_DOM__VOKU__SQUARE_BRACKET_RIGHT____',
            '____SIMPLE_HTML_DOM__VOKU__BRACKET_LEFT____',
            '____SIMPLE_HTML_DOM__VOKU__BRACKET_RIGHT____',
        ],
    ];

    /**
     * @var array
     */
    protected static $domReplaceHelper = [
        'orig' => ['&', '|', '+', '%', '@'],
        'tmp'  => [
            '____SIMPLE_HTML_DOM__VOKU__AMP____',
            '____SIMPLE_HTML_DOM__VOKU__PIPE____',
            '____SIMPLE_HTML_DOM__VOKU__PLUS____',
            '____SIMPLE_HTML_DOM__VOKU__PERCENT____',
            '____SIMPLE_HTML_DOM__VOKU__AT____',
        ],
    ];

    protected static $domHtmlWrapperHelper = '____simple_html_dom__voku__html_wrapper____';

    /**
     * @var array
     */
    protected static $domBrokenReplaceHelper = [];

    /**
     * @var callable
     */
    protected static $callback;

    /**
     * @var \DOMDocument
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
    protected $isDOMDocumentCreatedWithoutWrapper = false;

    /**
     * @var bool
     */
    protected $isDOMDocumentCreatedWithoutHeadWrapper = false;

    /**
     * @var bool
     */
    protected $isDOMDocumentCreatedWithoutHtmlWrapper = false;

    /**
     * @var bool
     */
    protected $isDOMDocumentCreatedWithFakeEndScript = false;

    /**
     * @var bool
     */
    protected $keepBrokenHtml;

    /**
     * Constructor
     *
     * @param \DOMNode|SimpleHtmlDom|string $element HTML code or SimpleHtmlDom, \DOMNode
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($element = null)
    {
        $this->document = new \DOMDocument('1.0', $this->getEncoding());

        // reset
        self::$domBrokenReplaceHelper = [];

        // DOMDocument settings
        $this->document->preserveWhiteSpace = true;
        $this->document->formatOutput = true;

        if ($element instanceof SimpleHtmlDom) {
            $element = $element->getNode();
        }

        if ($element instanceof \DOMNode) {
            $domNode = $this->document->importNode($element, true);

            if ($domNode instanceof \DOMNode) {
                /** @noinspection UnusedFunctionResultInspection */
                $this->document->appendChild($domNode);
            }

            return;
        }

        if ($element !== null) {
            /** @noinspection UnusedFunctionResultInspection */
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
        $name = \strtolower($name);

        if (isset(self::$functionAliases[$name])) {
            return \call_user_func_array([$this, self::$functionAliases[$name]], $arguments);
        }

        throw new \BadMethodCallException('Method does not exist: ' . $name);
    }

    /**
     * @param $name
     * @param $arguments
     *
     * @throws \BadMethodCallException
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     *
     * @return HtmlDomParser
     */
    public static function __callStatic($name, $arguments)
    {
        $arguments0 = '';
        if (isset($arguments[0])) {
            $arguments0 = $arguments[0];
        }

        $arguments1 = null;
        if (isset($arguments[1])) {
            $arguments1 = $arguments[1];
        }

        if ($name === 'str_get_html') {
            $parser = new self();

            return $parser->loadHtml($arguments0, $arguments1);
        }

        if ($name === 'file_get_html') {
            $parser = new self();

            return $parser->loadHtmlFile($arguments0, $arguments1);
        }

        throw new \BadMethodCallException('Method does not exist');
    }

    /** @noinspection MagicMethodsValidityInspection */

    /**
     * @param $name
     *
     * @return string
     */
    public function __get($name)
    {
        $name = \strtolower($name);

        switch ($name) {
            case 'outerhtml':
            case 'outertext':
                return $this->html();
            case 'innerhtml':
            case 'innertext':
                return $this->innerHtml();
            case 'text':
            case 'plaintext':
                return $this->text();
        }

        return null;
    }

    /**
     * @param string $selector
     * @param int    $idx
     *
     * @return SimpleHtmlDom|SimpleHtmlDom[]|SimpleHtmlDomNodeInterface
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
     * @deprecated
     *
     * @return bool
     */
    public function clear(): bool
    {
        return true;
    }

    /**
     * @param string $html
     *
     * @return string
     */
    public static function replaceToPreserveHtmlEntities(string $html): string
    {
        // init
        $linksNew = [];
        $linksOld = [];

        if (\strpos($html, 'http') !== false) {

            // regEx for e.g.: [https://www.domain.de/foo.php?foobar=1&email=lars%40moelleken.org&guid=test1233312&{{foo}}#foo]
            $regExUrl = '/(\[?\bhttps?:\/\/[^\s<>]+(?:\([\w]+\)|[^[:punct:]\s]|\/|\}|\]))/i';
            \preg_match_all($regExUrl, $html, $linksOld);

            if (!empty($linksOld[1])) {
                $linksOld = $linksOld[1];
                foreach ((array) $linksOld as $linkKey => $linkOld) {
                    $linksNew[$linkKey] = \str_replace(
                        self::$domLinkReplaceHelper['orig'],
                        self::$domLinkReplaceHelper['tmp'],
                        $linkOld
                    );
                }
            }
        }

        $linksNewCount = \count($linksNew);
        if ($linksNewCount > 0 && \count($linksOld) === $linksNewCount) {
            $search = \array_merge($linksOld, self::$domReplaceHelper['orig']);
            $replace = \array_merge($linksNew, self::$domReplaceHelper['tmp']);
        } else {
            $search = self::$domReplaceHelper['orig'];
            $replace = self::$domReplaceHelper['tmp'];
        }

        return \str_replace($search, $replace, $html);
    }

    /**
     * @param string $html
     *
     * @return string
     */
    public static function putReplacedBackToPreserveHtmlEntities(string $html): string
    {
        static $DOM_REPLACE__HELPER_CACHE = null;

        if ($DOM_REPLACE__HELPER_CACHE === null) {
            $DOM_REPLACE__HELPER_CACHE['tmp'] = \array_merge(
                self::$domLinkReplaceHelper['tmp'],
                self::$domReplaceHelper['tmp']
            );
            $DOM_REPLACE__HELPER_CACHE['orig'] = \array_merge(
                self::$domLinkReplaceHelper['orig'],
                self::$domReplaceHelper['orig']
            );

            $DOM_REPLACE__HELPER_CACHE['tmp']['html_wrapper__start'] = '<' . self::$domHtmlWrapperHelper . '>';
            $DOM_REPLACE__HELPER_CACHE['tmp']['html_wrapper__end'] = '</' . self::$domHtmlWrapperHelper . '>';

            $DOM_REPLACE__HELPER_CACHE['orig']['html_wrapper__start'] = '';
            $DOM_REPLACE__HELPER_CACHE['orig']['html_wrapper__end'] = '';
        }

        if (
            isset(self::$domBrokenReplaceHelper['tmp'])
            &&
            \count(self::$domBrokenReplaceHelper['tmp']) > 0
        ) {
            $html = \str_replace(self::$domBrokenReplaceHelper['tmp'], self::$domBrokenReplaceHelper['orig'], $html);
        }

        return \str_replace($DOM_REPLACE__HELPER_CACHE['tmp'], $DOM_REPLACE__HELPER_CACHE['orig'], $html);
    }

    /**
     * Create DOMDocument from HTML.
     *
     * @param string   $html
     * @param int|null $libXMLExtraOptions
     *
     * @return \DOMDocument
     */
    private function createDOMDocument(string $html, $libXMLExtraOptions = null): \DOMDocument
    {
        if ($this->keepBrokenHtml === true) {
            $html = $this->keepBrokenHtml(\trim($html));
        }

        if (\strpos($html, '<') === false) {
            $this->isDOMDocumentCreatedWithoutHtml = true;
        } elseif (\strpos(\ltrim($html), '<') !== 0) {
            $this->isDOMDocumentCreatedWithoutWrapper = true;
        }

        if (\strpos($html, '<html') === false) {
            $this->isDOMDocumentCreatedWithoutHtmlWrapper = true;
        }

        /** @noinspection HtmlRequiredTitleElement */
        if (\strpos($html, '<head>') === false) {
            $this->isDOMDocumentCreatedWithoutHeadWrapper = true;
        }

        if (
            \strpos($html, '</script>') === false
            &&
            \strpos($html, '<\/script>') !== false
        ) {
            $this->isDOMDocumentCreatedWithFakeEndScript = true;
        }

        // set error level
        $internalErrors = \libxml_use_internal_errors(true);
        $disableEntityLoader = \libxml_disable_entity_loader(true);
        \libxml_clear_errors();

        $optionsXml = \LIBXML_DTDLOAD | \LIBXML_DTDATTR | \LIBXML_NONET;

        if (\defined('LIBXML_BIGLINES')) {
            $optionsXml |= \LIBXML_BIGLINES;
        }

        if (\defined('LIBXML_COMPACT')) {
            $optionsXml |= \LIBXML_COMPACT;
        }

        if (\defined('LIBXML_HTML_NODEFDTD')) {
            $optionsXml |= \LIBXML_HTML_NODEFDTD;
        }

        if ($libXMLExtraOptions !== null) {
            $optionsXml |= $libXMLExtraOptions;
        }

        if (
            $this->isDOMDocumentCreatedWithoutWrapper === true
            ||
            $this->keepBrokenHtml === true
        ) {
            $html = '<' . self::$domHtmlWrapperHelper . '>' . $html . '</' . self::$domHtmlWrapperHelper . '>';
        }

        $html = self::replaceToPreserveHtmlEntities($html);

        $sxe = \simplexml_load_string($html, \SimpleXMLElement::class, $optionsXml);
        if ($sxe !== false && \count(\libxml_get_errors()) === 0) {
            $this->document = \dom_import_simplexml($sxe)->ownerDocument;
        } else {

            // UTF-8 hack: http://php.net/manual/en/domdocument.loadhtml.php#95251
            $xmlHackUsed = false;
            if (\stripos('<?xml', $html) !== 0) {
                $xmlHackUsed = true;
                $html = '<?xml encoding="' . $this->getEncoding() . '" ?>' . $html;
            }

            $this->document->loadHTML($html, $optionsXml);

            // remove the "xml-encoding" hack
            if ($xmlHackUsed === true) {
                foreach ($this->document->childNodes as $child) {
                    if ($child->nodeType === \XML_PI_NODE) {
                        /** @noinspection UnusedFunctionResultInspection */
                        $this->document->removeChild($child);

                        break;
                    }
                }
            }
        }

        // set encoding
        $this->document->encoding = $this->getEncoding();

        // restore lib-xml settings
        \libxml_clear_errors();
        \libxml_use_internal_errors($internalErrors);
        \libxml_disable_entity_loader($disableEntityLoader);

        return $this->document;
    }

    /**
     * @param string $html
     *
     * @return string
     */
    protected function keepBrokenHtml(string $html): string
    {
        do {
            $original = $html;

            $html = (string) \preg_replace_callback(
                '/(?<start>.*)<(?<element_start>[a-z]+)(?<element_start_addon> [^>]*)?>(?<value>.*?)<\/(?<element_end>\2)>(?<end>.*)/sui',
                function ($matches) {
                    return $matches['start'] .
                           '°lt_simple_html_dom__voku_°' . $matches['element_start'] . $matches['element_start_addon'] . '°gt_simple_html_dom__voku_°' .
                           $matches['value'] .
                           '°lt/_simple_html_dom__voku_°' . $matches['element_end'] . '°gt_simple_html_dom__voku_°' .
                           $matches['end'];
                },
                $html
            );
        } while ($original !== $html);

        do {
            $original = $html;

            $html = (string) \preg_replace_callback(
                '/(?<start>[^<]*)?(?<broken>(?:(?:<\/\w+(?:\s+\w+=\\"[^\"]+\\")*+)(?:[^<]+)>)+)(?<end>.*)/u',
                function ($matches) {
                    $matches['broken'] = \str_replace(
                        ['°lt/_simple_html_dom__voku_°', '°lt_simple_html_dom__voku_°', '°gt_simple_html_dom__voku_°'],
                        ['</', '<', '>'],
                        $matches['broken']
                    );

                    self::$domBrokenReplaceHelper['orig'][] = $matches['broken'];
                    self::$domBrokenReplaceHelper['tmp'][] = $matchesHash = '____simple_html_dom__voku__broken_html____' . \crc32($matches['broken']);

                    return $matches['start'] . $matchesHash . $matches['end'];
                },
                $html
            );
        } while ($original !== $html);

        return \str_replace(
            ['°lt/_simple_html_dom__voku_°', '°lt_simple_html_dom__voku_°', '°gt_simple_html_dom__voku_°'],
            ['</', '<', '>'],
            $html
        );
    }

    /**
     * Return element by #id.
     *
     * @param string $id
     *
     * @return SimpleHtmlDom|SimpleHtmlDomNodeBlank
     */
    public function getElementById(string $id)
    {
        return $this->find("#${id}", 0);
    }

    /**
     * Return element by tag name.
     *
     * @param string $name
     *
     * @return SimpleHtmlDom|SimpleHtmlDomNodeBlank
     */
    public function getElementByTagName(string $name)
    {
        $node = $this->document->getElementsByTagName($name)->item(0);

        if ($node === null) {
            return new SimpleHtmlDomNodeBlank();
        }

        return new SimpleHtmlDom($node);
    }

    /**
     * Returns elements by #id.
     *
     * @param string   $id
     * @param int|null $idx
     *
     * @return SimpleHtmlDom|SimpleHtmlDom[]|SimpleHtmlDomNodeInterface
     */
    public function getElementsById(string $id, $idx = null)
    {
        return $this->find("#${id}", $idx);
    }

    /**
     * Returns elements by tag name.
     *
     * @param string   $name
     * @param int|null $idx
     *
     * @return SimpleHtmlDom|SimpleHtmlDom[]|SimpleHtmlDomNode|SimpleHtmlDomNodeBlank
     */
    public function getElementsByTagName(string $name, $idx = null)
    {
        $nodesList = $this->document->getElementsByTagName($name);

        $elements = new SimpleHtmlDomNode();

        foreach ($nodesList as $node) {
            $elements[] = new SimpleHtmlDom($node);
        }

        // return all elements
        if ($idx === null) {
            return $elements;
        }

        // handle negative values
        if ($idx < 0) {
            $idx = \count($elements) + $idx;
        }

        // return one element
        return $elements[$idx] ?? new SimpleHtmlDomNodeBlank();
    }

    /**
     * Find one node with a CSS selector.
     *
     * @param string $selector
     *
     * @return SimpleHtmlDom|SimpleHtmlDomNodeInterface
     */
    public function findOne(string $selector)
    {
        return $this->find($selector, 0);
    }

    /**
     * Find list of nodes with a CSS selector.
     *
     * @param string $selector
     * @param int    $idx
     *
     * @return SimpleHtmlDom|SimpleHtmlDom[]|SimpleHtmlDomNodeInterface
     */
    public function find(string $selector, $idx = null)
    {
        $xPathQuery = SelectorConverter::toXPath($selector);

        $xPath = new \DOMXPath($this->document);
        $nodesList = $xPath->query($xPathQuery);
        $elements = new SimpleHtmlDomNode();

        foreach ($nodesList as $node) {
            $elements[] = new SimpleHtmlDom($node);
        }

        // return all elements
        if ($idx === null) {
            return $elements;
        }

        // handle negative values
        if ($idx < 0) {
            $idx = \count($elements) + $idx;
        }

        // return one element
        return $elements[$idx] ?? new SimpleHtmlDomNodeBlank();
    }

    /**
     * @param string $content
     * @param bool   $multiDecodeNewHtmlEntity
     *
     * @return string
     */
    public function fixHtmlOutput(string $content, bool $multiDecodeNewHtmlEntity = false): string
    {
        // INFO: DOMDocument will encapsulate plaintext into a e.g. paragraph tag (<p>),
        //          so we try to remove it here again ...

        if ($this->isDOMDocumentCreatedWithoutHtmlWrapper === true) {
            /** @noinspection HtmlRequiredLangAttribute */
            $content = \str_replace(
                [
                    "\n",
                    "\r\n",
                    "\r",
                    '<body>',
                    '</body>',
                    '<html>',
                    '</html>',
                ],
                '',
                $content
            );
        }

        if ($this->isDOMDocumentCreatedWithoutHeadWrapper === true) {
            /** @noinspection HtmlRequiredTitleElement */
            $content = \str_replace(
                [
                    '<head>',
                    '</head>',
                ],
                '',
                $content
            );
        }

        if ($this->isDOMDocumentCreatedWithFakeEndScript === true) {
            $content = \str_replace(
                '</script>',
                '',
                $content
            );
        }

        if ($this->isDOMDocumentCreatedWithoutWrapper === true) {
            $content = (string) \preg_replace('/^<p>/', '', $content);
            $content = (string) \preg_replace('/<\/p>/', '', $content);
        }

        if ($this->isDOMDocumentCreatedWithoutHtml === true) {
            $content = \str_replace(
                [
                    '<p>',
                    '</p>',
                    '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">',
                ],
                '',
                $content
            );
        }

        /** @noinspection CheckTagEmptyBody */
        /** @noinspection HtmlExtraClosingTag */
        /** @noinspection HtmlRequiredTitleElement */
        $content = \trim(
            \str_replace(
                [
                    '<simpleHtmlDomP>',
                    '</simpleHtmlDomP>',
                    '<head><head>',
                    '</head></head>',
                    '<br></br>',
                ],
                [
                    '',
                    '',
                    '<head>',
                    '</head>',
                    '<br>',
                ],
                $content
            )
        );

        if ($multiDecodeNewHtmlEntity === true) {
            if (\class_exists('\voku\helper\UTF8')) {

                /** @noinspection PhpUndefinedClassInspection */
                $content = UTF8::rawurldecode($content);
            } else {
                do {
                    $content_compare = $content;

                    $content = \rawurldecode(
                        \html_entity_decode(
                            $content,
                            \ENT_QUOTES | \ENT_HTML5
                        )
                    );
                } while ($content_compare !== $content);
            }
        } else {
            $content = \rawurldecode(
                \html_entity_decode(
                    $content,
                    \ENT_QUOTES | \ENT_HTML5
                )
            );
        }

        return self::putReplacedBackToPreserveHtmlEntities($content);
    }

    /**
     * @return \DOMDocument
     */
    public function getDocument(): \DOMDocument
    {
        return $this->document;
    }

    /**
     * Get the encoding to use.
     *
     * @return string
     */
    private function getEncoding(): string
    {
        return $this->encoding;
    }

    /**
     * @return bool
     */
    public function getIsDOMDocumentCreatedWithoutHtml(): bool
    {
        return $this->isDOMDocumentCreatedWithoutHtml;
    }

    /**
     * @return bool
     */
    public function getIsDOMDocumentCreatedWithoutHtmlWrapper(): bool
    {
        return $this->isDOMDocumentCreatedWithoutHtmlWrapper;
    }

    /**
     * @return bool
     */
    public function getIsDOMDocumentCreatedWithoutHeadWrapper(): bool
    {
        return $this->isDOMDocumentCreatedWithoutHeadWrapper;
    }

    /**
     * @return bool
     */
    public function getIsDOMDocumentCreatedWithoutWrapper(): bool
    {
        return $this->isDOMDocumentCreatedWithoutWrapper;
    }

    /**
     * Get dom node's outer html.
     *
     * @param bool $multiDecodeNewHtmlEntity
     *
     * @return string
     */
    public function html(bool $multiDecodeNewHtmlEntity = false): string
    {
        if ($this::$callback !== null) {
            \call_user_func($this::$callback, [$this]);
        }

        if ($this->getIsDOMDocumentCreatedWithoutHtmlWrapper()) {
            $content = $this->document->saveHTML($this->document->documentElement);
        } else {
            $content = $this->document->saveHTML();
        }

        return $this->fixHtmlOutput($content, $multiDecodeNewHtmlEntity);
    }

    /**
     * @param bool $keepBrokenHtml
     *
     * @return HtmlDomParser
     */
    public function useKeepBrokenHtml(bool $keepBrokenHtml): self
    {
        $this->keepBrokenHtml = $keepBrokenHtml;

        return $this;
    }

    /**
     * Get the HTML as XML.
     *
     * @param bool $multiDecodeNewHtmlEntity
     *
     * @return string
     */
    public function xml(bool $multiDecodeNewHtmlEntity = false): string
    {
        $xml = $this->document->saveXML(null, \LIBXML_NOEMPTYTAG);

        // remove the XML-header
        $xml = \ltrim((string) \preg_replace('/<\?xml.*\?>/', '', $xml));

        return $this->fixHtmlOutput($xml, $multiDecodeNewHtmlEntity);
    }

    /**
     * Get dom node's inner html.
     *
     * @param bool $multiDecodeNewHtmlEntity
     *
     * @return string
     */
    public function innerHtml(bool $multiDecodeNewHtmlEntity = false): string
    {
        // init
        $text = '';

        foreach ($this->document->documentElement->childNodes as $node) {
            $text .= $this->document->saveHTML($node);
        }

        return $this->fixHtmlOutput($text, $multiDecodeNewHtmlEntity);
    }

    /**
     * Load HTML from string.
     *
     * @param string   $html
     * @param int|null $libXMLExtraOptions
     *
     * @throws \InvalidArgumentException if argument is not string
     *
     * @return HtmlDomParser
     */
    public function loadHtml(string $html, $libXMLExtraOptions = null): self
    {
        $this->document = $this->createDOMDocument($html, $libXMLExtraOptions);

        return $this;
    }

    /**
     * Load HTML from file.
     *
     * @param string   $filePath
     * @param int|null $libXMLExtraOptions
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     *
     * @return HtmlDomParser
     */
    public function loadHtmlFile(string $filePath, $libXMLExtraOptions = null): self
    {
        if (
            !\preg_match("/^https?:\/\//i", $filePath)
            &&
            !\file_exists($filePath)
        ) {
            throw new \RuntimeException("File ${filePath} not found");
        }

        try {
            if (\class_exists('\voku\helper\UTF8')) {
                /** @noinspection PhpUndefinedClassInspection */
                $html = UTF8::file_get_contents($filePath);
            } else {
                $html = \file_get_contents($filePath);
            }
        } catch (\Exception $e) {
            throw new \RuntimeException("Could not load file ${filePath}");
        }

        if ($html === false) {
            throw new \RuntimeException("Could not load file ${filePath}");
        }

        return $this->loadHtml($html, $libXMLExtraOptions);
    }

    /**
     * Save the html-dom as string.
     *
     * @param string $filepath
     *
     * @return string
     */
    public function save(string $filepath = ''): string
    {
        $string = $this->innerHtml();
        if ($filepath !== '') {
            \file_put_contents($filepath, $string, \LOCK_EX);
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
     * Get dom node's plain text.
     *
     * @param bool $multiDecodeNewHtmlEntity
     *
     * @return string
     */
    public function text(bool $multiDecodeNewHtmlEntity = false): string
    {
        return $this->fixHtmlOutput($this->document->textContent, $multiDecodeNewHtmlEntity);
    }

    public function __clone()
    {
        $this->document = clone $this->document;
    }
}
