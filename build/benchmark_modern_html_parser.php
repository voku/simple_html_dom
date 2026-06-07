<?php

declare(strict_types=1);

use voku\helper\HtmlDomParser;

require_once dirname(__DIR__) . '/vendor/autoload.php';

final class BenchmarkLegacyHtmlDomParser extends HtmlDomParser
{
    protected function shouldUseModernHtmlDocument(int $optionsXml): bool
    {
        return false;
    }
}

final class BenchmarkModernHtmlDomParser extends HtmlDomParser
{
    public static function supportsModernPath(): bool
    {
        return \class_exists('Dom\\HTMLDocument')
            && \method_exists('Dom\\HTMLDocument', 'createFromString');
    }

    protected function shouldUseModernHtmlDocument(int $optionsXml): bool
    {
        return self::supportsModernPath();
    }
}

/**
 * @param class-string<HtmlDomParser> $parserClass
 *
 * @return array<string, float|int|string>
 */
function runBenchmark(string $parserClass, string $html, string $selector, int $iterations, int $optionsXml): array
{
    if (\function_exists('memory_reset_peak_usage')) {
        \memory_reset_peak_usage();
    }

    \gc_collect_cycles();

    $parseStart = \microtime(true);
    $documents = [];
    for ($i = 0; $i < $iterations; ++$i) {
        $documents[] = $parserClass::str_get_html($html, $optionsXml);
    }
    $parseTime = \microtime(true) - $parseStart;

    $selectorStart = \microtime(true);
    foreach ($documents as $document) {
        $document->findMulti($selector);
    }
    $selectorTime = \microtime(true) - $selectorStart;

    $serializationStart = \microtime(true);
    foreach ($documents as $document) {
        $document->html();
    }
    $serializationTime = \microtime(true) - $serializationStart;

    return [
        'parser' => $parserClass,
        'parse_ms' => \round($parseTime * 1000, 3),
        'selector_ms' => \round($selectorTime * 1000, 3),
        'serialize_ms' => \round($serializationTime * 1000, 3),
        'peak_bytes' => \memory_get_peak_usage(true),
    ];
}

function getLibxmlOptionMask(): int
{
    $optionsXml = 0;

    foreach ([
        'LIBXML_COMPACT',
        'LIBXML_DTDATTR',
        'LIBXML_DTDLOAD',
        'LIBXML_DTDVALID',
        'LIBXML_HTML_NOIMPLIED',
        'LIBXML_HTML_NODEFDTD',
        'LIBXML_NOCDATA',
        'LIBXML_NOEMPTYTAG',
        'LIBXML_NOENT',
        'LIBXML_NOERROR',
        'LIBXML_NONET',
        'LIBXML_NOWARNING',
        'LIBXML_NOBLANKS',
        'LIBXML_PARSEHUGE',
        'LIBXML_PEDANTIC',
        'LIBXML_XINCLUDE',
    ] as $optionName) {
        if (\defined($optionName)) {
            $optionsXml |= \constant($optionName);
        }
    }

    return $optionsXml;
}

$legacyOptionMask = getLibxmlOptionMask();

$cases = [
    'small-fragment' => [
        'iterations' => 400,
        'selector' => '.message',
        'html' => '<main><p class="message">old</p><p class="message">new</p></main>',
        'options_xml' => 0,
    ],
    'article' => [
        'iterations' => 125,
        'selector' => 'article p',
        'html' => '<article><header><h1>Title</h1><time datetime="2026-06-06">06 Jun</time></header><p>Intro</p><section><p>Body</p><figure><img src="hero.jpg" alt="Hero"><figcaption><strong>Caption</strong></figcaption></figure></section></article>',
        'options_xml' => 0,
    ],
    'malformed-large' => [
        'iterations' => 75,
        'selector' => 'li',
        'html' => '<ul><li>one<li>two<li><strong>three</ul><div><span>tail',
        'options_xml' => 0,
    ],
    'mixed-content' => [
        'iterations' => 125,
        'selector' => 'template section, svg circle, script',
        'html' => '<div><template id="card"><section><h2>Title</h2><p>Body</p></section></template><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use xlink:href="#icon"></use><circle id="dot"></circle></svg><script type="text/x-custom-template"><% _.each(items, function(item) { %><li><%= item.name %></li><% }); %></script></div>',
        'options_xml' => 0,
    ],
    'html-document-wrapper' => [
        'iterations' => 100,
        'selector' => 'main > article.item',
        'html' => '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Bench</title></head><body><main><article class="item"><h2>One</h2><p>Body</p></article><article class="item"><h2>Two</h2><p>Body</p></article></main></body></html>',
        'options_xml' => 0,
    ],
    'namespaced-svg-mathml' => [
        'iterations' => 100,
        'selector' => 'svg use, math mi, math mo',
        'html' => '<section><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><g id="icon"><path d="M0 0h10v10z"></path></g></defs><use xlink:href="#icon"></use></svg><math xmlns="http://www.w3.org/1998/Math/MathML"><mi>x</mi><mo>=</mo><mn>1</mn></math></section>',
        'options_xml' => 0,
    ],
    'modern-option-filtering' => [
        'iterations' => 100,
        'selector' => 'section.card[data-state], template li, svg use',
        'html' => '<main><section class="card" data-state="active"><template><ul><li>one</li><li>two</li></ul></template></section><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use xlink:href="#card"></use></svg></main>',
        'options_xml' => $legacyOptionMask,
    ],
];

$parserClasses = [
    'legacy' => BenchmarkLegacyHtmlDomParser::class,
];

if (BenchmarkModernHtmlDomParser::supportsModernPath()) {
    $parserClasses['modern+projection'] = BenchmarkModernHtmlDomParser::class;
}

echo "scenario\tparser\tparse_ms\tselector_ms\tserialize_ms\tpeak_bytes\n";

foreach ($cases as $scenario => $config) {
    foreach ($parserClasses as $label => $parserClass) {
        $result = runBenchmark(
            $parserClass,
            $config['html'],
            $config['selector'],
            $config['iterations'],
            $config['options_xml']
        );

        echo $scenario, "\t",
        $label, "\t",
        $result['parse_ms'], "\t",
        $result['selector_ms'], "\t",
        $result['serialize_ms'], "\t",
        $result['peak_bytes'], "\n";
    }
}
