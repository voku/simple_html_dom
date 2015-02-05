<?php

class HtmlDomParserTest extends PHPUnit_Framework_TestCase
{

  public function testStrGetHtml()
  {
    $str = <<<HTML
中

<form name="form1" method="post" action="">
    <input type="checkbox" name="checkbox1" value="checkbox1" checked>abc-1<br>
    <input type="checkbox" name="checkbox2" value="checkbox2">öäü-2<br>
    <input type="checkbox" name="checkbox3" value="checkbox3" checked>中文空白-3<br>
</form>
HTML;

    $html = voku\helper\HtmlDomParser::str_get_html($str);
    $checkboxArray = array();
    foreach ($html->find('input[type=checkbox]') as $checkbox) {
      if ($checkbox->checked) {
        $checkboxArray[$checkbox->name] = 'checked';
      } else {
        $checkboxArray[$checkbox->name] = 'not checked';
      }
    }

    $this->assertEquals(3, count($checkboxArray));
    $this->assertEquals('checked', $checkboxArray['checkbox1']);
    $this->assertEquals('not checked', $checkboxArray['checkbox2']);
    $this->assertEquals('checked', $checkboxArray['checkbox3']);
  }

  public function testOutertext()
  {
    $str = <<<HTML
<form name="form1" method="post" action=""><input type="checkbox" name="checkbox1" value="checkbox1" checked>中文空白</form>
HTML;

    $html = voku\helper\HtmlDomParser::str_get_html($str);

    foreach ($html->find('input') as $e) {
      $e->outertext = '[INPUT]';
    }

    $this->assertEquals('<form name="form1" method="post" action="">[INPUT]中文空白</form>', $html);
  }

  public function testInnertext()
  {
    $str = <<<HTML
<div id="hello">Hello</div><div id="world">World</div>
HTML;

    $html = voku\helper\HtmlDomParser::str_get_html($str);

    $html->find('div', 1)->class = 'bar';
    $html->find('div[id=hello]', 0)->innertext = 'foo';

    $this->assertEquals('<div id="hello">foo</div><div id="world" class="bar">World</div>', (string) $html);
  }

}