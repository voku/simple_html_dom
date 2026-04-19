<?php

use voku\helper\HtmlDomParser;

/**
 * @internal
 */
final class SimpleHtmlDomMemoryTest extends \PHPUnit\Framework\TestCase
{
    public function testMemoryLeak()
    {
        $dom = HtmlDomParser::file_get_html(__DIR__ . '/fixtures/memory_test.html');
        static::assertNotFalse($dom->findMultiOrFalse('h1, h2, h3'));

        for ($i = 0; $i < 100; ++$i) {
            $h = $dom->findMultiOrFalse('h1, h2, h3');

            foreach ($h as $tmp) {
                $tmp->innertext = 'foo';
            }

            $tempFile = \tempnam(\sys_get_temp_dir(), 'tmpTestFileFromHtmlDom');
            $dom->save($tempFile);
            \unlink($tempFile);

            if ($i === 1) {
                $memFirst = \memory_get_usage(false);
            }
        }

        static::assertLessThanOrEqual(1024, \memory_get_usage(false) - $memFirst);
    }
}
