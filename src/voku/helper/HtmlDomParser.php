<?php

declare(strict_types=1);

namespace voku\helper;

/**
 * @property-read string $outerText
 *                                 <p>Get dom node's outer html (alias for "outerHtml").</p>
 * @property-read string $outerHtml
 *                                 <p>Get dom node's outer html.</p>
 * @property-read string $innerText
 *                                 <p>Get dom node's inner html (alias for "innerHtml").</p>
 * @property-read string $innerHtml
 *                                 <p>Get dom node's inner html.</p>
 * @property-read string $plaintext
 *                                 <p>Get dom node's plain text.</p>
 *
 * @method string outerText()
 *                                 <p>Get dom node's outer html (alias for "outerHtml()").</p>
 * @method string outerHtml()
 *                                 <p>Get dom node's outer html.</p>
 * @method string innerText()
 *                                 <p>Get dom node's inner html (alias for "innerHtml()").</p>
 * @method HtmlDomParser load(string $html)
 *                                 <p>Load HTML from string.</p>
 * @method HtmlDomParser load_file(string $html)
 *                                 <p>Load HTML from file.</p>
 * @method static HtmlDomParser file_get_html($html, $libXMLExtraOptions = null)
 *                                 <p>Load HTML from file.</p>
 * @method static HtmlDomParser str_get_html($html, $libXMLExtraOptions = null)
 *                                 <p>Load HTML from string.</p>
 */
class HtmlDomParser extends AbstractDomParser
{
    /**
     * @var string[]
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
     * @param \DOMNode|SimpleHtmlDomInterface|string $element HTML code or SimpleHtmlDomInterface, \DOMNode
     */
    public function __construct($element = null)
    {
        $this->document = new \DOMDocument('1.0', $this->getEncoding());

        // reset
        self::$domBrokenReplaceHelper = [];

        // DOMDocument settings
        $this->document->preserveWhiteSpace = true;
        $this->document->formatOutput = true;

        if ($element instanceof SimpleHtmlDomInterface) {
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
     * @param string $name
     * @param array  $arguments
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
     * @param string $name
     * @param array  $arguments
     *
     * @throws \BadMethodCallException
     * @throws \RuntimeException
     *
     * @return HtmlDomParser
     */
    public static function __callStatic($name, $arguments)
    {
        $arguments0 = $arguments[0] ?? '';

        $arguments1 = $arguments[1] ?? null;

        if ($name === 'str_get_html') {
            $parser = new static();

            return $parser->loadHtml($arguments0, $arguments1);
        }

        if ($name === 'file_get_html') {
            $parser = new static();

            return $parser->loadHtmlFile($arguments0, $arguments1);
        }

        throw new \BadMethodCallException('Method does not exist');
    }

    /** @noinspection MagicMethodsValidityInspection */

    /**
     * @param string $name
     *
     * @return string|null
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
     *
     * @deprecated
     */
    public function clear(): bool
    {
        return true;
    }

    /**
     * Create DOMDocument from HTML.
     *
     * @param string   $html
     * @param int|null $libXMLExtraOptions
     *
     * @return \DOMDocument
     */
    protected function createDOMDocument(string $html, $libXMLExtraOptions = null): \DOMDocument
    {
        if ($this->keepBrokenHtml) {
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

        if (\strpos($html, '<script') !== false) {
            $this->html5FallbackForScriptTags($html);

            if (
                \strpos($html, 'type="text/html"') !== false
                ||
                \strpos($html, 'type=\'text/html\'') !== false
                ||
                \strpos($html, 'type=text/html') !== false
                ||
                \strpos($html, 'type="text/x-custom-template"') !== false
                ||
                \strpos($html, 'type=\'text/x-custom-template\'') !== false
                ||
                \strpos($html, 'type=text/x-custom-template') !== false
            ) {
                $this->keepSpecialScriptTags($html);
            }
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
            $this->isDOMDocumentCreatedWithoutWrapper
            ||
            $this->keepBrokenHtml
        ) {
            $html = '<' . self::$domHtmlWrapperHelper . '>' . $html . '</' . self::$domHtmlWrapperHelper . '>';
        }

        $html = self::replaceToPreserveHtmlEntities($html);

        $documentFound = false;
        $sxe = \simplexml_load_string($html, \SimpleXMLElement::class, $optionsXml);
        if ($sxe !== false && \count(\libxml_get_errors()) === 0) {
            $domElementTmp = \dom_import_simplexml($sxe);
            if ($domElementTmp) {
                $documentFound = true;
                $this->document = $domElementTmp->ownerDocument;
            }
        }

        if ($documentFound === false) {

            // UTF-8 hack: http://php.net/manual/en/domdocument.loadhtml.php#95251
            $xmlHackUsed = false;
            if (\stripos('<?xml', $html) !== 0) {
                $xmlHackUsed = true;
                $html = '<?xml encoding="' . $this->getEncoding() . '" ?>' . $html;
            }

            $this->document->loadHTML($html, $optionsXml);

            // remove the "xml-encoding" hack
            if ($xmlHackUsed) {
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
     * Find list of nodes with a CSS selector.
     *
     * @param string   $selector
     * @param int|null $idx
     *
     * @return SimpleHtmlDomInterface|SimpleHtmlDomInterface[]|SimpleHtmlDomNodeInterface
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
            if (\count($elements) === 0) {
                return new SimpleHtmlDomNodeBlank();
            }

            return $elements;
        }

        // handle negative values
        if ($idx < 0) {
            $idx = \count($elements) + $idx;
        }

        // return one element
        return $elements[$idx] ?? new SimpleHtmlDomBlank();
    }

    /**
     * Find nodes with a CSS selector.
     *
     * @param string $selector
     *
     * @return SimpleHtmlDomInterface[]|SimpleHtmlDomNodeInterface
     */
    public function findMulti(string $selector): SimpleHtmlDomNodeInterface
    {
        return $this->find($selector, null);
    }

    /**
     * Find one node with a CSS selector.
     *
     * @param string $selector
     *
     * @return SimpleHtmlDomInterface
     */
    public function findOne(string $selector): SimpleHtmlDomInterface
    {
        return $this->find($selector, 0);
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

        if ($this->isDOMDocumentCreatedWithoutHtmlWrapper) {
            /** @noinspection HtmlRequiredLangAttribute */
            $content = \str_replace(
                [
                    '<body>',
                    '</body>',
                    '<html>',
                    '</html>',
                ],
                '',
                $content
            );
        }

        if ($this->isDOMDocumentCreatedWithoutHeadWrapper) {
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

        if ($this->isDOMDocumentCreatedWithFakeEndScript) {
            $content = \str_replace(
                '</script>',
                '',
                $content
            );
        }

        if ($this->isDOMDocumentCreatedWithoutWrapper) {
            $content = (string) \preg_replace('/^<p>/', '', $content);
            $content = (string) \preg_replace('/<\/p>/', '', $content);
        }

        if ($this->isDOMDocumentCreatedWithoutHtml) {
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

        $content = $this->decodeHtmlEntity($content, $multiDecodeNewHtmlEntity);

        return self::putReplacedBackToPreserveHtmlEntities($content);
    }

    /**
     * Return elements by .class.
     *
     * @param string $class
     *
     * @return SimpleHtmlDomInterface[]|SimpleHtmlDomNodeInterface
     */
    public function getElementByClass(string $class): SimpleHtmlDomNodeInterface
    {
        return $this->findMulti(".${class}");
    }

    /**
     * Return element by #id.
     *
     * @param string $id
     *
     * @return SimpleHtmlDomInterface
     */
    public function getElementById(string $id): SimpleHtmlDomInterface
    {
        return $this->findOne("#${id}");
    }

    /**
     * Return element by tag name.
     *
     * @param string $name
     *
     * @return SimpleHtmlDomInterface
     */
    public function getElementByTagName(string $name): SimpleHtmlDomInterface
    {
        $node = $this->document->getElementsByTagName($name)->item(0);

        if ($node === null) {
            return new SimpleHtmlDomBlank();
        }

        return new SimpleHtmlDom($node);
    }

    /**
     * Returns elements by #id.
     *
     * @param string   $id
     * @param int|null $idx
     *
     * @return SimpleHtmlDomInterface|SimpleHtmlDomInterface[]|SimpleHtmlDomNodeInterface
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
     * @return SimpleHtmlDomInterface|SimpleHtmlDomInterface[]|SimpleHtmlDomNodeInterface
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
            if (\count($elements) === 0) {
                return new SimpleHtmlDomNodeBlank();
            }

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

        if ($content === false) {
            return '';
        }

        return $this->fixHtmlOutput($content, $multiDecodeNewHtmlEntity);
    }

    /**
     * Load HTML from string.
     *
     * @param string   $html
     * @param int|null $libXMLExtraOptions
     *
     * @return HtmlDomParser
     */
    public function loadHtml(string $html, $libXMLExtraOptions = null): DomParserInterface
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
     *
     * @return HtmlDomParser
     */
    public function loadHtmlFile(string $filePath, $libXMLExtraOptions = null): DomParserInterface
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

            $DOM_REPLACE__HELPER_CACHE['tmp']['html_special_script__start'] = '<' . self::$domHtmlSpecialScriptHelper;
            $DOM_REPLACE__HELPER_CACHE['tmp']['html_special_script__end'] = '</' . self::$domHtmlSpecialScriptHelper . '>';

            $DOM_REPLACE__HELPER_CACHE['orig']['html_special_script__start'] = '<script';
            $DOM_REPLACE__HELPER_CACHE['orig']['html_special_script__end'] = '</script>';
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
     * Get the HTML as XML or plain XML if needed.
     *
     * @param bool $multiDecodeNewHtmlEntity
     * @param bool $htmlToXml
     * @param bool $removeXmlHeader
     * @param int  $options
     *
     * @return string
     */
    public function xml(
        bool $multiDecodeNewHtmlEntity = false,
        bool $htmlToXml = true,
        bool $removeXmlHeader = true,
        int $options = \LIBXML_NOEMPTYTAG
    ): string {
        $xml = $this->document->saveXML(null, $options);

        if ($removeXmlHeader) {
            $xml = \ltrim((string) \preg_replace('/<\?xml.*\?>/', '', $xml));
        }

        if ($htmlToXml) {
            $return = $this->fixHtmlOutput($xml, $multiDecodeNewHtmlEntity);
        } else {
            $xml = $this->decodeHtmlEntity($xml, $multiDecodeNewHtmlEntity);

            $return = self::putReplacedBackToPreserveHtmlEntities($xml);
        }

        return $return;
    }

    /**
     * @param string $selector
     * @param int    $idx
     *
     * @return SimpleHtmlDomInterface|SimpleHtmlDomInterface[]|SimpleHtmlDomNodeInterface
     */
    public function __invoke($selector, $idx = null)
    {
        return $this->find($selector, $idx);
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
    public function getIsDOMDocumentCreatedWithoutWrapper(): bool
    {
        return $this->isDOMDocumentCreatedWithoutWrapper;
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
                static function ($matches) {
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
                static function ($matches) {
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
     * @param string $html
     */
    protected function keepSpecialScriptTags(string &$html)
    {
        $specialScripts = [];
        // regEx for e.g.: [<script id="elements-image-1" type="text/html">...</script>]
        $regExSpecialScript = '/<(script) [^>]*type=(["\']){0,1}(text\/html|text\/x-custom-template)\2{0,1}([^>]*)>.*<\/\1>/isU';
        \preg_match_all($regExSpecialScript, $html, $specialScripts);

        if (isset($specialScripts[0])) {
            foreach ($specialScripts[0] as $specialScript) {
                $specialNonScript = '<' . self::$domHtmlSpecialScriptHelper . \substr($specialScript, \strlen('<script'));
                $specialNonScript = \substr($specialNonScript, 0, -\strlen('</script>')) . '</' . self::$domHtmlSpecialScriptHelper . '>';
                // remove the html5 fallback
                $specialNonScript = \str_replace('<\/', '</', $specialNonScript);

                $html = \str_replace($specialScript, $specialNonScript, $html);
            }
        }
    }

    /**
     * @param bool $keepBrokenHtml
     *
     * @return HtmlDomParser
     */
    public function useKeepBrokenHtml(bool $keepBrokenHtml): DomParserInterface
    {
        $this->keepBrokenHtml = $keepBrokenHtml;

        return $this;
    }
}
