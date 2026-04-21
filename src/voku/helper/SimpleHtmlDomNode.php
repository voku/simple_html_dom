<?php

declare(strict_types=1);

namespace voku\helper;

/**
 * {@inheritdoc}
 */
class SimpleHtmlDomNode extends AbstractSimpleHtmlDomNode implements SimpleHtmlDomNodeInterface
{
    /**
     * Find list of nodes with a CSS selector.
     *
     * @param string   $selector
     * @param int|null $idx
     *
     * @return SimpleHtmlDomInterface|SimpleHtmlDomNodeInterface<SimpleHtmlDomInterface>|null
     */
    public function find(string $selector, $idx = null)
    {
        // init
        $elements = $this->createNodeList();

        foreach ($this as $node) {
            /** @var SimpleHtmlDomNodeInterface<SimpleHtmlDomInterface> $matches */
            $matches = $node->find($selector);

            foreach ($matches as $res) {
                $elements[] = $res;
            }
        }

        // return all elements
        if ($idx === null) {
            if (\count($elements) === 0) {
                return new SimpleHtmlDomNodeBlank();
            }

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
     * Find nodes with a CSS selector.
     *
     * @param string $selector
     *
     * @return SimpleHtmlDomInterface[]|SimpleHtmlDomNodeInterface<SimpleHtmlDomInterface>
     */
    public function findMulti(string $selector): SimpleHtmlDomNodeInterface
    {
        /** @var SimpleHtmlDomNodeInterface<SimpleHtmlDomInterface> $return */
        $return = $this->find($selector, null);

        return $return;
    }

    /**
     * Find nodes with a CSS selector.
     *
     * @param string $selector
     *
     * @return false|SimpleHtmlDomInterface[]|SimpleHtmlDomNodeInterface<SimpleHtmlDomInterface>
     */
    public function findMultiOrFalse(string $selector)
    {
        /** @var SimpleHtmlDomNodeInterface<SimpleHtmlDomInterface> $return */
        $return = $this->find($selector, null);

        if ($return instanceof SimpleHtmlDomNodeBlank) {
            return false;
        }

        return $return;
    }

    /**
     * Find nodes with a CSS selector or null, if no element is found.
     *
     * @param string $selector
     *
     * @return null|SimpleHtmlDomInterface[]|SimpleHtmlDomNodeInterface<SimpleHtmlDomInterface>
     */
    public function findMultiOrNull(string $selector)
    {
        /** @var SimpleHtmlDomNodeInterface<SimpleHtmlDomInterface> $return */
        $return = $this->find($selector, null);

        if ($return instanceof SimpleHtmlDomNodeBlank) {
            return null;
        }

        return $return;
    }

    /**
     * Find one node with a CSS selector.
     *
     * @param string $selector
     *
     * @return SimpleHtmlDomInterface|SimpleHtmlDomNodeInterface<SimpleHtmlDomInterface>
     */
    public function findOne(string $selector)
    {
        $return = $this->find($selector, 0);

        return $return ?? new SimpleHtmlDomNodeBlank();
    }

    /**
     * Find one node with a CSS selector.
     *
     * @param string $selector
     *
     * @return false|SimpleHtmlDomInterface
     */
    public function findOneOrFalse(string $selector)
    {
        /** @var SimpleHtmlDomInterface|null $return */
        $return = $this->find($selector, 0);

        return $return ?? false;
    }

    /**
     * Find one node with a CSS selector or null, if no element is found.
     *
     * @param string $selector
     *
     * @return null|SimpleHtmlDomInterface
     */
    public function findOneOrNull(string $selector)
    {
        /** @var SimpleHtmlDomInterface|null $return */
        $return = $this->find($selector, 0);

        return $return;
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
     *
     * @return string[]
     */
    public function innertext()
    {
        return $this->innerHtml();
    }

    /**
     * alias for "$this->innerHtml()" (added for compatibly-reasons with v1.x)
     *
     * @return string[]
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

    private function createNodeList(): self
    {
        // @phpstan-ignore new.static (node list wrappers intentionally preserve late static binding)
        return new static();
    }
}
