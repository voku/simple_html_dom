<?php

declare(strict_types=1);

namespace voku\helper;

/**
 * @property-read string outertext <p>Get dom node's outer html.</p>
 * @property-read string plaintext <p>Get dom node's plain text.</p>
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

        return null;
    }

    /**
     * alias for "$this->innerHtml()" (added for compatibly-reasons with v1.x)
     */
    public function outertext()
    {
        return $this->innerHtml();
    }

    /**
     * alias for "$this->innerHtml()" (added for compatibly-reasons with v1.x)
     */
    public function innertext()
    {
        return $this->innerHtml();
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
        $html = '';
        foreach ($this as $node) {
            $html .= $node->outertext;
        }

        return $html;
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
     * Find list of nodes with a CSS selector.
     *
     * @param string $selector
     * @param int    $idx
     *
     * @return SimpleHtmlDomNode|SimpleHtmlDomNode[]|null
     */
    public function find(string $selector, $idx = null)
    {
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
        if (isset($elements[$idx])) {
            return $elements[$idx];
        }

        return null;
    }

    /**
     * Get html of elements.
     *
     * @return array
     */
    public function innerHtml(): array
    {
        $html = [];
        foreach ($this as $node) {
            $html[] = $node->outertext;
        }

        return $html;
    }

    /**
     * Get plain text.
     *
     * @return array
     */
    public function text(): array
    {
        $text = [];
        foreach ($this as $node) {
            $text[] = $node->plaintext;
        }

        return $text;
    }
}
