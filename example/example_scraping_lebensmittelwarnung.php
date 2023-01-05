<?php

require_once '../vendor/autoload.php';

function scraping_lebensmittelwarnung($url)
{
    // init
    $return = [];

    // create HTML DOM
    $dom = \Voku\Helper\HtmlDomParser::file_get_html($url);

    foreach ($dom->findMulti('item') as $item) {
        $title = $item->getElementByTagName('title')->text();

        $return[$title]['Produkt'] = $title;
        $return[$title]['DatumTime'] = date('Y-m-d H:m:s', strtotime($item->getElementByTagName('pubDate')->text()));
        $return[$title]['Link'] = $item->getElementByTagName('link')->text();
        $return[$title]['Beschreibung'] = nl2br($item->getElementByTagName('description')->text());

        if (strpos($return[$title]['Beschreibung'], 'Gefahr') !== false) {
            $return[$title]['Gefahr'] = '!!!!!!!!!!!!!!!';
        } else {
            $return[$title]['Gefahr'] = '';
        }
    }
    
    return $return;
}

// -----------------------------------------------------------------------------

$data = scraping_lebensmittelwarnung('https://www.lebensmittelwarnung.de/bvl-lmw-de/opensaga/feed/alle/nordrhein_westfalen.rss');

foreach ($data as $v) {
    foreach ($v as $k_inner => $v_inner) {
        echo '<strong>' . $k_inner . ':</strong>&nbsp;' . $v_inner . '<br><br>';
    }
}
