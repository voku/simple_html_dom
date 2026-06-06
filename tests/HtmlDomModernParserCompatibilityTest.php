<?php

use voku\helper\HtmlDomParser;

/**
 * @internal
 */
final class HtmlDomModernParserCompatibilityTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        StrictModernHtmlDomParser::reset();
        ThrowingModernHtmlDomParser::reset();
        ProjectingModernHtmlDomParser::$modernDocumentFactory = null;
    }

    protected function tearDown(): void
    {
        ProjectingModernHtmlDomParser::$modernDocumentFactory = null;
    }

    /**
     * @param array<string, mixed> $properties
     */
    private function createModernNode(int $nodeType, array $properties = []): \stdClass
    {
        $node = new \stdClass();
        $node->nodeType = $nodeType;
        $node->childNodes = [];

        foreach ($properties as $property => $value) {
            $node->{$property} = $value;
        }

        return $node;
    }

    /**
     * @return array<string, array{class-string<HtmlDomParser>}>
     */
    public function provideParserClasses(): array
    {
        $parserClasses = [
            'legacy parser path' => [ForcedLegacyHtmlDomParser::class],
        ];

        if (StrictModernHtmlDomParser::supportsModernPath()) {
            $parserClasses['strict modern parser path'] = [StrictModernHtmlDomParser::class];
        }

        return $parserClasses;
    }

    private function requireModernPath(): void
    {
        if (!StrictModernHtmlDomParser::supportsModernPath()) {
            static::markTestSkipped('Dom\\HTMLDocument is not available on this runtime.');
        }
    }

    /**
     * @param mixed $modernNode
     *
     * @return object|null
     */
    private function findFirstModernNodeByLocalName($modernNode, string $localName)
    {
        if (!\is_object($modernNode)) {
            return null;
        }

        if (
            \property_exists($modernNode, 'localName')
            &&
            \strtolower((string) $modernNode->localName) === \strtolower($localName)
        ) {
            return $modernNode;
        }

        if (\property_exists($modernNode, 'content') && \is_object($modernNode->content)) {
            $match = $this->findFirstModernNodeByLocalName($modernNode->content, $localName);
            if ($match !== null) {
                return $match;
            }
        }

        if (!\property_exists($modernNode, 'childNodes')) {
            return null;
        }

        foreach ($modernNode->childNodes as $modernChildNode) {
            $match = $this->findFirstModernNodeByLocalName($modernChildNode, $localName);
            if ($match !== null) {
                return $match;
            }
        }

        return null;
    }

    /**
     * @param object $modernNode
     *
     * @return object|null
     */
    private function findModernAttributeByNodeName($modernNode, string $nodeName)
    {
        if (!\property_exists($modernNode, 'attributes')) {
            return null;
        }

        foreach ($modernNode->attributes as $modernAttribute) {
            if (
                \property_exists($modernAttribute, 'nodeName')
                &&
                (string) $modernAttribute->nodeName === $nodeName
            ) {
                return $modernAttribute;
            }
        }

        return null;
    }

    /**
     * @param object $modernNode
     */
    private function assertModernNodePropertyExists($modernNode, string $property): void
    {
        static::assertTrue(
            \property_exists($modernNode, $property),
            'Expected property "' . $property . '" on ' . \get_class($modernNode)
        );
    }

    private function normalizeHtmlFragment(string $html): string
    {
        $normalizedHtml = \preg_replace('/>\s+</', '><', \trim($html));

        return $normalizedHtml !== null ? $normalizedHtml : \trim($html);
    }

    /**
     * @param object $modernNode
     *
     * @return object|null
     */
    private function getFirstModernChildNode($modernNode)
    {
        $this->assertModernNodePropertyExists($modernNode, 'childNodes');

        foreach ($modernNode->childNodes as $modernChildNode) {
            return $modernChildNode;
        }

        return null;
    }

    /**
     * @dataProvider provideParserClasses
     *
     * @param class-string<HtmlDomParser> $parserClass
     */
    public function testParserPathKeepsLegacyDomTypesAndLiveMutations(string $parserClass): void
    {
        $dom = $parserClass::str_get_html('<main><p class="message">old</p></main>');

        static::assertInstanceOf(\DOMDocument::class, $dom->getDocument());

        $paragraph = $dom->findOne('.message');
        static::assertInstanceOf(\DOMNode::class, $paragraph->getNode());

        $paragraph->innerhtml = '<strong>new</strong>';
        static::assertStringContainsString(
            '<p class="message"><strong>new</strong></p>',
            $dom->html()
        );

        $rawNode = $paragraph->getNode();
        static::assertInstanceOf(\DOMElement::class, $rawNode);

        $rawNode->setAttribute('data-state', 'done');
        static::assertStringContainsString(
            '<p class="message" data-state="done"><strong>new</strong></p>',
            $dom->html()
        );

        if ($parserClass === StrictModernHtmlDomParser::class) {
            static::assertSame(1, StrictModernHtmlDomParser::$successfulModernProjectionCalls);
            static::assertSame(0, StrictModernHtmlDomParser::$legacyFallbackCalls);
        }
    }

    /**
     * @dataProvider provideParserClasses
     *
     * @param class-string<HtmlDomParser> $parserClass
     */
    public function testFileParsingKeepsTemplateAndSerializationCompatibility(string $parserClass): void
    {
        $filePath = \tempnam(\sys_get_temp_dir(), 'simple-html-dom-modern-');
        static::assertNotFalse($filePath);
        \chmod($filePath, 0600);

        $html = '<!DOCTYPE html><html><body><template id="card"><section><h2>Title</h2><p>Body</p></section></template><main>After</main></body></html>';
        $expectedHtml = \str_replace('<!DOCTYPE html>', '<!DOCTYPE html>' . "\n", $html);
        \file_put_contents($filePath, $html);

        try {
            $dom = new $parserClass();
            $dom->loadHtmlFile($filePath);

            static::assertSame($expectedHtml, $dom->html());
            static::assertSame('card', $dom->findOne('template')->getAttribute('id'));
            static::assertSame('<section><h2>Title</h2><p>Body</p></section>', $dom->findOne('template')->innerHTML);
            static::assertSame('After', $dom->findOne('main')->innerHTML);
        } finally {
            @\unlink($filePath);
        }
    }

    /**
     * @dataProvider provideParserClasses
     *
     * @param class-string<HtmlDomParser> $parserClass
     */
    public function testParserPathPreservesSvgNamespacedAttributeAccess(string $parserClass): void
    {
        $dom = $parserClass::str_get_html(
            '<div><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use xlink:href="#icon"></use></svg></div>'
        );

        static::assertSame('#icon', $dom->findOne('use')->getAttribute('xlink:href'));
        static::assertSame(
            '<div><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use xlink:href="#icon"></use></svg></div>',
            $dom->html()
        );
    }

    public function testRealModernParserPreservesBrowserStyleScriptWithoutLegacyFallback(): void
    {
        $this->requireModernPath();

        $html = '<p>Paragraph 1</p><script>console.log("</html>inside script");</script><p>Paragraph 2</p>';
        $dom = StrictModernHtmlDomParser::str_get_html($html);

        $paragraphs = $dom->findMulti('p');

        static::assertInstanceOf(\DOMDocument::class, $dom->getDocument());
        static::assertCount(2, $paragraphs);
        static::assertSame('Paragraph 1', $paragraphs[0]->text());
        static::assertSame('Paragraph 2', $paragraphs[1]->text());
        static::assertSame('console.log("</html>inside script");', $dom->findOne('script')->innerHTML);
        static::assertStringContainsString('<p>Paragraph 1</p>', $dom->html());
        static::assertStringContainsString('<p>Paragraph 2</p>', $dom->html());
        static::assertLessThan(
            \strpos($dom->html(), '<p>Paragraph 2</p>'),
            \strpos($dom->html(), '</script>')
        );
        static::assertSame(1, StrictModernHtmlDomParser::$modernCreateCalls);
        static::assertSame(1, StrictModernHtmlDomParser::$successfulModernProjectionCalls);
        static::assertSame(0, StrictModernHtmlDomParser::$legacyFallbackCalls);
    }

    public function testModernSupportGuardRequiresPhp84Runtime(): void
    {
        $parser = new SupportAwareHtmlDomParser();

        static::assertSame(\PHP_VERSION_ID >= 80400, $parser->supportsModernRuntimeGuard());
    }

    public function testInjectedModernDocumentProjectsLegacyNodesAndPreservesCompatibility(): void
    {
        $templateSection = $this->createModernNode(
            \XML_ELEMENT_NODE,
            [
                'localName' => 'section',
                'nodeName' => 'section',
                'attributes' => [],
                'childNodes' => [
                    $this->createModernNode(
                        \XML_ELEMENT_NODE,
                        [
                            'localName' => 'h2',
                            'nodeName' => 'h2',
                            'attributes' => [],
                            'childNodes' => [
                                $this->createModernNode(\XML_TEXT_NODE, ['nodeValue' => 'Title']),
                            ],
                        ]
                    ),
                    $this->createModernNode(
                        \XML_ELEMENT_NODE,
                        [
                            'localName' => 'p',
                            'nodeName' => 'p',
                            'attributes' => [],
                            'childNodes' => [
                                $this->createModernNode(\XML_TEXT_NODE, ['nodeValue' => 'Body']),
                            ],
                        ]
                    ),
                ],
            ]
        );

        $fakeDocument = $this->createModernNode(
            \XML_DOCUMENT_NODE,
            [
                'childNodes' => [
                    $this->createModernNode(
                        \XML_DOCUMENT_TYPE_NODE,
                        [
                            'name' => 'html',
                            'nodeName' => 'html',
                            'publicId' => '',
                            'systemId' => '',
                        ]
                    ),
                    $this->createModernNode(
                        \XML_ELEMENT_NODE,
                        [
                            'localName' => 'html',
                            'nodeName' => 'html',
                            'attributes' => [],
                            'childNodes' => [
                                $this->createModernNode(
                                    \XML_ELEMENT_NODE,
                                    [
                                        'localName' => 'body',
                                        'nodeName' => 'body',
                                        'attributes' => [],
                                        'childNodes' => [
                                            $this->createModernNode(
                                                \XML_ELEMENT_NODE,
                                                [
                                                    'localName' => 'main',
                                                    'nodeName' => 'main',
                                                    'attributes' => [],
                                                    'childNodes' => [
                                                        $this->createModernNode(
                                                            \XML_ELEMENT_NODE,
                                                            [
                                                                'localName' => 'p',
                                                                'nodeName' => 'p',
                                                                'attributes' => [
                                                                    $this->createModernNode(
                                                                        \XML_ATTRIBUTE_NODE,
                                                                        [
                                                                            'localName' => 'class',
                                                                            'nodeName' => 'class',
                                                                            'nodeValue' => 'message',
                                                                        ]
                                                                    ),
                                                                ],
                                                                'childNodes' => [
                                                                    $this->createModernNode(\XML_TEXT_NODE, ['nodeValue' => 'old']),
                                                                    $this->createModernNode(
                                                                        \XML_DOCUMENT_FRAG_NODE,
                                                                        [
                                                                            'childNodes' => [
                                                                                $this->createModernNode(\XML_TEXT_NODE, ['nodeValue' => ' via-fragment']),
                                                                            ],
                                                                        ]
                                                                    ),
                                                                    $this->createModernNode(\XML_CDATA_SECTION_NODE, ['nodeValue' => 'cdata']),
                                                                    $this->createModernNode(\XML_COMMENT_NODE, ['nodeValue' => 'note']),
                                                                    $this->createModernNode(
                                                                        \XML_PI_NODE,
                                                                        [
                                                                            'nodeName' => 'process',
                                                                            'nodeValue' => 'instruction',
                                                                        ]
                                                                    ),
                                                                ],
                                                            ]
                                                        ),
                                                        $this->createModernNode(
                                                            \XML_ELEMENT_NODE,
                                                            [
                                                                'localName' => 'template',
                                                                'nodeName' => 'template',
                                                                'attributes' => [
                                                                    $this->createModernNode(
                                                                        \XML_ATTRIBUTE_NODE,
                                                                        [
                                                                            'localName' => 'id',
                                                                            'nodeName' => 'id',
                                                                            'nodeValue' => 'card',
                                                                        ]
                                                                    ),
                                                                ],
                                                                'childNodes' => [],
                                                                'content' => $this->createModernNode(
                                                                    \XML_DOCUMENT_FRAG_NODE,
                                                                    ['childNodes' => [$templateSection]]
                                                                ),
                                                            ]
                                                        ),
                                                        $this->createModernNode(
                                                            \XML_ELEMENT_NODE,
                                                            [
                                                                'localName' => 'svg',
                                                                'nodeName' => 'svg',
                                                                'attributes' => [
                                                                    $this->createModernNode(
                                                                        \XML_ATTRIBUTE_NODE,
                                                                        [
                                                                            'localName' => 'xmlns',
                                                                            'nodeName' => 'xmlns',
                                                                            'nodeValue' => 'http://www.w3.org/2000/svg',
                                                                        ]
                                                                    ),
                                                                    $this->createModernNode(
                                                                        \XML_ATTRIBUTE_NODE,
                                                                        [
                                                                            'localName' => 'xlink',
                                                                            'nodeName' => 'xmlns:xlink',
                                                                            'prefix' => 'xmlns',
                                                                            'nodeValue' => 'http://www.w3.org/1999/xlink',
                                                                        ]
                                                                    ),
                                                                ],
                                                                'childNodes' => [
                                                                    $this->createModernNode(
                                                                        \XML_ELEMENT_NODE,
                                                                        [
                                                                            'localName' => 'use',
                                                                            'nodeName' => 'use',
                                                                            'attributes' => [
                                                                                $this->createModernNode(
                                                                                    \XML_ATTRIBUTE_NODE,
                                                                                    [
                                                                                        'localName' => 'href',
                                                                                        'nodeName' => 'xlink:href',
                                                                                        'prefix' => 'xlink',
                                                                                        'nodeValue' => '#icon',
                                                                                    ]
                                                                                ),
                                                                            ],
                                                                            'childNodes' => [],
                                                                        ]
                                                                    ),
                                                                ],
                                                            ]
                                                        ),
                                                    ],
                                                ]
                                            ),
                                        ],
                                    ]
                                ),
                            ],
                        ]
                    ),
                    $this->createModernNode(999, ['childNodes' => []]),
                ],
            ]
        );

        ProjectingModernHtmlDomParser::$modernDocumentFactory = static function () use ($fakeDocument) {
            return $fakeDocument;
        };

        try {
            $dom = ProjectingModernHtmlDomParser::str_get_html('<main><p class="message">old</p></main>');

            static::assertInstanceOf(\DOMDocument::class, $dom->getDocument());
            static::assertSame('old via-fragmentcdata', $dom->findOne('.message')->text());
            static::assertSame('card', $dom->findOne('template')->getAttribute('id'));
            static::assertSame('<section><h2>Title</h2><p>Body</p></section>', $dom->findOne('template')->innerHTML);
            static::assertSame('#icon', $dom->findOne('use')->getAttribute('xlink:href'));

            $paragraph = $dom->findOne('.message');
            $paragraph->innerhtml = '<strong>new</strong>';
            static::assertStringContainsString(
                '<p class="message"><strong>new</strong></p>',
                $dom->html()
            );
            static::assertInstanceOf(\DOMDocumentType::class, $dom->getDocument()->doctype);
        } finally {
            ProjectingModernHtmlDomParser::$modernDocumentFactory = null;
        }
    }

    public function testModernParserFallbackStillUsesLegacyParsingWhenModernCreationFails(): void
    {
        ThrowingModernHtmlDomParser::$legacyFallbackCalls = 0;

        $dom = ThrowingModernHtmlDomParser::str_get_html('<main><p class="message">old</p></main>');

        static::assertInstanceOf(\DOMDocument::class, $dom->getDocument());
        static::assertSame('old', $dom->findOne('.message')->text());
        static::assertStringContainsString('<p class="message">old</p>', $dom->html());
        static::assertSame(1, ThrowingModernHtmlDomParser::$legacyFallbackCalls);
    }

    public function testRealModernProjectionPreservesTemplateAndSvgNamespaces(): void
    {
        $this->requireModernPath();

        $dom = StrictModernHtmlDomParser::str_get_html(
            '</p><div><template id="card"><section><p>Template content</p></section></template><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use xlink:href="#icon"></use></svg></div>'
        );

        $modernDocument = StrictModernHtmlDomParser::$lastModernDocument;
        static::assertNotNull($modernDocument);
        $this->assertModernNodePropertyExists($modernDocument, 'nodeType');
        $this->assertModernNodePropertyExists($modernDocument, 'childNodes');
        static::assertSame(\XML_DOCUMENT_NODE, $modernDocument->nodeType);

        $templateNode = $this->findFirstModernNodeByLocalName($modernDocument, 'template');
        static::assertNotNull($templateNode);
        $this->assertModernNodePropertyExists($templateNode, 'nodeName');
        $this->assertModernNodePropertyExists($templateNode, 'localName');
        $this->assertModernNodePropertyExists($templateNode, 'namespaceURI');
        $this->assertModernNodePropertyExists($templateNode, 'attributes');
        $this->assertModernNodePropertyExists($templateNode, 'content');
        static::assertSame('template', \strtolower((string) $templateNode->nodeName));
        static::assertSame('template', \strtolower((string) $templateNode->localName));
        static::assertSame('http://www.w3.org/1999/xhtml', (string) $templateNode->namespaceURI);

        $templateIdAttribute = $this->findModernAttributeByNodeName($templateNode, 'id');
        static::assertNotNull($templateIdAttribute);
        $this->assertModernNodePropertyExists($templateIdAttribute, 'nodeValue');
        static::assertSame('card', (string) $templateIdAttribute->nodeValue);

        $templateParagraphNode = $this->findFirstModernNodeByLocalName($templateNode->content, 'p');
        static::assertNotNull($templateParagraphNode);
        $templateTextNode = $this->getFirstModernChildNode($templateParagraphNode);
        static::assertNotNull($templateTextNode);
        $this->assertModernNodePropertyExists($templateTextNode, 'nodeValue');
        static::assertSame('Template content', (string) $templateTextNode->nodeValue);

        $useNode = $this->findFirstModernNodeByLocalName($modernDocument, 'use');
        static::assertNotNull($useNode);
        $this->assertModernNodePropertyExists($useNode, 'nodeType');
        $this->assertModernNodePropertyExists($useNode, 'nodeName');
        $this->assertModernNodePropertyExists($useNode, 'localName');
        $this->assertModernNodePropertyExists($useNode, 'prefix');
        $this->assertModernNodePropertyExists($useNode, 'namespaceURI');
        $this->assertModernNodePropertyExists($useNode, 'attributes');
        static::assertSame(\XML_ELEMENT_NODE, $useNode->nodeType);
        static::assertSame('use', (string) $useNode->nodeName);
        static::assertSame('use', (string) $useNode->localName);
        static::assertSame('', (string) $useNode->prefix);
        static::assertSame('http://www.w3.org/2000/svg', (string) $useNode->namespaceURI);

        $xlinkHrefAttribute = $this->findModernAttributeByNodeName($useNode, 'xlink:href');
        static::assertNotNull($xlinkHrefAttribute);
        $this->assertModernNodePropertyExists($xlinkHrefAttribute, 'nodeType');
        $this->assertModernNodePropertyExists($xlinkHrefAttribute, 'nodeName');
        $this->assertModernNodePropertyExists($xlinkHrefAttribute, 'localName');
        $this->assertModernNodePropertyExists($xlinkHrefAttribute, 'prefix');
        $this->assertModernNodePropertyExists($xlinkHrefAttribute, 'namespaceURI');
        $this->assertModernNodePropertyExists($xlinkHrefAttribute, 'nodeValue');
        static::assertSame(\XML_ATTRIBUTE_NODE, $xlinkHrefAttribute->nodeType);
        static::assertSame('xlink:href', (string) $xlinkHrefAttribute->nodeName);
        static::assertSame('href', (string) $xlinkHrefAttribute->localName);
        static::assertSame('xlink', (string) $xlinkHrefAttribute->prefix);
        static::assertSame('http://www.w3.org/1999/xlink', (string) $xlinkHrefAttribute->namespaceURI);
        static::assertSame('#icon', (string) $xlinkHrefAttribute->nodeValue);

        $template = $dom->findOne('template#card');
        static::assertSame(
            '<section><p>Template content</p></section>',
            $this->normalizeHtmlFragment($template->innerHTML)
        );

        $use = $dom->findOne('use');
        static::assertSame('#icon', $use->getAttribute('xlink:href'));
        static::assertStringContainsString(
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use xlink:href="#icon"></use></svg>',
            $this->normalizeHtmlFragment($dom->html())
        );

        $legacyUseNode = $use->getNode();
        static::assertInstanceOf(\DOMElement::class, $legacyUseNode);
        static::assertSame('http://www.w3.org/2000/svg', $legacyUseNode->namespaceURI);

        $legacyXlinkHrefAttribute = $legacyUseNode->getAttributeNodeNS('http://www.w3.org/1999/xlink', 'href');
        static::assertInstanceOf(\DOMAttr::class, $legacyXlinkHrefAttribute);
        static::assertSame('#icon', $legacyXlinkHrefAttribute->value);
        static::assertSame('xlink:href', $legacyXlinkHrefAttribute->nodeName);
        static::assertSame('href', $legacyXlinkHrefAttribute->localName);
        static::assertSame('xlink', $legacyXlinkHrefAttribute->prefix);
        static::assertSame('http://www.w3.org/1999/xlink', $legacyXlinkHrefAttribute->namespaceURI);
        static::assertSame(1, StrictModernHtmlDomParser::$successfulModernProjectionCalls);
        static::assertSame(0, StrictModernHtmlDomParser::$legacyFallbackCalls);
    }
}

/**
 * @internal Test double that forces the legacy libxml parser path.
 */
class ForcedLegacyHtmlDomParser extends HtmlDomParser
{
    protected function shouldUseModernHtmlDocument(int $optionsXml): bool
    {
        return false;
    }
}

/**
 * @internal Test double that forces the PHP 8.4+ modern parser path and rejects fallback.
 */
class StrictModernHtmlDomParser extends HtmlDomParser
{
    /**
     * @var int
     */
    public static $legacyFallbackCalls = 0;

    /**
     * @var int
     */
    public static $successfulModernProjectionCalls = 0;

    /**
     * @var int
     */
    public static $modernCreateCalls = 0;

    /**
     * @var object|null
     */
    public static $lastModernDocument;

    public static function supportsModernPath(): bool
    {
        return \class_exists('Dom\\HTMLDocument')
            && \method_exists('Dom\\HTMLDocument', 'createFromString');
    }

    public static function reset(): void
    {
        self::$legacyFallbackCalls = 0;
        self::$successfulModernProjectionCalls = 0;
        self::$modernCreateCalls = 0;
        self::$lastModernDocument = null;
    }

    protected function shouldUseModernHtmlDocument(int $optionsXml): bool
    {
        return self::supportsModernPath();
    }

    /**
     * @return object
     */
    protected function createModernHtmlDocument(string $html, int $optionsXml)
    {
        ++self::$modernCreateCalls;

        self::$lastModernDocument = parent::createModernHtmlDocument($html, $optionsXml);

        return self::$lastModernDocument;
    }

    protected function createLegacyDocumentFromModernParser(string $html, int $optionsXml): \DOMDocument
    {
        $document = parent::createLegacyDocumentFromModernParser($html, $optionsXml);

        ++self::$successfulModernProjectionCalls;

        return $document;
    }

    protected function createLegacyDocumentWithLibxml(string $html, int $optionsXml): \DOMDocument
    {
        ++self::$legacyFallbackCalls;

        throw new \RuntimeException('Legacy fallback must not be used by this test.');
    }
}

/**
 * @internal Test double that exposes the runtime guard around Dom\HTMLDocument support.
 */
class SupportAwareHtmlDomParser extends HtmlDomParser
{
    public function supportsModernRuntimeGuard(): bool
    {
        return parent::supportsModernHtmlDocument();
    }
}

/**
 * @internal Test double that injects a fake modern DOM document on runtimes without PHP 8.4.
 */
class ProjectingModernHtmlDomParser extends HtmlDomParser
{
    /**
     * @var callable|null
     */
    public static $modernDocumentFactory;

    protected function shouldUseModernHtmlDocument(int $optionsXml): bool
    {
        return true;
    }

    /**
     * @return object
     */
    protected function createModernHtmlDocument(string $html, int $optionsXml)
    {
        if (self::$modernDocumentFactory === null) {
            throw new \RuntimeException('No projected modern document configured.');
        }

        return \call_user_func(self::$modernDocumentFactory, $html, $optionsXml, $this->getEncoding());
    }
}

/**
 * @internal Test double that forces the legacy fallback when modern document creation fails.
 */
class ThrowingModernHtmlDomParser extends HtmlDomParser
{
    /**
     * @var int
     */
    public static $legacyFallbackCalls = 0;

    public static function reset(): void
    {
        self::$legacyFallbackCalls = 0;
    }

    protected function shouldUseModernHtmlDocument(int $optionsXml): bool
    {
        return true;
    }

    protected function createLegacyDocumentFromModernParser(string $html, int $optionsXml): \DOMDocument
    {
        throw new \RuntimeException('boom');
    }

    protected function createLegacyDocumentWithLibxml(string $html, int $optionsXml): \DOMDocument
    {
        ++self::$legacyFallbackCalls;

        return parent::createLegacyDocumentWithLibxml($html, $optionsXml);
    }
}
