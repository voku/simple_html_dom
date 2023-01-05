<?php

use Voku\Helper\SimpleHtmlDomInterface;
use Voku\Helper\SimpleHtmlDomNode;
use Voku\Helper\SimpleHtmlDomNodeInterface;

require_once '../vendor/autoload.php';

/**
 * @param \Voku\Helper\HtmlDomParser $dom
 * @param string                     $selector
 * @param string                     $keyword
 *
 * @return SimpleHtmlDomInterface[]|SimpleHtmlDomNodeInterface<SimpleHtmlDomInterface>
 */
function find_contains(
    \Voku\Helper\HtmlDomParser $dom,
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

$document = new \Voku\Helper\HtmlDomParser($html);

foreach (find_contains($document, '.lall', 'foo') as $child_dom) {
    echo $child_dom->html() . "\n";
}
