<?php

/**
 * Evidence for SHD-2: legacy DOMDocument is the public bridge target of Html5DomParser.
 *
 * @internal
 */
final class Html5DomParserLegacyDomAttributeCapabilityTest extends \PHPUnit\Framework\TestCase
{
    /**
     * XML DOM APIs cannot create attribute names that HTML accepts but XML rejects.
     *
     * @dataProvider invalidXmlAttributeNameProvider
     *
     * @param string $name
     */
    public function testLegacyDomCannotCreateHtmlOnlyAttributeNames(string $name)
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $element = $document->createElement('div');
        $document->appendChild($element);

        $this->expectException(\DOMException::class);

        $element->setAttribute($name, 'value');
    }

    /**
     * @return array<string, array{0: string}>
     */
    public function invalidXmlAttributeNameProvider()
    {
        return [
            'at sign' => ['@foo'],
            'comma' => [','],
        ];
    }
}
