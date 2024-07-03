<?php

use PHPUnit\Framework\TestCase;

use voku\helper\HtmlDomHelper;

/**
 * @internal
 */
final class SimpleHtmlHelperTest extends TestCase
{
    public function testMergeHtmlAttributes()
    {
        $result = HtmlDomHelper::mergeHtmlAttributes(
            '<div class="foo" id="bar" data-foo="bar"></div>',
            'class="foo2" data-lall="foo"',
            '#bar'
        );

        self::assertSame('<div class="foo foo2" id="bar" data-foo="bar" data-lall="foo"></div>', $result);
    }

    public function testMergeHtmlAttributesWithZeroValues()
    {
        $result = HtmlDomHelper::mergeHtmlAttributes(
            '<input type="checkbox" class="foo" id="bar" data-foo="bar"></input>',
            'class="foo2" data-lall=0 value="0"',
            '#bar'
        );

        self::assertSame('<input type="checkbox" class="foo foo2" id="bar" data-foo="bar" data-lall="0" value="0">', $result);
    }
}
