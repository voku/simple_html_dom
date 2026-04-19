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

        static::assertSame(
            [
                'Example Produkt' => [
                    'Produkt' => 'Example Produkt',
                    'DatumTime' => '2024-01-03 12:00:00',
                    'Link' => 'https://example.com/produkt',
                    'Beschreibung' => 'Gefahr: Rückruf<br />\nWeitere Informationen',
                    'Gefahr' => '!!!!!!!!!!!!!!!',
                ],
            ],
            $result
        );
    }
}
