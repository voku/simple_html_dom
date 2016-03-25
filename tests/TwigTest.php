<?php
use voku\helper\HtmlDomParser;

/**
 * Class TwigTest
 */
class TwigTest extends PHPUnit_Framework_TestCase
{
  public function testTwig()
  {
    $filename = __DIR__ . '/test_template.twig';
    $html = HtmlDomParser::file_get_html($filename);
    $htmlNormalised = str_replace(array("\r", "\n"), ' ', file_get_contents($filename));

    // object to sting
    self::assertEquals($htmlNormalised, (string)$html);

    // ------------------
    // find
    // ------------------

    $navigation = $html->find('.navigation--element');

    self::assertEquals('navigation--element', $navigation[0]->class);
    self::assertEquals('<a href="{{ item.href }}">{{ item.caption }}</a>', $navigation[0]->innertext);

    // ------------------
    // edit
    // ------------------

    $navigation[0]->class = 'fooo';

    self::assertEquals(
        '<!DOCTYPE html> <html> <head>   <title>My Webpage</title> </head> <body> <ul class="navigation">   {% for item in navigation %}     <li class="fooo"><a href="{{ item.href }}">{{ item.caption }}</a></li>   {% endfor %} </ul>  <h1>My Webpage</h1> {{ a_variable }} </body> </html>',
        (string)$html
    );
  }
}
