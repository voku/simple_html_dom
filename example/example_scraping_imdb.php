<?php

require_once '../vendor/autoload.php';

function scraping_imdb($url)
{
    // init
    $return = [];

    // create HTML DOM
    $dom = \Voku\Helper\HtmlDomParser::file_get_html($url);

    // get title
    $return['Title'] = $dom->find('title', 0)->innertext;

    // get rating
    $return['Rating'] = $dom->find('.ratingValue strong', 0)->getAttribute('title');

    return $return;
}

// -----------------------------------------------------------------------------

$data = scraping_imdb('http://imdb.com/title/tt0335266/');

foreach ($data as $k => $v) {
    echo '<strong>' . $k . ' </strong>' . $v . '<br>';
}
