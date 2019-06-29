<?php

declare(strict_types=1);

namespace voku\helper;

/**
 * @method static XmlParser file_get_xml($xml, $libXMLExtraOptions = null)
 *                                 <p>Load XML from file.</p>
 * @method static XmlParser str_get_xml($xml, $libXMLExtraOptions = null)
 *                                 <p>Load XML from string.</p>
 */
class XmlParser extends HtmlDomParser
{
    /**
     * @param string $name
     * @param array  $arguments
     *
     * @throws \BadMethodCallException
     * @throws \RuntimeException
     *
     * @return XmlParser
     */
    public static function __callStatic($name, $arguments)
    {
        $arguments0 = $arguments[0] ?? '';

        $arguments1 = $arguments[1] ?? null;

        if ($name === 'str_get_xml') {
            $parser = new static();

            return $parser->loadXml($arguments0, $arguments1);
        }

        if ($name === 'file_get_xml') {
            $parser = new static();

            return $parser->loadXmlFile($arguments0, $arguments1);
        }

        throw new \BadMethodCallException('Method does not exist');
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->xml(false, false, true, 0);
    }

    /**
     * Create DOMDocument from XML.
     *
     * @param string   $xml
     * @param int|null $libXMLExtraOptions
     *
     * @return \DOMDocument
     */
    protected function createDOMDocument(string $xml, $libXMLExtraOptions = null): \DOMDocument
    {
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

        if ($libXMLExtraOptions !== null) {
            $optionsXml |= $libXMLExtraOptions;
        }

        $xml = self::replaceToPreserveHtmlEntities($xml);

        $documentFound = false;
        $sxe = \simplexml_load_string($xml, \SimpleXMLElement::class, $optionsXml);
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
            if (\stripos('<?xml', $xml) !== 0) {
                $xmlHackUsed = true;
                $xml = '<?xml encoding="' . $this->getEncoding() . '" ?>' . $xml;
            }

            $this->document->loadXML($xml, $optionsXml);

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
     * Load XML from string.
     *
     * @param string   $xml
     * @param int|null $libXMLExtraOptions
     *
     * @return XmlParser
     */
    public function loadXml(string $xml, $libXMLExtraOptions = null): self
    {
        $this->document = $this->createDOMDocument($xml, $libXMLExtraOptions);

        return $this;
    }

    /**
     * Load XML from file.
     *
     * @param string   $filePath
     * @param int|null $libXMLExtraOptions
     *
     * @throws \RuntimeException
     *
     * @return XmlParser
     */
    public function loadXmlFile(string $filePath, $libXMLExtraOptions = null): self
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
                $xml = UTF8::file_get_contents($filePath);
            } else {
                $xml = \file_get_contents($filePath);
            }
        } catch (\Exception $e) {
            throw new \RuntimeException("Could not load file ${filePath}");
        }

        if ($xml === false) {
            throw new \RuntimeException("Could not load file ${filePath}");
        }

        return $this->loadXml($xml, $libXMLExtraOptions);
    }

    /**
     * @param callable      $callback
     * @param \DOMNode|null $domNode
     */
    public function replaceTextWithCallback($callback, \DOMNode $domNode = null)
    {
        if ($domNode === null) {
            $domNode = $this->document;
        }

        if ($domNode->hasChildNodes()) {
            $children = [];

            // since looping through a DOM being modified is a bad idea we prepare an array:
            foreach ($domNode->childNodes as $child) {
                $children[] = $child;
            }

            foreach ($children as $child) {
                if ($child->nodeType === \XML_TEXT_NODE) {
                    $oldText = self::putReplacedBackToPreserveHtmlEntities($child->wholeText);
                    $newText = $callback($oldText);
                    if ($domNode->ownerDocument) {
                        $newTextNode = $domNode->ownerDocument->createTextNode(self::replaceToPreserveHtmlEntities($newText));
                        $domNode->replaceChild($newTextNode, $child);
                    }
                } else {
                    $this->replaceTextWithCallback($callback, $child);
                }
            }
        }
    }
}
