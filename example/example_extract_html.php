<?php

require_once '../vendor/autoload.php';

echo voku\helper\HtmlDomParser::file_get_html('http://www.google.com/')->plaintext;
