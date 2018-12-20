<?php

declare(strict_types=1);

namespace voku\helper;

/**
 * Interface for SimpleHtmlDomNode
 */
interface SimpleHtmlDomNodeInterface
{
    /**
     * Find list of nodes with a CSS selector
     *
     * @param string $selector
     * @param int    $idx
     *
     * @return SimpleHtmlDomNode|SimpleHtmlDomNode[]|null
     */
    public function find(string $selector, $idx = null);

    /**
     * Get html of Elements
     *
     * @return string|string[]
     */
    public function innerHtml();

    /**
     * Get plain text
     *
     * @return string|string[]
     */
    public function text();
}
