<?php

use voku\helper\HtmlDomParser;

require_once '../vendor/autoload.php';

$html = '<html><div id="p1" class="post">foo</div><div class="post" id="p2">bar</div></html>';

$document = new HtmlDomParser($html);

foreach ($document->find('div') as $e) {
  $attrs = array();
  foreach ($e->getAllAttributes() as $attrKey => $attrValue) {
    $attrs[$attrKey] = $attrValue;
    $e->$attrKey = null;
  }

  ksort($attrs);

  foreach ($attrs as $attrKey => $attrValue) {
    $e->$attrKey = $attrValue;
  }
}

echo $document->html();
