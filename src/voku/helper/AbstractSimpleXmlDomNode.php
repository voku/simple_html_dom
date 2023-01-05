<?php

declare(strict_types=1);

namespace Voku\Helper;

/**
 * {@inheritdoc}
 */
abstract class AbstractSimpleXmlDomNode extends \ArrayObject
{
    /** @noinspection MagicMethodsValidityInspection */

    /**
     * @param string $name
     *
     * @return array|int|null
     */
    public function __get($name)
    {
        // init
        $name = \strtolower($name);

        if ($name === 'length') {
            return $this->count();
        }

        if ($this->count() > 0) {
            $return = [];

            foreach ($this as $node) {
                if ($node instanceof SimpleXmlDomInterface) {
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
     * @return SimpleXmlDomNodeInterface<SimpleXmlDomInterface>|SimpleXmlDomNodeInterface[]|null
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
     * @param string $selector
     * @param int|null   $idx
     *
     * @return SimpleXmlDomNodeInterface<SimpleXmlDomInterface>|SimpleXmlDomNodeInterface[]|null
     */
    abstract public function find(string $selector, $idx = null);
}
