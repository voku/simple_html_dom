<?php

declare(strict_types=1);

namespace voku\helper;

/**
 * {@inheritdoc}
 *
 * @implements \IteratorAggregate<int, \DOMNode>
 */
class SimpleXmlDom extends AbstractSimpleXmlDom implements \IteratorAggregate, SimpleXmlDomInterface
{
    /**
     * @param \DOMElement|\DOMNode $node
     */
    public function __construct(\DOMNode $node)
    {
        $this->node = $node;
    }

    /**
     * @param string       $name
     * @param array<mixed> $arguments
     *
     * @throws \BadMethodCallException
     *
     * @return SimpleXmlDomInterface|string|null
     */
    public function __call($name, $arguments)
    {
        $name = \strtolower($name);

        if (isset(self::$functionAliases[$name])) {
            $method = self::$functionAliases[$name];

            return $this->{$method}(...$arguments);
        }

        throw new \BadMethodCallException('Method does not exist');
    }

    /**
     * Find list of nodes with a CSS or xPath selector.
     *
     * @param string   $selector
     * @param int|null $idx
     *
     * @return SimpleXmlDomInterface|SimpleXmlDomInterface[]|SimpleXmlDomNodeInterface<SimpleXmlDomInterface>
     */
    public function find(string $selector, $idx = null)
    {
        return $this->getXmlDomParser()->find($selector, $idx);
    }

    /**
     * Returns an array of attributes.
     *
     * @return string[]|null
     */
    public function getAllAttributes()
    {
        $node = $this->node();

        if (
            $node->hasAttributes()
        ) {
            $attributes = [];
            foreach ($node->attributes ?? [] as $attr) {
                $attributes[$attr->name] = XmlDomParser::putReplacedBackToPreserveHtmlEntities($attr->value);
            }

            return $attributes;
        }

        return null;
    }

    /**
     * @return bool
     */
    public function hasAttributes(): bool
    {
        return $this->node()->hasAttributes();
    }

    /**
     * Return attribute value.
     *
     * @param string $name
     *
     * @return string
     */
    public function getAttribute(string $name): string
    {
        if ($this->node instanceof \DOMElement) {
            return XmlDomParser::putReplacedBackToPreserveHtmlEntities(
                $this->node->getAttribute($name)
            );
        }

        return '';
    }

    /**
     * Determine if an attribute exists on the element.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasAttribute(string $name): bool
    {
        if (!$this->node instanceof \DOMElement) {
            return false;
        }

        return $this->node->hasAttribute($name);
    }

    /**
     * Get dom node's inner html.
     *
     * @param bool $multiDecodeNewHtmlEntity
     *
     * @return string
     */
    public function innerXml(bool $multiDecodeNewHtmlEntity = false): string
    {
        return $this->getXmlDomParser()->innerXml($multiDecodeNewHtmlEntity);
    }

    /**
     * Remove attribute.
     *
     * @param string $name <p>The name of the html-attribute.</p>
     *
     * @return SimpleXmlDomInterface
     */
    public function removeAttribute(string $name): SimpleXmlDomInterface
    {
        $node = $this->node();
        if ($node instanceof \DOMElement) {
            $node->removeAttribute($name);
        }

        return $this;
    }

    /**
     * Replace child node.
     *
     * @param string $string
     * @param bool   $putBrokenReplacedBack
     *
     * @return SimpleXmlDomInterface
     */
    protected function replaceChildWithString(string $string, bool $putBrokenReplacedBack = true): SimpleXmlDomInterface
    {
        $node = $this->node();

        if (!empty($string)) {
            $newDocument = new XmlDomParser($string);

            $tmpDomString = $this->normalizeStringForComparision($newDocument);
            $tmpStr = $this->normalizeStringForComparision($string);

            if ($tmpDomString !== $tmpStr) {
                throw new \RuntimeException(
                    'Not valid XML fragment!' . "\n" .
                    $tmpDomString . "\n" .
                    $tmpStr
                );
            }
        }

        /** @var \DOMNode[] $remove_nodes */
        $remove_nodes = [];
        if ($node->childNodes->length > 0) {
            // INFO: We need to fetch the nodes first, before we can delete them, because of missing references in the dom,
            // if we delete the elements on the fly.
            foreach ($node->childNodes as $childNode) {
                $remove_nodes[] = $childNode;
            }
        }
        foreach ($remove_nodes as $remove_node) {
            $node->removeChild($remove_node);
        }

        if (!empty($newDocument)) {
            $ownerDocument = $node->ownerDocument;
            if (
                $ownerDocument
                &&
                $newDocument->getDocument()->documentElement
            ) {
                $newNode = $ownerDocument->importNode($newDocument->getDocument()->documentElement, true);
                /** @noinspection UnusedFunctionResultInspection */
                $node->appendChild($newNode);
            }
        }

        return $this;
    }

    /**
     * Replace this node.
     *
     * @param string $string
     *
     * @return SimpleXmlDomInterface
     */
    protected function replaceNodeWithString(string $string): SimpleXmlDomInterface
    {
        $node = $this->node();

        if (empty($string)) {
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
            }

            return $this;
        }

        $newDocument = new XmlDomParser($string);

        $tmpDomOuterTextString = $this->normalizeStringForComparision($newDocument);
        $tmpStr = $this->normalizeStringForComparision($string);

        if ($tmpDomOuterTextString !== $tmpStr) {
            throw new \RuntimeException(
                'Not valid XML fragment!' . "\n"
                . $tmpDomOuterTextString . "\n" .
                $tmpStr
            );
        }

        $ownerDocument = $node->ownerDocument;
        if (
            $ownerDocument === null
            ||
            $newDocument->getDocument()->documentElement === null
        ) {
            return $this;
        }

        $newNode = $ownerDocument->importNode($newDocument->getDocument()->documentElement, true);

        if ($node->parentNode !== null) {
            $node->parentNode->replaceChild($newNode, $node);
            $this->node = $newNode;
        }

        return $this;
    }

    /**
     * Replace this node with text
     *
     * @param string $string
     *
     * @return SimpleXmlDomInterface
     */
    protected function replaceTextWithString($string): SimpleXmlDomInterface
    {
        $node = $this->node();

        if (empty($string)) {
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
            }

            return $this;
        }

        $ownerDocument = $node->ownerDocument;
        if ($ownerDocument) {
            $newElement = $ownerDocument->createTextNode($string);
            $newNode = $ownerDocument->importNode($newElement, true);
            if ($node->parentNode !== null) {
                $node->parentNode->replaceChild($newNode, $node);
                $this->node = $newNode;
            }
        }

        return $this;
    }

    /**
     * Set attribute value.
     *
     * @param string      $name       <p>The name of the html-attribute.</p>
     * @param string|null $value      <p>Set to NULL or empty string, to remove the attribute.</p>
     * @param bool        $strictEmptyValueCheck     </p>
     *                                $value must be NULL, to remove the attribute,
     *                                so that you can set an empty string as attribute-value e.g. autofocus=""
     *                                </p>
     *
     * @return SimpleXmlDomInterface
     */
    public function setAttribute(string $name, $value = null, bool $strictEmptyValueCheck = false): SimpleXmlDomInterface
    {
        $node = $this->node();

        if (
            ($strictEmptyValueCheck && $value === null)
            ||
            (!$strictEmptyValueCheck && empty($value))
        ) {
            /** @noinspection UnusedFunctionResultInspection */
            $this->removeAttribute($name);
        } elseif ($node instanceof \DOMElement) {
            /** @noinspection UnusedFunctionResultInspection */
            $node->setAttribute($name, HtmlDomParser::replaceToPreserveHtmlEntities((string) $value));
        }

        return $this;
    }

    /**
     * Get dom node's plain text.
     *
     * @return string
     */
    public function text(): string
    {
        $node = $this->node();

        if ($node instanceof \DOMCharacterData) {
            return XmlDomParser::putReplacedBackToPreserveHtmlEntities($node->nodeValue ?? '');
        }

        return $this->getXmlDomParser()->fixHtmlOutput($node->textContent);
    }

    /**
     * Get dom node's outer html.
     *
     * @param bool $multiDecodeNewHtmlEntity
     *
     * @return string
     */
    public function xml(bool $multiDecodeNewHtmlEntity = false): string
    {
        return $this->getXmlDomParser()->xml($multiDecodeNewHtmlEntity, false);
    }

    /**
     * Change the name of a tag in a "DOMNode".
     *
     * @param \DOMNode $node
     * @param string   $name
     *
     * @return \DOMElement|false
     *                          <p>DOMElement a new instance of class DOMElement or false
     *                          if an error occured.</p>
     */
    protected function changeElementName(\DOMNode $node, string $name)
    {
        $ownerDocument = $node->ownerDocument;
        if (!$ownerDocument) {
            return false;
        }

        $newNode = $ownerDocument->createElement($name);

        foreach ($node->childNodes as $child) {
            $child = $ownerDocument->importNode($child, true);
            $newNode->appendChild($child);
        }

        foreach ($node->attributes ?? [] as $attrNode) {
            /** @noinspection UnusedFunctionResultInspection */
            $newNode->setAttribute($attrNode->nodeName, $attrNode->value);
        }

        if ($node->parentNode) {
            /** @noinspection UnusedFunctionResultInspection */
            $node->parentNode->replaceChild($newNode, $node);
        }

        return $newNode;
    }

    /**
     * Returns children of node.
     *
     * @param int $idx
     *
     * @return SimpleXmlDomInterface|SimpleXmlDomInterface[]|SimpleXmlDomNodeInterface<SimpleXmlDomInterface>|null
     */
    public function childNodes(int $idx = -1)
    {
        $nodeList = $this->getIterator();

        if ($idx === -1) {
            return $nodeList;
        }

        return $nodeList[$idx] ?? null;
    }

    /**
     * Find nodes with a CSS or xPath selector.
     *
     * @param string $selector
     *
     * @return SimpleXmlDomInterface[]|SimpleXmlDomNodeInterface<SimpleXmlDomInterface>
     */
    public function findMulti(string $selector): SimpleXmlDomNodeInterface
    {
        return $this->getXmlDomParser()->findMulti($selector);
    }

    /**
     * Find nodes with a CSS or xPath selector.
     *
     * @param string $selector
     *
     * @return false|SimpleXmlDomInterface[]|SimpleXmlDomNodeInterface<SimpleXmlDomInterface>
     */
    public function findMultiOrFalse(string $selector)
    {
        return $this->getXmlDomParser()->findMultiOrFalse($selector);
    }

    /**
     * Find nodes with a CSS or xPath selector or null, if no element is found.
     *
     * @param string $selector
     *
     * @return null|SimpleXmlDomInterface[]|SimpleXmlDomNodeInterface<SimpleXmlDomInterface>
     */
    public function findMultiOrNull(string $selector)
    {
        return $this->getXmlDomParser()->findMultiOrNull($selector);
    }

    /**
     * Find one node with a CSS or xPath selector.
     *
     * @param string $selector
     *
     * @return SimpleXmlDomInterface
     */
    public function findOne(string $selector): SimpleXmlDomInterface
    {
        return $this->getXmlDomParser()->findOne($selector);
    }

    /**
     * Find one node with a CSS or xPath selector or false, if no element is found.
     *
     * @param string $selector
     *
     * @return false|SimpleXmlDomInterface
     */
    public function findOneOrFalse(string $selector)
    {
        return $this->getXmlDomParser()->findOneOrFalse($selector);
    }

    /**
     * Find one node with a CSS or xPath selector or null, if no element is found.
     *
     * @param string $selector
     *
     * @return null|SimpleXmlDomInterface
     */
    public function findOneOrNull(string $selector)
    {
        return $this->getXmlDomParser()->findOneOrNull($selector);
    }

    /**
     * Returns the first child of node.
     *
     * @return SimpleXmlDomInterface|null
     */
    public function firstChild()
    {
        /** @var \DOMNode|null $node */
        $node = $this->node()->firstChild;

        if ($node === null) {
            return null;
        }

        return $this->createWrapper($node);
    }

    /**
     * Return elements by ".class".
     *
     * @param string $class
     *
     * @return SimpleXmlDomInterface[]|SimpleXmlDomNodeInterface<SimpleXmlDomInterface>
     */
    public function getElementByClass(string $class): SimpleXmlDomNodeInterface
    {
        return $this->findMulti(".{$class}");
    }

    /**
     * Return element by #id.
     *
     * @param string $id
     *
     * @return SimpleXmlDomInterface
     */
    public function getElementById(string $id): SimpleXmlDomInterface
    {
        return $this->findOne("#{$id}");
    }

    /**
     * Return element by tag name.
     *
     * @param string $name
     *
     * @return SimpleXmlDomInterface
     */
    public function getElementByTagName(string $name): SimpleXmlDomInterface
    {
        if ($this->node instanceof \DOMElement) {
            $node = $this->node->getElementsByTagName($name)->item(0);
        } else {
            $node = null;
        }

        if ($node === null) {
            return new SimpleXmlDomBlank();
        }

        return $this->createWrapper($node);
    }

    /**
     * Returns elements by "#id".
     *
     * @param string   $id
     * @param int|null $idx
     *
     * @return SimpleXmlDomInterface|SimpleXmlDomInterface[]|SimpleXmlDomNodeInterface<SimpleXmlDomInterface>
     */
    public function getElementsById(string $id, $idx = null)
    {
        return $this->find("#{$id}", $idx);
    }

    /**
     * Returns elements by tag name.
     *
     * @param string   $name
     * @param int|null $idx
     *
     * @return SimpleXmlDomInterface|SimpleXmlDomInterface[]|SimpleXmlDomNodeInterface<SimpleXmlDomInterface>
     */
    public function getElementsByTagName(string $name, $idx = null)
    {
        if ($this->node instanceof \DOMElement) {
            $nodesList = $this->node->getElementsByTagName($name);
        } else {
            $nodesList = [];
        }

        $elements = new SimpleXmlDomNode();

        foreach ($nodesList as $node) {
            $elements[] = $this->createWrapper($node);
        }

        // return all elements
        if ($idx === null) {
            if (\count($elements) === 0) {
                return new SimpleXmlDomNodeBlank();
            }

            return $elements;
        }

        // handle negative values
        if ($idx < 0) {
            $idx = \count($elements) + $idx;
        }

        // return one element
        return $elements[$idx] ?? new SimpleXmlDomBlank();
    }

    /**
     * @return \DOMNode
     */
    public function getNode(): \DOMNode
    {
        return $this->node();
    }

    /**
     * Create a new "XmlDomParser"-object from the current context.
     *
     * @return XmlDomParser
     */
    public function getXmlDomParser(): XmlDomParser
    {
        return new XmlDomParser($this);
    }

    /**
     * Get dom node's inner html.
     *
     * @param bool $multiDecodeNewHtmlEntity
     * @param bool $putBrokenReplacedBack
     *
     * @return string
     */
    public function innerHtml(bool $multiDecodeNewHtmlEntity = false, bool $putBrokenReplacedBack = true): string
    {
        return $this->getXmlDomParser()->innerHtml($multiDecodeNewHtmlEntity, $putBrokenReplacedBack);
    }

    /**
     * Nodes can get partially destroyed in which they're still an
     * actual DOM node (such as \DOMElement) but almost their entire
     * body is gone, including the `nodeType` attribute.
     *
     * @return bool true if node has been destroyed
     */
    public function isRemoved(): bool
    {
        return !isset($this->node->nodeType);
    }

    /**
     * Returns the last child of node.
     *
     * @return SimpleXmlDomInterface|null
     */
    public function lastChild()
    {
        /** @var \DOMNode|null $node */
        $node = $this->node()->lastChild;

        if ($node === null) {
            return null;
        }

        return $this->createWrapper($node);
    }

    /**
     * Returns the next sibling of node.
     *
     * @return SimpleXmlDomInterface|null
     */
    public function nextSibling()
    {
        /** @var \DOMNode|null $node */
        $node = $this->node()->nextSibling;

        if ($node === null) {
            return null;
        }

        return $this->createWrapper($node);
    }

    /**
     * Returns the next sibling of node.
     *
     * @return SimpleXmlDomInterface|null
     */
    public function nextNonWhitespaceSibling()
    {
        /** @var \DOMNode|null $node */
        $node = $this->node()->nextSibling;

        if ($node === null) {
            return null;
        }

        while ($node && !\trim($node->textContent)) {
            /** @var \DOMNode|null $node */
            $node = $node->nextSibling;
        }

        if ($node === null) {
            return null;
        }

        return $this->createWrapper($node);
    }

    /**
     * Returns the parent of node.
     *
     * @return SimpleXmlDomInterface
     */
    public function parentNode(): SimpleXmlDomInterface
    {
        $parentNode = $this->node()->parentNode;
        if (
            $parentNode === null
            ||
            $parentNode instanceof \DOMDocument
        ) {
            return new SimpleXmlDomBlank();
        }

        return $this->createWrapper($parentNode);
    }

    /**
     * Returns the previous sibling of node.
     *
     * @return SimpleXmlDomInterface|null
     */
    public function previousSibling()
    {
        /** @var \DOMNode|null $node */
        $node = $this->node()->previousSibling;

        if ($node === null) {
            return null;
        }

        return $this->createWrapper($node);
    }

    /**
     * Returns the previous sibling of node.
     *
     * @return SimpleXmlDomInterface|null
     */
    public function previousNonWhitespaceSibling()
    {
        /** @var \DOMNode|null $node */
        $node = $this->node()->previousSibling;

        while ($node && !\trim($node->textContent)) {
            /** @var \DOMNode|null $node */
            $node = $node->previousSibling;
        }

        if ($node === null) {
            return null;
        }

        return $this->createWrapper($node);
    }

    /**
     * @param string|string[]|null $value <p>
     *                                    null === get the current input value
     *                                    text === set a new input value
     *                                    </p>
     *
     * @return string|string[]|null
     */
    public function val($value = null)
    {
        $node = $this->node();

        if ($value === null) {
            if (
                $this->tag === 'input'
                &&
                (
                    $this->getAttribute('type') === 'hidden'
                    ||
                    $this->getAttribute('type') === 'text'
                    ||
                    !$this->hasAttribute('type')
                )
            ) {
                return $this->getAttribute('value');
            }

            if (
                $this->hasAttribute('checked')
                &&
                \in_array($this->getAttribute('type'), ['checkbox', 'radio'], true)
            ) {
                return $this->getAttribute('value');
            }

            if ($node->nodeName === 'select') {
                $valuesFromDom = [];
                $options = $this->getElementsByTagName('option');
                if ($options instanceof SimpleXmlDomNode) {
                    foreach ($options as $option) {
                        if ($option->hasAttribute('selected')) {
                            $valuesFromDom[] = (string) $option->getAttribute('value');
                        }
                    }
                }

                if (\count($valuesFromDom) === 0) {
                    return null;
                }

                return $valuesFromDom;
            }

            if ($node->nodeName === 'textarea') {
                return $node->nodeValue;
            }
        } else {
            /** @noinspection NestedPositiveIfStatementsInspection */
            if (\in_array($this->getAttribute('type'), ['checkbox', 'radio'], true)) {
                $selectedValues = \is_array($value) ? $value : [$value];
                if (\in_array($this->getAttribute('value'), $selectedValues, true)) {
                    /** @noinspection UnusedFunctionResultInspection */
                    $this->setAttribute('checked', 'checked');
                } else {
                    /** @noinspection UnusedFunctionResultInspection */
                    $this->removeAttribute('checked');
                }
            } elseif ($this->node instanceof \DOMElement && $this->node->nodeName === 'select') {
                $selectedValues = \is_array($value) ? $value : [$value];
                foreach ($this->node->getElementsByTagName('option') as $option) {
                    /** @var \DOMElement $option */
                    if (\in_array($option->getAttribute('value'), $selectedValues, true)) {
                        /** @noinspection UnusedFunctionResultInspection */
                        $option->setAttribute('selected', 'selected');
                    } else {
                        $option->removeAttribute('selected');
                    }
                }
            } elseif ($node->nodeName === 'input' && \is_string($value)) {
                // Set value for input elements
                /** @noinspection UnusedFunctionResultInspection */
                $this->setAttribute('value', $value);
            } elseif ($node->nodeName === 'textarea' && \is_string($value)) {
                $node->nodeValue = $value;
            }
        }

        return null;
    }

    /**
     * Retrieve an external iterator.
     *
     * @see  http://php.net/manual/en/iteratoraggregate.getiterator.php
     *
     * @return SimpleXmlDomNode
     *                           <p>
     *                              An instance of an object implementing <b>Iterator</b> or
     *                              <b>Traversable</b>
     *                           </p>
     */
    public function getIterator(): SimpleXmlDomNodeInterface
    {
        $node = $this->node();
        $elements = new SimpleXmlDomNode();
        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $childNode) {
                $elements[] = $this->createWrapper($childNode);
            }
        }

        return $elements;
    }

    private function createWrapper(\DOMNode $node): self
    {
        // @phpstan-ignore new.static (wrapper subclasses intentionally preserve late static binding)
        return new static($node);
    }

    private function node(): \DOMNode
    {
        \assert($this->node instanceof \DOMNode);

        return $this->node;
    }

    /**
     * Normalize the given input for comparision.
     *
     * @param string|XmlDomParser $input
     *
     * @return string
     */
    private function normalizeStringForComparision($input): string
    {
        if ($input instanceof XmlDomParser) {
            $string = $input->html(false, false);
        } else {
            $string = (string) $input;
        }

        return
            \urlencode(
                \urldecode(
                    \trim(
                        \str_replace(
                            [
                                ' ',
                                "\n",
                                "\r",
                                '/>',
                            ],
                            [
                                '',
                                '',
                                '',
                                '>',
                            ],
                            \strtolower($string)
                        )
                    )
                )
            );
    }
}
