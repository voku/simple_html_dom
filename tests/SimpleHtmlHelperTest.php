<?php

use PHPUnit\Framework\TestCase;

use voku\helper\HtmlDomHelper;
use voku\helper\HtmlDomParser;

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
}
