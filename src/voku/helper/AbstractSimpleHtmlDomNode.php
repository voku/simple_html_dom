<?php

declare(strict_types=1);

namespace voku\helper;

abstract class AbstractSimpleHtmlDomNode extends \ArrayObject
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
                if ($node instanceof SimpleHtmlDomInterface) {
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
     * @param string   $selector
     * @param int|null $idx
     *
     * @return SimpleHtmlDomNodeInterface|SimpleHtmlDomNodeInterface[]|null
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

    abstract public function find(string $selector, $idx = null);
}
