<?php

use voku\helper\HtmlDomParser;

/**
 * @internal
 */
final class HtmlDomModernParserCompatibilityTest extends \PHPUnit\Framework\TestCase
{
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

        if (ForcedModernHtmlDomParser::supportsModernPath()) {
            $parserClasses['modern parser path'] = [ForcedModernHtmlDomParser::class];
        }

        return $parserClasses;
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

    public function testModernPathInvokesModernDocumentCreationWhenAvailable(): void
    {
        if (!TrackingModernHtmlDomParser::supportsModernPath()) {
            static::markTestSkipped('Dom\\HTMLDocument is not available on this runtime.');
        }

        TrackingModernHtmlDomParser::$modernCreateCalls = 0;

        TrackingModernHtmlDomParser::str_get_html('<div><template><p>ok</p></template></div>');

        static::assertSame(1, TrackingModernHtmlDomParser::$modernCreateCalls);
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
        $dom = ThrowingModernHtmlDomParser::str_get_html('<main><p class="message">old</p></main>');

        static::assertInstanceOf(\DOMDocument::class, $dom->getDocument());
        static::assertSame('old', $dom->findOne('.message')->text());
        static::assertStringContainsString('<p class="message">old</p>', $dom->html());
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
 * @internal Test double that forces the PHP 8.4+ modern parser path when available.
 */
class ForcedModernHtmlDomParser extends HtmlDomParser
{
    public static function supportsModernPath(): bool
    {
        return \class_exists('Dom\\HTMLDocument')
            && \method_exists('Dom\\HTMLDocument', 'createFromString');
    }

    protected function shouldUseModernHtmlDocument(int $optionsXml): bool
    {
        return self::supportsModernPath();
    }
}

/**
 * @internal Test double that records modern parser invocations.
 */
class TrackingModernHtmlDomParser extends ForcedModernHtmlDomParser
{
    /**
     * @var int
     */
    public static $modernCreateCalls = 0;

    protected function createLegacyDocumentFromModernParser(string $html, int $optionsXml): \DOMDocument
    {
        ++self::$modernCreateCalls;

        return parent::createLegacyDocumentFromModernParser($html, $optionsXml);
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
    protected function shouldUseModernHtmlDocument(int $optionsXml): bool
    {
        return true;
    }

    protected function createLegacyDocumentFromModernParser(string $html, int $optionsXml): \DOMDocument
    {
        throw new \RuntimeException('boom');
    }
}
