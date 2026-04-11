<?php

use PHPUnit\Framework\TestCase;
use voku\helper\HtmlDomParser;
use voku\helper\SelectorConverter;

/**
 * Tests for compound CSS selectors ending with 'text' or 'comment'.
 *
 * @internal
 */
final class SelectorConverterTextCommentTest extends TestCase
{
    protected function setUp(): void
    {
        SelectorConverter::clearCompiledCache();
    }

    // --- Unit tests: SelectorConverter::toXPath() ---

    public function testBareTextSelector(): void
    {
        static::assertSame('//text()', SelectorConverter::toXPath('text'));
    }

    public function testBareCommentSelector(): void
    {
        static::assertSame('//comment()', SelectorConverter::toXPath('comment'));
    }

    public function testDescendantTextSelector(): void
    {
        $xpath = SelectorConverter::toXPath('div text');
        static::assertStringContainsString('div', $xpath);
        static::assertStringContainsString('//text()', $xpath);
    }

    public function testDescendantCommentSelector(): void
    {
        $xpath = SelectorConverter::toXPath('div comment');
        static::assertStringContainsString('div', $xpath);
        static::assertStringContainsString('//comment()', $xpath);
    }

    public function testChildTextSelector(): void
    {
        $xpath = SelectorConverter::toXPath('div > text');
        static::assertStringContainsString('div', $xpath);
        static::assertStringContainsString('/text()', $xpath);
        static::assertStringNotContainsString('//text()', $xpath);
    }

    public function testChildCommentSelector(): void
    {
        $xpath = SelectorConverter::toXPath('div > comment');
        static::assertStringContainsString('div', $xpath);
        static::assertStringContainsString('/comment()', $xpath);
        static::assertStringNotContainsString('//comment()', $xpath);
    }

    public function testGeneralSiblingTextSelector(): void
    {
        $xpath = SelectorConverter::toXPath('div ~ text');
        static::assertStringContainsString('following-sibling', $xpath);
        static::assertStringContainsString('text()', $xpath);
    }

    public function testAdjacentSiblingTextSelector(): void
    {
        $xpath = SelectorConverter::toXPath('div + text');
        static::assertStringContainsString('following-sibling', $xpath);
        static::assertStringContainsString('text()', $xpath);
    }

    public function testMultipleGroupsWithTextSelector(): void
    {
        $xpath = SelectorConverter::toXPath('div text, span text');
        static::assertStringContainsString('div', $xpath);
        static::assertStringContainsString('span', $xpath);
        static::assertStringContainsString('text()', $xpath);
        static::assertStringContainsString(' | ', $xpath);
    }

    public function testClassSelectorWithTextSuffix(): void
    {
        $xpath = SelectorConverter::toXPath('.foo text');
        static::assertStringContainsString('text()', $xpath);
        // .foo should still be converted (contains 'foo' class check)
        static::assertStringContainsString('foo', $xpath);
    }

    // --- Integration tests: HtmlDomParser::find() ---

    public function testFindDivTextReturnsTextNode(): void
    {
        $html = '<div> foo </div>';
        $dom = HtmlDomParser::str_get_html($html);

        $node = $dom->find('div text', 0);
        static::assertNotNull($node);
        static::assertNotFalse($node);
        static::assertStringContainsString('foo', $node->plaintext);
    }

    public function testFindDivTextWithWhitespace(): void
    {
        $html = '<div> foo </div>';
        $dom = HtmlDomParser::str_get_html($html);

        $nodes = $dom->find('div text');
        static::assertGreaterThan(0, \count($nodes));
    }

    public function testFindDivChildTextNode(): void
    {
        $html = '<div>direct<span>child</span></div>';
        $dom = HtmlDomParser::str_get_html($html);

        $nodes = $dom->find('div > text');
        static::assertGreaterThan(0, \count($nodes));
        // Only the direct text child, not "child" inside <span>
        static::assertSame('direct', \trim($nodes[0]->plaintext));
    }

    public function testFindNestedDescendantText(): void
    {
        $html = '<div><p> foo </p></div>';
        $dom = HtmlDomParser::str_get_html($html);

        // "div text" should find text nodes that are descendants of div
        $nodes = $dom->find('div text');
        static::assertGreaterThan(0, \count($nodes));
        static::assertStringContainsString('foo', $nodes[0]->plaintext);
    }

    public function testFindMultipleGroupsDivAndSpanText(): void
    {
        $html = '<div>hello</div><span>world</span>';
        $dom = HtmlDomParser::str_get_html($html);

        $nodes = $dom->find('div text, span text');
        static::assertCount(2, $nodes);
    }

    public function testIssue62Reproduction(): void
    {
        // Exact reproduction from issue #62: compound selector 'div text' must find
        // the DOMText node inside <div>, equivalent to find('div',0)->find('text',0).
        $html = '<div> foo </div>';

        $dom = HtmlDomParser::str_get_html($html);

        $compoundResult = $dom->find('div text', 0);
        $chainedResult = $dom->find('div', 0)->find('text', 0);

        static::assertNotNull($compoundResult);
        static::assertNotFalse($compoundResult);

        // Both approaches must return the same text content
        static::assertSame($chainedResult->plaintext, $compoundResult->plaintext);
    }
}
