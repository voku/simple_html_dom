<?php


use PHPUnit\Framework\TestCase;
use voku\helper\HtmlDomParser;

final class AuxiliarFunctionsTest extends TestCase
{
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
        $parser = HtmlDomParser::str_get_html('<html><body><p>before</p><img src="x.jpg"><p>after</p></body></html>');
        $body = $parser->findOne('body');

        $body->findOne('img')->delete();

        static::assertSame('<html><body><p>before</p><p>after</p></body></html>', $parser->outerHtml());
        static::assertSame('<body><p>before</p><p>after</p></body>', $body->outerHtml());
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
