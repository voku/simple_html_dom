<?php

namespace voku\helper;

/**
 * @property-read string[] $outertext
 *                                    <p>Get dom node's outer html.</p>
 * @property-read string[] $plaintext
 *                                    <p>Get dom node's plain text.</p>
 */
interface SimpleHtmlDomNodeInterface extends \IteratorAggregate
{
    /**
     * Find list of nodes with a CSS selector.
     *
     * @param string $selector
     * @param int    $idx
     *
     * @return SimpleHtmlDomNode|SimpleHtmlDomNode[]|null
     */
    public function find(string $selector, $idx = null);

    /**
     * Find one node with a CSS selector.
     *
     * @param string $selector
     *
     * @return SimpleHtmlDomNode|null
     */
    public function findOne(string $selector);

    /**
     * Find nodes with a CSS selector.
     *
     * @param string $selector
     *
     * @return SimpleHtmlDomInterface[]|SimpleHtmlDomNodeInterface
     */
    public function findMulti(string $selector): self;

    /**
     * Get html of elements.
     *
     * @return string[]
     */
    public function innerHtml(): array;

    /**
     * alias for "$this->innerHtml()" (added for compatibly-reasons with v1.x)
     */
    public function innertext();

    /**
     * alias for "$this->innerHtml()" (added for compatibly-reasons with v1.x)
     */
    public function outertext();

    /**
     * Get plain text.
     *
     * @return string[]
     */
    public function text(): array;

    /**
     * @param string $name
     *
     * @return array|null
     */
    public function __get($name);

    /**
     * @param string $selector
     * @param int    $idx
     *
     * @return SimpleHtmlDomNodeInterface|SimpleHtmlDomNodeInterface[]|null
     */
    public function __invoke($selector, $idx = null);

    /**
     * @return string
     */
    public function __toString();
}
