<?php

/*
 * HTML5 DOMDocument PHP library (extends DOMDocument)
 * https://github.com/ivopetkov/html5-dom-document-php
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

use voku\helper\HtmlDomParser;

/**
 * @runTestsInSeparateProcesses
 *
 * @internal
 */
final class HTML5DOMDocumentTest extends PHPUnit\Framework\TestCase
{
    /**
     * @group classList
     */
    public function testClassListAdd()
    {
        $dom = new HtmlDomParser();
        $dom->loadHtml('<html><body class="  a   b c b a c"></body></html>');

        $html = $dom->findOne('html');
        $html->class = 'abc';
        static::assertSame('abc', $html->getAttribute('class'));

        $body = $dom->findOne('body');
        $body->class .= ' a d';
        static::assertSame('  a   b c b a c a d', $body->getAttribute('class'));
    }

    /**
     * @group classList
     */
    public function testClassListContains()
    {
        $dom = new HtmlDomParser();
        $dom->loadHtml('<html><body class=" c aaa b  c  "></body></html>');

        $html = $dom->findOne('html');
        static::assertFalse($html->classList->contains('a'));

        $body = $dom->findOne('body');
        $classList = $body->classList;
        static::assertFalse($classList->contains('a'));
        static::assertTrue($classList->contains('aaa'));
        static::assertTrue($classList->contains('b'));
        static::assertTrue($classList->contains('c'));
        static::assertFalse($classList->contains('d'));
    }

    /**
     * @group classList
     */
    public function testClassListEntries()
    {
        $dom = new HtmlDomParser();
        $dom->loadHtml('<html><body class="  a   b c b a c"></body></html>');

        $text = '';
        $html = $dom->findOne('html');
        foreach ($html->classList->entries() as $class) {
            $text .= "[${class}]";
        }
        static::assertSame('', $text);

        $text = '';
        $body = $dom->findOne('body');
        foreach ($body->classList->entries() as $class) {
            $text .= "[${class}]";
        }
        static::assertSame('[a][b][c]', $text);
    }

    /**
     * @group classList
     */
    public function testClassListItem()
    {
        $dom = new HtmlDomParser();
        $dom = $dom->loadHtml('<html><body class="  a   b c b a c"></body></html>');

        $html = $dom->findOne('html');
        static::assertNotNull($html->classList);
        static::assertNull($html->classList->item(1));

        $body = $dom->findOne('body');
        static::assertSame('a b c', (string) $body->classList);
        static::assertSame('b', $body->classList->item(1));
        static::assertSame('c', $body->classList->item(2));
        static::assertNull($body->classList->item(3));
    }

    /**
     * @group classList
     */
    public function testClassListLength()
    {
        $dom = new HtmlDomParser();
        $dom->loadHtml('<html><body class="  a   b c b a c"></body></html>');

        $html = $dom->findOne('html');
        static::assertSame(0, $html->classList->length);

        $body = $dom->findOne('body');
        static::assertSame(3, $body->classList->length);
    }

    /**
     * @group classList
     */
    public function testClassListOverwrite()
    {
        $dom = new HtmlDomParser();
        $dom->loadHtml('<html><body class="a b c"></body></html>');

        $body = $dom->findOne('body');
        static::assertSame('a b c', (string) $body->classList);
        static::assertSame('a b c', $body->getAttribute('class'));

        $body->setAttribute('class', 'd e f');
        static::assertSame('d e f', (string) $body->classList);
        static::assertSame('d e f', $body->getAttribute('class'));

        $body->classList = 'g h i';
        static::assertSame('g h i', (string) $body->classList);
        static::assertSame('g h i', $body->getAttribute('class'));
    }

    /**
     * @group classList
     */
    public function testClassListRemove()
    {
        $dom = new HtmlDomParser();
        $dom->loadHtml('<html><body class="  a   b c b a c"></body></html>');

        $html = $dom->findOne('html');
        $html->classList->remove('a');
        static::assertSame('', $html->getAttribute('class'));

        $body = $dom->findOne('body');
        $body->classList->remove('a', 'd');
        static::assertSame('b c', $body->getAttribute('class'));
    }

    /**
     * @group classList
     */
    public function testClassListReplace()
    {
        $dom = new HtmlDomParser();
        $dom->loadHtml('<html><body class="  a   b c b a c"></body></html>');

        $html = $dom->findOne('html');
        $html->classList->replace('a', 'b');
        static::assertSame('', $html->getAttribute('class'));

        $body = $dom->findOne('body');
        $body->classList->replace('a', 'a');
        static::assertSame('  a   b c b a c', $body->getAttribute('class')); // since no change is made

        $body->classList->replace('a', 'b');
        static::assertSame('b c', $body->getAttribute('class'));

        $body->classList->replace('c', 'd');
        static::assertSame('b d', $body->getAttribute('class'));
    }

    /**
     * @group classList
     */
    public function testClassListToString()
    {
        $dom = new HtmlDomParser();
        $dom->loadHtml('<html><body class="  a   b c b a c"></body></html>');

        $html = $dom->findOne('html');
        static::assertSame('', (string) $html->classList);

        $body = $dom->findOne('body');
        static::assertSame('a b c', (string) $body->classList);
    }

    /**
     * @group classList
     */
    public function testClassListToggle()
    {
        $dom = new HtmlDomParser();
        $dom->loadHtml('<html><body class="  a   b c b a c"></body></html>');

        $html = $dom->findOne('html');
        $isThere = $html->classList->toggle('a');
        static::assertTrue($isThere);
        static::assertSame('a', $html->getAttribute('class'));

        $body = $dom->findOne('body');
        $isThere = $body->classList->toggle('a');
        static::assertFalse($isThere);
        static::assertSame('b c', $body->getAttribute('class'));

        $isThere = $body->classList->toggle('d');
        static::assertTrue($isThere);
        static::assertSame('b c d', $body->getAttribute('class'));
    }

    /**
     * @group classList
     */
    public function testClassListToggleForce()
    {
        $dom = new HtmlDomParser();
        $dom->loadHtml('<html><body class="  a   b c b a c"></body></html>');

        $html = $dom->findOne('html');
        $isThere = $html->classList->toggle('a', false);
        static::assertFalse($isThere);
        static::assertSame('', $html->getAttribute('class'));
        $isThere = $html->classList->toggle('a', true);
        static::assertTrue($isThere);
        static::assertSame('a', $html->getAttribute('class'));
        $isThere = $html->classList->toggle('a', true);
        static::assertTrue($isThere);
        static::assertSame('a', $html->getAttribute('class'));

        $body = $dom->findOne('body');
        $isThere = $body->classList->toggle('a', false);
        static::assertFalse($isThere);
        static::assertSame('b c', $body->getAttribute('class'));
        $isThere = $body->classList->toggle('a', false);
        static::assertFalse($isThere);
        static::assertSame('b c', $body->getAttribute('class'));
        $isThere = $body->classList->toggle('b', true);
        static::assertTrue($isThere);
        static::assertSame('b c', $body->getAttribute('class'));
    }

    /**
     * @group classList
     */
    public function testClassListUndefinedProperty()
    {
        $this->expectException(\Exception::class);

        $dom = new HtmlDomParser();
        $dom->loadHtml('<html><body class="  a   b c b a c"></body></html>');

        $html = $dom->findOne('html');
        $html->classList->someProperty;
    }

    /**
     * @group classList
     */
    public function testClassListValue()
    {
        $dom = new HtmlDomParser();
        $dom->loadHtml('<html><body class="  a   b c b a c"></body></html>');

        $html = $dom->findOne('html');
        static::assertSame('', $html->classList->value);

        $body = $dom->findOne('body');
        static::assertSame('a b c', $body->classList->value);
    }

    public function testCompatibilityWithDOMDocument()
    {
        $compareDOMs = static function (HtmlDomParser $dom1, DOMDocument $dom2) {
            static::assertSame($dom1->getElementsByTagName('p')->length, $dom2->getElementsByTagName('head')->length);
            static::assertSame($dom1->getElementsByTagName('span')->length, $dom2->getElementsByTagName('body')->length);

            $updateNewLines = static function (&$content) {
                $content = \str_replace("<!DOCTYPE html>\n", '', $content);
                $content = \str_replace("\n<head>", '<head>', $content);
                $content = \str_replace("\n<body>", '<body>', $content);
                $content = \str_replace("\n</html>", '</html>', $content);
                $content = \rtrim($content, "\n");
            };

            $result1 = $dom1->html();
            $result2 = $dom2->saveHTML();
            $result2 = \preg_replace('/\<\!DOCTYPE(.*?)\>/', '<!DOCTYPE html>', $result2);
            $updateNewLines($result1);
            $updateNewLines($result2);
            static::assertSame($result1, $result2);

            if ($dom1->getElementsByTagName('html')->length > 0 && $dom2->getElementsByTagName('html')->length > 0) {
                $html1 = $dom1->html($dom1->getElementsByTagName('html')[0]);
                $html2 = $dom2->saveHTML($dom2->getElementsByTagName('html')[0]);
                $updateNewLines($html1);
                $updateNewLines($html2);
                static::assertSame($html1, $html2);
            }

            if ($dom1->getElementsByTagName('body')->length > 0 && $dom2->getElementsByTagName('body')->length > 0) {
                $body1 = $dom1->html($dom1->getElementsByTagName('body')[0]);
                $body2 = $dom2->saveHTML($dom2->getElementsByTagName('body')[0]);
                static::assertSame($body1, $body2);

                if ($dom1->getElementsByTagName('body')[0]->firstChild !== null) {
                    $firstChild1 = $dom1->html($dom1->getElementsByTagName('body')[0]->firstChild);
                    $firstChild2 = $dom2->saveHTML($dom2->getElementsByTagName('body')[0]->firstChild);
                    static::assertSame($firstChild1, $firstChild2);
                }
            }
        };

        $compareContent = static function ($content) use ($compareDOMs) {
            $dom = new HtmlDomParser();
            $dom->loadHtml($content);
            $dom2 = new DOMDocument();
            $dom2->loadHtml($content);
            $compareDOMs($dom, $dom2);
        };

        $content = '<div>hello</div>';
        $dom = new HtmlDomParser();
        $dom->loadHtml($content, \LIBXML_HTML_NOIMPLIED);
        $dom2 = new DOMDocument();
        $dom2->loadHtml($content, \LIBXML_HTML_NOIMPLIED);
        $compareDOMs($dom, $dom2);
    }

    public function testComplexfinds()
    {
        $dom = new HtmlDomParser();
        $dom->loadHtml(
            '<html><body>'
            . '<span>text1</span>'
            . '<span>text2</span>'
            . '<span>text3</span>'
            . '<div><span>text4</span></div>'
            . '<div id="id,1">text5</div>'
            . '<a href="#">text6</a>'
            . '<div"><a href="#">text7</a></div>'
            . '</body></html>'
        );

        static::assertSame($dom->findMulti('span, div')->length, 7); // 4 spans + 3 divs
        static::assertSame($dom->findMulti('span, [id="id,1"]')->length, 5); // 4 spans + 1 div
        static::assertSame($dom->findMulti('div, [id="id,1"]')->length, 3); // 3 divs

        static::assertSame($dom->findMulti('body div')->length, 3);
        static::assertSame($dom->findMulti('body a')->length, 2);

        static::assertSame($dom->findMulti('body > a')->length, 1);
        static::assertSame($dom->findOne('body > a')->innerHTML, 'text6');
        static::assertSame($dom->findMulti('div > a')->length, 1);
        static::assertSame($dom->findOne('div > a')->innerHTML, 'text7');

        static::assertSame($dom->findMulti('span + span')->length, 2);
        static::assertSame($dom->findMulti('span + span')[0]->innerHTML, 'text2');
        static::assertSame($dom->findMulti('span + span')[1]->innerHTML, 'text3');

        static::assertSame($dom->findMulti('span ~ div')->length, 3);
    }

    /**
     * Tests multiple query selectors matching. If a query selector is not greedy problems may arise.
     */
    public function testComplexfinds2()
    {
        $dom = new HtmlDomParser();
        $dom->loadHtml(
            '<body>'
            . '<div class="a1">1</div>'
            . '<div class="a2">2</div>'
            . '<div class="a3">3</div>'
            . '</body>'
        );
        $elements = $dom->findMulti('.a1,.a2,.a3');
        static::assertSame($elements->length, 3);
    }

    public function testDuplicateElementIDsException()
    {
        $content = '<div id="key1">1</div><div><div id="key1">2</div></div>';
        $dom = new HtmlDomParser();
        $dom->loadHtml($content);

        static::assertSame('<div id="key1">1</div>' . "\n" . '<div><div id="key1">2</div></div>', $dom->html());
    }

    public function testDuplicateElementIDsQueries()
    {
        $content = '<div id="key1">1</div><div id="key1">2</div><div id="key1">3</div><div id="keyA">A</div>';
        $dom = new HtmlDomParser();
        $dom->loadHtml($content);
        static::assertSame($dom->getElementById('key1')->innerHTML, '1');
        static::assertSame($dom->findOne('[id="key1"]')->innerHTML, '1');
        static::assertSame($dom->findMulti('[id="key1"]')->length, 3);
        static::assertSame($dom->findMulti('[id="key1"]')[0]->innerHTML, '1');
        static::assertSame($dom->findMulti('[id="key1"]')[1]->innerHTML, '2');
        static::assertSame($dom->findMulti('[id="key1"]')[2]->innerHTML, '3');
    }

    public function testElementfind()
    {
        $dom = new HtmlDomParser();
        $dom->loadHtml(
            '<html><head>'
            . '<link rel="icon" type="image/png" href="/favicon-16x16.png" sizes="16x16">'
            . '<link rel="icon" type="image/png" href="/favicon-32x32.png" sizes="32x32"></head>'
            . '<body><div id="container">'
            . '<div id="text1" class="class1">text1</div>'
            . '<div>text2</div>'
            . '<div>'
            . '<div class="class3 class1">text3</div>'
            . '</div>'
            . '<my-custom-element class="class5 class1">text5</my-custom-element>'
            . '<span id="text4" class="class1 class2">text4</div>'
            . '</div></body></html>'
        );

        static::assertSame(
            ['text1'],
            $dom->findOne('#container')->find('#text1')->text()
        );

        static::assertSame(
            7,
            $dom->findOne('#container')->findMulti('*')->length
        ); // 4 divs + 1 custom element + 1 span + 1 wrapper

        static::assertSame(
            5,
            $dom->findOne('#container')->findMulti('div')->length
        ); // 5 divs

        static::assertSame($dom->findOne('#container')->findMulti('#text1')->length, 1);
        static::assertSame(
            $dom->findOne('#container')
                ->findOne('#text1')
                ->innerHTML,
            'text1'
        );
        static::assertSame($dom->findOne('#container')->findMulti('.class3')->length, 1);
        static::assertSame(
            $dom->findOne('#container')
                ->findOne('.class3')
                ->innerHTML,
            'text3'
        );
        static::assertSame($dom->findOne('#container')->findMulti('[class~="class3"]')->length, 1);
        static::assertSame(
            $dom->findOne('#container')
                ->findOne('[class~="class3"]')
                ->innerHTML,
            'text3'
        );
        static::assertSame($dom->findOne('#container')->findMulti('[class|="class1"]')->length, 1);
        static::assertSame(
            $dom->findOne('#container')
                ->findOne('[class|="class1"]')
                ->innerHTML,
            'text1'
        );
        static::assertSame($dom->findOne('#container')->findMulti('[class^="class3"]')->length, 1);
        static::assertSame(
            $dom->findOne('#container')
                ->findOne('[class^="class3"]')
                ->innerHTML,
            'text3'
        );
        static::assertSame($dom->findOne('#container')->findMulti('[class$="class2"]')->length, 1);
        static::assertSame(
            $dom->findOne('#container')
                ->findMulti('[class$="class2"]')
                ->innerHTML,
            ['text4']
        );
        static::assertSame($dom->findOne('#container')->findMulti('[class*="ss3"]')->length, 1);
        static::assertSame(
            $dom->findOne('#container')
                ->findMulti('[class*="ss3"]')
                ->innerHTML,
            ['text3']
        );
        static::assertSame(
            $dom->findOne('#container')
                ->findMulti('div#text1')
                ->innerHTML,
            ['text1']
        );
        static::assertSame(
            $dom->findOne('#container')
                ->findMulti('span#text4')
                ->innerHTML,
            ['text4']
        );
        static::assertSame(
            $dom->findOne('#container')
                ->findMulti('[id="text4"]')
                ->innerHTML,
            ['text4']
        );
        static::assertSame(
            $dom->findOne('#container')
                ->findMulti('span[id="text4"]')
                ->innerHTML,
            ['text4']
        );
        static::assertSame($dom->findOne('#container')->findMulti('div#text4')->length, 0);
        static::assertSame($dom->findOne('#container')->findMulti('div.class1')->length, 2);
        static::assertSame($dom->findOne('#container')->findMulti('.class1')->length, 4);
        static::assertSame($dom->findOne('#container')->findMulti('div.class2')->length, 0);
        static::assertSame($dom->findOne('#container')->findMulti('span.class2')->length, 1);
        static::assertSame($dom->findOne('#container')->findMulti('my-custom-element')->length, 1);
        static::assertSame(
            $dom->findOne('#container')
                ->findMulti('my-custom-element.class5')->length,
            1
        );
        static::assertSame(
            $dom->findOne('#container')
                ->findOne('my-custom-element.class5')
                ->innerHTML,
            'text5'
        );

        static::assertSame($dom->findOne('#container')->findMulti('unknown')->length, 0);
        static::assertFalse($dom->findOne('#container')->findOneOrFalse('unknown'));
        static::assertSame($dom->findOne('#container')->findMulti('#unknown')->length, 0);
        static::assertFalse($dom->findOne('#container')->findMultiOrFalse('#unknown'));
        static::assertSame($dom->findOne('#container')->findMulti('.unknown')->length, 0);
        static::assertFalse($dom->findOne('#container')->findOneOrFalse('.unknown'));

        $multi = $dom->findMulti('link[rel="icon"]');
        static::assertSame(2, $multi->count());
        static::assertCount(2, $multi);

        static::assertSame(
            '/favicon-16x16.png',
            $dom->findOne('link[rel="icon"]')
                                      ->getAttribute('href')
        );
        static::assertSame(
            '/favicon-32x32.png',
            $dom->find('link[rel="icon"]', 1)
                                      ->getAttribute('href')
        );
        static::assertSame(
            '/favicon-16x16.png',
            $dom->findOne('link[rel="icon"][sizes="16x16"]')
                                      ->getAttribute('href')
        );

        $multi = $dom->findMulti('link[rel="icon"][sizes="999"]');
        static::assertSame(0, $multi->count());
        static::assertCount(0, $multi);

        static::assertFalse($dom->findMultiOrFalse('link[rel="icon"][sizes="999"]'));
    }

    public function testEmpty()
    {
        $testSource = static function ($source, $expectedSource) {
            $dom = new HtmlDomParser();
            $dom->loadHtml($source);
            static::assertSame($expectedSource, $dom->html());
        };

        $source = '<!DOCTYPE html>' . "\n" . '<html><head></head><body></body></html>';
        $testSource($source, $source);
        $source = '<!DOCTYPE html>' . "\n" . '<html><body></body></html>';
        $testSource($source, $source);
        $source = '<!DOCTYPE html>' . "\n" . '<html><head></head></html>';
        $testSource($source, $source);
        $source = '<!DOCTYPE html>' . "\n" . '<html></html>';
        $testSource($source, $source);
        $source = '<!DOCTYPE html>';
        $testSource($source, $source);

        $testSource('', '');
    }

    public function testFragments()
    {
        $fragments = [
            '<div>text</div>',
            '<p>text</p>',
            '<script type="text/javascript">var a = 1;</script>',
        ];
        foreach ($fragments as $fragment) {
            $dom = new HtmlDomParser();
            $dom->loadHtml($fragment, \LIBXML_HTML_NOIMPLIED | \LIBXML_HTML_NODEFDTD);
            static::assertSame($dom->findMulti('*')->length, 1);
            static::assertSame($fragment, $dom->html());
        }
    }

    public function testGetAttributes()
    {
        $dataAttributeValue = '&quot;<>&*;';
        $expectedDataAttributeValue = '&quot;<>&*;';
        $dom = new HtmlDomParser();
        $dom->loadHtml(
            '<html><body>'
            . '<div class="text1" data-value="' . $dataAttributeValue . '">text1</div>'
            . '</body></html>'
        );

        static::assertSame($dom->findOne('div')->getAttribute('class'), 'text1');
        static::assertSame($dom->findOne('div')->getAttribute('unknown'), '');
        static::assertSame($dom->findOne('div')->getAttribute('data-value'), $expectedDataAttributeValue);
        $attributes = $dom->findOne('div')->getAllAttributes();
        static::assertSame(\count($attributes), 2);
        static::assertSame($attributes['class'], 'text1');
    }

    public function testHtmlEntities()
    {
        $attributeContent = '&quot;&#8595; &amp;';
        $bodyContent = '<div data-value="' . $attributeContent . '"> &#8595; &amp; &quot; &Acirc; &rsaquo;&rsaquo;&Acirc; </div>';
        $expectedSource = '<div data-value="&quot;&#8595; &amp;"> &#8595; &amp; &quot; &Acirc; &rsaquo;&rsaquo;&Acirc; </div>';
        $dom = new HtmlDomParser();
        $dom->loadHtml($bodyContent);
        static::assertSame($expectedSource, $dom->html());
        static::assertSame(
            $attributeContent,
            $dom->findOne('div')->getAttribute('data-value')
        );
        $dom->findOne('div')->setAttribute('data-value', $attributeContent);
        static::assertSame($attributeContent, $dom->findOne('div')->getAttribute('data-value'));
    }

    public function testInnerHTML()
    {
        $dom = new HtmlDomParser();
        $dom->loadHtml(
            '<html><body>'
            . '<div>text1</div>'
            . '</body></html>'
        );

        static::assertSame($dom->findOne('body')->innerHTML, '<div>text1</div>');

        $dom = new HtmlDomParser();
        $dom->loadHtml('<div>text1</div>');
        $element = $dom->findOne('div');
        $element->innerHTML = 'text2';
        static::assertSame(
            '<div>text2</div>',
            $dom->html()
        );

        $dom = new HtmlDomParser();
        $dom->loadHtml('<div>text1</div>');
        $element = $dom->findOne('div');
        $element->innerHTML = '<div>text1<div>text2</div></div>';
        static::assertTrue(
            $dom->html() === '<div><div>text1<div>text2</div>
</div></div>' || $dom->html() === '<div><div>text1<div>text2</div></div></div>'
        );
    }

    public function testLIBXMLHTMLNODEFDTD()
    {
        $content = '<div>hello</div>';
        $dom = new HtmlDomParser();
        $dom->loadHtml($content, \LIBXML_HTML_NODEFDTD);
        static::assertSame($content, $dom->html());
    }

    public function testLIBXMLHTMLNOIMPLIED()
    {
        $content = '<div>hello</div>';
        $dom = new HtmlDomParser();
        $dom->loadHtml($content, \LIBXML_HTML_NOIMPLIED);
        static::assertSame($dom->getElementsByTagName('html')->length, 0);
        static::assertSame($dom->getElementsByTagName('head')->length, 0);
        static::assertSame($dom->getElementsByTagName('body')->length, 0);
        static::assertSame($content, $dom->html());
    }

    public function testNbspAndWhiteSpace()
    {
        $bodyContent = '<div> &nbsp; &nbsp; &nbsp; </div>'
                       . '<div> &nbsp;&nbsp;&nbsp; </div>'
                       . '<div> &nbsp; <span>&nbsp;</span></div>'
                       . '<div>text1 text2 </div>';
        $expectedSource = '<div> &nbsp; &nbsp; &nbsp; </div>
<div> &nbsp;&nbsp;&nbsp; </div>
<div> &nbsp; <span>&nbsp;</span>
</div>
<div>text1 text2 </div>';
        $dom = new HtmlDomParser();
        $dom->loadHtml($bodyContent);
        static::assertSame($expectedSource, $dom->html());
    }

    public function testOmitedElements()
    {
        $testSource = static function ($source, $expectedSource) {
            $dom = new HtmlDomParser();
            $dom->loadHtml($source);
            static::assertSame($expectedSource, $dom->html());
        };

        $bodyContent = '<div>hello</div>';

        $expectedSource = '<!DOCTYPE html>' . "\n" . '<html><body>' . $bodyContent . '</body></html>';
        $testSource('<!DOCTYPE html><html><body>' . $bodyContent . '</body></html>', $expectedSource);
        $expectedSource = '<html><body>' . $bodyContent . '</body></html>';
        $testSource('<html><body>' . $bodyContent . '</body></html>', $expectedSource);
        $expectedSource = '<body>' . $bodyContent . '</body>';
        $testSource('<body>' . $bodyContent . '</body>', $expectedSource);
        $expectedSource = $bodyContent;
        $testSource($bodyContent, $expectedSource);

        $headContent = '<script>alert(1);</script>';

        $expectedSource = '<!DOCTYPE html>' . "\n" . '<html><head>' . $headContent . '</head></html>';
        $testSource('<!DOCTYPE html><html><head>' . $headContent . '</head></html>', $expectedSource);
        $expectedSource = '<html><head><script>alert(1);</script></head></html>';
        $testSource('<html><head>' . $headContent . '</head></html>', $expectedSource);
        $expectedSource = '<head><script>alert(1);</script></head>';
        $testSource('<head>' . $headContent . '</head>', $expectedSource);
    }

    public function testWithoutPTag()
    {
        $dom = new HtmlDomParser();
        $dom->loadHtml(
            '<html><body>'
            . '<p>text1</p>'
            . '</body></html>'
        );

        static::assertSame('<p>text1</p>', $dom->findOne('p')->outerHTML);
        static::assertSame('<html><body><p>text1</p></body></html>', $dom->html());

        // ---

        $dom = new HtmlDomParser();
        $dom->loadHtml(
            '<html><body>'
            . '<p class="foo">text1</p>'
            . '</body></html>'
        );

        static::assertSame('<p class="foo">text1</p>', $dom->findOne('p')->outerHTML);
        static::assertSame('<html><body><p class="foo">text1</p></body></html>', $dom->html());
    }

    public function testOuterHTML()
    {
        $dom = new HtmlDomParser();
        $dom->loadHtml(
            '<html><body>'
            . '<div>text1</div><span title="hi"></span><br/>'
            . '</body></html>'
        );

        static::assertSame($dom->findOne('div')->outerHTML, '<div>text1</div>');
        static::assertSame((string) $dom->findOne('div'), '<div>text1</div>');

        static::assertSame($dom->findOne('span')->outerHTML, '<span title="hi"></span>');
        static::assertSame((string) $dom->findOne('span'), '<span title="hi"></span>');

        static::assertSame($dom->findOne('br')->outerHTML, '<br>');
        static::assertSame((string) $dom->findOne('br'), '<br>');

        $dom = new HtmlDomParser();
        $dom->loadHtml('<div>text1</div>');
        $element = $dom->findOne('div');
        $element->outerHTML = 'text2';
        static::assertSame('text2', $dom->html());

        $dom = new HtmlDomParser();
        $dom->loadHtml('<div>text1</div>');
        $element = $dom->findOne('div');
        $element->outerHTML = '<div>text2<div>text3</div></div>';
        static::assertTrue($dom->html() === '<div>text2<div>text3</div>
</div>' || $dom->html() === '<div>text2<div>text3</div></div>');
    }

    public function testSpecialCharsInScriptTags()
    {
        $js1 = 'var f1=function(t){
            return t.replace(/</g,"&lt;").replace(/>/g,"&gt;");
        };';
        $js2 = 'var f2=function(t){
            return t.replace(/</g,"&lt;").replace(/>/g,"&gt;");
        };';
        $content = '<html><head><script src="url"/><script type="text/javascript">' . $js1 . '</script><script>' . $js2 . '</script></head></html>';
        $dom = new HtmlDomParser();
        $dom->useKeepBrokenHtml(true);
        $dom->loadHtml($content);
        $scripts = $dom->findMulti('script');
        static::assertSame($scripts[0]->innerHTML, '');
        static::assertSame($scripts[1]->innerHTML, $js1);
        static::assertSame($scripts[2]->innerHTML, $js2);
        $expected = '<html><head><script src="url"></script><script type="text/javascript">var f1=function(t){
            return t.replace(/</g,"&lt;").replace(/>/g,"&gt;");
        };</script><script>var f2=function(t){
            return t.replace(/</g,"&lt;").replace(/>/g,"&gt;");
        };</script></head></html>';
        static::assertSame($expected, $dom->html());
    }

    public function testUTF()
    {
        $bodyContent = '<div>hello</div>' . "\n" . '<div>здравей</div>' . "\n" . '<div>你好</div>';
        $dom = new HtmlDomParser();
        $dom->loadHtml($bodyContent);
        static::assertSame($bodyContent, $dom->html());
    }

    public function testfind()
    {
        $dom = new HtmlDomParser();
        $dom->loadHtml(
            '<html><body>'
            . '<h1>text0</h1>'
            . '<div id="text1" class="class1">text1</div>'
            . '<div>text2</div>'
            . '<div>'
            . '<div class="text3 class1">text3</div>'
            . '</div>'
            . '<my-custom-element class="text5 class1">text5</my-custom-element>'
            . '<span id="text4" class="class1 class2">text4</div>'
            . '</body></html>'
        );

        static::assertSame($dom->findOne('#text1')->innerHTML, 'text1');

        static::assertSame($dom->findMulti('*')->length, 9); // html + body + 1 h1 + 4 divs + 1 custom element + 1 span
        static::assertSame($dom->findOne('h1')->innerHTML, 'text0');
        static::assertSame($dom->findMulti('div')->length, 4); // 4 divs
        static::assertSame($dom->findMulti('#text1')->length, 1);
        static::assertSame($dom->findMulti('#text1')->innerHTML, ['text1']);
        static::assertSame($dom->findMulti('.text3')->length, 1);
        static::assertSame($dom->findMulti('.text3')->innerHTML, ['text3']);
        static::assertSame($dom->findMulti('div#text1')->innerHTML, ['text1']);
        static::assertSame($dom->findMulti('span#text4')->innerHTML, ['text4']);
        static::assertSame($dom->findMulti('[id="text4"]')->innerHTML, ['text4']);
        static::assertSame($dom->findMulti('span[id="text4"]')->innerHTML, ['text4']);
        static::assertSame($dom->findMulti('[id]')->innerHTML, ['text1', 'text4']);
        static::assertSame($dom->findMulti('[id]')->length, 2);
        static::assertSame($dom->findMulti('span[id]')->innerHTML, ['text4']);
        static::assertSame($dom->findMulti('span[data-other]')->length, 0);
        static::assertSame($dom->findMulti('div#text4')->length, 0);
        static::assertSame($dom->findMulti('div.class1')->length, 2);
        static::assertSame($dom->findMulti('.class1')->length, 4);
        static::assertSame($dom->findMulti('.class1.class2')->length, 1);
        static::assertSame($dom->findMulti('.class2.class1')->length, 1);
        static::assertSame($dom->findMulti('div.class2')->length, 0);
        static::assertSame($dom->findMulti('span.class2')->length, 1);
        static::assertSame($dom->findMulti('my-custom-element')->length, 1);
        static::assertSame($dom->findMulti('my-custom-element.text5')->length, 1);
        static::assertSame($dom->findMulti('my-custom-element.text5')->innerHTML, ['text5']);

        static::assertSame($dom->findMulti('unknown')->length, 0);
        static::assertFalse($dom->findMultiOrFalse('unknown'));
        static::assertSame($dom->findMulti('#unknown')->length, 0);
        static::assertFalse($dom->findMultiOrFalse('#unknown'));
        static::assertSame($dom->findMulti('.unknown')->length, 0);
        static::assertFalse($dom->findMultiOrFalse('.unknown'));
    }

    public function testHtml()
    {
        $testSource = static function ($source, $expectedSource) {
            $dom = new HtmlDomParser();
            $dom->loadHtml($source);
            static::assertSame($expectedSource, $dom->html());
        };

        $bodyContent = '<div>hello</div>';

        $source = '<!DOCTYPE html>' . "\n" . '<html><body>' . $bodyContent . '</body></html>';
        $testSource($source, $source);

        $source = '<!DOCTYPE html>' . "\n" . '<html><head></head><body>' . $bodyContent . '</body></html>';
        $testSource($source, $source);

        // test custom attributes
        //$source = '<!DOCTYPE html>' . "\n" . '<html custom-attribute="1"><head custom-attribute="2"></head><body custom-attribute="3">' . $bodyContent . '</body></html>';
        //$testSource($source, $source);

        $dom = new HtmlDomParser();
        // without loading anything
        static::assertSame('', $dom->html());
    }

    public function testhtmlForNodes()
    {
        // A custom html tags makes the default html function return more whitespaces
        $html = '<html><head><component><script src="url1"/><script src="url2"/></component></head><body><div><component><ul><li><a href="#">Link 1</a></li><li><a href="#">Link 2</a></li></ul></component></div>';

        $dom = new HtmlDomParser();
        $dom->loadHtml($html);

        $expectedOutput = '<html>
<head><component><script src="url1"></script><script src="url2"></script></component></head>
<body><div><component><ul>
<li><a href="#">Link 1</a></li>
<li><a href="#">Link 2</a></li>
</ul></component></div></body>
</html>';
        static::assertSame($expectedOutput, $dom->html());

        $expectedOutput = '<body><div><component><ul>
<li><a href="#">Link 1</a></li>
<li><a href="#">Link 2</a></li>
</ul></component></div></body>';
        static::assertSame($expectedOutput, $dom->findOne('div')->parentNode()->html());

        $expectedOutput = '<html>
<head><component><script src="url1"></script><script src="url2"></script></component></head>
<body><div><component><ul>
<li><a href="#">Link 1</a></li>
<li><a href="#">Link 2</a></li>
</ul></component></div></body>
</html>';
        static::assertSame($expectedOutput, $dom->findOne('div')->parentNode()->parentNode()->html());

        $div = $dom->findOne('div');
        $a = $div->findOne('a');
        static::assertSame('Link 1', $a->innertext);

        $expectedOutput = '<script src="url1"></script>';
        static::assertSame($expectedOutput, $dom->findOne('script')->html);

        $expectedOutput = '<component><script src="url1"></script><script src="url2"></script></component>';
        static::assertSame($expectedOutput, $dom->findOne('script')->parentNode()->html());

        $expectedOutput = '<head><component><script src="url1"></script><script src="url2"></script></component></head>';
        static::assertSame($expectedOutput, $dom->findOne('script')->parentNode()->parentNode()->html);

        $expectedOutput = '<html>
<head><component><script src="url1"></script><script src="url2"></script></component></head>
<body><div><component><ul>
<li><a href="#">Link 1</a></li>
<li><a href="#">Link 2</a></li>
</ul></component></div></body>
</html>';
        static::assertSame($expectedOutput, $dom->findOne('script')->parentNode()->parentNode()->parentNode()->html);
    }
}
