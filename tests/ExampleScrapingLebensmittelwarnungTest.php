<?php

require_once __DIR__ . '/../example/example_scraping_lebensmittelwarnung.php';

/**
 * @internal
 */
final class ExampleScrapingLebensmittelwarnungTest extends \PHPUnit\Framework\TestCase
{
    public function testScrapingLebensmittelwarnungUsesXmlParsingForRssFields()
    {
        $result = scraping_lebensmittelwarnung(__DIR__ . '/fixtures/lebensmittelwarnung.xml');

        static::assertSame('Example Produkt', $result['Example Produkt']['Produkt']);
        static::assertSame('2024-01-03 12:00:00', $result['Example Produkt']['DatumTime']);
        static::assertSame('https://example.com/produkt', $result['Example Produkt']['Link']);
        static::assertSame("Gefahr: Rückruf<br />\nWeitere Informationen", $result['Example Produkt']['Beschreibung']);
        static::assertSame('!!!!!!!!!!!!!!!', $result['Example Produkt']['Gefahr']);
    }
}
