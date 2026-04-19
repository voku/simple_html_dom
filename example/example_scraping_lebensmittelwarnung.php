<?php

require_once __DIR__ . '/../vendor/autoload.php';

use voku\helper\XmlDomParser;

function scraping_lebensmittelwarnung($url)
{
    // init
    $return = [];

    // create XML DOM
    $dom = XmlDomParser::file_get_xml($url);

    foreach ($dom->findMulti('//item') as $item) {
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

if (\realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $data = scraping_lebensmittelwarnung('https://www.lebensmittelwarnung.de/bvl-lmw-de/opensaga/feed/alle/nordrhein_westfalen.rss');

    foreach ($data as $v) {
        foreach ($v as $k_inner => $v_inner) {
            echo '<strong>' . $k_inner . ':</strong>&nbsp;' . $v_inner . '<br><br>';
        }
    }
}
