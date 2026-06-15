<?php

use voku\helper\HtmlDomParser;

/**
 * @internal
 */
final class HtmlDomParserLegacyCompatibilityTest extends \PHPUnit\Framework\TestCase
{
    public function testParserKeepsLegacyDomTypesAndLiveMutations(): void
    {
        $dom = HtmlDomParser::str_get_html('<main><p class="message">old</p></main>');

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

    public function testFileParsingKeepsTemplateAndSerializationCompatibility(): void
    {
        $filePath = \tempnam(\sys_get_temp_dir(), 'simple-html-dom-legacy-');
        static::assertNotFalse($filePath);
        \chmod($filePath, 0600);

        $html = '<!DOCTYPE html><html><body><template id="card"><section><h2>Title</h2><p>Body</p></section></template><main>After</main></body></html>';
        $expectedHtml = \str_replace('<!DOCTYPE html>', '<!DOCTYPE html>' . "\n", $html);
        \file_put_contents($filePath, $html);

        try {
            $dom = new HtmlDomParser();
            $dom->loadHtmlFile($filePath);

            static::assertSame($expectedHtml, $dom->html());
            static::assertSame('card', $dom->findOne('template')->getAttribute('id'));
            static::assertSame('<section><h2>Title</h2><p>Body</p></section>', $dom->findOne('template')->innerHTML);
            static::assertSame('After', $dom->findOne('main')->innerHTML);
        } finally {
            @\unlink($filePath);
        }
    }

    public function testParserPreservesSvgNamespacedAttributeAccess(): void
    {
        $dom = HtmlDomParser::str_get_html(
            '<div><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use xlink:href="#icon"></use></svg></div>'
        );

        static::assertSame('#icon', $dom->findOne('use')->getAttribute('xlink:href'));
        static::assertSame(
            '<div><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use xlink:href="#icon"></use></svg></div>',
            $dom->html()
        );
    }
}
