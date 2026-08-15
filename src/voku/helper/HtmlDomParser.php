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
 * @method static HtmlDomParser file_get_html($filePath, $libXMLExtraOptions = null)
 *                                 <p>Load HTML from file.</p>
 * @method static HtmlDomParser str_get_html($html, $libXMLExtraOptions = null)
 *                                 <p>Load HTML from string.</p>
 */
class HtmlDomParser extends AbstractDomParser
{
    /**
     * @var callable|null
     *
     * @phpstan-var null|callable(string $cssSelectorString, string $xPathString, \DOMXPath, \voku\helper\HtmlDomParser): string
     */
    private $callbackXPathBeforeQuery;

    /**
     * @var callable|null
     *
     * @phpstan-var null|callable(string $htmlString, \voku\helper\HtmlDomParser): string
     */
    private $callbackBeforeCreateDom;

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
     * @var string[]
     */
    protected $templateLogicSyntaxInSpecialScriptTags = [
        '+',
        '<%',
        '{%',
        '{{',
    ];

    /**
     * The properties specified for each special script tag is an array.
     *
     * ```php
     * protected $specialScriptTags = [
     *     'text/html',
     *     'text/template',
     *     'text/x-custom-template',
     *     'text/x-handlebars-template'
     * ]
     * ```
     *
     * @var string[]
     */
    protected $specialScriptTags = [
        'text/html',
        'text/template',
        'text/x-custom-template',
        'text/x-handlebars-template',
    ];

    /**
     * @var string[]
     */
    protected $selfClosingTags = [
        'area',
        'base',
        'br',
        'col',
        'command',
        'embed',
        'hr',
        'img',
        'input',
        'keygen',
        'link',
        'meta',
        'param',
        'source',
        'track',
        'wbr',
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
    protected $isDOMDocumentCreatedWithCommentWrapper = false;

    /**
     * @var bool
     */
    protected $isDOMDocumentCreatedWithoutHeadWrapper = false;

    /**
     * @var bool
     */
    protected $isDOMDocumentCreatedWithoutPTagWrapper = false;

    /**
     * @var bool
     */
    protected $isDOMDocumentCreatedWithoutHtmlWrapper = false;

    /**
     * @var bool
     */
    protected $isDOMDocumentCreatedWithoutBodyWrapper = false;

    /**
     * @var bool
     */
    protected $isDOMDocumentCreatedWithMultiRoot = false;

    /**
     * @var bool
     */
    protected $isDOMDocumentCreatedWithEdgeWhitespace = false;

    /**
     * @var bool
     */
    protected $isDOMDocumentCreatedWithFakeEndScript = false;

    /**
     * @var bool
     */
    protected $createdFromNode = false;

    /**
     * @var bool
     */
    protected $keepBrokenHtml = false;

    /**
     * Default for new instances, see "useHtml5ParserByDefault()".
     *
     * @var bool
     */
    protected static $useHtml5ParserByDefault = false;

    /**
     * @var bool
     */
    protected $useHtml5Parser = false;

    /**
     * @var bool
     */
    protected $isDOMDocumentCreatedWithHtml5Parser = false;

    /**
     * Placeholder attribute name used while bridging an HTML5-parsed document, see
     * "parkXmlnsAttributes()".
     *
     * @var string
     */
    private static $domHtmlXmlnsHelper = 'data-simplevokuxmlns';

    /**
     * @param \DOMNode|SimpleHtmlDomInterface|string $element HTML code or SimpleHtmlDomInterface, \DOMNode
     */
    public function __construct($element = null)
    {
        $this->useHtml5Parser = static::$useHtml5ParserByDefault;

        $this->document = new \DOMDocument('1.0', $this->getEncoding());

        // DOMDocument settings
        $this->document->preserveWhiteSpace = true;
        $this->document->formatOutput = false;

        if ($element instanceof SimpleHtmlDomInterface) {
            $element = $element->getNode();
        }

        if ($element instanceof \DOMDocument) {
            $html = $element->saveHTML();
            if ($html !== false) {
                $this->loadHtml($html);
            }

            return;
        }

        if ($element instanceof \DOMNode) {
            $this->createdFromNode = true;

            $domNode = $this->document->importNode($element, true);

            // @phpstan-ignore instanceof.alwaysTrue (importNode() returns DOMNode here)
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
     * @param string       $name
     * @param array<mixed> $arguments
     *
     * @return bool|mixed
     */
    public function __call($name, $arguments)
    {
        $name = \strtolower($name);

        if (isset(self::$functionAliases[$name])) {
            $method = self::$functionAliases[$name];

            return $this->{$method}(...$arguments);
        }

        throw new \BadMethodCallException('Method does not exist: ' . $name);
    }

    /**
     * @param string       $name
     * @param array<mixed> $arguments
     *
     * @throws \BadMethodCallException
     * @throws \RuntimeException
     *
     * @return static
     */
    public static function __callStatic($name, $arguments)
    {
        $arguments0 = $arguments[0] ?? '';

        $arguments1 = $arguments[1] ?? null;

        if ($name === 'str_get_html') {
            $parser = self::createStaticParser();

            return $parser->loadHtml($arguments0, $arguments1);
        }

        if ($name === 'file_get_html') {
            $parser = self::createStaticParser();

            return $parser->loadHtmlFile($arguments0, $arguments1);
        }

        throw new \BadMethodCallException('Method does not exist');
    }

    /**
     * @return static
     */
    private static function createStaticParser()
    {
        // @phpstan-ignore new.static (factory methods intentionally preserve late static binding)
        return new static();
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
            case 'innerhtmlkeep':
                return $this->innerHtml(false, false);
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
     * @param bool     $useDefaultLibXMLOptions
     *
     * @return \DOMDocument
     */
    protected function createDOMDocument(string $html, $libXMLExtraOptions = null, $useDefaultLibXMLOptions = true): \DOMDocument
    {
        $this->resetDynamicDomHelpers();

        if ($this->callbackBeforeCreateDom) {
            $html = \call_user_func($this->callbackBeforeCreateDom, $html, $this);
        }

        // Remove content before <!DOCTYPE.*> because otherwise the DOMDocument can not handle the input.
        $isDOMDocumentCreatedWithDoctype = false;
        if (\stripos($html, '<!DOCTYPE') !== false) {
            $isDOMDocumentCreatedWithDoctype = true;
            if (
                \preg_match('/(^.*?)<!DOCTYPE(?: [^>]*)?>/sui', $html, $matches_before_doctype)
                &&
                \trim($matches_before_doctype[1])
            ) {
                $html = \str_replace($matches_before_doctype[1], '', $html);
            }
        }

        if ($this->keepBrokenHtml) {
            $html = $this->keepBrokenHtml(\trim($html));
        }

        if (\strpos($html, '<') === false) {
            $this->isDOMDocumentCreatedWithoutHtml = true;
        } elseif (\strpos(\ltrim($html), '<') !== 0) {
            $this->isDOMDocumentCreatedWithoutWrapper = true;
        }

        if (\strpos(\ltrim($html), '<!--') === 0) {
            $this->isDOMDocumentCreatedWithCommentWrapper = true;
        }

        /** @noinspection HtmlRequiredLangAttribute */
        if (
            \strpos($html, '<html ') === false
            &&
            \strpos($html, '<html>') === false
        ) {
            $this->isDOMDocumentCreatedWithoutHtmlWrapper = true;
        }

        if (
            \strpos($html, '<body ') === false
            &&
            \strpos($html, '<body>') === false
        ) {
            $this->isDOMDocumentCreatedWithoutBodyWrapper = true;
        }

        if (
            $this->isDOMDocumentCreatedWithoutHtmlWrapper
            &&
            $this->isDOMDocumentCreatedWithoutBodyWrapper
            &&
            \trim($html) !== $html
            &&
            \substr_count($html, '</') >= 2
            &&
            \preg_match('#^\s*<([a-zA-Z][^\\s>/]*)>.*?</\\1>#su', $html) === 1
        ) {
            $this->isDOMDocumentCreatedWithEdgeWhitespace = true;
        }

        /** @noinspection HtmlRequiredTitleElement */
        if (
            \strpos($html, '<head ') === false
            &&
            \strpos($html, '<head>') === false
        ) {
            $this->isDOMDocumentCreatedWithoutHeadWrapper = true;
        }

        if (
            \stripos($html, '<p ') === false
            &&
            \stripos($html, '<p>') === false
        ) {
            $this->isDOMDocumentCreatedWithoutPTagWrapper = true;
        }

        if (
            \strpos($html, '</script>') === false
            &&
            \strpos($html, '<\/script>') !== false
        ) {
            $this->isDOMDocumentCreatedWithFakeEndScript = true;
        }

        if (\stripos($html, '</html>') !== false) {
            /** @noinspection NestedPositiveIfStatementsInspection */
            if (
                \preg_match('/<\/html>(.*?)/suiU', $html, $matches_after_html)
                &&
                \trim($matches_after_html[1])
            ) {
                $html = \str_replace($matches_after_html[0], $matches_after_html[1] . '</html>', $html);
            }
        }

        $this->isDOMDocumentCreatedWithHtml5Parser = false;

        // INFO: PHP >= 8.4 ships "\Dom\HTMLDocument", an HTML5-spec parser that recovers from
        //          broken markup the way a browser does. It is opt-in, because it parses into
        //          the new DOM implementation and this class must keep handing out a legacy
        //          "\DOMDocument", which costs one extra serialize + parse round-trip.
        if ($this->useHtml5Parser && !$this->keepBrokenHtml) {
            $html5Document = $this->createDOMDocumentViaHtml5Parser($html);

            if ($html5Document !== null) {
                $this->document = $html5Document;
                $this->isDOMDocumentCreatedWithHtml5Parser = true;

                return $this->document;
            }
        }

        if (\strpos($html, '<script') !== false) {
            // keepSpecialScriptTags must run before html5FallbackForScriptTags so
            // that special-type scripts (type="text/html", etc.) are converted to
            // the simplevokuspecialscript placeholder element before the script-tag
            // regex runs.  On PHP < 8.0 the regex uses hash placeholders; if it
            // ran first the special-script content would be hashed and
            // keepSpecialScriptTags would only see the hash, losing the ability to
            // pass the real HTML content to the DOM for error-recovery parsing.
            foreach ($this->specialScriptTags as $tag) {
                if (\strpos($html, $tag) !== false) {
                    $this->keepSpecialScriptTags($html);
                    break;
                }
            }

            $this->html5FallbackForScriptTags($html);
        }

        if (\strpos($html, '<svg') !== false) {
            $this->keepSpecialSvgTags($html);
        }

        $html = \str_replace(
            \array_map(static function ($e) {
                return '<' . $e . '>';
            }, $this->selfClosingTags),
            \array_map(static function ($e) {
                return '<' . $e . '/>';
            }, $this->selfClosingTags),
            $html
        );

        // set error level
        $internalErrors = \libxml_use_internal_errors(true);
        if (\PHP_VERSION_ID < 80000) {
            $disableEntityLoader = \libxml_disable_entity_loader(true);
        }
        \libxml_clear_errors();

        $optionsXml = 0;
        if ($useDefaultLibXMLOptions) {
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
        }

        if ($libXMLExtraOptions !== null) {
            $optionsXml |= $libXMLExtraOptions;
        }

        if (
            $this->isDOMDocumentCreatedWithoutHtmlWrapper
            &&
            $this->isDOMDocumentCreatedWithoutBodyWrapper
        ) {
            $this->isDOMDocumentCreatedWithMultiRoot = $this->hasMultipleTopLevelNodes($html, $optionsXml);
        }

        if (
            $this->isDOMDocumentCreatedWithMultiRoot
            ||
            $this->isDOMDocumentCreatedWithEdgeWhitespace
            ||
            $this->isDOMDocumentCreatedWithoutWrapper
            ||
            $this->isDOMDocumentCreatedWithCommentWrapper
            ||
            (
                !$isDOMDocumentCreatedWithDoctype
                &&
                $this->keepBrokenHtml
            )
        ) {
            $html = '<' . self::$domHtmlWrapperHelper . '>' . $html . '</' . self::$domHtmlWrapperHelper . '>';
        }

        $html = self::replaceToPreserveHtmlEntities($html);

        $documentFound = false;
        $sxe = \simplexml_load_string($html, \SimpleXMLElement::class, $optionsXml);
        if ($sxe !== false && \count(\libxml_get_errors()) === 0) {
            $domElementTmp = \dom_import_simplexml($sxe);
            if ($domElementTmp->ownerDocument instanceof \DOMDocument) {
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

            if ($html !== '') {
                $this->document->loadHTML($html, $optionsXml);
            }

            // remove the "xml-encoding" hack
            if ($xmlHackUsed) {
                foreach ($this->document->childNodes as $child) {
                    if ($child->nodeType === \XML_PI_NODE) {
                        $this->document->removeChild($child);

                        break;
                    }
                }
            }
        }

        $this->markSyntheticParagraphWrapper();

        // set encoding
        $this->document->encoding = $this->getEncoding();

        // restore lib-xml settings
        \libxml_clear_errors();
        \libxml_use_internal_errors($internalErrors);
        // @phpstan-ignore isset.variable (only defined on PHP < 8 paths where it is used)
        if (\PHP_VERSION_ID < 80000 && isset($disableEntityLoader)) {
            \libxml_disable_entity_loader($disableEntityLoader);
        }

        return $this->document;
    }

    /**
     * Parse the HTML with the HTML5 parser of PHP >= 8.4 and bridge the result into a
     * legacy "\DOMDocument".
     *
     * The bridge is a serialize + parse round-trip on purpose: "\Dom\HTMLDocument" and
     * "\DOMDocument" are separate implementations on top of the same libxml document, and
     * PHP refuses to hand a node of the new implementation to the old one (and the other
     * way around), so there is no zero-copy handoff to use instead. XML is used as the
     * transport because the tree is already HTML5-normalized at that point, so the XML
     * parser only has to rebuild it, while re-parsing it as HTML would hand the tree back
     * to the very parser this method exists to bypass.
     *
     * "\Dom\HTML_NO_DEFAULT_NS" keeps the elements out of the XHTML namespace, so the
     * resulting document matches what the legacy parser produces and the generated XPath
     * queries of this library keep working without namespace handling.
     *
     * @param string $html
     *
     * @return \DOMDocument|null <p>NULL if the HTML5 parser is unavailable or refused the input;
     *                           the caller then falls back to the legacy parser.</p>
     */
    private function createDOMDocumentViaHtml5Parser(string $html): ?\DOMDocument
    {
        if (!self::isHtml5ParserSupported()) {
            return null;
        }

        // INFO: the HTML5 parser detects the encoding the way the specification does - from a
        //          byte-order mark or a <meta> charset - which is the browser behavior this
        //          parser is used for. Only a parser that was configured for a specific
        //          encoding overrules that detection.
        $encoding = $this->getEncoding();
        $overrideEncoding = \strcasecmp($encoding, 'UTF-8') === 0 ? null : $encoding;

        try {
            /** @phpstan-ignore class.notFound, classConstant.notFound (PHP >= 8.4 only, guarded by isHtml5ParserSupported()) */
            $html5Document = \Dom\HTMLDocument::createFromString(
                $html,
                \LIBXML_NOERROR | \Dom\HTML_NO_DEFAULT_NS,
                $overrideEncoding
            );

            // INFO: in HTML an "xmlns" attribute is just an attribute, but the XML transport
            //          used below would turn it into a real namespace declaration and every
            //          generated XPath query of this library would stop matching. It is
            //          parked under a placeholder name and restored after the transport.
            $hasXmlnsAttributes = \stripos($html, 'xmlns') !== false
                                  &&
                                  $this->parkXmlnsAttributes($html5Document);

            $xml = $html5Document->saveXml();
        } catch (\Throwable $throwable) {
            return null;
        }

        if ($xml === false || $xml === '') {
            return null;
        }

        $document = new \DOMDocument('1.0', $this->getEncoding());
        $document->preserveWhiteSpace = true;
        $document->formatOutput = false;

        $internalErrors = \libxml_use_internal_errors(true);
        \libxml_clear_errors();

        $loaded = $document->loadXML($xml, \LIBXML_NONET);

        \libxml_clear_errors();
        \libxml_use_internal_errors($internalErrors);

        if ($loaded === false) {
            return null;
        }

        if ($hasXmlnsAttributes) {
            $this->restoreXmlnsAttributes($document);
        }

        $document->encoding = $this->getEncoding();

        return $document;
    }

    /**
     * Rename every "xmlns" attribute of an HTML5-parsed document to a placeholder name.
     *
     * @param object $html5Document <p>A "\Dom\HTMLDocument" of PHP >= 8.4.</p>
     *
     * @return bool <p>TRUE if at least one attribute was renamed.</p>
     */
    private function parkXmlnsAttributes($html5Document): bool
    {
        /** @phpstan-ignore class.notFound, argument.type (PHP >= 8.4 only, guarded by isHtml5ParserSupported()) */
        $xPath = new \Dom\XPath($html5Document);
        $elements = $xPath->query('//*[@xmlns]');

        if ($elements->length === 0) {
            return false;
        }

        foreach ($elements as $element) {
            /** @phpstan-ignore method.notFound, method.notFound (\Dom\Element of PHP >= 8.4) */
            $element->setAttribute(self::$domHtmlXmlnsHelper, $element->getAttribute('xmlns'));
            /** @phpstan-ignore method.notFound (\Dom\Element of PHP >= 8.4) */
            $element->removeAttribute('xmlns');
        }

        return true;
    }

    /**
     * Restore the "xmlns" attributes that parkXmlnsAttributes() renamed.
     *
     * @param \DOMDocument $document
     *
     * @return void
     */
    private function restoreXmlnsAttributes(\DOMDocument $document)
    {
        $xPath = new \DOMXPath($document);
        $elements = $xPath->query('//*[@' . self::$domHtmlXmlnsHelper . ']');

        if ($elements === false) {
            return;
        }

        foreach ($elements as $element) {
            if (!$element instanceof \DOMElement) {
                continue;
            }

            $element->setAttribute('xmlns', $element->getAttribute(self::$domHtmlXmlnsHelper));
            $element->removeAttribute(self::$domHtmlXmlnsHelper);
        }
    }

    /**
     * Check if the HTML5 parser of PHP >= 8.4 can be used on this runtime.
     *
     * @return bool
     */
    public static function isHtml5ParserSupported(): bool
    {
        return \PHP_VERSION_ID >= 80400
               &&
               \class_exists('Dom\HTMLDocument')
               &&
               \defined('Dom\HTML_NO_DEFAULT_NS');
    }

    /**
     * Use the HTML5 parser of PHP >= 8.4 ("\Dom\HTMLDocument") for this instance.
     *
     * It parses like a browser does: implied "tbody", auto-closed "p" / "li" / "td",
     * recovery from nested tables and from misnested formatting tags. The libxml-based
     * parser stays the default, because bridging the result back into the "\DOMDocument"
     * that this library hands out costs an extra serialize + parse round-trip.
     *
     * The legacy parser is used anyway - without an error - when the runtime is older than
     * PHP 8.4, when "useKeepBrokenHtml()" is active (that repair is written for the legacy
     * parser), or when the HTML5 parser refuses the input. Use
     * "getIsDOMDocumentCreatedWithHtml5Parser()" to see which parser produced the current
     * document.
     *
     * @param bool $useHtml5Parser
     *
     * @return $this
     */
    public function useHtml5Parser(bool $useHtml5Parser = true): DomParserInterface
    {
        $this->useHtml5Parser = $useHtml5Parser;

        return $this;
    }

    /**
     * Use the HTML5 parser of PHP >= 8.4 for every "HtmlDomParser" created from now on.
     *
     * This is the switch for the static entry points ("HtmlDomParser::str_get_html()",
     * "HtmlDomParser::file_get_html()"), which create their instance internally. It does
     * not change instances that already exist.
     *
     * @param bool $useHtml5Parser
     *
     * @return void
     */
    public static function useHtml5ParserByDefault(bool $useHtml5Parser = true)
    {
        static::$useHtml5ParserByDefault = $useHtml5Parser;
    }

    /**
     * Check if the current document was created by the HTML5 parser of PHP >= 8.4.
     *
     * @return bool
     */
    public function getIsDOMDocumentCreatedWithHtml5Parser(): bool
    {
        return $this->isDOMDocumentCreatedWithHtml5Parser;
    }

    /**
     * Find list of nodes with a CSS selector.
     *
     * @param string   $selector
     * @param int|null $idx
     *
     * @return SimpleHtmlDomInterface|SimpleHtmlDomInterface[]|SimpleHtmlDomNodeInterface<SimpleHtmlDomInterface>
     */
    public function find(string $selector, $idx = null)
    {
        return $this->findInNodeContext($selector, null, $idx);
    }

    /**
     * Find list of nodes with a CSS selector within an optional DOM context.
     *
     * @param string        $selector
     * @param \DOMNode|null $contextNode
     * @param int|null      $idx
     *
     * @return SimpleHtmlDomInterface|SimpleHtmlDomInterface[]|SimpleHtmlDomNodeInterface<SimpleHtmlDomInterface>
     *
     * @internal Used by wrapped SimpleHtmlDom instances to preserve parser
     *           callback state when scoping queries to an existing DOM node.
     */
    public function findInNodeContext(string $selector, ?\DOMNode $contextNode = null, $idx = null)
    {
        return self::findInDocumentContext(
            $selector,
            $this->document,
            $contextNode,
            $idx,
            $this->callbackXPathBeforeQuery,
            $this
        );
    }

    /**
     * Find list of nodes with a CSS selector within an optional DOMDocument
     * context, optionally applying the parser callback before the XPath query.
     *
     * @param string        $selector
     * @param \DOMDocument  $document
     * @param \DOMNode|null $contextNode
     * @param int|null      $idx
     * @param callable|null $callbackXPathBeforeQuery
     * @param self|null     $queryHtmlDomParser
     *
     * @return SimpleHtmlDomInterface|SimpleHtmlDomInterface[]|SimpleHtmlDomNodeInterface<SimpleHtmlDomInterface>
     *
     * @phpstan-param null|callable(string, string, \DOMXPath, self): string $callbackXPathBeforeQuery
     *
     * @internal Used by wrapped SimpleHtmlDom instances to keep queries scoped
     *           to an existing DOMDocument while preserving parser callback
     *           behavior.
     */
    public static function findInDocumentContext(
        string $selector,
        \DOMDocument $document,
        ?\DOMNode $contextNode = null,
        $idx = null,
        ?callable $callbackXPathBeforeQuery = null,
        ?self $queryHtmlDomParser = null
    ) {
        $xPathQuery = SelectorConverter::toXPath($selector);

        $xPath = new \DOMXPath($document);

        if ($callbackXPathBeforeQuery !== null && $queryHtmlDomParser !== null) {
            $xPathQuery = \call_user_func($callbackXPathBeforeQuery, $selector, $xPathQuery, $xPath, $queryHtmlDomParser);
        }

        if ($contextNode !== null) {
            $xPathQuery = self::scopeXPathQueryToContextNode($xPathQuery);
        }

        $nodesList = $xPath->query($xPathQuery, $contextNode);

        return self::createFindResultFromNodeList($nodesList, $idx, $queryHtmlDomParser);
    }

    /**
     * Prefix absolute XPath segments so they stay scoped to the provided
     * context node, including every branch of union expressions.
     *
     * @param string $xPathQuery
     *
     * @return string
     */
    private static function scopeXPathQueryToContextNode(string $xPathQuery): string
    {
        $scopedXPathQuery = '';
        $quoteCharacter = null;
        $bracketDepth = 0;
        $parenthesisDepth = 0;
        $isAtBranchStart = true;
        $length = \strlen($xPathQuery);

        for ($i = 0; $i < $length; ++$i) {
            $character = $xPathQuery[$i];

            if ($quoteCharacter !== null) {
                $scopedXPathQuery .= $character;

                if ($character === $quoteCharacter) {
                    $quoteCharacter = null;
                }

                continue;
            }

            if ($character === '"' || $character === "'") {
                $scopedXPathQuery .= $character;
                $quoteCharacter = $character;

                continue;
            }

            if ($isAtBranchStart) {
                if (\trim($character) === '') {
                    $scopedXPathQuery .= $character;

                    continue;
                }

                if ($character === '/') {
                    $scopedXPathQuery .= '.';
                }

                $isAtBranchStart = false;
            }

            if ($character === '[') {
                ++$bracketDepth;
            } elseif ($character === ']' && $bracketDepth > 0) {
                --$bracketDepth;
            } elseif ($character === '(') {
                ++$parenthesisDepth;
            } elseif ($character === ')' && $parenthesisDepth > 0) {
                --$parenthesisDepth;
            }

            $scopedXPathQuery .= $character;

            if ($character === '|' && $bracketDepth === 0 && $parenthesisDepth === 0) {
                $isAtBranchStart = true;
            }
        }

        return $scopedXPathQuery;
    }

    /**
     * @param \DOMNodeList<\DOMNameSpaceNode|\DOMNode>|false $nodesList
     * @param int|null                                       $idx
     *
     * @return SimpleHtmlDomInterface|SimpleHtmlDomInterface[]|SimpleHtmlDomNodeInterface<SimpleHtmlDomInterface>
     */
    private static function createFindResultFromNodeList($nodesList, $idx, ?self $queryHtmlDomParser = null)
    {
        $elements = new SimpleHtmlDomNode();

        if ($nodesList) {
            foreach ($nodesList as $node) {
                if (!$node instanceof \DOMNode) {
                    continue;
                }

                $elements[] = new SimpleHtmlDom($node, $queryHtmlDomParser);
            }
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
     * @return SimpleHtmlDomInterface[]|SimpleHtmlDomNodeInterface<SimpleHtmlDomInterface>
     */
    public function findMulti(string $selector): SimpleHtmlDomNodeInterface
    {
        /** @var SimpleHtmlDomNodeInterface<SimpleHtmlDomInterface> $return */
        $return = $this->find($selector, null);

        return $return;
    }

    /**
     * Find nodes with a CSS selector or false, if no element is found.
     *
     * @param string $selector
     *
     * @return false|SimpleHtmlDomInterface[]|SimpleHtmlDomNodeInterface<SimpleHtmlDomInterface>
     */
    public function findMultiOrFalse(string $selector)
    {
        /** @var SimpleHtmlDomNodeInterface<SimpleHtmlDomInterface> $return */
        $return = $this->find($selector, null);

        if ($return instanceof SimpleHtmlDomNodeBlank) {
            return false;
        }

        return $return;
    }

    /**
     * Find nodes with a CSS selector or null, if no element is found.
     *
     * @param string $selector
     *
     * @return null|SimpleHtmlDomInterface[]|SimpleHtmlDomNodeInterface<SimpleHtmlDomInterface>
     */
    public function findMultiOrNull(string $selector)
    {
        /** @var SimpleHtmlDomNodeInterface<SimpleHtmlDomInterface> $return */
        $return = $this->find($selector, null);

        if ($return instanceof SimpleHtmlDomNodeBlank) {
            return null;
        }

        return $return;
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
        /** @var SimpleHtmlDomInterface $return */
        $return = $this->find($selector, 0);

        return $return;
    }

    /**
     * Find one node with a CSS selector or false, if no element is found.
     *
     * @param string $selector
     *
     * @return false|SimpleHtmlDomInterface
     */
    public function findOneOrFalse(string $selector)
    {
        /** @var SimpleHtmlDomInterface $return */
        $return = $this->find($selector, 0);

        if ($return instanceof SimpleHtmlDomBlank) {
            return false;
        }

        return $return;
    }

    /**
     * Find one node with a CSS selector or null, if no element is found.
     *
     * @param string $selector
     *
     * @return null|SimpleHtmlDomInterface
     */
    public function findOneOrNull(string $selector)
    {
        /** @var SimpleHtmlDomInterface $return */
        $return = $this->find($selector, 0);

        if ($return instanceof SimpleHtmlDomBlank) {
            return null;
        }

        return $return;
    }

    /**
     * @param string $content
     * @param bool   $multiDecodeNewHtmlEntity
     * @param bool   $putBrokenReplacedBack
     *
     * @return string
     */
    public function fixHtmlOutput(
        string $content,
        bool $multiDecodeNewHtmlEntity = false,
        bool $putBrokenReplacedBack = true
    ): string {
        // INFO: DOMDocument will encapsulate plaintext into a e.g. paragraph tag (<p>),
        //          so we try to remove it here again ...

        if ($this->getIsDOMDocumentCreatedWithoutHtmlWrapper()) {
            /** @noinspection HtmlRequiredLangAttribute */
            $content = \str_replace(
                [
                    '<html>',
                    '</html>',
                ],
                '',
                $content
            );
        }

        if ($this->getIsDOMDocumentCreatedWithoutHeadWrapper()) {
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

        if ($this->getIsDOMDocumentCreatedWithoutBodyWrapper()) {
            $content = \str_replace(
                [
                    '<body>',
                    '</body>',
                ],
                '',
                $content
            );
        }

        if ($this->getIsDOMDocumentCreatedWithFakeEndScript()) {
            $content = \str_replace(
                '</script>',
                '',
                $content
            );
        }

        if ($this->getIsDOMDocumentCreatedWithoutWrapper()) {
            $content = (string) \preg_replace('/^<p>/', '', $content);
            $content = (string) \preg_replace('/<\/p>/', '', $content);
        }

        if ($this->getIsDOMDocumentCreatedWithoutHtml()) {
            $content = \str_replace(
                '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">',
                '',
                $content
            );
        }

        // https://bugs.php.net/bug.php?id=73175
        $content = \str_replace(
            \array_map(static function ($e) {
                return '</' . $e . '>';
            }, $this->selfClosingTags),
            '',
            $content
        );

        /** @noinspection HtmlRequiredTitleElement */
        $content = \trim(
            \str_replace(
                [
                    '<simpleHtmlDomHtml>',
                    '</simpleHtmlDomHtml>',
                    '<simpleHtmlDomP>',
                    '</simpleHtmlDomP>',
                    '<head><head>',
                    '</head></head>',
                ],
                [
                    '',
                    '',
                    '',
                    '',
                    '<head>',
                    '</head>',
                ],
                $content
            )
        );

        $content = $this->decodeHtmlEntity($content, $multiDecodeNewHtmlEntity);

        return self::putReplacedBackToPreserveHtmlEntities($content, $putBrokenReplacedBack);
    }

    /**
     * Return elements by ".class".
     *
     * @param string $class
     *
     * @return SimpleHtmlDomInterface[]|SimpleHtmlDomNodeInterface<SimpleHtmlDomInterface>
     */
    public function getElementByClass(string $class): SimpleHtmlDomNodeInterface
    {
        return $this->findMulti('.' . $class);
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
        return $this->findOne('#' . $id);
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

        return new SimpleHtmlDom($node, $this);
    }

    /**
     * Returns elements by "#id".
     *
     * @param string   $id
     * @param int|null $idx
     *
     * @return SimpleHtmlDomInterface|SimpleHtmlDomInterface[]|SimpleHtmlDomNodeInterface<SimpleHtmlDomInterface>
     */
    public function getElementsById(string $id, $idx = null)
    {
        return $this->find('#' . $id, $idx);
    }

    /**
     * Returns elements by tag name.
     *
     * @param string   $name
     * @param int|null $idx
     *
     * @return SimpleHtmlDomInterface|SimpleHtmlDomInterface[]|SimpleHtmlDomNodeInterface<SimpleHtmlDomInterface>
     */
    public function getElementsByTagName(string $name, $idx = null)
    {
        $nodesList = $this->document->getElementsByTagName($name);

        $elements = new SimpleHtmlDomNode();

        foreach ($nodesList as $node) {
            $elements[] = new SimpleHtmlDom($node, $this);
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
     * @param bool $putBrokenReplacedBack
     *
     * @return string
     */
    public function html(bool $multiDecodeNewHtmlEntity = false, bool $putBrokenReplacedBack = true): string
    {
        if (static::$callback !== null) {
            \call_user_func(static::$callback, [$this]);
        }

        if ($this->shouldUseWholeDocumentSerializationForHtmlOnPhpLt8()) {
            $content = $this->document->saveHTML();
        } elseif ($this->usesInternalWrapperDocument()) {
            $content = $this->serializeInternalWrapperContent();
        } elseif ($this->createdFromNode) {
            if (\PHP_VERSION_ID < 80000) {
                $content = $this->serializeCreatedFromNodeForPhpLt8();
            } else {
                $content = $this->serializeChildNodes($this->document);
            }
        } elseif ($this->getIsDOMDocumentCreatedWithoutHtmlWrapper()) {
            if ($this->isDOMDocumentCreatedWithHtml5Parser) {
                $content = $this->serializeHtml5DocumentWithoutHtmlWrapper();
            } else {
                $content = $this->document->saveHTML($this->document->documentElement);
            }
        } else {
            $content = $this->document->saveHTML();
        }

        if ($content === false) {
            return '';
        }

        $output = $this->fixHtmlOutput($content, $multiDecodeNewHtmlEntity, $putBrokenReplacedBack);

        return $output;
    }

    /**
     * Mark a parser-generated <p>-wrapper so fixHtmlOutput() can remove only
     * the synthetic wrapper instead of stripping all paragraph tags. The
     * wrapper is renamed to the placeholder tag that fixHtmlOutput() already
     * strips from serialized output.
     *
     * @return void
     */
    private function markSyntheticParagraphWrapper(): void
    {
        if (!$this->isDOMDocumentCreatedWithoutPTagWrapper) {
            return;
        }

        $html = $this->document->documentElement;
        if (
            !$html instanceof \DOMElement
            ||
            \strtolower($html->tagName) !== 'html'
        ) {
            return;
        }

        $body = $this->document->getElementsByTagName('body')->item(0);
        if (!$body instanceof \DOMElement) {
            return;
        }

        $wrapper = null;
        foreach ($body->childNodes as $child) {
            if ($child instanceof \DOMText && \trim($child->nodeValue ?? '') === '') {
                continue;
            }

            if ($wrapper !== null) {
                return;
            }

            if (!$child instanceof \DOMElement) {
                return;
            }

            if (\strtolower($child->tagName) !== 'p') {
                return;
            }

            $wrapper = $child;
        }

        if (!$wrapper instanceof \DOMElement || $wrapper->parentNode === null) {
            return;
        }

        $replacement = $this->document->createElement('simpleHtmlDomP');

        while ($wrapper->firstChild !== null) {
            $replacement->appendChild($wrapper->firstChild);
        }

        $wrapper->parentNode->replaceChild($replacement, $wrapper);
    }

    /**
     * Serialize a single DOM node to HTML.
     *
     * A detached DOMDocument is used so that the serialization context is
     * independent of the internal wrapper tag name (older libxml HTML
     * serializers treat unknown hyphenated tags as block-level and inject
     * formatting newlines into the wrapper's children when saving the full
     * document).
     *
     * On PHP < 8.0, DOMElement instances are serialized through
     * serializeElementNodeForPhpLt8() so older libxml cannot inject formatting
     * newlines when saveHTML($node) is used on detached block-level elements.
     * Text and other non-element nodes still use the fresh-document approach
     * directly because they do not need the extra wrapper stripping.
     *
     * @param \DOMNode $node
     */
    private function serializeNode(\DOMNode $node): string
    {
        if (\PHP_VERSION_ID < 80000 && $node instanceof \DOMElement) {
            return $this->serializeElementNodeForPhpLt8($node);
        }

        $document = new \DOMDocument('1.0', $this->getEncoding());
        $document->preserveWhiteSpace = true;
        $document->formatOutput = false;

        $importedNode = $document->importNode($node, true);
        // @phpstan-ignore instanceof.alwaysTrue (importNode() returns DOMNode here)
        if (!$importedNode instanceof \DOMNode) {
            return '';
        }

        $document->appendChild($importedNode);

        $content = $document->saveHTML($importedNode);

        if ($content === false) {
            return '';
        }

        return $content;
    }

    /**
     * On PHP < 8.0, saveHTML($node) injects formatting newlines for detached
     * block-level elements, so serialize a temporary whole document instead.
     *
     * @param \DOMElement $node
     *
     * @return string
     */
    private function serializeElementNodeForPhpLt8(\DOMElement $node): string
    {
        $document = new \DOMDocument('1.0', $this->getEncoding());
        $document->preserveWhiteSpace = true;
        $document->formatOutput = false;

        $importedNode = $document->importNode($node, true);
        // @phpstan-ignore instanceof.alwaysTrue (importNode() returns DOMNode here)
        if (!$importedNode instanceof \DOMElement) {
            return '';
        }

        $document->appendChild($importedNode);

        $content = $document->saveHTML();
        if ($content === false) {
            return '';
        }

        $content = $this->stripLibxmlDocumentWrappers($content, \strtolower($importedNode->tagName));

        if (\substr($content, -1) === "\n") {
            $content = \substr($content, 0, -1);
        }

        return $content;
    }

    /**
     * Serialize the single element that was imported via the node-backed
     * constructor, for PHP < 8.0.
     *
     * On PHP < 8, saveHTML($node) with a node argument always injects
     * formatting newlines between block-level child elements and a trailing
     * "\n" after raw-text elements (script, style), even with formatOutput
     * set to false.  saveHTML() called without a node argument respects
     * formatOutput=false and does not inject those newlines.
     *
     * We call saveHTML() on the constructor document (which already has the
     * imported element as its only child / documentElement) and strip the
     * DOCTYPE and structural wrappers (html, body) that libxml may add around
     * elements that are not recognised HTML root elements.
     *
     * @return string
     */
    private function serializeCreatedFromNodeForPhpLt8(): string
    {
        $full = $this->document->saveHTML();
        if ($full === false) {
            return '';
        }

        $documentElement = $this->document->documentElement;
        $tagName = $documentElement instanceof \DOMElement
            ? \strtolower($documentElement->tagName)
            : '';

        $full = $this->stripLibxmlDocumentWrappers($full, $tagName, true);

        return $full;
    }

    /**
     * Strip the synthetic wrappers libxml adds when serializing a whole
     * document around a non-root HTML element on PHP < 8.
     */
    private function stripLibxmlDocumentWrappers(string $content, string $tagName, bool $trim = false): string
    {
        $content = (string) \preg_replace('/^<!DOCTYPE[^>]+>\s*/i', '', $content);
        if ($trim) {
            $content = \trim($content);
        }

        if ($tagName !== 'html') {
            $content = (string) \preg_replace('/^<html[^>]*>/i', '', $content);
            $content = (string) \preg_replace('/<\/html>\s*$/i', '', $content);
            if ($trim) {
                $content = \trim($content);
            }

            if ($tagName !== 'body') {
                $content = (string) \preg_replace('/^<body[^>]*>/i', '', $content);
                $content = (string) \preg_replace('/<\/body>\s*$/i', '', $content);
                $content = \str_replace('<body></body>', '', $content);
                if ($trim) {
                    $content = \trim($content);
                }
            }
        }

        return $content;
    }

    /**
     * Serialize an HTML5-parsed document whose input had no <html> wrapper.
     *
     * The HTML5 parser always builds a complete document, so a comment that was written
     * before or after the markup ends up as a sibling of the <html> element instead of a
     * node inside it. Serializing only the document element - what the legacy parser needs -
     * would drop those comments, and serializing the whole document would let the HTML
     * serializer re-encode the output for a <meta> charset that only exists because the
     * fragment was placed in a generated <head>.
     *
     * @return string
     */
    private function serializeHtml5DocumentWithoutHtmlWrapper(): string
    {
        $content = '';

        foreach ($this->document->childNodes as $childNode) {
            if ($childNode === $this->document->documentElement) {
                $content .= (string) $this->document->saveHTML($childNode);

                continue;
            }

            if ($childNode instanceof \DOMComment) {
                $content .= $this->serializeNode($childNode);
            }
        }

        return $content;
    }

    /**
     * @param \DOMNode $parentNode
     *
     * @return string
     */
    private function serializeChildNodes(\DOMNode $parentNode): string
    {
        $content = '';

        foreach ($parentNode->childNodes as $childNode) {
            $content .= $this->serializeNode($childNode);
        }

        return $content;
    }

    /**
     * @return bool
     */
    private function usesInternalWrapperDocument(): bool
    {
        return $this->document->documentElement instanceof \DOMElement
            && $this->document->documentElement->tagName === self::$domHtmlWrapperHelper;
    }

    /**
     * Older libxml preserves body-only fragments more faithfully when the whole
     * temporary document is serialized and fixHtmlOutput() removes the wrappers
     * afterwards. Head-only fragments still need root-element serialization, or
     * <meta charset=...> can trigger output re-encoding (e.g. utf-7).
     */
    private function isBodyOnlyHtmlFragmentDocument(): bool
    {
        $documentElement = $this->document->documentElement;
        if (!$documentElement instanceof \DOMElement || \strtolower($documentElement->tagName) !== 'html') {
            return false;
        }

        $head = $documentElement->getElementsByTagName('head')->item(0);
        $body = $documentElement->getElementsByTagName('body')->item(0);

        $hasHeadContent = $head instanceof \DOMElement && $head->childNodes->length > 0;
        $hasBodyContent = $body instanceof \DOMElement && $body->childNodes->length > 0;

        return !$hasHeadContent && $hasBodyContent;
    }

    private function shouldUseWholeDocumentSerializationForHtmlOnPhpLt8(): bool
    {
        if (\PHP_VERSION_ID >= 80000) {
            return false;
        }

        if ($this->usesInternalWrapperDocument()) {
            return true;
        }

        if (!$this->getIsDOMDocumentCreatedWithoutHtmlWrapper()) {
            return false;
        }

        $documentElement = $this->document->documentElement;
        if (!$documentElement instanceof \DOMElement) {
            return false;
        }

        return \strtolower($documentElement->tagName) !== 'html'
            || $this->isBodyOnlyHtmlFragmentDocument();
    }

    private function shouldUseWholeDocumentSerializationForInnerHtmlOnPhpLt8(): bool
    {
        return \PHP_VERSION_ID < 80000
            && (
                $this->usesInternalWrapperDocument()
                || $this->isBodyOnlyHtmlFragmentDocument()
            );
    }

    /**
     * Keep helper wrapper markers around detached child serialization so
     * fixHtmlOutput() does not trim leading/trailing fragment whitespace.
     *
     * @return string
     */
    private function serializeInternalWrapperContent(): string
    {
        if ($this->document->documentElement === null) {
            return '';
        }

        $wrapperTag = self::$domHtmlWrapperHelper;

        return '<' . $wrapperTag . '>'
            . $this->serializeChildNodes($this->document->documentElement)
            . '</' . $wrapperTag . '>';
    }

    /**
     * Parse the fragment inside the internal wrapper and count significant
     * direct children. This is more reliable than regex for fragments whose
     * top-level elements have attributes or nested markup.
     *
     * @param string $html
     * @param int    $optionsXml
     *
     * @return bool
     */
    private function hasMultipleTopLevelNodes(string $html, int $optionsXml): bool
    {
        $internalErrors = \libxml_use_internal_errors(true);
        try {
            \libxml_clear_errors();

            $xmlProbe = '<' . self::$domHtmlWrapperHelper . '>'
                . self::replaceToPreserveHtmlEntities($html)
                . '</' . self::$domHtmlWrapperHelper . '>';

            $simpleXml = \simplexml_load_string($xmlProbe, \SimpleXMLElement::class, $optionsXml);
            if ($simpleXml === false || \count(\libxml_get_errors()) > 0) {
                return false;
            }

            $wrapper = \dom_import_simplexml($simpleXml);
            if (!$wrapper instanceof \DOMElement) {
                return false;
            }

            return $this->countSignificantChildNodes($wrapper) > 1;
        } finally {
            \libxml_clear_errors();
            \libxml_use_internal_errors($internalErrors);
        }
    }

    /**
     * @param \DOMNode $node
     *
     * @return int
     */
    private function countSignificantChildNodes(\DOMNode $node): int
    {
        $count = 0;

        foreach ($node->childNodes as $childNode) {
            if (
                $childNode->nodeType === \XML_TEXT_NODE
                &&
                \trim($childNode->textContent) === ''
            ) {
                continue;
            }

            ++$count;
            if ($count > 1) {
                return $count;
            }
        }

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function innerHtml(bool $multiDecodeNewHtmlEntity = false, bool $putBrokenReplacedBack = true): string
    {
        $text = '';

        if ($this->document->documentElement) {
            if ($this->shouldUseWholeDocumentSerializationForInnerHtmlOnPhpLt8()) {
                $text = $this->document->saveHTML();
            } elseif ($this->usesInternalWrapperDocument()) {
                $text = $this->serializeInternalWrapperContent();
            } else {
                $text = $this->serializeChildNodes($this->document->documentElement);
            }
        }

        if ($text === false) {
            $text = '';
        }

        $output = $this->fixHtmlOutput($text, $multiDecodeNewHtmlEntity, $putBrokenReplacedBack);

        return $output;
    }

    /**
     * Get dom node's plain text.
     *
     * HTML document plaintext should exclude raw-text container contents like
     * <script> and <style> while still preserving other text nodes in document
     * order (e.g. <title> content).
     *
     * @param bool $multiDecodeNewHtmlEntity
     *
     * @return string
     */
    public function text(bool $multiDecodeNewHtmlEntity = false): string
    {
        $parts = [];

        $xPath = new \DOMXPath($this->document);
        $textNodes = $xPath->query(
            \sprintf(
                '//text()[not(ancestor::script or ancestor::style or ancestor::%s)]',
                self::$domHtmlSpecialScriptHelper
            )
        );

        if ($textNodes !== false) {
            foreach ($textNodes as $textNode) {
                $parts[] = $textNode->nodeValue;
            }
        }

        return $this->fixHtmlOutput(\implode('', $parts), $multiDecodeNewHtmlEntity);
    }

    /**
     * Load HTML from string.
     *
     * @param string   $html
     * @param int|null $libXMLExtraOptions
     * @param bool     $useDefaultLibXMLOptions
     *
     * @return $this
     */
    public function loadHtml(string $html, $libXMLExtraOptions = null, $useDefaultLibXMLOptions = true): DomParserInterface
    {
        $this->document = $this->createDOMDocument($html, $libXMLExtraOptions, $useDefaultLibXMLOptions);

        return $this;
    }

    /**
     * Load HTML from file.
     *
     * @param string   $filePath
     * @param int|null $libXMLExtraOptions
     * @param bool     $useDefaultLibXMLOptions
     *
     * @throws \RuntimeException
     *
     * @return $this
     */
    public function loadHtmlFile(string $filePath, $libXMLExtraOptions = null, $useDefaultLibXMLOptions = true): DomParserInterface
    {
        if (!\preg_match("/^https?:\/\//i", $filePath)) {
            if (!\file_exists($filePath)) {
                throw new \RuntimeException('File ' . $filePath . ' not found');
            }

            if (!\is_file($filePath)) {
                throw new \RuntimeException('Could not load file ' . $filePath);
            }
        }

        try {
            if (\class_exists('\voku\helper\UTF8')) {
                $html = \voku\helper\UTF8::file_get_contents($filePath);
            } else {
                $html = \file_get_contents($filePath);
            }
        } catch (\Exception $e) {
            throw new \RuntimeException('Could not load file ' . $filePath);
        }

        if ($html === false) {
            throw new \RuntimeException('Could not load file ' . $filePath);
        }

        return $this->loadHtml($html, $libXMLExtraOptions, $useDefaultLibXMLOptions);
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
        if ($xml === false) {
            return '';
        }

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
     * @return SimpleHtmlDomInterface|SimpleHtmlDomInterface[]|SimpleHtmlDomNodeInterface<SimpleHtmlDomInterface>
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
    public function getIsDOMDocumentCreatedWithoutPTagWrapper(): bool
    {
        return $this->isDOMDocumentCreatedWithoutPTagWrapper;
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
    public function getIsDOMDocumentCreatedWithoutBodyWrapper(): bool
    {
        return $this->isDOMDocumentCreatedWithoutBodyWrapper;
    }

    /**
     * @return bool
     */
    public function getIsDOMDocumentCreatedWithMultiRoot(): bool
    {
        return $this->isDOMDocumentCreatedWithMultiRoot;
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
     * @return bool
     */
    public function getIsDOMDocumentCreatedWithFakeEndScript(): bool
    {
        return $this->isDOMDocumentCreatedWithFakeEndScript;
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
                '/(?<start>[^<]*)?(?<broken>(?:<\/\w+(?:\s+\w+=\"[^"]+\")*+[^<]+>)+)(?<end>.*)/u',
                function ($matches) {
                    $matches['broken'] = \str_replace(
                        ['°lt/_simple_html_dom__voku_°', '°lt_simple_html_dom__voku_°', '°gt_simple_html_dom__voku_°'],
                        ['</', '<', '>'],
                        $matches['broken']
                    );

                    $matchesHash = self::$domHtmlBrokenHtmlHelper . \crc32($matches['broken']);
                    $this->registerDynamicDomBrokenReplaceHelper($matches['broken'], $matchesHash);

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
     * workaround for bug: https://bugs.php.net/bug.php?id=74628
     *
     * @param string $html
     *
     * @return void
     */
    protected function keepSpecialSvgTags(string &$html)
    {
        // regEx for e.g.: [mask-image:url('data:image/svg+xml;utf8,<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">...</svg>')]
        /** @noinspection HtmlDeprecatedTag */
        $regExSpecialSvg = '/\((["\'])?(?<start>data:image\/svg.*)<svg(?<attr>[^>]*?)>(?<content>.*)<\/svg>\1\)/isU';
        $htmlTmp = \preg_replace_callback(
            $regExSpecialSvg,
            function ($svgs) {
                $content = '<svg' . $svgs['attr'] . '>' . $svgs['content'] . '</svg>';
                $matchesHash = self::$domHtmlBrokenHtmlHelper . \crc32($content);
                $this->registerDynamicDomBrokenReplaceHelper($content, $matchesHash);

                return '(' . $svgs[1] . $svgs['start'] . $matchesHash . $svgs[1] . ')';
            },
            $html
        );

        if ($htmlTmp !== null) {
            $html = $htmlTmp;
        }
    }

    /**
     * @param string $html
     *
     * @return void
     */
    protected function keepSpecialScriptTags(string &$html)
    {
        // regEx for e.g.: [<script id="elements-image-1" type="text/html">...</script>]
        $tags = \implode('|', \array_map(
            static function ($value) {
                return \preg_quote($value, '/');
            },
            $this->specialScriptTags
        ));
        $html = (string) \preg_replace_callback(
            '/(?<start>(<script [^>]*type=["\']?(?:' . $tags . ')+[^>]*>))(?<innerContent>.*)(?<end><\/script>)/isU',
            function ($matches) {
                // Check for logic in special script tags containing EJS/ERB-style template syntax
                // (e.g. <% ... %> blocks), because often this looks like non-valid html in the template itself.
                foreach ($this->templateLogicSyntaxInSpecialScriptTags as $logicSyntaxInSpecialScriptTag) {
                    if (\strpos($matches['innerContent'], $logicSyntaxInSpecialScriptTag) !== false) {
                        // remove the html5 fallback
                        $matches['innerContent'] = \str_replace('<\/', '</', $matches['innerContent']);

                        $matchesHash = self::$domHtmlBrokenHtmlHelper . \crc32($matches['innerContent']);
                        $this->registerDynamicDomBrokenReplaceHelper($matches['innerContent'], $matchesHash);

                        return $matches['start'] . $matchesHash . $matches['end'];
                    }
                }

                // remove the html5 fallback
                $matches[0] = \str_replace('<\/', '</', $matches[0]);

                $specialNonScript = '<' . self::$domHtmlSpecialScriptHelper . \substr($matches[0], \strlen('<script'));

                return \substr($specialNonScript, 0, -\strlen('</script>')) . '</' . self::$domHtmlSpecialScriptHelper . '>';
            },
            $html
        );
    }

    /**
     * @param bool $keepBrokenHtml
     *
     * @return $this
     */
    public function useKeepBrokenHtml(bool $keepBrokenHtml): DomParserInterface
    {
        $this->keepBrokenHtml = $keepBrokenHtml;

        return $this;
    }

    /**
     * @param string[] $templateLogicSyntaxInSpecialScriptTags
     *
     * @return $this
     */
    public function overwriteTemplateLogicSyntaxInSpecialScriptTags(array $templateLogicSyntaxInSpecialScriptTags): DomParserInterface
    {
        foreach ($templateLogicSyntaxInSpecialScriptTags as $tmp) {
            // @phpstan-ignore function.alreadyNarrowedType (runtime guard kept for public API validation)
            if (!\is_string($tmp)) {
                throw new \InvalidArgumentException('setTemplateLogicSyntaxInSpecialScriptTags only allows string[]');
            }
        }

        $this->templateLogicSyntaxInSpecialScriptTags = $templateLogicSyntaxInSpecialScriptTags;

        return $this;
    }

    /**
     * @param string[] $specialScriptTags
     *
     * @return $this
     */
    public function overwriteSpecialScriptTags(array $specialScriptTags): DomParserInterface
    {
        foreach ($specialScriptTags as $tag) {
            // @phpstan-ignore function.alreadyNarrowedType (runtime guard kept for public API validation)
            if (!\is_string($tag)) {
                throw new \InvalidArgumentException('SpecialScriptTags only allows string[]');
            }
        }

        $this->specialScriptTags = $specialScriptTags;

        return $this;
    }

    /**
     * @param callable $callbackXPathBeforeQuery
     *
     * @phpstan-param callable(string $cssSelectorString, string $xPathString,\DOMXPath,\voku\helper\HtmlDomParser): string $callbackXPathBeforeQuery
     *
     * @return $this
     */
    public function setCallbackXPathBeforeQuery(callable $callbackXPathBeforeQuery): self
    {
        $this->callbackXPathBeforeQuery = $callbackXPathBeforeQuery;

        return $this;
    }

    /**
     * @param callable $callbackBeforeCreateDom
     *
     * @phpstan-param callable(string $htmlString, \voku\helper\HtmlDomParser): string $callbackBeforeCreateDom
     *
     * @return $this
     */
    public function setCallbackBeforeCreateDom(callable $callbackBeforeCreateDom): self
    {
        $this->callbackBeforeCreateDom = $callbackBeforeCreateDom;

        return $this;
    }
}
