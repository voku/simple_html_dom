<?php

use voku\helper\HtmlDomParser;

require_once '../vendor/autoload.php';

$templateHtml = '
<table>
<tr>
<td>
 text 1
</td>
<td>
 <span class="delete">DELETE</span>
</td>
</tr>
<tr>
<td>
 text 2
</td>
<td>
</td>
</tr>
<tr>
<td>
 text 3
</td>
<td>
 <span class="delete">DELETE</span>
</td>
</tr>
</table>
';

// remove: "<br>" from "<ul>"
$htmlTmp = HtmlDomParser::str_get_html($templateHtml);
foreach ($htmlTmp->findMulti('tr') as $tr) {
    foreach ($tr->findMulti('.delete') as $spanDelete) {
        $tr->outerhtml = '';
    }
}

$templateHtml = $htmlTmp->save();

// dump contents
echo $templateHtml;
