<?php

use voku\helper\HtmlDomParser;

require_once '../vendor/autoload.php';

$templateHtml = '
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="description" content="Free Web tutorials">
  <meta name="keywords" content="HTML,CSS,XML,JavaScript">
  <meta name="author" content="Lars Moelleken">
</head>
<body>

<p>All meta information goes in the head section...</p>

</body>
</html>
';

$htmlTmp = HtmlDomParser::str_get_html($templateHtml);
foreach ($htmlTmp->find('meta') as $meta) {
    if ($meta->hasAttribute('content')) {
        $meta_data[$meta->getAttribute('name')][] = $meta->getAttribute('content');
    }
}

// dump contents
/** @noinspection ForgottenDebugOutputInspection */
var_export($meta_data, false);

/*
[
    'description' => [
        'Free Web tutorials',
    ],
    'keywords' => [
        'HTML,CSS,XML,JavaScript',
    ],
    'author' => [
        'Lars Moelleken',
    ],
]
 */
