<?php

declare(strict_types=1);

use voku\helper\HtmlDomParser;

require_once dirname(__DIR__) . '/vendor/autoload.php';

final class BenchmarkLegacyHtmlDomParser extends HtmlDomParser
{
    /**
     * @var array<string, float>
     */
    private static $instrumentation = [
        'backend_ms' => 0.0,
    ];

    public static function resetInstrumentation(): void
    {
        self::$instrumentation = [
            'backend_ms' => 0.0,
        ];
    }

    /**
     * @return array<string, float>
     */
    public static function getInstrumentation(): array
    {
        return self::$instrumentation;
    }

    protected function shouldUseModernHtmlDocument(int $optionsXml): bool
    {
        return false;
    }

    protected function createLegacyDocumentWithLibxml(string $html, int $optionsXml): \DOMDocument
    {
        $start = \microtime(true);

        try {
            return parent::createLegacyDocumentWithLibxml($html, $optionsXml);
        } finally {
            self::$instrumentation['backend_ms'] += (\microtime(true) - $start) * 1000;
        }
    }
}

class BenchmarkModernHtmlDomParser extends HtmlDomParser
{
    /**
     * @var array<string, float>
     */
    private static $instrumentation = [
        'backend_ms' => 0.0,
        'modern_create_ms' => 0.0,
        'bridge_ms' => 0.0,
        'projection_ms' => 0.0,
    ];

    /**
     * @var bool
     */
    private static $rejectLegacyFallback = false;

    /**
     * @var bool
     */
    private static $modernParseAttempted = false;

    public static function resetInstrumentation(): void
    {
        self::$instrumentation = [
            'backend_ms' => 0.0,
            'modern_create_ms' => 0.0,
            'bridge_ms' => 0.0,
            'projection_ms' => 0.0,
        ];
        self::$modernParseAttempted = false;
    }

    /**
     * @return array<string, float>
     */
    public static function getInstrumentation(): array
    {
        return self::$instrumentation;
    }

    public static function rejectLegacyFallback(bool $rejectLegacyFallback): void
    {
        self::$rejectLegacyFallback = $rejectLegacyFallback;
    }

    public static function supportsModernPath(): bool
    {
        return \class_exists('Dom\\HTMLDocument')
            && \method_exists('Dom\\HTMLDocument', 'createFromString');
    }

    protected function shouldUseModernHtmlDocument(int $optionsXml): bool
    {
        return self::supportsModernPath();
    }

    protected function createLegacyDocumentFromModernParser(string $html, int $optionsXml): \DOMDocument
    {
        self::$modernParseAttempted = true;

        $backendStart = \microtime(true);

        try {
            return parent::createLegacyDocumentFromModernParser($html, $optionsXml);
        } finally {
            self::$instrumentation['backend_ms'] += (\microtime(true) - $backendStart) * 1000;
        }
    }

    protected function createLegacyDocumentViaXmlBridge($modernDocument): \DOMDocument
    {
        $bridgeStart = \microtime(true);

        try {
            return parent::createLegacyDocumentViaXmlBridge($modernDocument);
        } finally {
            self::$instrumentation['bridge_ms'] += (\microtime(true) - $bridgeStart) * 1000;
        }
    }

    protected function shouldUseModernXmlInputBridgeShortcut(): bool
    {
        return false;
    }

    protected function createLegacyDocumentViaXmlInputBridge(string $html): ?\DOMDocument
    {
        $bridgeStart = \microtime(true);

        try {
            return parent::createLegacyDocumentViaXmlInputBridge($html);
        } finally {
            self::$instrumentation['bridge_ms'] += (\microtime(true) - $bridgeStart) * 1000;
        }
    }

    protected function createLegacyDocumentWithLibxml(string $html, int $optionsXml): \DOMDocument
    {
        if (self::$rejectLegacyFallback && self::$modernParseAttempted) {
            throw new \RuntimeException('BenchmarkModernHtmlDomParser unexpectedly fell back to the legacy parser.');
        }

        return parent::createLegacyDocumentWithLibxml($html, $optionsXml);
    }

    protected function createLegacyDocumentViaCompatibilityProjection($modernDocument): \DOMDocument
    {
        $projectionStart = \microtime(true);

        try {
            return parent::createLegacyDocumentViaCompatibilityProjection($modernDocument);
        } finally {
            self::$instrumentation['projection_ms'] += (\microtime(true) - $projectionStart) * 1000;
        }
    }

    /**
     * @return object
     */
    protected function createModernHtmlDocument(string $html, int $optionsXml)
    {
        $modernCreateStart = \microtime(true);

        try {
            return parent::createModernHtmlDocument($html, $optionsXml);
        } finally {
            self::$instrumentation['modern_create_ms'] += (\microtime(true) - $modernCreateStart) * 1000;
        }
    }
}

final class BenchmarkProjectedModernHtmlDomParser extends BenchmarkModernHtmlDomParser
{
    protected function canUseModernXmlBridge(string $html): bool
    {
        return false;
    }
}

/**
 * @param class-string<HtmlDomParser> $parserClass
 *
 * @return array<string, float|int|string>
 */
function runBenchmarkOnce(string $parserClass, string $html, string $selector, int $iterations, int $optionsXml): array
{
    if (\method_exists($parserClass, 'resetInstrumentation')) {
        $parserClass::resetInstrumentation();
    }

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

    $instrumentation = [];
    if (\method_exists($parserClass, 'getInstrumentation')) {
        /** @var array<string, float> $instrumentation */
        $instrumentation = $parserClass::getInstrumentation();
    }

    return [
        'parser' => $parserClass,
        'parse_ms' => \round($parseTime * 1000, 3),
        'selector_ms' => \round($selectorTime * 1000, 3),
        'serialize_ms' => \round($serializationTime * 1000, 3),
        'total_ms' => \round(($parseTime + $selectorTime + $serializationTime) * 1000, 3),
        'peak_bytes' => \memory_get_peak_usage(true),
        'backend_ms' => \round($instrumentation['backend_ms'] ?? 0.0, 3),
        'modern_create_ms' => \round($instrumentation['modern_create_ms'] ?? 0.0, 3),
        'bridge_ms' => \round($instrumentation['bridge_ms'] ?? 0.0, 3),
        'projection_ms' => \round($instrumentation['projection_ms'] ?? 0.0, 3),
    ];
}

/**
 * @param array<string, class-string<HtmlDomParser>> $parserClasses
 *
 * @return array<string, array<string, float|int|string>>
 */
function runScenarioBenchmarks(array $parserClasses, string $html, string $selector, int $iterations, int $optionsXml, int $samples = 5, int $warmupRuns = 1): array
{
    $resultsByLabel = [];

    for ($sampleIndex = 0; $sampleIndex < ($samples + $warmupRuns); ++$sampleIndex) {
        $orderedParsers = $sampleIndex % 2 === 0
            ? $parserClasses
            : \array_reverse($parserClasses, true);

        foreach ($orderedParsers as $label => $parserClass) {
            $result = runBenchmarkOnce($parserClass, $html, $selector, $iterations, $optionsXml);

            if ($sampleIndex < $warmupRuns) {
                continue;
            }

            $resultsByLabel[$label][] = $result;
        }
    }

    $aggregatedResults = [];
    foreach ($parserClasses as $label => $parserClass) {
        $aggregatedResults[$label] = aggregateBenchmarkSamples($resultsByLabel[$label] ?? [], $parserClass);
    }

    return $aggregatedResults;
}

/**
 * @param array<int, array<string, float|int|string>> $samples
 * @param class-string<HtmlDomParser>                 $parserClass
 *
 * @return array<string, float|int|string>
 */
function aggregateBenchmarkSamples(array $samples, string $parserClass): array
{
    if ($samples === []) {
        throw new \RuntimeException('No benchmark samples collected for parser "' . $parserClass . '".');
    }

    $metrics = [
        'parse_ms',
        'selector_ms',
        'serialize_ms',
        'total_ms',
        'peak_bytes',
        'backend_ms',
        'modern_create_ms',
        'bridge_ms',
        'projection_ms',
    ];

    $aggregated = [
        'parser' => $parserClass,
    ];

    foreach ($metrics as $metric) {
        $values = [];

        foreach ($samples as $sample) {
            $values[] = (float) $sample[$metric];
        }

        \sort($values);
        $middleIndex = (int) \floor(\count($values) / 2);

        if (\count($values) % 2 === 0) {
            $median = ($values[$middleIndex - 1] + $values[$middleIndex]) / 2;
        } else {
            $median = $values[$middleIndex];
        }

        $aggregated[$metric] = $metric === 'peak_bytes'
            ? (int) \round($median)
            : \round($median, 3);
    }

    return $aggregated;
}

/**
 * @param array<string, float|int|string> $baseline
 * @param array<string, float|int|string> $result
 */
function formatTimeComparison(array $baseline, array $result, string $metric): string
{
    $baselineValue = (float) $baseline[$metric];
    if ($baselineValue === 0.0) {
        return 'n/a';
    }

    $delta = (float) $result[$metric] - $baselineValue;
    $percent = ($delta / $baselineValue) * 100;
    $direction = $delta <= 0.0 ? 'faster' : 'slower';

    return \sprintf('%+.3fms/%+.1f%%-%s', $delta, $percent, $direction);
}

/**
 * @param array<string, float|int|string> $baseline
 * @param array<string, float|int|string> $result
 */
function formatMemoryComparison(array $baseline, array $result): string
{
    $baselineValue = (int) $baseline['peak_bytes'];
    if ($baselineValue === 0) {
        return 'n/a';
    }

    $delta = (int) $result['peak_bytes'] - $baselineValue;
    $percent = ($delta / $baselineValue) * 100;
    $direction = $delta <= 0 ? 'lower' : 'higher';

    return \sprintf('%+dB/%+.1f%%-%s', $delta, $percent, $direction);
}

/**
 * @param array<string, float|int|string> $result
 */
function formatComponentShare(array $result, string $componentMetric, string $totalMetric): string
{
    $componentValue = (float) $result[$componentMetric];
    $totalValue = (float) $result[$totalMetric];

    if ($componentValue <= 0.0 || $totalValue <= 0.0) {
        return 'n/a';
    }

    return \sprintf('%.1f%%', ($componentValue / $totalValue) * 100);
}

/**
 * @param class-string<HtmlDomParser> $parserClass
 *
 * @return array<string, mixed>
 */
function createScenarioSignature(string $parserClass, string $html, string $selector, int $optionsXml): array
{
    $dom = $parserClass::str_get_html($html, $optionsXml);

    return [
        'html' => $dom->html(),
        'selector_count' => \count($dom->findMulti($selector)),
    ];
}

/**
 * @param array<string, class-string<HtmlDomParser>> $parserClasses
 *
 * @return array{0: array<string, class-string<HtmlDomParser>>, 1: array<string, string>}
 */
function getComparableScenarioParsers(array $parserClasses, string $html, string $selector, int $optionsXml): array
{
    if (!isset($parserClasses['legacy'])) {
        return [$parserClasses, []];
    }

    $legacySignature = createScenarioSignature($parserClasses['legacy'], $html, $selector, $optionsXml);

    $comparableParsers = [
        'legacy' => $parserClasses['legacy'],
    ];
    $invalidReasons = [];

    foreach ($parserClasses as $label => $parserClass) {
        if ($label === 'legacy') {
            continue;
        }

        $signature = createScenarioSignature($parserClass, $html, $selector, $optionsXml);

        if ($signature['selector_count'] !== $legacySignature['selector_count']) {
            $invalidReasons[$label] = 'selector-count-mismatch';

            continue;
        }

        if ($signature['html'] !== $legacySignature['html']) {
            $invalidReasons[$label] = 'serialization-mismatch';

            continue;
        }

        $comparableParsers[$label] = $parserClass;
    }

    return [$comparableParsers, $invalidReasons];
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
    BenchmarkModernHtmlDomParser::rejectLegacyFallback(true);
    BenchmarkProjectedModernHtmlDomParser::rejectLegacyFallback(true);
    $parserClasses['modern+projection'] = BenchmarkProjectedModernHtmlDomParser::class;
    $parserClasses['modern+bridge'] = BenchmarkModernHtmlDomParser::class;
}

echo "scenario\tparser\tcomparison_status\tparse_ms\tparse_vs_legacy\tselector_ms\tselector_vs_legacy\tserialize_ms\tserialize_vs_legacy\ttotal_ms\ttotal_vs_legacy\ttotal_vs_modern_projection\tpeak_bytes\tpeak_vs_legacy\tbackend_ms\tbackend_vs_legacy\tbackend_vs_modern_projection\tbackend_vs_parse\tmodern_create_ms\tbridge_ms\tbridge_vs_backend\tprojection_ms\tprojection_vs_backend\n";

foreach ($cases as $scenario => $config) {
    list($comparableParsers, $invalidReasons) = getComparableScenarioParsers(
        $parserClasses,
        $config['html'],
        $config['selector'],
        $config['options_xml']
    );
    if (isset($config['modern_skip_reason'])) {
        foreach ($parserClasses as $label => $parserClass) {
            if ($label === 'legacy') {
                continue;
            }

            unset($comparableParsers[$label]);
            $invalidReasons[$label] = $config['modern_skip_reason'];
        }
    }

    $results = runScenarioBenchmarks(
        $comparableParsers,
        $config['html'],
        $config['selector'],
        $config['iterations'],
        $config['options_xml']
    );
    $baseline = $results['legacy'];

    foreach ($parserClasses as $label => $parserClass) {
        if (isset($invalidReasons[$label])) {
            echo $scenario, "\t",
            $label, "\t",
            $invalidReasons[$label], "\t",
            "n/a\t",
            "n/a\t",
            "n/a\t",
            "n/a\t",
            "n/a\t",
            "n/a\t",
            "n/a\t",
            "n/a\t",
            "n/a\t",
            "n/a\t",
            "n/a\t",
            "n/a\t",
            "n/a\t",
            "n/a\t",
            "n/a\t",
            "n/a\t",
            "n/a\t",
            "n/a\n";

            continue;
        }

        $result = $results[$label];
        $comparisonStatus = $label === 'legacy' ? 'baseline' : 'comparable';
        $parseComparison = $label === 'legacy' ? 'baseline' : formatTimeComparison($baseline, $result, 'parse_ms');
        $selectorComparison = $label === 'legacy' ? 'baseline' : formatTimeComparison($baseline, $result, 'selector_ms');
        $serializeComparison = $label === 'legacy' ? 'baseline' : formatTimeComparison($baseline, $result, 'serialize_ms');
        $totalComparison = $label === 'legacy' ? 'baseline' : formatTimeComparison($baseline, $result, 'total_ms');
        $projectionTotalComparison = 'n/a';
        if (isset($results['modern+projection'])) {
            if ($label === 'modern+projection') {
                $projectionTotalComparison = 'baseline';
            } elseif ($label !== 'legacy') {
                $projectionTotalComparison = formatTimeComparison($results['modern+projection'], $result, 'total_ms');
            }
        }
        $memoryComparison = $label === 'legacy' ? 'baseline' : formatMemoryComparison($baseline, $result);
        $backendComparison = $label === 'legacy' ? 'baseline' : formatTimeComparison($baseline, $result, 'backend_ms');
        $projectionBackendComparison = 'n/a';
        if (isset($results['modern+projection'])) {
            if ($label === 'modern+projection') {
                $projectionBackendComparison = 'baseline';
            } elseif ($label !== 'legacy') {
                $projectionBackendComparison = formatTimeComparison($results['modern+projection'], $result, 'backend_ms');
            }
        }
        $backendShare = formatComponentShare($result, 'backend_ms', 'parse_ms');
        $bridgeShare = formatComponentShare($result, 'bridge_ms', 'backend_ms');
        $projectionShare = formatComponentShare($result, 'projection_ms', 'backend_ms');
        $modernCreate = (float) $result['modern_create_ms'] > 0.0 ? (string) $result['modern_create_ms'] : 'n/a';
        $bridge = (float) $result['bridge_ms'] > 0.0 ? (string) $result['bridge_ms'] : 'n/a';
        $projection = (float) $result['projection_ms'] > 0.0 ? (string) $result['projection_ms'] : 'n/a';

        echo $scenario, "\t",
        $label, "\t",
        $comparisonStatus, "\t",
        $result['parse_ms'], "\t",
        $parseComparison, "\t",
        $result['selector_ms'], "\t",
        $selectorComparison, "\t",
        $result['serialize_ms'], "\t",
        $serializeComparison, "\t",
        $result['total_ms'], "\t",
        $totalComparison, "\t",
        $projectionTotalComparison, "\t",
        $result['peak_bytes'], "\t",
        $memoryComparison, "\t",
        $result['backend_ms'], "\t",
        $backendComparison, "\t",
        $projectionBackendComparison, "\t",
        $backendShare, "\t",
        $modernCreate, "\t",
        $bridge, "\t",
        $bridgeShare, "\t",
        $projection, "\t",
        $projectionShare, "\n";
    }
}
