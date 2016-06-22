<?php

require_once '../vendor/autoload.php';

echo voku\helper\HtmlDomParser::file_get_html('https://www.google.com/')->plaintext;
