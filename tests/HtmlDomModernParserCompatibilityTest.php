<?php

use voku\helper\HtmlDomParser;

/**
 * @internal
 */
final class HtmlDomModernParserCompatibilityTest extends \PHPUnit\Framework\TestCase
{
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
        $expectedHtml = '<!DOCTYPE html>' . "\n" . '<html><body><template id="card"><section><h2>Title</h2><p>Body</p></section></template><main>After</main></body></html>';
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
}

class ForcedLegacyHtmlDomParser extends HtmlDomParser
{
    protected function shouldUseModernHtmlDocument(int $optionsXml): bool
    {
        return false;
    }
}

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
