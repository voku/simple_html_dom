<?php

use voku\helper\HtmlDomParser;

require_once '../vendor/autoload.php';

$templateHtml = '<ul><li>test321</li></ul>';

// add: "<br>" to "<li>"
$htmlTmp = HtmlDomParser::str_get_html($templateHtml);
foreach ($htmlTmp->find('ul li') as $li) {
    $li->innerhtml = '<br>' . $li->innerhtml . '<br>';
}

$templateHtml = $htmlTmp->save();

// dump contents
echo $templateHtml; // <ul><li><br>test321<br></li></ul>
