<?php

use Voku\Helper\HtmlDomParser;

require_once '../vendor/autoload.php';

$templateHtml = '
<div>
    <div class="inner">
        <ul>
          <li><img alt="" src="none.jpg" data-lazyload="/pc/aaa.jpg"></a></li>
          <li><img alt="" src="none.jpg" data-lazyload="/pc/bbb.jpg"></a></li>
        </ul>
    </div>
</div>
';

$htmlTmp = HtmlDomParser::str_get_html($templateHtml);
$data_attribute = [];

foreach ($htmlTmp->find('.inner img') as $meta) {
    if ($meta->hasAttribute('data-lazyload')) {
        $data_attribute[] = $meta->getAttribute('data-lazyload');
    }
}

// dump contents
/** @noinspection ForgottenDebugOutputInspection */
var_export($data_attribute, false);

/*
[
    0 => '/pc/aaa.jpg',
    1 => '/pc/bbb.jpg',
]
 */
