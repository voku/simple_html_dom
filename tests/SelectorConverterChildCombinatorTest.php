<?php

use PHPUnit\Framework\TestCase;
use voku\helper\HtmlDomParser;
use voku\helper\SelectorConverter;

/**
 * @internal
 */
final class SelectorConverterChildCombinatorTest extends TestCase
{
    protected function setUp(): void
    {
        // Clear the compiled cache between tests via reflection
        $ref = new \ReflectionProperty(SelectorConverter::class, 'compiled');
        $ref->setValue(null, []);
    }

    // Unit tests: SelectorConverter::toXPath()

    public function testChildCombinatorSpan()
    {
        $xpath = SelectorConverter::toXPath('> span');
        static::assertSame('/*/span', $xpath);
    }

    public function testChildCombinatorStar()
    {
        $xpath = SelectorConverter::toXPath('> *');
        static::assertSame('/*/*', $xpath);
    }

    public function testNestedChildCombinator()
    {
        $xpath = SelectorConverter::toXPath('> p > span');
        static::assertSame('/*/p/span', $xpath);
    }

    public function testChildCombinatorWithClass()
    {
        $xpath = SelectorConverter::toXPath('> span.highlight');
        static::assertStringStartsWith('/*/', $xpath);
        static::assertStringContainsString('span', $xpath);
    }

    public function testEmptyCombinatorThrows()
    {
        $this->expectException(\RuntimeException::class);
        SelectorConverter::toXPath('> ');
    }

    public function testAdjacentSiblingXPath()
    {
        $xpath = SelectorConverter::toXPath('+ span');
        static::assertStringContainsString('following-sibling', $xpath);
        static::assertStringContainsString('self::span', $xpath);
    }

    public function testGeneralSiblingXPath()
    {
        $xpath = SelectorConverter::toXPath('~ span');
        static::assertStringContainsString('following-sibling', $xpath);
        static::assertStringContainsString('span', $xpath);
    }

    // Integration tests: element->find() with leading >

    public function testIssue102Reproduction()
    {
        $html = '<td class="text-right"><span>29.30</span><span> nodes</span></td>';
        $dom = HtmlDomParser::str_get_html($html);
        $td = $dom->findOne('td');

        $spans = $td->find('> span');
        static::assertCount(2, $spans);
        static::assertSame('29.30', $spans[0]->text());
        static::assertStringContainsString('nodes', $spans[1]->text());
    }

    public function testChildCombinatorOnlyMatchesDirectChildren()
    {
        $html = '<div><p><span>nested</span></p><span>direct</span></div>';
        $dom = HtmlDomParser::str_get_html($html);
        $div = $dom->findOne('div');

        $spans = $div->find('> span');
        static::assertCount(1, $spans);
        static::assertSame('direct', $spans[0]->text());
    }

    public function testChildCombinatorStarIntegration()
    {
        $html = '<div><p>para</p><span>span</span><em>em</em></div>';
        $dom = HtmlDomParser::str_get_html($html);
        $div = $dom->findOne('div');

        $children = $div->find('> *');
        static::assertCount(3, $children);
    }
}
