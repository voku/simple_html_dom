<?php

require_once '../vendor/autoload.php';

// -----------------------------------------------------------------------------

$html = '
<p class="lall">lall<br></p>
<img id="test" src="lall.png" alt="foo">
<p class="lall">foo</p>
';

$document = new \voku\helper\HtmlDomParser($html);

$imageOrFalse = $document->findOneOrFalse('#test');
if ($imageOrFalse !== false) {
    echo $imageOrFalse->getAttribute('src') . "\n";
}

// -----------------------------------------------------------------------------

$html = '
<p class="lall">lall<br></p>
<img id="test" src="lall.png" alt="foo">
<p class="lall">foo</p>
';

$document = new \voku\helper\HtmlDomParser($html);

$imageOrFalse = $document->findOneOrFalse('#non_test');
if ($imageOrFalse !== false) {
    echo $imageOrFalse->getAttribute('src') . "\n";
}

// -----------------------------------------------------------------------------

$html = '
<p class="lall">lall<br></p>
<img class="image_foo" src="lall1.png" alt="foo1">
<p class="lall">foo</p>
<img class="image_foo" src="lall2.png" alt="foo2">
';

$document = new \voku\helper\HtmlDomParser($html);

$imagesOrFalse = $document->findMultiOrFalse('.image_foo');
if ($imagesOrFalse !== false) {
    foreach ($imagesOrFalse as $image) {
        echo $image->getAttribute('src') . "\n";
    }
}