[![SensioLabsInsight](https://insight.sensiolabs.com/projects/be3e4851-272f-4499-9fc4-4b2704a43301/mini.png)](https://insight.sensiolabs.com/projects/be3e4851-272f-4499-9fc4-4b2704a43301)
[![Total Downloads](https://poser.pugx.org/voku/simple_html_dom/downloads.svg)](https://packagist.org/packages/voku/simple_html_dom)


simple_html_dom
===============

A copy of the [PHP Simple HTML DOM Parser project](http://simplehtmldom.sourceforge.net/) usable as a [Composer](http://getcomposer.org/) package.

## Installation

First, you need to add this repository at the root of your `composer.json`:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/voku/simple_html_dom"
    }
]
```

Then, require this package in the same way as any other package:

```json
"require": {
    "simple_html_dom/simple_html_dom": "*"
}
```

Do a `composer validate`, just to be sure that your file is still valid.

And voilà, you’re ready to `composer update`.

## Usage

Since this library doesn’t use namespaces, it lives in the global namespace.

```php
$instance = new \simple_html_dom();
```

Check the [official documentation at SourceForge](http://simplehtmldom.sourceforge.net/manual.htm).
