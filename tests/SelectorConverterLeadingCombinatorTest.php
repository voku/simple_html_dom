<?php

use PHPUnit\Framework\TestCase;
use voku\helper\HtmlDomParser;
use voku\helper\SelectorConverter;

/**
 * @internal
 */
final class SelectorConverterLeadingCombinatorTest extends TestCase
{
    protected function setUp(): void
    {
        $compiledProperty = new ReflectionProperty(SelectorConverter::class, 'compiled');
        $compiledProperty->setAccessible(true);
        $compiledProperty->setValue(null, []);
    }

    public function testChildCombinatorSpanXPath(): void
    {
        static::assertSame('/*/span', SelectorConverter::toXPath('> span'));
    }

    public function testChildCombinatorPreservesNestedDescendantAxis(): void
    {
        static::assertSame(
            '/*/div/descendant-or-self::*/span',
            SelectorConverter::toXPath('> div span')
        );
    }

    public function testAdjacentSiblingCombinatorXPath(): void
    {
        static::assertSame(
            '/*/following-sibling::*[1]/self::span',
            SelectorConverter::toXPath('+ span')
        );
    }

    public function testGeneralSiblingCombinatorXPath(): void
    {
        static::assertSame('/*/following-sibling::span', SelectorConverter::toXPath('~ span'));
    }

    public function testLeadingCombinatorGroupedSelectorOnlyRewritesMatchingGroup(): void
    {
        static::assertSame(
            '/*/span | descendant-or-self::div',
            SelectorConverter::toXPath('> span, div')
        );
    }

    public function testChildCombinatorTextXPath(): void
    {
        static::assertSame('/*/text()', SelectorConverter::toXPath('> text'));
    }

    public function testEmptyLeadingCombinatorThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Selector cannot end with a combinator');

        SelectorConverter::toXPath('> ');
    }

    public function testChildCombinatorOnlyMatchesDirectChildren(): void
    {
        $html = '<div><span>direct</span><p><span>nested</span></p></div>';
        $dom = HtmlDomParser::str_get_html($html);

        $matches = $dom->findOne('div')->find('> span');

        static::assertCount(1, $matches);
        static::assertSame('direct', $matches[0]->text());
    }

    public function testChildCombinatorWithDescendantSelectorStaysScopedToDirectChild(): void
    {
        $html = '<section><div><span>hit</span></div><article><div><span>miss</span></div></article></section>';
        $dom = HtmlDomParser::str_get_html($html);

        $matches = $dom->findOne('section')->find('> div span');

        static::assertCount(1, $matches);
        static::assertSame('hit', $matches[0]->text());
    }

    public function testChildCombinatorTextOnlyMatchesDirectTextNodes(): void
    {
        $html = '<div>direct<span>child</span><p>nested</p></div>';
        $dom = HtmlDomParser::str_get_html($html);

        $matches = $dom->findOne('div')->find('> text');

        static::assertCount(1, $matches);
        static::assertSame('direct', trim($matches[0]->text()));
    }
}
