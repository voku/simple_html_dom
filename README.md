[![Build Status](https://travis-ci.org/voku/simple_html_dom.svg?branch=master)](https://travis-ci.org/voku/simple_html_dom)
[![FOSSA Status](https://app.fossa.io/api/projects/git%2Bgithub.com%2Fvoku%2Fsimple_html_dom.svg?type=shield)](https://app.fossa.io/projects/git%2Bgithub.com%2Fvoku%2Fsimple_html_dom?ref=badge_shield)
[![Coverage Status](https://coveralls.io/repos/github/voku/simple_html_dom/badge.svg?branch=master)](https://coveralls.io/github/voku/simple_html_dom?branch=master)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/3290fdc35c8f49ad9abdf053582466eb)](https://www.codacy.com/app/voku/simple_html_dom?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=voku/simple_html_dom&amp;utm_campaign=Badge_Grade)
[![Latest Stable Version](https://poser.pugx.org/voku/simple_html_dom/v/stable)](https://packagist.org/packages/voku/simple_html_dom) 
[![Total Downloads](https://poser.pugx.org/voku/simple_html_dom/downloads)](https://packagist.org/packages/voku/simple_html_dom) 
[![License](https://poser.pugx.org/voku/simple_html_dom/license)](https://packagist.org/packages/voku/simple_html_dom)

# :scroll: Simple Html Dom Parser for PHP

A HTML DOM parser written in PHP - let you manipulate HTML in a very easy way!
This is a fork of [PHP Simple HTML DOM Parser project](http://simplehtmldom.sourceforge.net/) but instead of string manipulation we use DOMDocument and modern php classes like "Symfony CssSelector".

- PHP 7.0+ Support
- PHP-FIG Standard
- Composer & PSR-4 support
- PHPUnit testing via Travis CI
- PHP-Quality testing via SensioLabsInsight
- UTF-8 Support (more support via "voku/portable-utf8")
- Invalid HTML Support (partly ...)
- Find tags on an HTML page with selectors just like jQuery
- Extract contents from HTML in a single line


## Install via "composer require"

```shell
composer require voku/simple_html_dom
composer require voku/portable-utf8 # if you need e.g. UTF-8 fixed output
```

## Quick Start

```php
use voku\helper\HtmlDomParser;

require_once 'composer/autoload.php';

...
$dom = HtmlDomParser::str_get_html($str);
// or 
$dom = HtmlDomParser::file_get_html($file);

$elems = $dom->find('#css-selector');
...

```

## Examples

[github.com/voku/simple_html_dom/tree/master/example](https://github.com/voku/simple_html_dom/tree/master/example)


## License
[![FOSSA Status](https://app.fossa.io/api/projects/git%2Bgithub.com%2Fvoku%2Fsimple_html_dom.svg?type=large)](https://app.fossa.io/projects/git%2Bgithub.com%2Fvoku%2Fsimple_html_dom?ref=badge_large)
