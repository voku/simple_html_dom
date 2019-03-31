<?php

declare(strict_types=1);

namespace voku\helper;

/**
 * @property-read string[] $outertext
 *                                    <p>Get dom node's outer html.</p>
 * @property-read string[] $plaintext
 *                                    <p>Get dom node's plain text.</p>
 */
class SimpleHtmlDomNode extends \ArrayObject implements SimpleHtmlDomNodeInterface
{
    /** @noinspection MagicMethodsValidityInspection */

    /**
     * @param string $name
     *
     * @return array|null
     */
    public function __get($name)
    {
        // init
        $name = \strtolower($name);

        if ($this->count() > 0) {
            $return = [];

            foreach ($this as $node) {
                if ($node instanceof SimpleHtmlDom) {
                    $return[] = $node->{$name};
                }
            }

            return $return;
        }

        if ($name === 'plaintext' || $name === 'outertext') {
            return [];
        }

        return null;
    }

    /**
     * @param string $selector
     * @param int    $idx
     *
     * @return SimpleHtmlDomNode|SimpleHtmlDomNode[]|null
     */
    public function __invoke($selector, $idx = null)
    {
        return $this->find($selector, $idx);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        // init
        $html = '';

        foreach ($this as $node) {
            $html .= $node->outertext;
        }

        return $html;
    }

    /**
     * Find list of nodes with a CSS selector.
     *
     * @param string $selector
     * @param int    $idx
     *
     * @return SimpleHtmlDomNode|SimpleHtmlDomNode[]|null
     */
    public function find(string $selector, $idx = null)
    {
        // init
        $elements = new self();

        foreach ($this as $node) {
            foreach ($node->find($selector) as $res) {
                $elements->append($res);
            }
        }

        // return all elements
        if ($idx === null) {
            return $elements;
        }

        // handle negative values
        if ($idx < 0) {
            $idx = \count($elements) + $idx;
        }

        // return one element
        return $elements[$idx] ?? null;
    }

    /**
     * Find one node with a CSS selector.
     *
     * @param string $selector
     *
     * @return SimpleHtmlDomNode|null
     */
    public function findOne(string $selector)
    {
        return $this->find($selector, 0);
    }

    /**
     * Get html of elements.
     *
     * @return string[]
     */
    public function innerHtml(): array
    {
        // init
        $html = [];

        foreach ($this as $node) {
            $html[] = $node->outertext;
        }

        return $html;
    }

    /**
     * alias for "$this->innerHtml()" (added for compatibly-reasons with v1.x)
     */
    public function innertext()
    {
        return $this->innerHtml();
    }

    /**
     * alias for "$this->innerHtml()" (added for compatibly-reasons with v1.x)
     */
    public function outertext()
    {
        return $this->innerHtml();
    }

    /**
     * Get plain text.
     *
     * @return string[]
     */
    public function text(): array
    {
        // init
        $text = [];

        foreach ($this as $node) {
            $text[] = $node->plaintext;
        }

        return $text;
    }
}
