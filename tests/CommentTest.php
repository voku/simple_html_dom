<?php

use PHPUnit\Framework\TestCase;
use Voku\Helper\HtmlDomParser;

/**
 * Checks if the parser properly handles comments
 *
 * copy&past from https://github.com/simplehtmldom/simplehtmldom/
 *
 * @internal
 */
final class CommentTest extends TestCase
{
    /**
     * @var HtmlDomParser|null
     */
    private $html;

    /**
     * @dataProvider dataProvider_for_comment_should_parse
     *
     * @param string $expected
     * @param string $doc
     *
     * @return void
     */
    public function testCommentShouldParse($expected, $doc)
    {
        $this->html = new HtmlDomParser();

        $this->html->load($doc);
        static::assertSame($expected, $this->html->find('//comment()', 0)->text(), 'tested: ' . $this->html->html());
        static::assertSame($doc, $this->html->save());
    }

    public function dataProvider_for_comment_should_parse()
    {
        return [
            'empty' => [
                '',
                '<!---->',
            ],
            'space' => [
                '',
                '<!-- -->',
            ],
            'brackets' => [
                ']][[',
                '<!--]][[-->',
            ],
            'html' => [
                '<p>Hello, World!</p>',
                '<!--<p>Hello, World!</p>-->',
            ],
            'cdata' => [
                '<![CDATA[Hello, World!]]>',
                '<!--<![CDATA[Hello, World!]]>-->',
            ],
            'newline' => [
                "Hello\nWorld!",
                "<!--Hello\nWorld!-->",
            ],
            'nested comment start tag' => [
                '<!--',
                '<!--<!---->',
            ],
            /* ?
            'reverse comment start tag' => [
                '--!>',
                '<!----!>-->',
            ],
            */
            'almost comment start tag' => [
                '<!-',
                '<!--<!--->',
            ],
        ];
    }

    public function testHtmlInsideCommentShouldNotAppearInTheDom()
    {
        $this->html = new HtmlDomParser();

        $this->html->load('<!-- <div>Hello, World!</div> -->');
        static::assertFalse($this->html->findOneOrFalse('div'));

        $this->html->load('<!--<div>Hello, World!</div>-->');
        static::assertFalse($this->html->findOneOrFalse('div'));

        $this->html->load('<!---<div>Hello, World!</div>-->');
        static::assertFalse($this->html->findOneOrFalse('div'));
    }
}
