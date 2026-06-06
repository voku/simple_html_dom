<?php

declare(strict_types=1);

use voku\helper\HtmlDomParser;

require dirname(__DIR__) . '/vendor/autoload.php';

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
function runBenchmark(string $parserClass, string $html, string $selector, int $iterations): array
{
    if (\function_exists('memory_reset_peak_usage')) {
        \memory_reset_peak_usage();
    }

    \gc_collect_cycles();

    $parseStart = \microtime(true);
    $documents = [];
    for ($i = 0; $i < $iterations; ++$i) {
        $documents[] = $parserClass::str_get_html($html);
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

$cases = [
    'small-fragment' => [
        'iterations' => 400,
        'selector' => '.message',
        'html' => '<main><p class="message">old</p><p class="message">new</p></main>',
    ],
    'article' => [
        'iterations' => 125,
        'selector' => 'article p',
        'html' => '<article><header><h1>Title</h1><time datetime="2026-06-06">06 Jun</time></header><p>Intro</p><section><p>Body</p><figure><img src="hero.jpg" alt="Hero"><figcaption><strong>Caption</strong></figcaption></figure></section></article>',
    ],
    'malformed-large' => [
        'iterations' => 75,
        'selector' => 'li',
        'html' => '<ul><li>one<li>two<li><strong>three</ul><div><span>tail',
    ],
    'mixed-content' => [
        'iterations' => 125,
        'selector' => 'template section, svg circle, script',
        'html' => '<div><template id="card"><section><h2>Title</h2><p>Body</p></section></template><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use xlink:href="#icon"></use><circle id="dot"></circle></svg><script type="text/x-custom-template"><% _.each(items, function(item) { %><li><%= item.name %></li><% }); %></script></div>',
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
            $config['iterations']
        );

        echo $scenario, "\t",
            $label, "\t",
            $result['parse_ms'], "\t",
            $result['selector_ms'], "\t",
            $result['serialize_ms'], "\t",
            $result['peak_bytes'], "\n";
    }
}
