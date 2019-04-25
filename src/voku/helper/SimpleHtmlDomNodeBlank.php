<?php

declare(strict_types=1);

namespace voku\helper;

class SimpleHtmlDomNodeBlank extends AbstractSimpleHtmlDomNode implements SimpleHtmlDomNodeInterface
{
    /**
     * @param string $selector
     * @param int|null   $idx
     *
     * @return null
     */
    public function find(string $selector, $idx = null)
    {
        return null;
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
     * Find nodes with a CSS selector.
     *
     * @param string $selector
     *
     * @return SimpleHtmlDomInterface[]|SimpleHtmlDomNodeInterface
     */
    public function findMulti(string $selector): SimpleHtmlDomNodeInterface
    {
        return new self();
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
