<?php


require_once '../vendor/autoload.php';

function html_no_comment(string $html): string
{
    // create HTML DOM
    $dom = \Voku\Helper\HtmlDomParser::str_get_html($html);

    // remove all comment elements
    foreach ($dom->find('comment') as $e) {
        $e->outertext = '';
    }

    return $dom->save();
}

// -----------------------------------------------------------------------------

$html = '
<p>lall<br></p>
<!-- comment -->
<ul><li>test321<br>test123</li><!----></ul>
';

// html without comments
echo html_no_comment($html); // <p>lall<br></p>
                                  //
                                  // <ul><li>test321<br>test123</li></ul>
