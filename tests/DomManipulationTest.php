<?php

use PHPUnit\Framework\TestCase;

use Voku\Helper\HtmlDomParser;

/**
 * Tests the DOM manipulation ability of the parser
 *
 * copy&past from https://github.com/simplehtmldom/simplehtmldom/
 *
 * @internal
 */
final class DomManipulationTest extends TestCase
{
    /**
     * @var HtmlDomParser|null
     */
    private $dom;

    public function testDomShouldAcceptNestedElements()
    {
        $this->dom = new HtmlDomParser();

        $expected = "<html>\n<head></head>\n<body></body>\n</html>";

        $html = $this->dom->getDocument()->createElement('html');
        $head = $this->dom->getDocument()->createElement('head');
        $body = $this->dom->getDocument()->createElement('body');

        $this->dom->getDocument()->appendChild($html);

        $html->appendChild($head);
        $html->appendChild($body);

        static::assertSame($expected, $this->dom->save());
    }

    public function testDomShouldFindAddedElements()
    {
        $this->dom = new HtmlDomParser();

        $html = $this->dom->getDocument()->createElement('html');
        $head = $this->dom->getDocument()->createElement('head');
        $body = $this->dom->getDocument()->createElement('body');

        $this->dom->getDocument()->appendChild($html);

        $html
            ->appendChild($head)
            ->appendChild($body);

        static::assertNotNull($this->dom->find('html', 0));
        static::assertNotNull($this->dom->find('head', 0));
        static::assertNotNull($this->dom->find('body', 0));
    }

    public function testDomShouldFindElementsAddedToExistingDom()
    {
        $this->dom = new HtmlDomParser();

        $this->dom->load('<html></html>');

        $head = $this->dom->getDocument()->createElement('head');
        $body = $this->dom->getDocument()->createElement('body');

        $this->dom->getDocument()->appendChild($head);
        $this->dom->getDocument()->appendChild($body);

        static::assertNotNull($this->dom->find('html', 0));
        static::assertNotNull($this->dom->find('head', 0));
        static::assertNotNull($this->dom->find('body', 0));
    }

    public function testDomShouldFindElementsAddedToExistingNestedDom()
    {
        $this->dom = new HtmlDomParser();

        $this->dom->load('<html><body></body></html>');

        $table = $this->dom->getDocument()->createElement('table');
        $tr = $this->dom->getDocument()->createElement('tr');

        $this->dom->getDocument()->appendChild($table);
        $table->appendChild($tr);

        static::assertNotNull($this->dom->find('table', 0));
        static::assertNotNull($this->dom->find('tr', 0));
    }

    public function testDomShouldFindElementsAddInReverse()
    {
        $this->dom = new HtmlDomParser();

        $html = $this->dom->getDocument()->createElement('html');
        $head = $this->dom->getDocument()->createElement('head');
        $body = $this->dom->getDocument()->createElement('body');

        $html
            ->appendChild($head)
            ->appendChild($body);

        $this->dom->getDocument()->appendChild($html);

        static::assertNotNull($this->dom->find('html', 0));
        static::assertNotNull($this->dom->find('head', 0));
        static::assertNotNull($this->dom->find('body', 0));
    }
}
