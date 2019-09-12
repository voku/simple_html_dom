<?php

namespace voku\helper;

/**
 * @property-read int      $length
 *                                    <p>The list items count.</p>
 * @property-read string[] $outertext
 *                                    <p>Get dom node's outer html.</p>
 * @property-read string[] $plaintext
 *                                    <p>Get dom node's plain text.</p>
 */
interface SimpleXmlDomNodeInterface extends \IteratorAggregate
{
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
     * @return SimpleXmlDomNodeInterface|SimpleXmlDomNodeInterface[]|null
     */
    public function __invoke($selector, $idx = null);

    /**
     * @return string
     */
    public function __toString();

    /**
     * Find list of nodes with a CSS selector.
     *
     * @param string $selector
     * @param int    $idx
     *
     * @return SimpleXmlDomNode|SimpleXmlDomNode[]|null
     */
    public function find(string $selector, $idx = null);

    /**
     * Find nodes with a CSS selector.
     *
     * @param string $selector
     *
     * @return SimpleXmlDomInterface[]|SimpleXmlDomNodeInterface
     */
    public function findMulti(string $selector): self;

    /**
     * Find nodes with a CSS selector.
     *
     * @param string $selector
     *
     * @return false|SimpleXmlDomInterface[]|SimpleXmlDomNodeInterface
     */
    public function findMultiOrFalse(string $selector);

    /**
     * Find one node with a CSS selector.
     *
     * @param string $selector
     *
     * @return SimpleXmlDomNode|null
     */
    public function findOne(string $selector);

    /**
     * Find one node with a CSS selector or false, if no element is found.
     *
     * @param string $selector
     *
     * @return false|SimpleXmlDomNode
     */
    public function findOneOrFalse(string $selector);

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
}
