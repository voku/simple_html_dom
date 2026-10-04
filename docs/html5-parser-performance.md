# HTML5 parser performance evidence

This document records the pre-release performance evidence for the opt-in
`Html5DomParser` shipped with 5.1.0. It complements
`build/benchmark_html5_parser.php`; it is not a promise that every input is faster.

## Method

The final 5.1.0 release candidate was measured on GitHub-hosted Ubuntu runners with
coverage instrumentation disabled. The existing benchmark was run on PHP 8.4.26 and
PHP 8.5.11.

For each runtime:

- the benchmark used 100 iterations per sample;
- five samples were collected per scenario after a discarded warm-up;
- parser order alternated between samples;
- the median was reported;
- the complete benchmark was repeated three times;
- parse, selector, serialization and peak-memory behavior were measured separately.

The compared work is a complete `loadHtml()` + selector + `html()` round-trip.

## Aggregate result

A factor below 1.00 means the HTML5 path was faster than `HtmlDomParser`.

| Runtime | Run 1 | Run 2 | Run 3 | Median |
| --- | ---: | ---: | ---: | ---: |
| PHP 8.4.26 | 0.85 | 0.84 | 0.84 | **0.84** |
| PHP 8.5.11 | 0.84 | 0.83 | 0.85 | **0.84** |

The aggregate result therefore stayed at the previously measured ~0.84 factor after
the final SHD-2 and SHD-3 compatibility fixes.

This does **not** mean every scenario is faster. Small synthetic fragments and some
mixed/foreign-content cases are slower, while the larger repository fixtures are
around parity or faster and dominate the aggregate workload.

Peak memory is higher for `Html5DomParser` because the bridge temporarily owns both
the modern PHP HTML5 DOM and the legacy `DOMDocument`. That architectural cost is
expected and should be considered for memory-sensitive workloads.

## Late-fix focused measurements

The final verification also measured the paths changed late in the 5.1.0 cycle.

| Operation | PHP 8.4.26 | PHP 8.5.11 |
| --- | ---: | ---: |
| safe HTML5 load + serialize | 0.0254 ms/op | 0.0113 ms/op |
| `@foo` HTML-only attribute load + serialize | 0.0662 ms/op | 0.0256 ms/op |
| legacy table mutation | 0.0532 ms/op | 0.0224 ms/op |
| HTML5 context-aware table mutation | 0.0686 ms/op | 0.0282 ms/op |
| safe HTML5 fragment mutation | 0.0687 ms/op | 0.0268 ms/op |
| `@foo` HTML5 fragment mutation | 0.0825 ms/op | 0.0320 ms/op |

The collision-safe SHD-2 attribute bridge is relatively more expensive when it is
actually needed, but its absolute measured cost remained below 0.1 ms per complete
operation. SHD-3 context-aware table mutation was roughly 26-29% slower than the
legacy mutation path in this micro-benchmark, and combining SHD-2 with SHD-3 added
about 20% compared with the equivalent safe HTML5 fragment mutation.

Correctness checks ran alongside the focused benchmark so a faster but semantically
wrong result could not count as a successful measurement. The checks covered implied
`tbody` construction and preservation of HTML-valid / XML-invalid `@foo` attributes.

## Reproducing

Run the maintained repository benchmark on PHP >= 8.4:

```shell
php build/benchmark_html5_parser.php 100
```

Absolute timings depend on CPU, PHP patch version, runner load and input. Compare the
parser factors and scenario shape rather than treating the GitHub-hosted millisecond
values as hardware-independent constants.
