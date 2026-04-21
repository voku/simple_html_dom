<?php

use PHPUnit\Framework\TestCase;
use voku\helper\AbstractDomParser;
use voku\helper\AbstractSimpleHtmlDom;
use voku\helper\AbstractSimpleXmlDom;
use voku\helper\HtmlDomHelper;
use voku\helper\HtmlDomParser;
use voku\helper\SelectorConverter;
use voku\helper\SimpleHtmlDom;
use voku\helper\SimpleHtmlDomBlank;
use voku\helper\SimpleHtmlDomNodeBlank;
use voku\helper\SimpleXmlDom;
use voku\helper\SimpleXmlDomBlank;
use voku\helper\SimpleXmlDomNodeBlank;

/**
 * @internal
 */
final class CoverageBoostTest extends TestCase
{
    /**
     * @return \ReflectionMethod
     */
    private function reflectMethod(string $className, string $methodName)
    {
        $method = new \ReflectionMethod($className, $methodName);
        if (\PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }

        return $method;
    }

    /**
     * @return \ReflectionProperty
     */
    private function reflectProperty(string $className, string $propertyName)
    {
        $property = new \ReflectionProperty($className, $propertyName);
        if (\PHP_VERSION_ID < 80100) {
            $property->setAccessible(true);
        }

        return $property;
    }

    public function testSimpleHtmlDomWrapperCoveragePaths()
    {
        $document = HtmlDomParser::str_get_html(
            '<html><body><div id="target" class="alpha"><span>first</span>  <span id="two">second</span> <em>third</em></div></body></html>'
        );
        $root = new SimpleHtmlDom($document->getDocument()->documentElement, $document);
        $target = $root->getElementById('target');

        static::assertSame('<span>first</span>  <span id="two">second</span> <em>third</em>', $target->innerXml());
        static::assertInstanceOf(SimpleHtmlDomBlank::class, $target->getElementByTagName('missing'));
        static::assertNull($target->findMultiOrNull('missing'));

        $firstSpan = $target->getElementByTagName('span');
        $em = $target->getElementByTagName('em');
        static::assertNull($firstSpan->previousNonWhitespaceSibling());
        static::assertNull($em->nextNonWhitespaceSibling());

        $looseTextNode = new SimpleHtmlDom($document->getDocument()->createTextNode('loose'), $document);
        static::assertSame('', $looseTextNode->getAttribute('id'));
        static::assertFalse($looseTextNode->hasAttribute('id'));
        static::assertInstanceOf(SimpleHtmlDomBlank::class, $looseTextNode->getElementByTagName('span'));
        static::assertInstanceOf(SimpleHtmlDomBlank::class, $looseTextNode->getElementsByTagName('span', 0));

        $detached = new SimpleHtmlDom(new \DOMElement('free'));
        static::assertInstanceOf(SimpleHtmlDomNodeBlank::class, $detached->find('div'));
        static::assertInstanceOf(SimpleHtmlDomBlank::class, $detached->find('div', 0));
        static::assertNull($detached->findMultiOrNull('div'));

        $emptyInnerDocument = HtmlDomParser::str_get_html('<html><body><div id="target"><span>x</span></div></body></html>');
        $emptyInnerDocument->getElementById('target')->innertext = '';
        static::assertSame('<html><body><div id="target"></div></body></html>', $emptyInnerDocument->html());

        $metaReplacementDocument = HtmlDomParser::str_get_html('<html><body><div id="target"></div></body></html>');
        $metaReplacementDocument->getElementById('target')->innertext = '<meta charset="utf-8">';
        static::assertSame(
            '<html><body><div id="target"><meta charset="utf-8"></div></body></html>',
            $metaReplacementDocument->html()
        );

        $multiRootReplacementDocument = HtmlDomParser::str_get_html('<html><body><div id="target"><span>x</span></div></body></html>');
        $multiRootReplacementDocument->getElementById('target')->outertext = '<title>T</title><section>new</section>';
        static::assertSame(
            '<html><body><title>T</title><section>new</section></body></html>',
            $multiRootReplacementDocument->html()
        );

        $createFindResult = $this->reflectMethod(SimpleHtmlDom::class, 'createFindResultFromNodeList');
        $spanNodeList = $document->getDocument()->getElementsByTagName('span');

        $allMatches = $createFindResult->invoke($target, $spanNodeList, null);
        $lastMatch = $createFindResult->invoke($target, $spanNodeList, -1);
        $outOfRange = $createFindResult->invoke($target, $spanNodeList, 99);
        $blankMatches = $createFindResult->invoke($target, false, null);

        static::assertCount(2, $allMatches);
        static::assertSame('two', $lastMatch->getAttribute('id'));
        static::assertInstanceOf(SimpleHtmlDomBlank::class, $outOfRange);
        static::assertInstanceOf(SimpleHtmlDomNodeBlank::class, $blankMatches);
    }

    public function testSimpleXmlDomWrapperCoveragePaths()
    {
        $xml = \voku\helper\XmlDomParser::str_get_xml(
            '<root><group id="grp" class="wrapper">'
            . "\n  "
            . '<item id="first" class="alpha"><title>First</title><meta>One</meta></item>'
            . "\n  "
            . '<item id="second" class="beta"><title>Second</title></item>'
            . "\n"
            . '</group><tail id="tail"/></root>'
        );
        $group = $xml->findOne('group');
        $firstItem = $group->findOne('item');
        $secondItem = $group->getElementById('second');

        static::assertCount(2, $group->findMulti('item'));
        static::assertFalse($group->findMultiOrFalse('missing'));
        static::assertFalse($group->findOneOrFalse('missing'));
        static::assertNull($group->findOneOrNull('missing'));
        static::assertCount(1, $group->getElementByClass('alpha'));
        static::assertSame('second', $group->getElementById('second')->getAttribute('id'));
        static::assertSame('second', $group->getElementsById('second', 0)->getAttribute('id'));
        static::assertSame('title', $firstItem->firstChild()->tag);
        static::assertSame('meta', $firstItem->lastChild()->tag);
        static::assertSame('meta', $firstItem->firstChild()->nextSibling()->tag);
        static::assertSame('title', $firstItem->lastChild()->previousSibling()->tag);
        static::assertSame('item', $firstItem->nextNonWhitespaceSibling()->tag);
        static::assertNull($firstItem->previousNonWhitespaceSibling());
        static::assertNull($secondItem->previousNonWhitespaceSibling()->previousNonWhitespaceSibling());

        $looseTextNode = new SimpleXmlDom($xml->getDocument()->createTextNode('loose'));
        static::assertSame('', $looseTextNode->getAttribute('id'));
        static::assertFalse($looseTextNode->hasAttribute('id'));
        static::assertInstanceOf(SimpleXmlDomBlank::class, $looseTextNode->getElementByTagName('item'));
        static::assertInstanceOf(SimpleXmlDomNodeBlank::class, $looseTextNode->getElementsByTagName('item'));

        $corrupted = new SimpleXmlDom(new \DOMElement('free'));
        $nodeProperty = $this->reflectProperty(AbstractSimpleXmlDom::class, 'node');
        $nodeProperty->setValue($corrupted, new \stdClass());
        static::assertTrue($corrupted->isRemoved());

        $replaceTextXml = \voku\helper\XmlDomParser::str_get_xml('<root><item><title>Old</title></item></root>');
        $replaceTextXml->findOne('title')->plaintext = 'Changed';
        static::assertSame('<root><item>Changed</item></root>', \trim($replaceTextXml->xml()));

        $replaceChildXml = \voku\helper\XmlDomParser::str_get_xml('<root><item><title>Old</title></item></root>');
        $replaceChildXml->findOne('item')->innertext = '<replacement>New</replacement>';
        static::assertSame('<root><item><replacement>New</replacement></item></root>', \trim($replaceChildXml->xml()));

        $replaceNodeXml = \voku\helper\XmlDomParser::str_get_xml('<root><item><title>Old</title></item></root>');
        $replaceNodeXml->findOne('title')->outertext = '<headline>New</headline>';
        static::assertSame('<root><item><headline>New</headline></item></root>', \trim($replaceNodeXml->xml()));

        $removeNodeXml = \voku\helper\XmlDomParser::str_get_xml('<root><item><title>Old</title></item></root>');
        $removeNodeXml->findOne('title')->outertext = '';
        static::assertSame('<root><item></item></root>', \trim($removeNodeXml->xml()));
    }

    public function testSelectorConverterInternalHelpersCoverage()
    {
        $createElementAxisPrefix = $this->reflectMethod(SelectorConverter::class, 'createElementAxisPrefix');
        $createNodeTestXPath = $this->reflectMethod(SelectorConverter::class, 'createNodeTestXPath');
        $replaceLeadingAxis = $this->reflectMethod(SelectorConverter::class, 'replaceLeadingAxis');
        $parseTrailingNodeTestSelector = $this->reflectMethod(SelectorConverter::class, 'parseTrailingNodeTestSelector');
        $createCompiledCacheKey = $this->reflectMethod(SelectorConverter::class, 'createCompiledCacheKey');
        $splitSelectorGroups = $this->reflectMethod(SelectorConverter::class, 'splitSelectorGroups');

        static::assertSame('./', $createElementAxisPrefix->invoke(null, '>'));
        static::assertSame('./following-sibling::*[1]/self::', $createElementAxisPrefix->invoke(null, '+'));
        static::assertSame('./following-sibling::', $createElementAxisPrefix->invoke(null, '~'));

        try {
            $createElementAxisPrefix->invoke(null, '!');
            static::fail('Expected RuntimeException for invalid element combinator.');
        } catch (\RuntimeException $e) {
            static::assertStringContainsString('Unexpected combinator', $e->getMessage());
        }

        static::assertSame('./text()', $createNodeTestXPath->invoke(null, '>', 'text()'));
        static::assertSame('./following-sibling::node()[1]/self::comment()', $createNodeTestXPath->invoke(null, '+', 'comment()'));
        static::assertSame('./following-sibling::text()', $createNodeTestXPath->invoke(null, '~', 'text()'));

        try {
            $createNodeTestXPath->invoke(null, '!', 'text()');
            static::fail('Expected RuntimeException for invalid node-test combinator.');
        } catch (\RuntimeException $e) {
            static::assertStringContainsString('Unexpected combinator', $e->getMessage());
        }

        static::assertSame('./div', $replaceLeadingAxis->invoke(null, 'descendant-or-self::div', './'));
        static::assertSame('./div', $replaceLeadingAxis->invoke(null, '//div', './'));
        static::assertSame('./div', $replaceLeadingAxis->invoke(null, 'div', './'));

        static::assertSame(
            ['prefixSelector' => 'div', 'combinator' => '', 'nodeTest' => 'text()'],
            $parseTrailingNodeTestSelector->invoke(null, 'div text')
        );
        static::assertSame(
            ['prefixSelector' => 'div', 'combinator' => '>', 'nodeTest' => 'comment()'],
            $parseTrailingNodeTestSelector->invoke(null, 'div > comment')
        );
        static::assertNull($parseTrailingNodeTestSelector->invoke(null, 'text'));
        static::assertNull($parseTrailingNodeTestSelector->invoke(null, 'divtext'));
        static::assertSame(
            ['div[data-label="a,b"]', ' span[data-value="c\\,d"]', ' a:is(.x,.y)'],
            $splitSelectorGroups->invoke(null, 'div[data-label="a,b"], span[data-value="c\\,d"], a:is(.x,.y)')
        );

        static::assertSame(\json_encode(['div', false, true]), $createCompiledCacheKey->invoke(null, 'div', false, true));

        try {
            $createCompiledCacheKey->invoke(null, "\xB1\x31", false, true);
            static::fail('Expected RuntimeException for invalid UTF-8 cache key input.');
        } catch (\RuntimeException $e) {
            static::assertStringContainsString('Unable to encode', $e->getMessage());
        }

        static::assertSame('div[', SelectorConverter::toXPath('div[', true));
        static::assertStringContainsString('comment()', SelectorConverter::toXPath('> div comment'));
    }

    public function testHtmlAndXmlReplacementEdgeCoverage()
    {
        $htmlInnerDocument = HtmlDomParser::str_get_html('<div id="target"><span>x</span></div>');
        try {
            $htmlInnerDocument->getElementById('target')->innertext = '<div';
            static::fail('Expected RuntimeException for invalid HTML child replacement.');
        } catch (\RuntimeException $e) {
            static::assertStringContainsString('Not valid HTML fragment!', $e->getMessage());
        }

        $htmlOuterDocument = HtmlDomParser::str_get_html('<html><body><div id="target"><span>x</span></div></body></html>');
        try {
            $htmlOuterDocument->getElementById('target')->outertext = '<div';
            static::fail('Expected RuntimeException for invalid HTML node replacement.');
        } catch (\RuntimeException $e) {
            static::assertStringContainsString('Not valid HTML fragment!', $e->getMessage());
        }

        $htmlHeadDocument = HtmlDomParser::str_get_html('<html><body><div id="target"><span>x</span></div></body></html>');
        $htmlHeadDocument->getElementById('target')->outertext = '<head><meta charset="utf-8"></head><body><section>new</section></body>';
        static::assertSame('<html><body><meta charset="utf-8"></body></html>', $htmlHeadDocument->html());

        $detachedHtmlElement = new SimpleHtmlDom(new \DOMElement('free'));
        $detachedHtmlElement->innertext = '<span>ignored</span>';
        $detachedHtmlElement->outertext = '<span>ignored</span>';
        static::assertSame('<free></free>', $detachedHtmlElement->outerHtml());

        $xmlInnerDocument = \voku\helper\XmlDomParser::str_get_xml('<root><item><title>x</title></item></root>');
        try {
            $xmlInnerDocument->findOne('item')->innertext = '<entry';
            static::fail('Expected RuntimeException for invalid XML child replacement.');
        } catch (\RuntimeException $e) {
            static::assertTrue(
                \strpos($e->getMessage(), 'Not valid XML fragment!') !== false
                || \strpos($e->getMessage(), 'XML-Errors:') !== false
            );
        }

        $xmlOuterDocument = \voku\helper\XmlDomParser::str_get_xml('<root><item><title>x</title></item></root>');
        try {
            $xmlOuterDocument->findOne('item')->outertext = '<entry';
            static::fail('Expected RuntimeException for invalid XML node replacement.');
        } catch (\RuntimeException $e) {
            static::assertTrue(
                \strpos($e->getMessage(), 'Not valid XML fragment!') !== false
                || \strpos($e->getMessage(), 'XML-Errors:') !== false
            );
        }

        $xmlMismatchInnerDocument = \voku\helper\XmlDomParser::str_get_xml('<root><item><title>x</title></item></root>');
        try {
            $xmlMismatchInnerDocument->findOne('item')->innertext = '<entry />';
            static::fail('Expected RuntimeException for normalized XML child replacement mismatch.');
        } catch (\RuntimeException $e) {
            static::assertStringContainsString('Not valid XML fragment!', $e->getMessage());
        }

        $xmlMismatchOuterDocument = \voku\helper\XmlDomParser::str_get_xml('<root><item><title>x</title></item></root>');
        try {
            $xmlMismatchOuterDocument->findOne('item')->outertext = '<entry attr="1" />';
            static::fail('Expected RuntimeException for normalized XML node replacement mismatch.');
        } catch (\RuntimeException $e) {
            static::assertStringContainsString('Not valid XML fragment!', $e->getMessage());
        }

        $xmlTextNode = new SimpleXmlDom((new \DOMDocument())->createTextNode('loose'));
        $xmlTextNode->setAttribute('id', 'value');
        static::assertSame('', $xmlTextNode->getAttribute('id'));

        $xmlAttributeDocument = \voku\helper\XmlDomParser::str_get_xml('<root><item/></root>');
        $xmlAttributeItem = $xmlAttributeDocument->findOne('item');
        $xmlAttributeItem->setAttribute('empty', '', true);
        static::assertSame('', $xmlAttributeItem->getAttribute('empty'));
        $xmlAttributeItem->setAttribute('empty', null, true);
        static::assertSame('', $xmlAttributeItem->getAttribute('empty'));

        $detachedXmlElement = new SimpleXmlDom(new \DOMElement('free'));
        $detachedXmlElement->innertext = '<entry>ignored</entry>';
        $detachedXmlElement->outertext = '<entry>ignored</entry>';
        $detachedXmlElement->plaintext = 'ignored';
        static::assertSame('<free></free>', \trim($detachedXmlElement->xml()));

        $whitespaceSiblingXml = \voku\helper\XmlDomParser::str_get_xml("<root><item>\n  <first/>\n  \n</item></root>");
        static::assertNull($whitespaceSiblingXml->findOne('first')->nextNonWhitespaceSibling());
    }

    public function testForcedCleanHtmlWrapperAndXmlFileCoverage()
    {
        $cleanHtmlWrapper = $this->reflectMethod(SimpleHtmlDom::class, 'cleanHtmlWrapper');

        $cleanupHost = HtmlDomParser::str_get_html('<html><body><div id="host"></div></body></html>');
        $cleanupFragment = HtmlDomParser::str_get_html('<!doctype html><html><head><title>T</title></head><body><p><span>x</span></p></body></html>');
        $this->reflectProperty(HtmlDomParser::class, 'isDOMDocumentCreatedWithoutHtmlWrapper')->setValue($cleanupFragment, true);
        $this->reflectProperty(HtmlDomParser::class, 'isDOMDocumentCreatedWithoutPTagWrapper')->setValue($cleanupFragment, true);
        $cleanHtmlWrapper->invoke($cleanupHost->getElementById('host'), $cleanupFragment, false);
        static::assertSame('<head><title>T</title></head>', $cleanupFragment->html());

        $headHost = HtmlDomParser::str_get_html('<html><head><title>T</title></head><body><div id="host"></div></body></html>');
        $headBodyWrapper = new SimpleHtmlDom($headHost->getDocument()->getElementsByTagName('body')->item(0), $headHost);
        $headFragment = HtmlDomParser::str_get_html('<html><head><meta charset="utf-8"></head><body><section>new</section></body></html>');
        $this->reflectProperty(HtmlDomParser::class, 'isDOMDocumentCreatedWithoutHeadWrapper')->setValue($headFragment, true);
        $cleanHtmlWrapper->invoke($headBodyWrapper, $headFragment, true);
        static::assertSame('<html><title>T</title><body><div id="host"></div></body></html>', $headHost->html());

        $xmlParser = \voku\helper\XmlDomParser::str_get_xml('<root><item><title>x</title></item></root>');
        $callbackCalls = 0;
        $xmlParser->set_callback(function () use (&$callbackCalls) {
            ++$callbackCalls;
        });
        static::assertSame('<root><item><title>x</title></item></root>', \trim($xmlParser->html()));
        static::assertSame(1, $callbackCalls);

        try {
            (new \voku\helper\XmlDomParser())->loadXmlFile('/definitely/missing.xml');
            static::fail('Expected RuntimeException for missing XML file.');
        } catch (\RuntimeException $e) {
            static::assertStringContainsString('not found', $e->getMessage());
        }

        try {
            (new \voku\helper\XmlDomParser())->loadHtmlFile('/definitely/missing.html');
            static::fail('Expected RuntimeException for missing HTML file.');
        } catch (\RuntimeException $e) {
            static::assertStringContainsString('not found', $e->getMessage());
        }

        try {
            (new \voku\helper\XmlDomParser())->loadXmlFile('.');
            static::fail('Expected RuntimeException for unreadable XML file path.');
        } catch (\RuntimeException $e) {
            static::assertStringContainsString('Could not load file', $e->getMessage());
        }

        try {
            (new \voku\helper\XmlDomParser())->loadHtmlFile('.');
            static::fail('Expected RuntimeException for unreadable HTML file path.');
        } catch (\RuntimeException $e) {
            static::assertStringContainsString('Could not load file', $e->getMessage());
        }
    }

    public function testHtmlDomHelperAndXmlHeaderCoverage()
    {
        static::assertSame(
            '<div id="x"></div>',
            HtmlDomHelper::mergeHtmlAttributes('<div id="x"></div>', 'class="merged"', 'textarea')
        );
        static::assertSame(
            '<textarea id="x"></textarea>',
            HtmlDomHelper::mergeHtmlAttributes('<textarea id="x"></textarea>', '', 'textarea')
        );

        $xmlHeaderParser = \voku\helper\XmlDomParser::str_get_xml('<root>&amp;</root>');
        $xmlWithHeader = $xmlHeaderParser->xml(false, false, false);
        static::assertStringContainsString('<?xml', $xmlWithHeader);
        static::assertStringContainsString('<root>&amp;</root>', $xmlWithHeader);

        $emptyHtmlParser = new HtmlDomParser();
        static::assertSame('', $emptyHtmlParser->html());
        static::assertSame('', $emptyHtmlParser->innerHtml());
        static::assertInstanceOf(SimpleHtmlDomNodeBlank::class, $emptyHtmlParser->getElementsByTagName('missing', 0));

        $htmlXmlOutput = HtmlDomParser::str_get_html('<div>&amp;</div>')->xml(false, false, false);
        static::assertStringContainsString('<?xml', $htmlXmlOutput);
    }

    public function testHtmlDomParserHtmlCallbackAndWrapperCoverage()
    {
        $parserWithCallback = HtmlDomParser::str_get_html('<html><body><div>callback</div></body></html>');
        $callbackCalls = 0;
        $parserWithCallback->set_callback(function () use (&$callbackCalls) {
            ++$callbackCalls;
        });
        static::assertStringContainsString('<div>callback</div>', $parserWithCallback->html());
        static::assertSame(1, $callbackCalls);

        $wrapperFlagParser = HtmlDomParser::str_get_html('<html><body><div>wrapper</div></body></html>');
        $this->reflectProperty(HtmlDomParser::class, 'isDOMDocumentCreatedWithoutHtmlWrapper')->setValue($wrapperFlagParser, true);
        static::assertStringContainsString('<body><div>wrapper</div></body>', $wrapperFlagParser->html());
    }

    public function testParserConvenienceCloneAndFileLoadingCoverage()
    {
        $parser = HtmlDomParser::str_get_html('<div id="first" class="alpha"></div><div id="second" class="beta"></div>');
        static::assertTrue($parser->getIsDOMDocumentCreatedWithMultiRoot());
        static::assertCount(1, $parser->getElementByClass('alpha'));
        static::assertSame('second', $parser->getElementsById('second', 0)->id);
        static::assertInstanceOf(SimpleHtmlDomBlank::class, $parser->getElementByTagName('missing'));
        static::assertTrue($parser->clear());

        $callbackProperty = $this->reflectProperty(AbstractDomParser::class, 'callback');
        $originalCallback = $callbackProperty->getValue();
        $callbackCalled = false;
        $parser->set_callback(
            static function ($value) use (&$callbackCalled) {
                $callbackCalled = \is_array($value) && isset($value[0]);
            }
        );

        try {
            static::assertNotSame('', $parser->html(false, false));
            static::assertNotSame('', $parser->innerHtml(false, false));
            static::assertStringContainsString('<?xml', $parser->xml(false, false, false, 0));
        } finally {
            $callbackProperty->setValue(null, $originalCallback);
        }

        static::assertTrue($callbackCalled);

        $clone = clone $parser;
        $clone->loadHtml('<span id="clone">value</span>');
        static::assertInstanceOf(SimpleHtmlDomBlank::class, $parser->findOne('span'));
        static::assertSame('clone', $clone->findOne('span')->id);

        $tmpFile = \tempnam(\sys_get_temp_dir(), 'simple-html-dom');
        static::assertNotFalse($tmpFile);

        try {
            \file_put_contents($tmpFile, '<p id="from-file">loaded</p>');
            $loaded = (new HtmlDomParser())->loadHtmlFile($tmpFile);
            static::assertSame('loaded', $loaded->getElementById('from-file')->text());
        } finally {
            if (\is_file($tmpFile)) {
                \unlink($tmpFile);
            }
        }
    }

    public function testHtmlHelperAndXmlParserCoveragePaths()
    {
        $fallbackParser = new class('<div></div>') extends HtmlDomParser {
            public function exposeHtml5FallbackForScriptTags(string &$html): void
            {
                $this->html5FallbackForScriptTags($html);
            }
        };

        $scriptHtml = '<script src="a.js"/><script>if (a) { document.write("</tag>"); }</script>';
        $fallbackParser->exposeHtml5FallbackForScriptTags($scriptHtml);
        static::assertSame(
            '<script src="a.js"></script><script>if (a) { document.write("<\/tag>"); }</script>',
            HtmlDomParser::putReplacedBackToPreserveHtmlEntities($scriptHtml)
        );

        static::assertSame($fallbackParser, $fallbackParser->overwriteSpecialScriptTags(['text/x-custom', 'text/custom']));

        try {
            $fallbackParser->overwriteSpecialScriptTags([123]);
            static::fail('Expected InvalidArgumentException for non-string special script tag.');
        } catch (\InvalidArgumentException $e) {
            static::assertStringContainsString('string[]', $e->getMessage());
        }

        $serializeNode = $this->reflectMethod(HtmlDomParser::class, 'serializeNode');
        $serializeDocument = HtmlDomParser::str_get_html('<div><span>one</span>tail</div>');
        $spanNode = $serializeDocument->getElementByTagName('span')->getNode();
        $textNode = $serializeDocument->getElementByTagName('div')->getNode()->childNodes->item(1);

        static::assertSame('<span>one</span>', $serializeNode->invoke($serializeDocument, $spanNode));
        static::assertSame('tail', \trim($serializeNode->invoke($serializeDocument, $textNode)));

        $hostDocument = HtmlDomParser::str_get_html('<html><body><div id="host"><span>orig</span></div></body></html>');
        $hostNode = $hostDocument->getElementById('host')->getNode();
        $cleaner = new class($hostNode, $hostDocument) extends SimpleHtmlDom {
            public function callClean(HtmlDomParser $newDocument, bool $removeExtraHeadTag = false): HtmlDomParser
            {
                return $this->cleanHtmlWrapper($newDocument, $removeExtraHeadTag);
            }
        };

        static::assertSame(
            '<meta charset="utf-8">',
            $cleaner->callClean(new HtmlDomParser('<meta charset="utf-8"><p>new</p>'))->html()
        );
        static::assertSame('foo', $cleaner->callClean(new HtmlDomParser('foo'))->html());
        static::assertSame(
            '<head><meta charset="utf-8"></head>',
            $cleaner->callClean(
                new HtmlDomParser('<head><meta charset="utf-8"></head><body><span>x</span></body>'),
                true
            )->html()
        );

        $xmlParser = new \voku\helper\XmlDomParser('<root><item id="first">one</item><empty/></root>');
        static::assertSame('one', $xmlParser->plaintext);
        static::assertNull($xmlParser->missing);
        static::assertSame('one', $xmlParser->findOneOrFalse('item')->text());
        static::assertFalse($xmlParser->findOneOrFalse('missing'));
        static::assertStringContainsString('<item id="first">one</item>', $xmlParser->html(false, false));

        $xmlItem = $xmlParser->findOne('item');
        static::assertNull($xmlParser->findOne('empty')->firstChild());
        static::assertNull($xmlParser->findOne('empty')->lastChild());
        static::assertNull($xmlItem->firstChild()->previousSibling());
        static::assertNull($xmlItem->firstChild()->nextSibling());

        $detachedXmlElement = new SimpleXmlDom(new \DOMElement('free'));
        $detachedXmlElement->innertext = '<entry>new</entry>';
        $detachedXmlElement->outertext = '';
        $detachedXmlElement->plaintext = '';
        static::assertSame('free', $detachedXmlElement->tag);

        $mutableDetachedXmlElement = new SimpleXmlDom((new \DOMDocument())->createElement('free'));
        $mutableDetachedXmlElement->setAttribute('id', 'value');
        static::assertSame('value', $mutableDetachedXmlElement->getAttribute('id'));

        $xmlFile = \tempnam(\sys_get_temp_dir(), 'simple-xml-dom-xml');
        $htmlFile = \tempnam(\sys_get_temp_dir(), 'simple-xml-dom-html');
        static::assertNotFalse($xmlFile);
        static::assertNotFalse($htmlFile);

        try {
            \file_put_contents($xmlFile, '<root><item id="xml-file">xml</item></root>');
            \file_put_contents($htmlFile, '<root><item id="html-file">html</item></root>');

            $loadedXml = (new \voku\helper\XmlDomParser())->loadXmlFile($xmlFile);
            $loadedHtml = (new \voku\helper\XmlDomParser())->loadHtmlFile($htmlFile);

            static::assertSame('xml', $loadedXml->getElementById('xml-file')->text());
            static::assertSame('html', $loadedHtml->getElementById('html-file')->text());
        } finally {
            if (\is_file($xmlFile)) {
                \unlink($xmlFile);
            }
            if (\is_file($htmlFile)) {
                \unlink($htmlFile);
            }
        }
    }

    public function testAbstractSimpleHtmlDomMagicCoveragePaths()
    {
        $wrapper = new class() extends AbstractSimpleHtmlDom {
            public $calls = [];
            public $attributes = [
                'data-extra' => 'attr:data-extra',
            ];

            public function __construct()
            {
                $this->node = new \stdClass();
                $this->node->nodeName = 'article';
                $this->node->foo = 'bar';
                $this->node->count = 7;
                $this->node->nullable = null;
                $this->node->content = '';
                $this->node->{'class'} = 'alpha beta';
            }

            public function getTag(): string
            {
                return (string) $this->node->nodeName;
            }

            public function childNodes(int $idx = -1)
            {
                $this->calls[] = ['childNodes', $idx];

                return $idx;
            }

            public function firstChild()
            {
                return 'first';
            }

            public function lastChild()
            {
                return 'last';
            }

            public function nextSibling()
            {
                return 'next';
            }

            public function previousSibling()
            {
                return 'prev';
            }

            public function parentNode()
            {
                return 'parent';
            }

            public function innerHtmlKeep(): string
            {
                return '<inner-keep-method>';
            }

            public function find(string $selector, $idx = null)
            {
                $this->calls[] = ['find', $selector, $idx];

                return $selector . ':' . (string) $idx;
            }

            public function getAllAttributes()
            {
                return $this->attributes;
            }

            public function getAttribute(string $name): string
            {
                return $this->attributes[$name] ?? '';
            }

            public function hasAttribute(string $name): bool
            {
                return isset($this->attributes[$name]);
            }

            public function html(bool $multiDecodeNewHtmlEntity = false): string
            {
                $this->calls[] = ['html', $multiDecodeNewHtmlEntity];

                return '<html>';
            }

            public function innerHtml(bool $multiDecodeNewHtmlEntity = false, bool $putBrokenReplacedBack = true): string
            {
                $this->calls[] = ['innerHtml', $multiDecodeNewHtmlEntity, $putBrokenReplacedBack];

                return $putBrokenReplacedBack ? '<inner>' : '<inner-keep>';
            }

            public function removeAttribute(string $name): \voku\helper\SimpleHtmlDomInterface
            {
                $this->calls[] = ['removeAttribute', $name];
                unset($this->attributes[$name]);

                return new SimpleHtmlDomBlank();
            }

            protected function replaceChildWithString(string $string, bool $putBrokenReplacedBack = true): \voku\helper\SimpleHtmlDomInterface
            {
                $this->calls[] = ['replaceChildWithString', $string, $putBrokenReplacedBack];

                return new SimpleHtmlDomBlank();
            }

            protected function replaceNodeWithString(string $string): \voku\helper\SimpleHtmlDomInterface
            {
                $this->calls[] = ['replaceNodeWithString', $string];

                return new SimpleHtmlDomBlank();
            }

            protected function replaceTextWithString($string): \voku\helper\SimpleHtmlDomInterface
            {
                $this->calls[] = ['replaceTextWithString', $string];

                return new SimpleHtmlDomBlank();
            }

            public function setAttribute(string $name, $value = null, bool $strictEmptyValueCheck = false): \voku\helper\SimpleHtmlDomInterface
            {
                $this->calls[] = ['setAttribute', $name, $value, $strictEmptyValueCheck];
                $this->attributes[$name] = (string) $value;

                return new SimpleHtmlDomBlank();
            }

            public function text(): string
            {
                return 'plain';
            }
        };

        static::assertSame(2, $wrapper->children(2));
        static::assertSame('first', $wrapper->first_child());
        static::assertSame('last', $wrapper->last_child());
        static::assertSame('next', $wrapper->next_sibling());
        static::assertSame('prev', $wrapper->prev_sibling());
        static::assertSame('parent', $wrapper->parent());
        static::assertSame('<html>', $wrapper->outertext());
        static::assertSame('<inner>', $wrapper->innertext());
        static::assertSame('<inner-keep-method>', $wrapper->innerhtmlkeep());
        static::assertSame('span:3', $wrapper('span', 3));
        static::assertSame('article', $wrapper->tag);
        static::assertSame('bar', $wrapper->foo);
        static::assertSame(7, $wrapper->count);
        static::assertSame('attr:data-extra', $wrapper->{'data-extra'});
        static::assertInstanceOf(\voku\helper\SimpleHtmlAttributes::class, $wrapper->classlist);
        static::assertTrue(isset($wrapper->outertext));
        static::assertTrue(isset($wrapper->foo));
        static::assertFalse(isset($wrapper->nullable));
        static::assertTrue(isset($wrapper->{'data-extra'}));

        $wrapper->outertext = '<section>node</section>';
        $wrapper->innertext = '<em>child</em>';
        $wrapper->innerhtmlkeep = '<em>keep</em>';
        $wrapper->plaintext = 'plain text';
        $wrapper->content = 123;
        $wrapper->classlist = 'gamma';
        $wrapper->title = 'headline';
        unset($wrapper->{'data-extra'});

        static::assertSame('123', $wrapper->content);
        static::assertSame('gamma', $wrapper->class);
        static::assertFalse(isset($wrapper->{'data-extra'}));
        static::assertSame('<html>', (string) $wrapper);
        static::assertContains(['replaceNodeWithString', '<section>node</section>'], $wrapper->calls);
        static::assertContains(['replaceChildWithString', '<em>child</em>', true], $wrapper->calls);
        static::assertContains(['replaceChildWithString', '<em>keep</em>', false], $wrapper->calls);
        static::assertContains(['replaceTextWithString', 'plain text'], $wrapper->calls);
        static::assertContains(['setAttribute', 'title', 'headline', false], $wrapper->calls);
        static::assertContains(['removeAttribute', 'data-extra'], $wrapper->calls);

        try {
            $wrapper->missingMethod();
            static::fail('Expected BadMethodCallException for missing HTML alias method.');
        } catch (\BadMethodCallException $e) {
            static::assertSame('Method does not exist', $e->getMessage());
        }
    }

    public function testAbstractSimpleXmlDomMagicCoveragePaths()
    {
        $wrapper = new class() extends AbstractSimpleXmlDom {
            public $calls = [];
            public $attributes = [
                'data-extra' => 'attr:data-extra',
            ];

            public function __construct()
            {
                $this->node = new \stdClass();
                $this->node->nodeName = 'entry';
                $this->node->foo = 'bar';
                $this->node->count = 7;
                $this->node->nullable = null;
            }

            public function childNodes(int $idx = -1)
            {
                $this->calls[] = ['childNodes', $idx];

                return $idx;
            }

            public function firstChild()
            {
                return 'first';
            }

            public function lastChild()
            {
                return 'last';
            }

            public function nextSibling()
            {
                return 'next';
            }

            public function previousSibling()
            {
                return 'prev';
            }

            public function parentNode()
            {
                return 'parent';
            }

            public function find(string $selector, $idx = null)
            {
                $this->calls[] = ['find', $selector, $idx];

                return $selector . ':' . (string) $idx;
            }

            public function getAllAttributes()
            {
                return $this->attributes;
            }

            public function getAttribute(string $name): string
            {
                return $this->attributes[$name] ?? '';
            }

            public function hasAttribute(string $name): bool
            {
                return isset($this->attributes[$name]);
            }

            public function innerXml(bool $multiDecodeNewHtmlEntity = false): string
            {
                return '<inner-xml>';
            }

            public function removeAttribute(string $name): \voku\helper\SimpleXmlDomInterface
            {
                $this->calls[] = ['removeAttribute', $name];
                unset($this->attributes[$name]);

                return new SimpleXmlDomBlank();
            }

            protected function replaceChildWithString(string $string, bool $putBrokenReplacedBack = true): \voku\helper\SimpleXmlDomInterface
            {
                $this->calls[] = ['replaceChildWithString', $string, $putBrokenReplacedBack];

                return new SimpleXmlDomBlank();
            }

            protected function replaceNodeWithString(string $string): \voku\helper\SimpleXmlDomInterface
            {
                $this->calls[] = ['replaceNodeWithString', $string];

                return new SimpleXmlDomBlank();
            }

            protected function replaceTextWithString($string): \voku\helper\SimpleXmlDomInterface
            {
                $this->calls[] = ['replaceTextWithString', $string];

                return new SimpleXmlDomBlank();
            }

            public function setAttribute(string $name, $value = null, bool $strictEmptyValueCheck = false): \voku\helper\SimpleXmlDomInterface
            {
                $this->calls[] = ['setAttribute', $name, $value, $strictEmptyValueCheck];
                $this->attributes[$name] = (string) $value;

                return new SimpleXmlDomBlank();
            }

            public function text(): string
            {
                return 'plain';
            }

            public function xml(bool $multiDecodeNewHtmlEntity = false): string
            {
                $this->calls[] = ['xml', $multiDecodeNewHtmlEntity];

                return '<xml />';
            }
        };

        static::assertSame(1, $wrapper->children(1));
        static::assertSame('first', $wrapper->first_child());
        static::assertSame('last', $wrapper->last_child());
        static::assertSame('next', $wrapper->next_sibling());
        static::assertSame('prev', $wrapper->prev_sibling());
        static::assertSame('parent', $wrapper->parent());
        static::assertSame('item:4', $wrapper('item', 4));
        static::assertSame('<xml />', $wrapper->xml);
        static::assertSame('plain', $wrapper->plaintext);
        static::assertSame('entry', $wrapper->tag);
        static::assertSame('bar', $wrapper->foo);
        static::assertSame(7, $wrapper->count);
        static::assertSame('attr:data-extra', $wrapper->{'data-extra'});
        static::assertTrue(isset($wrapper->outertext));
        static::assertTrue(isset($wrapper->foo));
        static::assertFalse(isset($wrapper->nullable));
        static::assertTrue(isset($wrapper->{'data-extra'}));

        $wrapper->outertext = '<node />';
        $wrapper->innertext = '<child />';
        $wrapper->innerhtmlkeep = '<keep />';
        $wrapper->plaintext = 'plain text';
        $wrapper->foo = 'baz';
        $wrapper->title = 'headline';
        unset($wrapper->{'data-extra'});

        static::assertSame('baz', $wrapper->foo);
        static::assertFalse(isset($wrapper->{'data-extra'}));
        static::assertSame('<xml />', (string) $wrapper);
        static::assertContains(['replaceNodeWithString', '<node />'], $wrapper->calls);
        static::assertContains(['replaceChildWithString', '<child />', true], $wrapper->calls);
        static::assertContains(['replaceChildWithString', '<keep />', false], $wrapper->calls);
        static::assertContains(['replaceTextWithString', 'plain text'], $wrapper->calls);
        static::assertContains(['setAttribute', 'title', 'headline', false], $wrapper->calls);
        static::assertContains(['removeAttribute', 'data-extra'], $wrapper->calls);

        try {
            $wrapper->missingMethod();
            static::fail('Expected BadMethodCallException for missing XML alias method.');
        } catch (\BadMethodCallException $e) {
            static::assertSame('Method does not exist', $e->getMessage());
        }
    }

    public function testAbstractSimpleDomNodeEmptyCollectionCoveragePaths()
    {
        $emptyHtmlNodes = new \voku\helper\SimpleHtmlDomNode();
        static::assertSame(0, $emptyHtmlNodes->length);
        static::assertSame([], $emptyHtmlNodes->outertext);
        static::assertNull($emptyHtmlNodes->missing);
        static::assertSame('', (string) $emptyHtmlNodes);

        $emptyXmlNodes = new \voku\helper\SimpleXmlDomNode();
        static::assertSame(0, $emptyXmlNodes->length);
        static::assertSame([], $emptyXmlNodes->outertext);
        static::assertNull($emptyXmlNodes->missing);
        static::assertSame('', (string) $emptyXmlNodes);
    }
}
