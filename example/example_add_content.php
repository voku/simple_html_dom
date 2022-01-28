<?php

use voku\helper\HtmlDomParser;

require_once '../vendor/autoload.php';

$templateHtml = '<ul><li>test321</li></ul>';

// add: "<br>" to "<li>"
$htmlTmp = HtmlDomParser::str_get_html($templateHtml);
foreach ($htmlTmp->findMulti('ul li') as $li) {
    $li->innerhtml = '<br>' . $li->innerhtml . '<br>';
}
foreach ($htmlTmp->findMulti('br') as $br) {
    // DEBUG:
    echo $br->tag; // br
}

$templateHtml = $htmlTmp->save();

// dump contents
echo $templateHtml; // <ul><li><br>test321<br></li></ul>
