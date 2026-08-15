<?php

declare(strict_types=1);

/*
 * Compare the default libxml parser with the opt-in HTML5 parser of PHP >= 8.4.
 *
 * The HTML5 parser has to be bridged back into a "\DOMDocument", because that is what this
 * library hands out, so it cannot be free. This script measures what it costs on real input
 * instead of guessing:
 *
 *   php build/benchmark_html5_parser.php [iterations]
 *
 * It reports the wall time of one complete parse per fixture - what a caller pays for
 * "HtmlDomParser::str_get_html()" plus one query and one serialization - for both parsers.
 */

require __DIR__ . '/../vendor/autoload.php';

use voku\helper\HtmlDomParser;

$iterations = isset($argv[1]) ? \max(1, (int) $argv[1]) : 20;

$fixtureDirectory = __DIR__ . '/../tests/fixtures';
$fixtures = [
    'big.html',
    'test_page.html',
    'issue81.html',
    'test_mail.html',
    'horrible.html',
];

/**
 * @param string $html
 * @param bool   $useHtml5Parser
 *
 * @return array{time: float, nodes: int, length: int, html5: bool}
 */
function benchmarkOneParse(string $html, bool $useHtml5Parser): array
{
    $start = \microtime(true);

    $dom = new HtmlDomParser();
    $dom->useHtml5Parser($useHtml5Parser);
    $dom->loadHtml($html);

    $nodes = \count($dom->findMulti('div'));
    $length = \strlen($dom->html());

    return [
        'time'   => \microtime(true) - $start,
        'nodes'  => $nodes,
        'length' => $length,
        'html5'  => $dom->getIsDOMDocumentCreatedWithHtml5Parser(),
    ];
}

echo 'PHP ' . \PHP_VERSION . "\n";
echo 'HTML5 parser supported: ' . (HtmlDomParser::isHtml5ParserSupported() ? 'yes' : 'no') . "\n";
echo 'Iterations per fixture: ' . $iterations . "\n\n";

if (!HtmlDomParser::isHtml5ParserSupported()) {
    echo "Nothing to compare: this runtime has no \"\\Dom\\HTMLDocument\".\n";

    exit(0);
}

\printf(
    "%-22s %7s %12s %12s %8s %10s %10s\n",
    'fixture',
    'KiB',
    'legacy (ms)',
    'html5 (ms)',
    'factor',
    'legacy div',
    'html5 div'
);
echo \str_repeat('-', 88) . "\n";

$totalLegacy = 0.0;
$totalHtml5 = 0.0;

foreach ($fixtures as $fixture) {
    $path = $fixtureDirectory . '/' . $fixture;
    $html = \file_get_contents($path);
    if ($html === false) {
        echo 'skipped (unreadable): ' . $fixture . "\n";

        continue;
    }

    // warm up both paths, so the first measured run does not pay for lazy autoloading
    benchmarkOneParse($html, false);
    benchmarkOneParse($html, true);

    $legacyTime = 0.0;
    $html5Time = 0.0;
    $legacyResult = [];
    $html5Result = [];

    for ($i = 0; $i < $iterations; ++$i) {
        $legacyResult = benchmarkOneParse($html, false);
        $legacyTime += $legacyResult['time'];

        $html5Result = benchmarkOneParse($html, true);
        $html5Time += $html5Result['time'];
    }

    if ($html5Result['html5'] === false) {
        echo 'note: the HTML5 parser refused ' . $fixture . ' and fell back to the legacy parser' . "\n";
    }

    $totalLegacy += $legacyTime;
    $totalHtml5 += $html5Time;

    \printf(
        "%-22s %7.1f %12.3f %12.3f %8.2f %10d %10d\n",
        $fixture,
        \strlen($html) / 1024,
        $legacyTime / $iterations * 1000,
        $html5Time / $iterations * 1000,
        $legacyTime > 0.0 ? $html5Time / $legacyTime : 0.0,
        $legacyResult['nodes'],
        $html5Result['nodes']
    );
}

echo \str_repeat('-', 88) . "\n";
\printf(
    "%-22s %7s %12.3f %12.3f %8.2f\n",
    'total',
    '',
    $totalLegacy / $iterations * 1000,
    $totalHtml5 / $iterations * 1000,
    $totalLegacy > 0.0 ? $totalHtml5 / $totalLegacy : 0.0
);

echo "\n";
echo "The factor column is the cost of the HTML5 parser relative to the default parser.\n";
echo "A different node count is not an error: the HTML5 parser builds the tree a browser\n";
echo "would build, so it can find markup that the libxml parser dropped or nested differently.\n";

// the numbers above are the result; the exit code only reports that the run itself worked
exit(0);
