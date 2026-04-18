<?php


use PHPUnit\Framework\TestCase;
use voku\helper\HtmlDomParser;

final class AuxiliarFunctionsTest extends TestCase
{
    /**
     * @return array{0: \voku\helper\HtmlDomParser, 1: \voku\helper\SimpleHtmlDomInterface}
     */
    private function createNestedFindFixture(): array
    {
        $parser = HtmlDomParser::str_get_html(
            '<html><body><p>before</p><img src="x.jpg"><p>after</p></body><footer><img src="y.jpg"></footer></html>'
        );

        return [$parser, $parser->findOne('body')];
    }

    public function testGetTag()
    {
        $parser= HtmlDomParser::str_get_html('<body><span>Hello</span></body>');
        static::assertSame('span', $parser->findOne('span')->getTag());
    }

    public function testRemoveAttributes()
    {
        $parser= HtmlDomParser::str_get_html('<body><span id=\'hello\' class=\'hello\'>Hello</span></body>');
        static::assertSame('<span>Hello</span>', $parser->findOne('span')->removeAttributes()->outerHtml());
    }

    public function testRemoveUsingDelete()
    {
        $parser= HtmlDomParser::str_get_html("<body><span id='hello' class='hello'>Hello</span></body>");
        $parser->findOne('span')->delete();
        static::assertSame('<body></body>', $parser->outerHtml());
    }

    public function testRemoveUsingDeleteFromNestedFind()
    {
        [$parser, $body] = $this->createNestedFindFixture();

        $body->findOne('img')->delete();

        static::assertSame(
            '<html><body><p>before</p><p>after</p></body><footer><img src="y.jpg"></footer></html>',
            $parser->outerHtml()
        );
        static::assertSame('<body><p>before</p><p>after</p></body>', $body->outerHtml());
    }

    public function testNestedFindReturnsScopedCollection()
    {
        [$parser, $body] = $this->createNestedFindFixture();

        $images = $body->find('img');

        static::assertCount(1, $images);
        static::assertSame('<img src="x.jpg">', $images[0]->outerHtml());

        $images[0]->delete();

        static::assertSame(
            '<html><body><p>before</p><p>after</p></body><footer><img src="y.jpg"></footer></html>',
            $parser->outerHtml()
        );
    }

    public function testNestedFindMultiReturnsScopedCollection()
    {
        [$parser, $body] = $this->createNestedFindFixture();

        $images = $body->findMulti('img');

        static::assertCount(1, $images);
        static::assertSame('<img src="x.jpg">', $images[0]->outerHtml());

        $images[0]->delete();

        static::assertSame(
            '<html><body><p>before</p><p>after</p></body><footer><img src="y.jpg"></footer></html>',
            $parser->outerHtml()
        );
    }

    public function testNestedFindMultiOrFalseReturnsScopedCollection()
    {
        [$parser, $body] = $this->createNestedFindFixture();

        $images = $body->findMultiOrFalse('img');

        static::assertNotFalse($images);
        static::assertCount(1, $images);
        static::assertSame('<img src="x.jpg">', $images[0]->outerHtml());

        $images[0]->delete();

        static::assertSame(
            '<html><body><p>before</p><p>after</p></body><footer><img src="y.jpg"></footer></html>',
            $parser->outerHtml()
        );
    }

    public function testNestedFindMultiOrNullReturnsScopedCollection()
    {
        [$parser, $body] = $this->createNestedFindFixture();

        $images = $body->findMultiOrNull('img');

        static::assertNotNull($images);
        static::assertCount(1, $images);
        static::assertSame('<img src="x.jpg">', $images[0]->outerHtml());

        $images[0]->delete();

        static::assertSame(
            '<html><body><p>before</p><p>after</p></body><footer><img src="y.jpg"></footer></html>',
            $parser->outerHtml()
        );
    }

    public function testNestedFindOneReturnsScopedElement()
    {
        [$parser, $body] = $this->createNestedFindFixture();

        $image = $body->findOne('img');

        static::assertSame('<img src="x.jpg">', $image->outerHtml());

        $image->delete();

        static::assertSame(
            '<html><body><p>before</p><p>after</p></body><footer><img src="y.jpg"></footer></html>',
            $parser->outerHtml()
        );
    }

    public function testNestedFindOneOrFalseReturnsScopedElement()
    {
        [$parser, $body] = $this->createNestedFindFixture();

        $image = $body->findOneOrFalse('img');

        static::assertNotFalse($image);
        static::assertSame('<img src="x.jpg">', $image->outerHtml());

        $image->delete();

        static::assertSame(
            '<html><body><p>before</p><p>after</p></body><footer><img src="y.jpg"></footer></html>',
            $parser->outerHtml()
        );
    }

    public function testNestedFindOneOrNullReturnsScopedElement()
    {
        [$parser, $body] = $this->createNestedFindFixture();

        $image = $body->findOneOrNull('img');

        static::assertNotNull($image);
        static::assertSame('<img src="x.jpg">', $image->outerHtml());

        $image->delete();

        static::assertSame(
            '<html><body><p>before</p><p>after</p></body><footer><img src="y.jpg"></footer></html>',
            $parser->outerHtml()
        );
    }

    public function testNestedFindScopesUnionSelectors()
    {
        $parser = HtmlDomParser::str_get_html(
            '<html><body>body<!--body-comment--></body><footer>footer<!--footer-comment--></footer></html>'
        );

        $nodes = $parser->findOne('body')->find('text, comment');

        static::assertCount(2, $nodes);
        static::assertSame(['body', 'body-comment'], $nodes->text());
    }

    public function testRemoveMethod()
    {
        $html = HtmlDomParser::str_get_html(
            <<<EOD
<html>
<body>
<table>
    <tr><th>Title</th></tr>
    <tr><td>Row 1</td></tr>
</table>
</body>
</html>
EOD
        );

        $table = $html->find('table', 0);
        $table->remove();

        static::assertStringContainsString('<html>', (string) $html);
        static::assertStringContainsString('<body>', (string) $html);
        static::assertStringNotContainsString('<table>', (string) $html);
    }
}
