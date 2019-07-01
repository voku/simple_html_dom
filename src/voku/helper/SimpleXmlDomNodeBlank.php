<?php

declare(strict_types=1);

namespace voku\helper;

class SimpleXmlDomNodeBlank extends AbstractSimpleXmlDomNode implements SimpleXmlDomNodeInterface
{
    /**
     * @param string   $selector
     * @param int|null $idx
     *
     * @return null
     */
    public function find(string $selector, $idx = null)
    {
        return null;
    }

    /**
     * Find nodes with a CSS selector.
     *
     * @param string $selector
     *
     * @return SimpleXmlDomInterface[]|SimpleXmlDomNodeInterface
     */
    public function findMulti(string $selector): SimpleXmlDomNodeInterface
    {
        return new self();
    }

    /**
     * Find one node with a CSS selector.
     *
     * @param string $selector
     *
     * @return null
     */
    public function findOne(string $selector)
    {
        return null;
    }

    /**
     * @return string[]
     */
    public function innerHtml(): array
    {
        return [];
    }

    /**
     * alias for "$this->innerHtml()" (added for compatibly-reasons with v1.x)
     */
    public function innertext()
    {
        return [];
    }

    /**
     * alias for "$this->innerHtml()" (added for compatibly-reasons with v1.x)
     */
    public function outertext()
    {
        return [];
    }

    /**
     * @return string[]
     */
    public function text(): array
    {
        return [];
    }
}
