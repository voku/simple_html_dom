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
        ProjectingModernHtmlDomParser::$lastOptionsXml = null;
    }

    protected function tearDown(): void
    {
        ProjectingModernHtmlDomParser::$modernDocumentFactory = null;
        ProjectingModernHtmlDomParser::$lastOptionsXml = null;
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
        return [
            'legacy parser path' => [ForcedLegacyHtmlDomParser::class],
        ];
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

    public function testStrictModernParserUsesModernPathWithoutFallback(): void
    {
        $this->requireModernPath();

        $dom = StrictModernHtmlDomParser::str_get_html('<div><p>Paragraph</p></div>');

        static::assertInstanceOf(\DOMDocument::class, $dom->getDocument());
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

    public function testModernParserFiltersUnsupportedModernHtmlDocumentOptions(): void
    {
        $fakeDocument = $this->createModernNode(
            \XML_DOCUMENT_NODE,
            [
                'childNodes' => [
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
        );

        ProjectingModernHtmlDomParser::$modernDocumentFactory = static function () use ($fakeDocument) {
            return $fakeDocument;
        };

        $options = \LIBXML_DTDLOAD | \LIBXML_DTDATTR | \LIBXML_NONET | \LIBXML_NOERROR;
        if (\defined('LIBXML_BIGLINES')) {
            $options |= \LIBXML_BIGLINES;
        }
        if (\defined('LIBXML_COMPACT')) {
            $options |= \LIBXML_COMPACT;
        }
        if (\defined('LIBXML_HTML_NODEFDTD')) {
            $options |= \LIBXML_HTML_NODEFDTD;
        }

        try {
            $dom = ProjectingModernHtmlDomParser::str_get_html('<main></main>', $options);

            static::assertInstanceOf(\DOMDocument::class, $dom->getDocument());

            // Intentionally mirror PHP 8.4's public Dom\HTMLDocument flag
            // contract here so the test fails if unsupported flags start
            // leaking back into the modern parser path.
            $expectedOptions = $options & (\LIBXML_NOERROR | (\defined('LIBXML_COMPACT') ? \LIBXML_COMPACT : 0));
            if (\defined('LIBXML_HTML_NOIMPLIED')) {
                $expectedOptions |= $options & \LIBXML_HTML_NOIMPLIED;
            }
            if (\defined('Dom\\HTML_NO_DEFAULT_NS')) {
                $expectedOptions |= $options & \constant('Dom\\HTML_NO_DEFAULT_NS');
            }

            static::assertSame($expectedOptions, ProjectingModernHtmlDomParser::$lastOptionsXml);
        } finally {
            ProjectingModernHtmlDomParser::$modernDocumentFactory = null;
            ProjectingModernHtmlDomParser::$lastOptionsXml = null;
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

    public function testProjectedModernNamespacesUseLegacyDomNamespaces(): void
    {
        $fakeDocument = $this->createModernNode(
            \XML_DOCUMENT_NODE,
            [
                'childNodes' => [
                    $this->createModernNode(
                        \XML_ELEMENT_NODE,
                        [
                            'localName' => 'svg',
                            'nodeName' => 'svg',
                            'namespaceURI' => 'http://www.w3.org/2000/svg',
                            'attributes' => [
                                $this->createModernNode(
                                    \XML_ATTRIBUTE_NODE,
                                    [
                                        'localName' => 'href',
                                        'nodeName' => 'xlink:href',
                                        'prefix' => 'xlink',
                                        'namespaceURI' => 'http://www.w3.org/1999/xlink',
                                        'nodeValue' => '#icon',
                                    ]
                                ),
                            ],
                            'childNodes' => [],
                        ]
                    ),
                ],
            ]
        );

        ProjectingModernHtmlDomParser::$modernDocumentFactory = static function () use ($fakeDocument) {
            return $fakeDocument;
        };

        try {
            $dom = ProjectingModernHtmlDomParser::str_get_html('<svg></svg>');
            $svgNode = $dom->getDocument()->documentElement;

            static::assertInstanceOf(\DOMElement::class, $svgNode);
            static::assertSame('http://www.w3.org/2000/svg', $svgNode->namespaceURI);

            $hrefAttribute = $svgNode->getAttributeNodeNS('http://www.w3.org/1999/xlink', 'href');
            static::assertInstanceOf(\DOMAttr::class, $hrefAttribute);
            static::assertSame('#icon', $hrefAttribute->value);
        } finally {
            ProjectingModernHtmlDomParser::$modernDocumentFactory = null;
        }
    }

    public function testModernProjectionFailureFallsBackToLegacyParsing(): void
    {
        $fakeDocument = new \stdClass();
        $fakeDocument->nodeType = \XML_DOCUMENT_NODE;

        ProjectingModernHtmlDomParser::$modernDocumentFactory = static function () use ($fakeDocument) {
            return $fakeDocument;
        };

        try {
            $dom = ProjectingModernHtmlDomParser::str_get_html('<main>fallback</main>');

            static::assertSame('<main>fallback</main>', $dom->html());
        } finally {
            ProjectingModernHtmlDomParser::$modernDocumentFactory = null;
        }
    }

    public function testStrictModernParserProcessesComplexHtmlWithoutFallback(): void
    {
        $this->requireModernPath();

        $dom = StrictModernHtmlDomParser::str_get_html(
            // Start with an unmatched closing paragraph tag so the real HTML5 parser
            // must recover browser-style markup before projection into DOMDocument.
            '</p><div><template id="card"><section><p>Template content</p></section></template><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use xlink:href="#icon"></use></svg></div>'
        );

        static::assertInstanceOf(\DOMDocument::class, $dom->getDocument());

        $modernDocument = StrictModernHtmlDomParser::$lastModernDocument;
        static::assertNotNull($modernDocument);
        $this->assertModernNodePropertyExists($modernDocument, 'nodeType');
        $this->assertModernNodePropertyExists($modernDocument, 'childNodes');
        static::assertContains($modernDocument->nodeType, [\XML_DOCUMENT_NODE, \XML_DOCUMENT_FRAG_NODE]);
        static::assertNotNull($this->findFirstModernNodeByLocalName($modernDocument, 'template'));
        static::assertNotNull($this->findFirstModernNodeByLocalName($modernDocument, 'use'));

        static::assertSame(1, StrictModernHtmlDomParser::$modernCreateCalls);
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

    /**
     * @var int|null
     */
    public static $lastOptionsXml;

    protected function shouldUseModernHtmlDocument(int $optionsXml): bool
    {
        return true;
    }

    /**
     * @return object
     */
    protected function createModernHtmlDocument(string $html, int $optionsXml)
    {
        self::$lastOptionsXml = $optionsXml;

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
