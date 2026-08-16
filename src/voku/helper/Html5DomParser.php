<?php

declare(strict_types=1);

namespace voku\helper;

/**
 * HTML5 variant of "HtmlDomParser", built on the HTML5 parser that PHP >= 8.4 ships as
 * "\Dom\HTMLDocument".
 *
 * It is a separate class for the same reason PHP put "\Dom\HTMLDocument" next to
 * "\DOMDocument" instead of adding a mode to it: the parsing rules are different, and that
 * difference is visible in the result. Everything else is deliberately identical - the same
 * methods, the same static entry points, the same "\DOMDocument" behind "getDocument()" - so
 * switching means changing the class name and nothing else.
 *
 * ```php
 * $dom = Html5DomParser::str_get_html('<table><tr><td>x</table>');
 * $dom->html(); // '<table><tbody><tr><td>x</td></tr></tbody></table>'
 * ```
 *
 * What the HTML5 parser does differently, all of it on purpose:
 *
 * - it builds the tree a browser builds: implied "tbody", auto-closed "p" / "li" / "td",
 *   recovery from misnested formatting tags, elements moved out of "head" when they may not
 *   be there, tag names in lower case;
 * - it resolves HTML entities to their characters, so "&nbsp;" and "&amp;" come back as
 *   characters instead of staying entities;
 * - it detects the encoding from a byte-order mark or a "meta" charset, like a browser;
 * - it always builds a complete document, so "getDocument()->documentElement" is "<html>"
 *   even for a fragment - "html()" and "innerHtml()" still return the fragment;
 * - boolean attributes are serialized as "checked=\"\"" instead of "checked", an artifact of
 *   the XML bridge below.
 *
 * The parser hands out a legacy "\DOMDocument", because that is what this library is built
 * on, so the HTML5 tree has to be carried over. There is no zero-copy handoff: PHP refuses
 * to pass a node of the new DOM implementation to the old one. The tree is therefore
 * serialized as XML and re-parsed, which is cheap because it is already normalized at that
 * point.
 *
 * Choosing this class is a strict parser choice. If the PHP 8.4 HTML5 backend is not
 * available, or the normalized tree cannot be represented by the legacy "\DOMDocument"
 * bridge this library exposes, parsing throws instead of silently switching to libxml. Call
 * "isHtml5ParserSupported()" before selecting this class when an application supports older
 * runtimes, and choose "HtmlDomParser" explicitly when legacy semantics are the desired
 * fallback.
 *
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
 * @method Html5DomParser load(string $html)
 *                                 <p>Load HTML from string.</p>
 * @method Html5DomParser load_file(string $html)
 *                                 <p>Load HTML from file.</p>
 * @method static Html5DomParser file_get_html($filePath, $libXMLExtraOptions = null)
 *                                 <p>Load HTML from file.</p>
 * @method static Html5DomParser str_get_html($html, $libXMLExtraOptions = null)
 *                                 <p>Load HTML from string.</p>
 */
class Html5DomParser extends HtmlDomParser
{
    /**
     * Placeholder attribute name used while bridging the parsed document, see
     * "parkXmlnsAttributes()".
     *
     * @var string
     */
    private static $domHtmlXmlnsHelper = 'data-simplevokuxmlns';

    /**
     * @var bool
     */
    protected $isDOMDocumentCreatedWithHtml5Parser = false;

    /**
     * Check if the HTML5 parser of PHP >= 8.4 can be used on this runtime.
     *
     * When it cannot, this class still works: it parses with the libxml parser of
     * "HtmlDomParser" and reports the fallback.
     *
     * @return bool
     */
    /**
     * Protect only input that the shared output cleanup would otherwise change or that the
     * XML transport cannot represent directly.
     *
     * Do not run the full libxml protection pass here: protecting ampersands would suppress
     * native HTML5 entity parsing, and moving the legacy pass before backend selection breaks
     * the existing special-script preprocessing order.
     *
     * @param string $html
     *
     * @return string
     */
    private static function protectHtml5BridgeSensitiveInput(string $html): string
    {
        $search = [];
        $replace = [];

        foreach (self::$domReplaceHelper['orig'] as $index => $original) {
            if ($original !== '%' && $original !== '<html ⚡') {
                continue;
            }

            $search[] = $original;
            $replace[] = self::$domReplaceHelper['tmp'][$index];
        }

        return \str_replace($search, $replace, $html);
    }

    public static function isHtml5ParserSupported(): bool
    {
        return \PHP_VERSION_ID >= 80400
               &&
               \class_exists('Dom\HTMLDocument')
               &&
               \defined('Dom\HTML_NO_DEFAULT_NS');
    }

    /**
     * Check if the current document was built by the HTML5 parser.
     *
     * @return bool
     */
    public function getIsDOMDocumentCreatedWithHtml5Parser(): bool
    {
        return $this->isDOMDocumentCreatedWithHtml5Parser;
    }

    /**
     * Parse the prepared HTML with the HTML5 parser.
     *
     * "keepBrokenHtml" works on top of this: that repair replaced the broken fragments with
     * text placeholders before this point and puts them back after serialization, and the
     * HTML5 parser carries text through. Where text may not be - inside a table, inside the
     * head - HTML5 tree construction moves such a placeholder where a browser would move it,
     * so a preserved fragment can come back in a different position than "HtmlDomParser"
     * returns it. The fragment itself is never lost.
     *
     * @param string $html
     *
     * @return \DOMDocument|null <p>NULL to let "HtmlDomParser" parse with libxml instead.</p>
     */
    protected function createDOMDocumentFromPreparedHtml(string $html)
    {
        $this->isDOMDocumentCreatedWithHtml5Parser = false;

        if (!static::isHtml5ParserSupported()) {
            throw new \RuntimeException(
                'Html5DomParser requires PHP >= 8.4 with "\\Dom\\HTMLDocument" and "\\Dom\\HTML_NO_DEFAULT_NS".'
            );
        }

        $html = self::protectHtml5BridgeSensitiveInput($html);

        $document = $this->createDOMDocumentViaHtml5Parser($html);
        $this->isDOMDocumentCreatedWithHtml5Parser = true;

        return $document;
    }

    /**
     * Serialize a document whose input had no <html> wrapper.
     *
     * The HTML5 parser always builds a complete document, so a comment that was written
     * before or after the markup ends up as a sibling of the <html> element instead of a
     * node inside it. Serializing only the document element - what the libxml parser needs -
     * would drop those comments, and serializing the whole document would let the HTML
     * serializer re-encode the output for a <meta> charset that only exists because the
     * fragment was placed in a generated <head>.
     *
     * @return string
     */
    protected function serializeDocumentWithoutHtmlWrapper(): string
    {
        if (!$this->isDOMDocumentCreatedWithHtml5Parser) {
            return parent::serializeDocumentWithoutHtmlWrapper();
        }

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
     * Parse with "\Dom\HTMLDocument" and bridge the result into a legacy "\DOMDocument".
     *
     * The bridge is a serialize + parse round-trip on purpose: "\Dom\HTMLDocument" and
     * "\DOMDocument" are separate implementations on top of the same libxml document, and
     * PHP refuses to hand a node of the new implementation to the old one (and the other
     * way around), so there is no zero-copy handoff to use instead. XML is used as the
     * transport because the tree is already HTML5-normalized at that point, so the XML
     * parser only has to rebuild it, while re-parsing it as HTML would hand the tree back
     * to the very parser this method exists to use.
     *
     * "\Dom\HTML_NO_DEFAULT_NS" keeps the elements out of the XHTML namespace, so the
     * resulting document matches what the libxml parser produces and the generated XPath
     * queries of this library keep working without namespace handling.
     *
     * The caller checks "isHtml5ParserSupported()", so this method is only reached on a
     * runtime that has the parser.
     *
     * @param string $html
     *
     * @throws \RuntimeException <p>If the normalized HTML5 tree cannot be represented by
     *                           the legacy DOMDocument bridge.</p>
     *
     * @return \DOMDocument
     */
    private function createDOMDocumentViaHtml5Parser(string $html)
    {
        // INFO: the HTML5 parser detects the encoding the way the specification does - from a
        //          byte-order mark or a <meta> charset - which is the browser behavior this
        //          parser is used for. Only a parser that was configured for a specific
        //          encoding overrules that detection.
        $encoding = $this->getEncoding();
        $overrideEncoding = \strcasecmp($encoding, 'UTF-8') === 0 ? null : $encoding;

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
        $xmlnsHelper = \stripos($html, 'xmlns') !== false
            ? $this->parkXmlnsAttributes($html5Document)
            : null;

        $xml = $html5Document->saveXml();

        if ($xml === false || $xml === '') {
            throw new \RuntimeException('Html5DomParser could not serialize the normalized HTML5 document for the DOMDocument bridge.');
        }

        $document = new \DOMDocument('1.0', $this->getEncoding());
        $document->preserveWhiteSpace = true;
        $document->formatOutput = false;

        $internalErrors = \libxml_use_internal_errors(true);
        \libxml_clear_errors();

        $loaded = $document->loadXML($xml, \LIBXML_NONET);
        $lastError = \libxml_get_last_error();

        \libxml_clear_errors();
        \libxml_use_internal_errors($internalErrors);

        if ($loaded === false) {
            $detail = $lastError instanceof \LibXMLError ? ' ' . \trim($lastError->message) : '';

            throw new \RuntimeException('Html5DomParser could not bridge the normalized HTML5 document into DOMDocument.' . $detail);
        }

        if ($xmlnsHelper !== null) {
            $this->restoreXmlnsAttributes($document, $xmlnsHelper);
        }

        $document->encoding = $this->getEncoding();

        return $document;
    }

    /**
     * Rename every "xmlns" attribute of an HTML5-parsed document to a placeholder name.
     *
     * The placeholder must not collide with an attribute the caller wrote, so a document
     * that already uses the name gets a numbered variant.
     *
     * @param object $html5Document <p>A "\Dom\HTMLDocument" of PHP >= 8.4.</p>
     *
     * @return string|null <p>The placeholder name that was used, or NULL when the document
     *                     has no "xmlns" attribute.</p>
     */
    private function parkXmlnsAttributes($html5Document)
    {
        /** @phpstan-ignore class.notFound, argument.type (PHP >= 8.4 only, guarded by isHtml5ParserSupported()) */
        $xPath = new \Dom\XPath($html5Document);
        $elements = $xPath->query('//*[@xmlns]');

        if ($elements->length === 0) {
            return null;
        }

        $helper = self::$domHtmlXmlnsHelper;
        $suffix = 0;
        while ($xPath->query('//*[@' . $helper . ']')->length > 0) {
            $helper = self::$domHtmlXmlnsHelper . '-' . ++$suffix;
        }

        foreach ($elements as $element) {
            /** @phpstan-ignore method.notFound, method.notFound (\Dom\Element of PHP >= 8.4) */
            $element->setAttribute($helper, $element->getAttribute('xmlns'));
            /** @phpstan-ignore method.notFound (\Dom\Element of PHP >= 8.4) */
            $element->removeAttribute('xmlns');
        }

        return $helper;
    }

    /**
     * Restore the "xmlns" attributes that parkXmlnsAttributes() renamed.
     *
     * @param \DOMDocument $document
     * @param string       $helper
     *
     * @return void
     */
    private function restoreXmlnsAttributes(\DOMDocument $document, string $helper)
    {
        // The helper is generated internally from a safe attribute name, and //*[] only selects elements.
        /** @var \DOMNodeList<\DOMElement> $elements */
        $elements = (new \DOMXPath($document))->query('//*[@' . $helper . ']');

        foreach ($elements as $element) {
            $element->setAttribute('xmlns', $element->getAttribute($helper));
            $element->removeAttribute($helper);
        }
    }
}
