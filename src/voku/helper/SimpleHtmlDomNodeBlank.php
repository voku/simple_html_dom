<?php

declare(strict_types=1);

namespace voku\helper;

/**
 * @property-read string[] $outertext
 *                                    <p>Get dom node's outer html.</p>
 * @property-read string[] $plaintext
 *                                    <p>Get dom node's plain text.</p>
 */
class SimpleHtmlDomNodeBlank extends \ArrayObject implements SimpleHtmlDomNodeInterface
{
    /** @noinspection MagicMethodsValidityInspection */

    /**
     * @param string $name
     * @param mixed  $arguments
     *
     * @return null
     */
    public function __call($name, $arguments)
    {
        return null;
    }

    /**
     * @param string $name
     *
     * @return string|array
     */
    public function __get($name)
    {
        if ($name === 'plaintext' || $name === 'outertext') {
            return [];
        }

        return '';
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        return;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return false;
    }

    /**
     * @param string $selector
     * @param int    $idx
     *
     * @return null
     */
    public function __invoke($selector, $idx = null)
    {
        return null;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return '';
    }

    /**
     * @param string $selector
     * @param null   $idx
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
     * Get html of Elements
     *
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
     * Get plain text
     *
     * @return string[]
     */
    public function text(): array
    {
        return [];
    }
}
