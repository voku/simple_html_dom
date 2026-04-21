<?php

declare(strict_types=1);

namespace voku\helper;

/**
 * {@inheritdoc}
 *
 * @implements \IteratorAggregate<int, \DOMNode>
 */
class SimpleHtmlDom extends AbstractSimpleHtmlDom implements \IteratorAggregate, SimpleHtmlDomInterface
{
    /**
     * @var HtmlDomParser|null
     */
    private $queryHtmlDomParser;

    /**
     * Create a wrapper around an existing DOM node.
     *
     * @param \DOMElement|\DOMNode $node
     * @param HtmlDomParser|null   $queryHtmlDomParser Internal parser context
     *                                                 used for wrappers created
     *                                                 by HtmlDomParser.
     */
    public function __construct(\DOMNode $node, ?HtmlDomParser $queryHtmlDomParser = null)
    {
        $this->node = $node;
        $this->queryHtmlDomParser = $queryHtmlDomParser;
    }

    /**
     * @param string       $name
     * @param array<mixed> $arguments
     *
     * @throws \BadMethodCallException
     *
     * @return SimpleHtmlDomInterface|string|null
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
     * Find list of nodes with a CSS selector.
     *
     * @param string   $selector
     * @param int|null $idx
     *
     * @return SimpleHtmlDomInterface|SimpleHtmlDomInterface[]|SimpleHtmlDomNodeInterface<SimpleHtmlDomInterface>
     */
    public function find(string $selector, $idx = null)
    {
        $node = $this->node();
        $document = $node instanceof \DOMDocument ? $node : $node->ownerDocument;

        if (!$document instanceof \DOMDocument) {
            if ($idx === null) {
                return new SimpleHtmlDomNodeBlank();
            }

            return new SimpleHtmlDomBlank();
        }

        if ($this->queryHtmlDomParser !== null) {
            return $this->queryHtmlDomParser->findInNodeContext($selector, $node, $idx);
        }

        return HtmlDomParser::findInDocumentContext(
            $selector,
            $document,
            $node,
            $idx,
            null,
            null
        );
    }

    public function getTag(): string
    {
        return $this->tag;
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
                $attributes[$attr->name] = HtmlDomParser::putReplacedBackToPreserveHtmlEntities($attr->value);
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
            return HtmlDomParser::putReplacedBackToPreserveHtmlEntities(
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
     * Get dom node's outer html.
     *
     * @param bool $multiDecodeNewHtmlEntity
     *
     * @return string
     */
    public function html(bool $multiDecodeNewHtmlEntity = false): string
    {
        return $this->getHtmlDomParser()->html($multiDecodeNewHtmlEntity);
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
        return $this->getHtmlDomParser()->innerHtml($multiDecodeNewHtmlEntity, $putBrokenReplacedBack);
    }

    /**
     * Remove attribute.
     *
     * @param string $name <p>The name of the html-attribute.</p>
     *
     * @return SimpleHtmlDomInterface
     */
    public function removeAttribute(string $name): SimpleHtmlDomInterface
    {
        $node = $this->node();
        if ($node instanceof \DOMElement) {
            $node->removeAttribute($name);
        }

        return $this;
    }

    /**
     * Remove all attributes
     *
     * @return SimpleHtmlDomInterface
     */
    public function removeAttributes(): SimpleHtmlDomInterface
    {
        if ($this->hasAttributes()) {
            foreach (array_keys((array)$this->getAllAttributes()) as $attribute) {
                $this->removeAttribute($attribute);
            }
        }
        return $this;
    }

    /**
     * Replace child node.
     *
     * @param string $string
     * @param bool   $putBrokenReplacedBack
     *
     * @return SimpleHtmlDomInterface
     */
    protected function replaceChildWithString(string $string, bool $putBrokenReplacedBack = true): SimpleHtmlDomInterface
    {
        $node = $this->node();

        if (!empty($string)) {
            $newDocument = new HtmlDomParser($string);

            $tmpDomString = $this->normalizeStringForComparison($newDocument);
            $tmpStr = $this->normalizeStringForComparison($string);

            if ($tmpDomString !== $tmpStr) {
                throw new \RuntimeException(
                    'Not valid HTML fragment!' . "\n" .
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
            $newDocument = $this->cleanHtmlWrapper($newDocument);
            $ownerDocument = $node->ownerDocument;
            if (
                $ownerDocument
                &&
                $newDocument->getDocument()->documentElement
            ) {
                $newNode = $ownerDocument->importNode($newDocument->getDocument()->documentElement, true);
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
     * @return SimpleHtmlDomInterface
     */
    protected function replaceNodeWithString(string $string): SimpleHtmlDomInterface
    {
        $node = $this->node();

        if (empty($string)) {
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
            }
            $this->node = new \DOMText();

            return $this;
        }

        $newDocument = new HtmlDomParser($string);

        $tmpDomOuterTextString = $this->normalizeStringForComparison($newDocument);
        $tmpStr = $this->normalizeStringForComparison($string);

        if ($tmpDomOuterTextString !== $tmpStr) {
            throw new \RuntimeException(
                'Not valid HTML fragment!' . "\n"
                . $tmpDomOuterTextString . "\n" .
                $tmpStr
            );
        }

        $newDocument = $this->cleanHtmlWrapper($newDocument, true);
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
        }
        $this->node = $newNode;

        // Remove head element, preserving child nodes. (again)
        if (
            $this->node->parentNode instanceof \DOMElement
            &&
            $newDocument->getIsDOMDocumentCreatedWithoutHeadWrapper()
        ) {
            $html = $this->node->parentNode->getElementsByTagName('head')[0];

            if (
                $html !== null
                &&
                $this->node()->parentNode instanceof \DOMElement
                &&
                $this->node()->parentNode->ownerDocument
            ) {
                $fragment = $this->node()->parentNode->ownerDocument->createDocumentFragment();
                /** @var \DOMNode $html */
                while ($html->childNodes->length > 0) {
                    $tmpNode = $html->childNodes->item(0);
                    if ($tmpNode !== null) {
                        /** @noinspection UnusedFunctionResultInspection */
                        $fragment->appendChild($tmpNode);
                    }
                }
                if ($html->parentNode !== null) {
                    $html->parentNode->replaceChild($fragment, $html);
                }
            }
        }

        return $this;
    }

    /**
     * Replace this node with text
     *
     * @param string $string
     *
     * @return SimpleHtmlDomInterface
     */
    protected function replaceTextWithString($string): SimpleHtmlDomInterface
    {
        $node = $this->node();

        if (empty($string)) {
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
            }
            $this->node = new \DOMText();

            return $this;
        }

        $ownerDocument = $node->ownerDocument;
        if ($ownerDocument) {
            $newElement = $ownerDocument->createTextNode($string);
            $newNode = $ownerDocument->importNode($newElement, true);
            if ($node->parentNode !== null) {
                $node->parentNode->replaceChild($newNode, $node);
            }
            $this->node = $newNode;
        }

        return $this;
    }

    /**
     * Set attribute value.
     *
     * @param string      $name                     <p>The name of the html-attribute.</p>
     * @param string|null $value                    <p>Set to NULL or empty string, to remove the attribute.</p>
     * @param bool        $strictEmptyValueCheck <p>
     *                                $value must be NULL, to remove the attribute,
     *                                so that you can set an empty string as attribute-value e.g. autofocus=""
     *                                </p>
     *
     * @return SimpleHtmlDomInterface
     */
    public function setAttribute(string $name, $value = null, bool $strictEmptyValueCheck = false): SimpleHtmlDomInterface
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
            return HtmlDomParser::putReplacedBackToPreserveHtmlEntities($node->nodeValue ?? '');
        }

        return $this->getHtmlDomParser()->fixHtmlOutput($node->textContent);
    }

    /**
     * Change the name of a tag in a "DOMNode".
     *
     * @param \DOMNode $node
     * @param string   $name
     *
     * @return \DOMElement|false
     *                          <p>DOMElement a new instance of class DOMElement or false
     *                          if an error occurred.</p>
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
     * @return SimpleHtmlDomInterface|SimpleHtmlDomInterface[]|SimpleHtmlDomNodeInterface|null
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
     * Find nodes with a CSS selector or false, if no element is found.
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
     * @return SimpleHtmlDomInterface
     */
    public function findOne(string $selector): SimpleHtmlDomInterface
    {
        /** @var SimpleHtmlDomInterface $return */
        $return = $this->find($selector, 0);

        return $return;
    }

    /**
     * Find one node with a CSS selector or false, if no element is found.
     *
     * @param string $selector
     *
     * @return false|SimpleHtmlDomInterface
     */
    public function findOneOrFalse(string $selector)
    {
        /** @var SimpleHtmlDomInterface $return */
        $return = $this->find($selector, 0);

        if ($return instanceof SimpleHtmlDomBlank) {
            return false;
        }

        return $return;
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
        /** @var SimpleHtmlDomInterface $return */
        $return = $this->find($selector, 0);

        if ($return instanceof SimpleHtmlDomBlank) {
            return null;
        }

        return $return;
    }

    /**
     * Returns the first child of node.
     *
     * @return SimpleHtmlDomInterface|null
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
     * @return SimpleHtmlDomInterface[]|SimpleHtmlDomNodeInterface<SimpleHtmlDomInterface>
     */
    public function getElementByClass(string $class): SimpleHtmlDomNodeInterface
    {
        return $this->findMulti(".{$class}");
    }

    /**
     * Return element by #id.
     *
     * @param string $id
     *
     * @return SimpleHtmlDomInterface
     */
    public function getElementById(string $id): SimpleHtmlDomInterface
    {
        return $this->findOne("#{$id}");
    }

    /**
     * Return element by tag name.
     *
     * @param string $name
     *
     * @return SimpleHtmlDomInterface
     */
    public function getElementByTagName(string $name): SimpleHtmlDomInterface
    {
        if ($this->node instanceof \DOMElement) {
            $node = $this->node->getElementsByTagName($name)->item(0);
        } else {
            $node = null;
        }

        if ($node === null) {
            return new SimpleHtmlDomBlank();
        }

        return $this->createWrapper($node);
    }

    /**
     * Returns elements by "#id".
     *
     * @param string   $id
     * @param int|null $idx
     *
     * @return SimpleHtmlDomInterface|SimpleHtmlDomInterface[]|SimpleHtmlDomNodeInterface<SimpleHtmlDomInterface>
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
     * @return SimpleHtmlDomInterface|SimpleHtmlDomInterface[]|SimpleHtmlDomNodeInterface<SimpleHtmlDomInterface>
     */
    public function getElementsByTagName(string $name, $idx = null)
    {
        if ($this->node instanceof \DOMElement) {
            $nodesList = $this->node->getElementsByTagName($name);
        } else {
            $nodesList = false;
        }

        return $this->createFindResultFromNodeList($nodesList, $idx);
    }

    /**
     * Create a new "HtmlDomParser"-object from the current context.
     *
     * @return HtmlDomParser
     */
    public function getHtmlDomParser(): HtmlDomParser
    {
        return new HtmlDomParser($this);
    }

    /**
     * @return \DOMNode
     */
    public function getNode(): \DOMNode
    {
        return $this->node();
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
     * @return SimpleHtmlDomInterface|null
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
     * @return SimpleHtmlDomInterface|null
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
     * @return SimpleHtmlDomInterface|null
     */
    public function nextNonWhitespaceSibling()
    {
        /** @var \DOMNode|null $node */
        $node = $this->node()->nextSibling;

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
     * @return SimpleHtmlDomInterface|null
     */
    public function parentNode(): ?SimpleHtmlDomInterface
    {
        $node = $this->node();
        if (
            ($node = $node->parentNode)
            &&
            !$node instanceof \DOMDocument
        ) {
            return $this->createWrapper($node);
        }

        return null;
    }

    /**
     * Returns the previous sibling of node.
     *
     * @return SimpleHtmlDomInterface|null
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
     * @return SimpleHtmlDomInterface|null
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
                if ($options instanceof SimpleHtmlDomNode) {
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
     * @param HtmlDomParser $newDocument
     * @param bool          $removeExtraHeadTag
     *
     * @return HtmlDomParser
     */
    protected function cleanHtmlWrapper(
        HtmlDomParser $newDocument,
        $removeExtraHeadTag = false
    ): HtmlDomParser {
        if (
            $newDocument->getIsDOMDocumentCreatedWithoutHtml()
            ||
            $newDocument->getIsDOMDocumentCreatedWithoutHtmlWrapper()
        ) {
            // Remove doc-type node.
            if ($newDocument->getDocument()->doctype !== null) {
                if ($newDocument->getDocument()->doctype->parentNode !== null) {
                    $newDocument->getDocument()->doctype->parentNode->removeChild($newDocument->getDocument()->doctype);
                }
            }

            // Replace html element, preserving child nodes -> but keep the html wrapper, otherwise we got other problems ...
            // so we replace it with "<simpleHtmlDomHtml>" and delete this at the ending.
            $item = $newDocument->getDocument()->getElementsByTagName('html')->item(0);
            if ($item !== null) {
                /** @noinspection UnusedFunctionResultInspection */
                $this->changeElementName($item, 'simpleHtmlDomHtml');
            }

            if ($newDocument->getIsDOMDocumentCreatedWithoutPTagWrapper()) {
                // Remove <p>-element, preserving child nodes.
                $pElement = $newDocument->getDocument()->getElementsByTagName('p')->item(0);
                if ($pElement instanceof \DOMElement) {
                    $fragment = $newDocument->getDocument()->createDocumentFragment();

                    while ($pElement->childNodes->length > 0) {
                        $tmpNode = $pElement->childNodes->item(0);
                        if ($tmpNode !== null) {
                            /** @noinspection UnusedFunctionResultInspection */
                            $fragment->appendChild($tmpNode);
                        }
                    }

                    if ($pElement->parentNode !== null) {
                        $pElement->parentNode->replaceChild($fragment, $pElement);
                    }
                }
            }

            // Remove <body>-element, preserving child nodes.
            $body = $newDocument->getDocument()->getElementsByTagName('body')->item(0);
            if ($body instanceof \DOMElement) {
                $fragment = $newDocument->getDocument()->createDocumentFragment();

                while ($body->childNodes->length > 0) {
                    $tmpNode = $body->childNodes->item(0);
                    if ($tmpNode !== null) {
                        /** @noinspection UnusedFunctionResultInspection */
                        $fragment->appendChild($tmpNode);
                    }
                }

                if ($body->parentNode !== null) {
                    $body->parentNode->replaceChild($fragment, $body);
                }
            }
        }

        // Remove head element, preserving child nodes.
        $node = $this->node();
        if (
            $removeExtraHeadTag
            &&
            $node->parentNode instanceof \DOMElement
            &&
            $newDocument->getIsDOMDocumentCreatedWithoutHeadWrapper()
        ) {
            $html = $node->parentNode->getElementsByTagName('head')[0] ?? null;

            if (
                $html !== null
                &&
                $node->parentNode->ownerDocument
            ) {
                $fragment = $node->parentNode->ownerDocument->createDocumentFragment();

                /** @var \DOMNode $html */
                while ($html->childNodes->length > 0) {
                    $tmpNode = $html->childNodes->item(0);
                    if ($tmpNode !== null) {
                        /** @noinspection UnusedFunctionResultInspection */
                        $fragment->appendChild($tmpNode);
                    }
                }

                if ($html->parentNode !== null) {
                    $html->parentNode->replaceChild($fragment, $html);
                }
            }
        }

        return $newDocument;
    }

    /**
     * Retrieve an external iterator.
     *
     * @see  http://php.net/manual/en/iteratoraggregate.getiterator.php
     *
     * @return SimpleHtmlDomNode
     *                           <p>
     *                              An instance of an object implementing <b>Iterator</b> or
     *                              <b>Traversable</b>
     *                           </p>
     */
    public function getIterator(): SimpleHtmlDomNodeInterface
    {
        $node = $this->node();
        $elements = new SimpleHtmlDomNode();
        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $childNode) {
                $elements[] = $this->createWrapper($childNode);
            }
        }

        return $elements;
    }

    /**
     * @param \DOMNode $node
     *
     * @return static
     */
    private function createWrapper(\DOMNode $node)
    {
        // @phpstan-ignore new.static (wrapper subclasses intentionally preserve late static binding)
        return new static($node, $this->queryHtmlDomParser);
    }

    private function node(): \DOMNode
    {
        \assert($this->node instanceof \DOMNode);

        return $this->node;
    }

    /**
     * @param \DOMNodeList<\DOMNode>|false $nodesList
     * @param int|null                     $idx
     *
     * @return SimpleHtmlDomInterface|SimpleHtmlDomInterface[]|SimpleHtmlDomNodeInterface<SimpleHtmlDomInterface>
     */
    private function createFindResultFromNodeList($nodesList, $idx)
    {
        $elements = new SimpleHtmlDomNode();

        if ($nodesList) {
            foreach ($nodesList as $node) {
                $elements[] = $this->createWrapper($node);
            }
        }

        if ($idx === null) {
            if (\count($elements) === 0) {
                return new SimpleHtmlDomNodeBlank();
            }

            return $elements;
        }

        if ($idx < 0) {
            $idx = \count($elements) + $idx;
        }

        return $elements[$idx] ?? new SimpleHtmlDomBlank();
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
        return $this->getHtmlDomParser()->innerXml($multiDecodeNewHtmlEntity);
    }

    /**
     * Normalize the given input for comparison.
     *
     * @param HtmlDomParser|string $input
     *
     * @return string
     */
    private function normalizeStringForComparison($input): string
    {
        if ($input instanceof HtmlDomParser) {
            $string = $input->html(false, true);

            if ($input->getIsDOMDocumentCreatedWithoutHeadWrapper()) {
                /** @noinspection HtmlRequiredTitleElement */
                $string = \str_replace(['<head>', '</head>'], '', $string);
            }
        } else {
            // Also restore any broken-HTML placeholders that may already be
            // present in the raw string (e.g. when innerhtmlKeep concatenates
            // the current innerHTML — which still contains placeholders — with
            // new content before passing the combined string back as the new
            // innerHTML).  This keeps both sides of the comparison at the same
            // level of substitution.
            $string = HtmlDomParser::putReplacedBackToPreserveHtmlEntities((string) $input, true);
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

    /**
     * Remove this node from the DOM.
     *
     * @return void
     */
    public function delete()
    {
        $this->outertext = '';
    }

    /**
     * Remove this node from the DOM (alias for delete).
     *
     * @return void
     */
    public function remove()
    {
        $this->delete();
    }
}
