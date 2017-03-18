[![Build Status](https://travis-ci.org/voku/simple_html_dom.svg?branch=master)](https://travis-ci.org/voku/simple_html_dom)
[![Coverage Status](https://coveralls.io/repos/github/voku/simple_html_dom/badge.svg?branch=master)](https://coveralls.io/github/voku/simple_html_dom?branch=master)
[![codecov.io](http://codecov.io/github/voku/simple_html_dom/coverage.svg?branch=master)](http://codecov.io/github/voku/simple_html_dom?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/voku/simple_html_dom/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/voku/simple_html_dom/?branch=master)
[![Codacy Badge](https://www.codacy.com/project/badge/3290fdc35c8f49ad9abdf053582466eb)](https://www.codacy.com/app/voku/simple_html_dom)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/be3e4851-272f-4499-9fc4-4b2704a43301/mini.png)](https://insight.sensiolabs.com/projects/be3e4851-272f-4499-9fc4-4b2704a43301)
[![Dependency Status](https://www.versioneye.com/php/voku:simple_html_dom/dev-master/badge.svg)](https://www.versioneye.com/php/voku:simple_html_dom/dev-master)
[![Latest Stable Version](https://poser.pugx.org/voku/simple_html_dom/v/stable)](https://packagist.org/packages/voku/simple_html_dom) 
[![Total Downloads](https://poser.pugx.org/voku/simple_html_dom/downloads)](https://packagist.org/packages/voku/simple_html_dom) 
[![Latest Unstable Version](https://poser.pugx.org/voku/simple_html_dom/v/unstable)](https://packagist.org/packages/voku/simple_html_dom) 
[![PHP 7 ready](http://php7ready.timesplinter.ch/voku/simple_html_dom/badge.svg)](https://travis-ci.org/voku/simple_html_dom)
[![License](https://poser.pugx.org/voku/simple_html_dom/license)](https://packagist.org/packages/voku/simple_html_dom)



A HTML DOM parser written in PHP - let you manipulate HTML in a very easy way!
===============

This is a fork of [PHP Simple HTML DOM Parser project](http://simplehtmldom.sourceforge.net/) but instead of string manipulation we use DOMDocument and modern php classes like "Symfony CssSelector" and "Portable UTF-8".

- PHP 5.3+ Support
- PHP-FIG Standard
- Composer & PSR-4 support
- PHPUnit testing via Travis CI
- PHP-Quality testing via SensioLabsInsight
- UTF-8 Support
- Invalid HTML Support
- Find tags on an HTML page with selectors just like jQuery
- Extract contents from HTML in a single line


## Install via "composer require"

```shell
composer require voku/simple_html_dom
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
