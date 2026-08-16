<?php

declare(strict_types=1);

/*
 * Compare HtmlDomParser (libxml) with Html5DomParser (the HTML5 parser of PHP >= 8.4).
 *
 *   php build/benchmark_html5_parser.php [iterations]
 *
 * The measurement follows the method of pull request #146, which is the part of that attempt
 * worth keeping:
 *
 * - the parsers alternate order between samples, so a warm cache cannot favor whichever runs
 *   first;
 * - one warm-up sample is discarded before anything is recorded;
 * - the reported value is the median of the samples, not the mean, so one slow sample does
 *   not decide the result;
 * - the work is split into parse / selector / serialize, because a total hides which phase
 *   actually moved;
 * - peak memory is reported next to the time, since the HTML5 path builds a second document.
 *
 * The scenarios are the ones from that pull request as well: they cover a fragment, an
 * article, malformed markup, mixed content, a complete document, foreign content, and the
 * repository fixtures.
 */

require __DIR__ . '/../vendor/autoload.php';

use voku\helper\Html5DomParser;
use voku\helper\HtmlDomParser;

$iterations = isset($argv[1]) ? \max(1, (int) $argv[1]) : 50;
$samples = 5;

/**
 * @param class-string<HtmlDomParser> $parserClass
 *
 * @return array<string, float|int>
 */
function runBenchmarkOnce(string $parserClass, string $html, string $selector, int $iterations): array
{
    if (\function_exists('memory_reset_peak_usage')) {
        \memory_reset_peak_usage();
    }
    \gc_collect_cycles();

    $peakBefore = \memory_get_usage();
    $documents = [];

    // Keep the PR #146 phase layout: build the whole parser batch first, then query it, then
    // serialize it. This makes parse / selector / serialization timing directly comparable to
    // that benchmark and keeps its peak-memory pressure visible instead of freeing each DOM
    // before the next iteration.
    $start = \microtime(true);
    for ($i = 0; $i < $iterations; ++$i) {
        $dom = new $parserClass();
        $dom->loadHtml($html);
        $documents[] = $dom;
    }
    $parseSeconds = \microtime(true) - $start;

    $matches = 0;
    $start = \microtime(true);
    foreach ($documents as $dom) {
        $matches = \count($dom->findMulti($selector));
    }
    $selectorSeconds = \microtime(true) - $start;

    $length = 0;
    $start = \microtime(true);
    foreach ($documents as $dom) {
        $length = \strlen($dom->html());
    }
    $serializeSeconds = \microtime(true) - $start;

    $peakBytes = \max(0, \memory_get_peak_usage() - $peakBefore);
    unset($documents);

    return [
        'parse_ms'     => $parseSeconds / $iterations * 1000,
        'selector_ms'  => $selectorSeconds / $iterations * 1000,
        'serialize_ms' => $serializeSeconds / $iterations * 1000,
        'total_ms'     => ($parseSeconds + $selectorSeconds + $serializeSeconds) / $iterations * 1000,
        'peak_bytes'   => $peakBytes,
        'matches'      => $matches,
        'length'       => $length,
    ];
}

/**
 * @param array<string, class-string<HtmlDomParser>> $parserClasses
 *
 * @return array<string, array<string, float|int>>
 */
function runScenario(array $parserClasses, string $html, string $selector, int $iterations, int $samples): array
{
    $collected = [];

    // one warm-up round plus the recorded samples; the parser order alternates
    for ($sampleIndex = 0; $sampleIndex <= $samples; ++$sampleIndex) {
        $ordered = $sampleIndex % 2 === 0 ? $parserClasses : \array_reverse($parserClasses, true);

        foreach ($ordered as $label => $parserClass) {
            $result = runBenchmarkOnce($parserClass, $html, $selector, $iterations);

            if ($sampleIndex === 0) {
                continue;
            }

            $collected[$label][] = $result;
        }
    }

    $aggregated = [];
    foreach ($parserClasses as $label => $parserClass) {
        $samplesForLabel = $collected[$label];
        $metrics = ['parse_ms', 'selector_ms', 'serialize_ms', 'total_ms', 'peak_bytes'];

        foreach ($metrics as $metric) {
            $values = \array_column($samplesForLabel, $metric);
            \sort($values);
            $middle = (int) \floor((\count($values) - 1) / 2);
            $aggregated[$label][$metric] = \count($values) % 2 === 0
                ? ($values[$middle] + $values[$middle + 1]) / 2
                : $values[$middle];
        }

        $aggregated[$label]['matches'] = $samplesForLabel[0]['matches'];
        $aggregated[$label]['length'] = $samplesForLabel[0]['length'];
    }

    return $aggregated;
}

/**
 * @return array<string, array{html: string, selector: string}>
 */
function benchmarkScenarios(): array
{
    $fixtureDirectory = __DIR__ . '/../tests/fixtures';

    $scenarios = [
        'small-fragment' => [
            'html'     => '<main><p class="message">old</p><p class="message">new</p></main>',
            'selector' => '.message',
        ],
        'article' => [
            'html'     => '<article><header><h1>Title</h1><time datetime="2026-06-06">06 Jun</time></header><p>Intro</p>'
                          . '<section><p>Body</p><figure><img src="hero.jpg" alt="Hero"><figcaption><strong>Caption</strong>'
                          . '</figcaption></figure></section></article>',
            'selector' => 'article p',
        ],
        'malformed' => [
            'html'     => '<ul><li>one<li>two<li><strong>three</ul><div><span>tail',
            'selector' => 'li',
        ],
        'mixed-content' => [
            'html'     => '<div><template id="card"><section><h2>Title</h2><p>Body</p></section></template>'
                          . '<svg xmlns="http://www.w3.org/2000/svg"><circle id="dot"></circle></svg>'
                          . '<script type="text/x-custom-template"><% _.each(items, function(item) { %><li><%= item.name %></li><% }); %></script></div>',
            'selector' => 'template section, svg circle, script',
        ],
        'html-document' => [
            'html'     => '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Bench</title></head><body><main>'
                          . '<article class="item"><h2>One</h2><p>Body</p></article><article class="item"><h2>Two</h2><p>Body</p>'
                          . '</article></main></body></html>',
            'selector' => 'main > article.item',
        ],
        'foreign-content' => [
            'html'     => '<section><svg xmlns="http://www.w3.org/2000/svg"><defs><g id="icon"><path d="M0 0h10v10z"></path></g>'
                          . '</defs><use href="#icon"></use></svg><math xmlns="http://www.w3.org/1998/Math/MathML"><mi>x</mi>'
                          . '<mo>=</mo><mn>1</mn></math></section>',
            'selector' => 'svg use, math mi, math mo',
        ],
    ];

    foreach (['big.html' => 'div', 'test_page.html' => 'a', 'issue81.html' => 'div'] as $fixture => $selector) {
        $html = @\file_get_contents($fixtureDirectory . '/' . $fixture);
        if ($html === false) {
            continue;
        }

        $scenarios['fixture:' . $fixture] = ['html' => $html, 'selector' => $selector];
    }

    return $scenarios;
}

echo 'PHP ' . \PHP_VERSION . "\n";
echo 'HTML5 parser supported: ' . (Html5DomParser::isHtml5ParserSupported() ? 'yes' : 'no') . "\n";
echo 'Iterations per sample: ' . $iterations . ', samples per scenario: ' . $samples . " (median, warm-up discarded)\n\n";

if (!Html5DomParser::isHtml5ParserSupported()) {
    echo "Nothing to compare: this runtime has no \"\\Dom\\HTMLDocument\".\n";

    exit(0);
}

$parserClasses = [
    'legacy' => HtmlDomParser::class,
    'html5'  => Html5DomParser::class,
];

\printf(
    "%-22s %9s %9s %9s %9s %8s %9s %7s %7s\n",
    'scenario',
    'parse',
    'selector',
    'serialize',
    'total',
    'factor',
    'peak',
    'legacy',
    'html5'
);
\printf(
    "%-22s %9s %9s %9s %9s %8s %9s %7s %7s\n",
    '',
    'ms',
    'ms',
    'ms',
    'ms',
    'vs libxml',
    'vs libxml',
    'nodes',
    'nodes'
);
echo \str_repeat('-', 104) . "\n";

$totalLegacy = 0.0;
$totalHtml5 = 0.0;

foreach (benchmarkScenarios() as $scenario => $config) {
    $results = runScenario($parserClasses, $config['html'], $config['selector'], $iterations, $samples);

    $legacy = $results['legacy'];
    $html5 = $results['html5'];

    $totalLegacy += $legacy['total_ms'];
    $totalHtml5 += $html5['total_ms'];

    \printf(
        "%-22s %9.3f %9.3f %9.3f %9.3f %8.2f %9s %7d %7d\n",
        $scenario,
        $html5['parse_ms'],
        $html5['selector_ms'],
        $html5['serialize_ms'],
        $html5['total_ms'],
        $legacy['total_ms'] > 0.0 ? $html5['total_ms'] / $legacy['total_ms'] : 0.0,
        $legacy['peak_bytes'] > 0 ? \sprintf('%.2f', $html5['peak_bytes'] / $legacy['peak_bytes']) : 'n/a',
        $legacy['matches'],
        $html5['matches']
    );

    if ($legacy['matches'] !== $html5['matches']) {
        \printf(
            "%-22s note: the parsers disagree on the node count, which is what the HTML5 tree construction changes\n",
            ''
        );
    }
}

echo \str_repeat('-', 104) . "\n";
\printf(
    "%-22s %9s %9s %9s %9.3f %8.2f\n",
    'total',
    '',
    '',
    '',
    $totalHtml5,
    $totalLegacy > 0.0 ? $totalHtml5 / $totalLegacy : 0.0
);

echo "\n";
echo "The times are the HTML5 parser; the factor columns compare it against HtmlDomParser.\n";
echo "A factor below 1.00 means the HTML5 path was faster for that scenario.\n";
echo "A different node count is not an error: the HTML5 parser builds the tree a browser\n";
echo "would build, so it can find markup that the libxml parser dropped or nested differently.\n";

exit(0);
