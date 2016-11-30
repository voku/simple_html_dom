<?php

use voku\helper\HtmlDomParser;

/**
 * Class TwigTest
 */
class TwigTest extends PHPUnit_Framework_TestCase
{
  public function testTwig()
  {
    $filename = __DIR__ . '/fixtures/test_template.twig';
    $html = HtmlDomParser::file_get_html($filename);
    $htmlNormalised = str_replace(array("\r\n", "\r", "\n"), ' ', file_get_contents($filename));

    // object to sting
    self::assertSame($htmlNormalised, str_replace(array("\r\n", "\r", "\n"), ' ', (string)$html));

    // ------------------
    // find
    // ------------------

    $navigation = $html->find('.navigation--element');
    $secondAnchor = $navigation[1]->find('a');

    self::assertSame('navigation--element', $navigation[0]->class);
    self::assertSame('<a href="{{ item.href }}">{{ item.caption }}</a>', $navigation[0]->innertext);
    self::assertSame('https://foo?lall=###FOO###&lall={#lall#}#foo', $secondAnchor[0]->href);

    // ------------------
    // edit
    // ------------------

    $navigation[0]->class = 'fooo';
    $navigation[1]->class = 'fooo';

    $expected = '<!DOCTYPE html> <html> <head><title>My Webpage</title></head> <body> <ul class="navigation">  {% for item in navigation %}     <li class="fooo"><a href="{{ item.href }}">{{ item.caption }}</a></li>     <li class="fooo"><a href="https://foo?lall=###FOO###&lall={#lall#}#foo">link</a></li>  {% endfor %} </ul> <h1>My Webpage</h1> {{ a_variable }} </body> </html>';

    self::assertSame(
        str_replace(array("\r\n", "\r", "\n"), ' ', $expected),
        str_replace(array("\r\n", "\r", "\n"), ' ', (string)$html)
    );
  }
}
