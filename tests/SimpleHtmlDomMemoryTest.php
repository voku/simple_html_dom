<?php

use Voku\Helper\HtmlDomParser;

/**
 * @internal
 */
final class SimpleHtmlDomMemoryTest extends \PHPUnit\Framework\TestCase
{
    public function testMemoryLeak()
    {
        if (PHP_MAJOR_VERSION == 7 && PHP_MINOR_VERSION === 3) {
            self::markTestSkipped('not working in PHP 7.3?!');
        }

        $dom = HtmlDomParser::file_get_html('https://www.php.net/');
        for ($i = 0; $i < 100; ++$i) {
            $h = $dom->findMultiOrFalse('h1, h2, h3');

            foreach ($h as $tmp) {
                $tmp->innertext = 'foo';
            }

            $tempFile = \tempnam(\sys_get_temp_dir(), 'tmpTestFileFromHtmlDom');
            $dom->save($tempFile);
            unset($tempFile);

            if ($i === 1) {
                $memFirst = \memory_get_usage(false);
            }
        }

        static::assertSame(\memory_get_usage(false), $memFirst);
    }
}
