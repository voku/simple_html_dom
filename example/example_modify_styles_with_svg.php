<?php

use voku\helper\HtmlDomParser;

require_once '../vendor/autoload.php';

$html = <<<HTML
<html lang='en'><head>
<style>
    .wp-block-image{margin:0 0 1em}.wp-block-image img{vertical-align:bottom}.wp-block-image:not(.is-style-rounded)>a,.wp-block-image:not(.is-style-rounded) img{border-radius:inherit}.wp-block-image.aligncenter{text-align:center}.wp-block-image.alignfull img,.wp-block-image.alignwide img{height:auto;width:100%}.wp-block-image .aligncenter,.wp-block-image .alignleft,.wp-block-image .alignright{display:table}.wp-block-image .aligncenter>figcaption,.wp-block-image .alignleft>figcaption,.wp-block-image .alignright>figcaption{caption-side:bottom;display:table-caption}.wp-block-image .alignleft{float:left;margin:.5em 1em .5em 0}.wp-block-image .alignright{float:right;margin:.5em 0 .5em 1em}.wp-block-image .aligncenter{margin-left:auto;margin-right:auto}.wp-block-image figcaption{margin-bottom:1em;margin-top:.5em}.wp-block-image.is-style-circle-mask img,.wp-block-image.is-style-rounded img{border-radius:9999px}@supports ((-webkit-mask-image:none) or (mask-image:none)) or (-webkit-mask-image:none){.wp-block-image.is-style-circle-mask img{border-radius:0;-webkit-mask-image:url('data:image/svg+xml;utf8,<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="50"/></svg>');mask-image:url('data:image/svg+xml;utf8,<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="50"/></svg>');mask-mode:alpha;-webkit-mask-position:center;mask-position:center;-webkit-mask-repeat:no-repeat;mask-repeat:no-repeat;-webkit-mask-size:contain;mask-size:contain}}.wp-block-image figure{margin:0}
</style>
</head><body><div id='p1' class='post'>foo</div><div class='post' id='p2'>bar</div></body></html>
HTML;

$document = new HtmlDomParser($html);

$tags = $document->find( 'style' );
foreach ( $tags as $tag ) {
    // INFO: here we need to use "innerhtmlKeep" instead of "innerhtml", so that we keep the svg-hack
    $tag->innerhtmlKeep .= ' .test{color: red;} ';
}

echo $document->html();

/*
 * <html lang="en"><head>
 * <style>.wp-block-image{margin:0 0 1em}.wp-block-image img{vertical-align:bottom}.wp-block-image:not(.is-style-rounded)>a,.wp-block-image:not(.is-style-rounded) img{border-radius:inherit}.wp-block-image.aligncenter{text-align:center}.wp-block-image.alignfull img,.wp-block-image.alignwide img{height:auto;width:100%}.wp-block-image .aligncenter,.wp-block-image .alignleft,.wp-block-image .alignright{display:table}.wp-block-image .aligncenter>figcaption,.wp-block-image .alignleft>figcaption,.wp-block-image .alignright>figcaption{caption-side:bottom;display:table-caption}.wp-block-image .alignleft{float:left;margin:.5em 1em .5em 0}.wp-block-image .alignright{float:right;margin:.5em 0 .5em 1em}.wp-block-image .aligncenter{margin-left:auto;margin-right:auto}.wp-block-image figcaption{margin-bottom:1em;margin-top:.5em}.wp-block-image.is-style-circle-mask img,.wp-block-image.is-style-rounded img{border-radius:9999px}@supports ((-webkit-mask-image:none) or (mask-image:none)) or (-webkit-mask-image:none){.wp-block-image.is-style-circle-mask img{border-radius:0;-webkit-mask-image:url('data:image/svg+xml;utf8,<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="50"/></svg>');mask-image:url('data:image/svg+xml;utf8,<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="50"/></svg>');mask-mode:alpha;-webkit-mask-position:center;mask-position:center;-webkit-mask-repeat:no-repeat;mask-repeat:no-repeat;-webkit-mask-size:contain;mask-size:contain}}.wp-block-image figure{margin:0} .test{color: red;} </style>
 * </head><body><div id="p1" class="post">foo</div><div class="post" id="p2">bar</div></body></html>
 */
