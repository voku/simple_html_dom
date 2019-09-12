<?php

declare(strict_types=1);

namespace voku\helper;

/**
 * {@inheritDoc}
 */
class SimpleHtmlAttributes implements SimpleHtmlAttributesInterface
{
    /**
     * @var string
     */
    private $attributeName;

    /**
     * @var null|\DOMElement
     */
    private $element;

    /**
     * @var string[]
     */
    private $tokens = [];

    /**
     * @var null|string
     */
    private $previousValue;

    /**
     * Creates a list of space-separated tokens based on the attribute value of an element.
     *
     * @param null|\DOMElement $element
     *                                  <p>The DOM element.</p>
     * @param string $attributeName
     *                                  <p>The name of the attribute.</p>
     */
    public function __construct($element, string $attributeName)
    {
        $this->element = $element;
        $this->attributeName = $attributeName;

        $this->tokenize();
    }

    /** @noinspection MagicMethodsValidityInspection */
    /**
     * Returns the value for the property specified.
     *
     * @param string $name The name of the property
     *
     * @return string The value of the property specified
     */
    public function __get(string $name)
    {
        if ($name === 'length') {
            $this->tokenize();

            return count($this->tokens);
        }

        if ($name === 'value') {
            return (string) $this;
        }

        throw new \InvalidArgumentException('Undefined property: $' . $name);
    }

    /**
     *
     * @return string
     */
    public function __toString(): string
    {
        $this->tokenize();

        return implode(' ', $this->tokens);
    }

    /**
     * {@inheritDoc}
     */
    public function add(string ...$tokens)
    {
        if (count($tokens) === 0) {
            return null;
        }

        foreach ($tokens as $t) {
            if (in_array($t, $this->tokens)) {
                continue;
            }

            $this->tokens[] = $t;
        }

        return $this->setAttributeValue();
    }

    /**
     * {@inheritDoc}
     */
    public function contains(string $token): bool
    {
        $this->tokenize();

        return in_array($token, $this->tokens);
    }

    /**
     * {@inheritDoc}
     */
    public function entries(): \ArrayIterator
    {
        $this->tokenize();

        return new \ArrayIterator($this->tokens);
    }

    public function item(int $index)
    {
        $this->tokenize();
        if ($index >= count($this->tokens)) {
            return null;
        }

        return $this->tokens[$index];
    }

    /**
     * {@inheritDoc}
     */
    public function remove(string ...$tokens)
    {
        if (count($tokens) === 0) {
            return null;
        }

        if (count($this->tokens) === 0) {
            return null;
        }

        foreach ($tokens as $t) {
            $i = array_search($t, $this->tokens);
            if ($i === false) {
                continue;
            }

            array_splice($this->tokens, $i, 1);
        }

        return $this->setAttributeValue();
    }

    /**
     * {@inheritDoc}
     */
    public function replace(string $old, string $new)
    {
        if ($old === $new) {
            return null;
        }

        $this->tokenize();
        $i = array_search($old, $this->tokens);
        if ($i !== false) {
            $j = array_search($new, $this->tokens);
            if ($j === false) {
                $this->tokens[$i] = $new;
            } else {
                array_splice($this->tokens, $i, 1);
            }

            return $this->setAttributeValue();
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function toggle(string $token, bool $force = null): bool
    {
        // init
        $this->tokenize();
        $isThereAfter = false;

        $i = array_search($token, $this->tokens);
        if ($force === null) {
            if ($i === false) {
                $this->tokens[] = $token;
                $isThereAfter = true;
            } else {
                array_splice($this->tokens, $i, 1);
            }
        } elseif ($force) {
            if ($i === false) {
                $this->tokens[] = $token;
            }
            $isThereAfter = true;
        } else {
            /** @noinspection NestedPositiveIfStatementsInspection */
            if ($i !== false) {
                array_splice($this->tokens, $i, 1);
            }
        }

        /** @noinspection UnusedFunctionResultInspection */
        $this->setAttributeValue();

        return $isThereAfter;
    }

    /**
     * @return \DOMAttr|false|null
     */
    private function setAttributeValue()
    {
        if ($this->element === null) {
            return false;
        }

        $value = implode(' ', $this->tokens);
        if ($this->previousValue === $value) {
            return null;
        }

        $this->previousValue = $value;

        return $this->element->setAttribute($this->attributeName, $value);
    }

    /**
     * @return void
     */
    private function tokenize()
    {
        if ($this->element === null) {
            return;
        }

        $current = $this->element->getAttribute($this->attributeName);
        if ($this->previousValue === $current) {
            return;
        }

        $this->previousValue = $current;
        $tokens = explode(' ', $current);
        $finals = [];
        foreach ($tokens as $token) {

            if ($token === '') {
                continue;
            }

            if (in_array($token, $finals, true)) {
                continue;
            }

            $finals[] = $token;
        }

        $this->tokens = $finals;
    }

}
