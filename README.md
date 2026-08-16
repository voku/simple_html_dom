[![Build Status](https://github.com/voku/simple_html_dom/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/voku/simple_html_dom/actions)
[![Coverage Status](https://coveralls.io/repos/github/voku/simple_html_dom/badge.svg?branch=master)](https://coveralls.io/github/voku/simple_html_dom?branch=master)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/3290fdc35c8f49ad9abdf053582466eb)](https://www.codacy.com/app/voku/simple_html_dom?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=voku/simple_html_dom&amp;utm_campaign=Badge_Grade)
[![Latest Stable Version](https://poser.pugx.org/voku/simple_html_dom/v/stable)](https://packagist.org/packages/voku/simple_html_dom) 
[![Total Downloads](https://poser.pugx.org/voku/simple_html_dom/downloads)](https://packagist.org/packages/voku/simple_html_dom) 
[![License](https://poser.pugx.org/voku/simple_html_dom/license)](https://packagist.org/packages/voku/simple_html_dom)
[![Donate to this project using Paypal](https://img.shields.io/badge/paypal-donate-yellow.svg)](https://www.paypal.me/moelleken)
[![Donate to this project using Patreon](https://img.shields.io/badge/patreon-donate-yellow.svg)](https://www.patreon.com/voku)

# :scroll: Simple Html Dom Parser for PHP

A HTML DOM parser written in PHP - let you manipulate HTML in a very easy way!
This is a fork of [PHP Simple HTML DOM Parser project](http://simplehtmldom.sourceforge.net/) but instead of string manipulation we use DOMDocument and modern php classes like "Symfony CssSelector".

- PHP 7.1+ runtime support, tested on PHP 7.1 - 8.4
- PHP-FIG Standard
- Composer & PSR-4 support
- PHPUnit testing via GitHub Actions
- PHPStan-clean source tree on the current release branch
- PHP-Quality testing via SensioLabsInsight
- UTF-8 Support (more support via "voku/portable-utf8")
- Invalid HTML Support (partly ...)
- Find tags on an HTML page with selectors just like jQuery
- Extract contents from HTML in a single line


### Install via "composer require"

```shell
composer require voku/simple_html_dom
composer require voku/portable-utf8 # if you need e.g. UTF-8 fixed output
```

### Upgrade notes for v5.0.0

- PHP 7.0 is no longer supported; the package now requires PHP 7.1 or newer.
- Nested `find*()` calls now return live nodes scoped to the original DOM, so mutating nested results updates the source document.
- See the [CHANGELOG](https://github.com/voku/simple_html_dom/blob/master/CHANGELOG) for the full release notes.

### Quick Start

```php
use voku\helper\HtmlDomParser;

require_once 'composer/autoload.php';

...
$dom = HtmlDomParser::str_get_html($str);
// or 
$dom = HtmlDomParser::file_get_html($file);

$element = $dom->findOne('#css-selector'); // "$element" === instance of "SimpleHtmlDomInterface"

$elements = $dom->findMulti('.css-selector'); // "$elements" === instance of SimpleHtmlDomNodeInterface<int, SimpleHtmlDomInterface>

$elementOrFalse = $dom->findOneOrFalse('#css-selector'); // "$elementOrFalse" === instance of "SimpleHtmlDomInterface" or false

$elementsOrFalse = $dom->findMultiOrFalse('.css-selector'); // "$elementsOrFalse" === instance of SimpleHtmlDomNodeInterface<int, SimpleHtmlDomInterface> or false
...

```

### HTML5 parsing on PHP >= 8.4: `Html5DomParser`

PHP 8.4 added `\Dom\HTMLDocument`, a parser that follows the HTML5 specification and therefore
recovers from broken markup the way a browser does. This library exposes it as its own class,
for the same reason PHP put it next to `\DOMDocument` instead of adding a mode to it: the
parsing rules are different, and that difference is visible in the result.

`Html5DomParser` extends `HtmlDomParser` and has the same API, so switching means changing the
class name and nothing else:

```php
use voku\helper\Html5DomParser;

$dom = Html5DomParser::str_get_html('<table><tr><td>x</table><p>a<p>b');

$dom->html(); // '<table><tbody><tr><td>x</td></tr></tbody></table><p>a</p><p>b</p>'

// HtmlDomParser, unchanged, still returns:
// '<table><tr><td>x</td></tr></table><p>a</p><p>b</p>'
```

`HtmlDomParser` is not affected by any of this and stays the default parser of this library.

What you get with `Html5DomParser`: implied `<tbody>`, auto-closed `<p>` / `<li>` / `<td>`,
recovery from misnested formatting tags, tag names normalized to lower case, camel-case SVG
names (`viewBox`, `feColorMatrix`) restored, the encoding detected from the document like a
browser does, and elements that libxml would have dropped or moved.

What is different, and why it is a separate class:

- HTML entities are resolved to their characters, as the specification requires, so `&nbsp;`
  and `&amp;` come back as ` ` and `&` instead of staying entities.
- The HTML5 parser always builds a complete document, so `getDocument()->documentElement` is
  always `<html>`, even for a fragment. `html()` / `innerHtml()` still return the fragment.
- A bare attribute has the empty string as its value, exactly as in a browser, so
  `<input checked>` gives `getAttribute('checked') === ''`. Test presence with
  `hasAttribute()`, and expect `checked=""` in the serialized output.
- Content written after `</body>` is moved back into the body, like a browser does.
- `useKeepBrokenHtml()` works on top of it: the broken fragments are preserved verbatim, but
  because they travel through the parser as text, HTML5 tree construction can move such a
  fragment out of a `<table>` or out of the `<head>`. The fragment itself is never lost.

`tests/Html5DomParserCompatibilityTest.php` is the `HtmlDomParser` test suite run against
`Html5DomParser`, so every one of these differences is pinned by a test, and everything else is
proven to be unchanged.

The result is bridged back into a `\DOMDocument`, which costs one extra serialize + parse and
more transient memory. Measure it for your own input:

```shell
php build/benchmark_html5_parser.php
```

On the fixtures of this repository the complete `loadHtml()` + query + `html()` round-trip is
currently *faster* than the libxml path (factor 0.72 - 0.95), because the HTML5 parser needs
none of the string preprocessing that path does; small synthetic fragments are slower
(factor ~1.3 - 1.5), and peak memory is higher in both cases.

`Html5DomParser` is a strict parser choice. It does **not** silently switch back to
`HtmlDomParser`, because that would make the class name lie about the parsing semantics. On an
application that also runs on PHP < 8.4, check support before selecting the class:

```php
if (Html5DomParser::isHtml5ParserSupported()) {
    $dom = Html5DomParser::str_get_html($html);
} else {
    $dom = HtmlDomParser::str_get_html($html); // explicit application decision
}
```

Parsing throws a `RuntimeException` when the HTML5 backend is unavailable or when its normalized
tree cannot be represented by the legacy `\DOMDocument` XML bridge (for example an HTML-valid
attribute name that XML cannot represent). Use `HtmlDomParser` explicitly if legacy parsing is
the intended fallback. A successful `Html5DomParser` parse therefore always means HTML5 tree
construction actually happened.

### Examples

[github.com/voku/simple_html_dom/tree/master/example](https://github.com/voku/simple_html_dom/tree/master/example)

### API

[github.com/voku/simple_html_dom/tree/master/README_API.md](https://github.com/voku/simple_html_dom/tree/master/README_API.md)

### Support

For support and donations please visit [Github](https://github.com/voku/simple_html_dom/) | [Issues](https://github.com/voku/simple_html_dom/issues) | [PayPal](https://paypal.me/moelleken) | [Patreon](https://www.patreon.com/voku).

For status updates and release announcements please visit [Releases](https://github.com/voku/simple_html_dom/releases) | [Twitter](https://twitter.com/suckup_de) | [Patreon](https://www.patreon.com/voku/posts).

For professional support please contact [me](https://about.me/voku).

### Thanks

- Thanks to [GitHub](https://github.com) (Microsoft) for hosting the code and a good infrastructure including Issues-Managment, etc.
- Thanks to [IntelliJ](https://www.jetbrains.com) as they make the best IDEs for PHP and they gave me an open source license for PhpStorm!
- Thanks to [Travis CI](https://travis-ci.com/) for being the most awesome, easiest continous integration tool out there!
- Thanks to [StyleCI](https://styleci.io/) for the simple but powerfull code style check.
- Thanks to [PHPStan](https://github.com/phpstan/phpstan) && [Psalm](https://github.com/vimeo/psalm) for relly great Static analysis tools and for discover bugs in the code!

### License
[![FOSSA Status](https://app.fossa.io/api/projects/git%2Bgithub.com%2Fvoku%2Fsimple_html_dom.svg?type=large)](https://app.fossa.io/projects/git%2Bgithub.com%2Fvoku%2Fsimple_html_dom?ref=badge_large)
