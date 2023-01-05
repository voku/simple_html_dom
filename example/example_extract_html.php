<?php

require_once '../vendor/autoload.php';

echo Voku\Helper\HtmlDomParser::file_get_html('https://www.google.com/')->plaintext;
