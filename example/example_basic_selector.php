<?php

use voku\helper\HtmlDomParser;

require_once '../vendor/autoload.php';

// get DOM from URL or file
$html = HtmlDomParser::file_get_html('http://www.google.com/');

// find all link
foreach ($html->find('a') as $e) {
    echo $e->href . '<br>';
}

// find all image
foreach ($html->find('img') as $e) {
    echo $e->src . '<br>';
}

// find all image with full tag
foreach ($html->find('img') as $e) {
    echo $e->outertext . '<br>';
}

// find all div tags with id=gbar
foreach ($html->find('div#gbar') as $e) {
    echo $e->innertext . '<br>';
}

// find all span tags with class=gb1
foreach ($html->find('span.gb1') as $e) {
    echo $e->outertext . '<br>';
}

// find all td tags with attribute align=center
foreach ($html->find('td[align=center]') as $e) {
    echo $e->innertext . '<br>';
}

// extract text from table
echo $html->find('td[align="center"]', 1)->plaintext . '<br><hr>';

// extract text from HTML
echo $html->plaintext;
