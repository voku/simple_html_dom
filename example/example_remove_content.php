<?php

use voku\helper\HtmlDomParser;

require_once '../vendor/autoload.php';

$templateHtml = '
<p>lall<br></p>
<ul><li>test321<br>test123</li></ul>
';

// remove: "<br>" from "<ul>"
$htmlTmp = HtmlDomParser::str_get_html($templateHtml);
foreach ($htmlTmp->find('ul br') as $br) {
  $br->outertext = '';
}

$templateHtml = $htmlTmp->save();

// dump contents
echo $templateHtml;
