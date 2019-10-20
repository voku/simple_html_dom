<?php

use voku\helper\SimpleHtmlDomInterface;
use voku\helper\SimpleHtmlDomNode;
use voku\helper\SimpleHtmlDomNodeInterface;

require_once '../vendor/autoload.php';

/**
 * @param \voku\helper\HtmlDomParser $dom
 * @param string                     $selector
 * @param string                     $keyword
 *
 * @return SimpleHtmlDomInterface[]|SimpleHtmlDomNodeInterface
 */
function find_contains(
    \voku\helper\HtmlDomParser $dom,
    string $selector,
    string $keyword
) {
    // init
    $elements = new SimpleHtmlDomNode();

    foreach ($dom->find($selector) as $e) {
        if (strpos($e->innerText(), $keyword) !== false) {
            $elements[] = $e;
        }
    }

    return $elements;
}

// -----------------------------------------------------------------------------

$html = '
<p class="lall">lall<br></p>
<p class="lall">foo</p>
<ul><li class="lall">test321<br>foo</li><!----></ul>
';

$document = new \voku\helper\HtmlDomParser($html);

foreach (find_contains($document, '.lall', 'foo') as $child_dom) {
    echo $child_dom->html() . "\n";
}
