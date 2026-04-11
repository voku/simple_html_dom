<?php

declare(strict_types=1);

namespace voku\helper;

use Symfony\Component\CssSelector\CssSelectorConverter;

class SelectorConverter
{
    private const CHILD_COMBINATOR = '>';
    private const ADJACENT_SIBLING_COMBINATOR = '+';
    private const GENERAL_SIBLING_COMBINATOR = '~';
    private const LEADING_COMBINATORS = [
        self::CHILD_COMBINATOR,
        self::ADJACENT_SIBLING_COMBINATOR,
        self::GENERAL_SIBLING_COMBINATOR,
    ];
    private const DESCENDANT_OR_SELF_AXIS_PREFIX = 'descendant-or-self::';
    private const SELECTOR_WHITESPACE_CHARACTERS = " \t\n\r\0\x0B";

    /**
     * @var string[]
     *
     * @phpstan-var array<string,string>
     */
    protected static $compiled = [];

    /**
     * @param string $selector
     * @param bool $ignoreCssSelectorErrors
     *                                      <p>
     *                                      Ignore css selector errors and use the $selector as it is on error,
     *                                      so that you can also use xPath selectors.
     *                                      </p>
     * @param bool $isForHtml
     *
     * @return string
     */
    public static function toXPath(string $selector, bool $ignoreCssSelectorErrors = false, bool $isForHtml = true)
    {
        // Select DOMText
        if ($selector === 'text') {
            return '//text()';
        }

        // Select DOMComment
        if ($selector === 'comment') {
            return '//comment()';
        }

        if (\strpos($selector, '//') === 0) {
            return $selector;
        }

        $cacheKey = self::createCompiledCacheKey($selector, $ignoreCssSelectorErrors, $isForHtml);
        if (isset(self::$compiled[$cacheKey])) {
            return self::$compiled[$cacheKey];
        }

        if (!\class_exists(CssSelectorConverter::class)) {
            throw new \RuntimeException('Unable to filter with a CSS selector as the Symfony CssSelector 2.8+ is not installed (you can use filterXPath instead).');
        }

        $converterKey = '-' . $isForHtml . '-' . $ignoreCssSelectorErrors . '-';
        static $converterArray = [];
        if (!isset($converterArray[$converterKey])) {
            $converterArray[$converterKey] = new CssSelectorConverter($isForHtml);
        }
        $converter = $converterArray[$converterKey];
        assert($converter instanceof CssSelectorConverter);

        try {
            $xPathQuery = self::convertSelectorListToXPath($selector, $converter);
        } catch (\Exception $e) {
            if (!$ignoreCssSelectorErrors) {
                throw $e;
            }

            $xPathQuery = $selector;
        }

        self::$compiled[$cacheKey] = $xPathQuery;

        return $xPathQuery;
    }

    public static function clearCompiledCache(): void
    {
        self::$compiled = [];
    }

    private static function convertSelectorListToXPath(string $selector, CssSelectorConverter $converter): string
    {
        $xPathQueries = [];
        foreach (self::splitSelectorGroups($selector) as $selectorGroup) {
            $xPathQueries[] = self::convertSelectorGroupToXPath($selectorGroup, $converter);
        }

        return \implode(' | ', $xPathQueries);
    }

    /**
     * @return string[]
     */
    private static function splitSelectorGroups(string $selector): array
    {
        $selectorGroups = [];
        $currentSelectorGroup = '';
        $quote = '';
        $bracketLevel = 0;
        $parenthesisLevel = 0;
        $isEscaped = false;

        $selectorLength = \strlen($selector);
        for ($i = 0; $i < $selectorLength; ++$i) {
            $char = $selector[$i];

            if ($isEscaped) {
                $currentSelectorGroup .= $char;
                $isEscaped = false;

                continue;
            }

            if ($char === '\\') {
                $currentSelectorGroup .= $char;
                $isEscaped = true;

                continue;
            }

            if ($quote !== '') {
                $currentSelectorGroup .= $char;
                if ($char === $quote) {
                    $quote = '';
                }

                continue;
            }

            if ($char === '"' || $char === '\'') {
                $currentSelectorGroup .= $char;
                $quote = $char;

                continue;
            }

            if ($char === '[') {
                ++$bracketLevel;
                $currentSelectorGroup .= $char;

                continue;
            }

            if ($char === ']') {
                if ($bracketLevel > 0) {
                    --$bracketLevel;
                }
                $currentSelectorGroup .= $char;

                continue;
            }

            if ($char === '(') {
                ++$parenthesisLevel;
                $currentSelectorGroup .= $char;

                continue;
            }

            if ($char === ')') {
                if ($parenthesisLevel > 0) {
                    --$parenthesisLevel;
                }
                $currentSelectorGroup .= $char;

                continue;
            }

            if ($char === ',' && $bracketLevel === 0 && $parenthesisLevel === 0) {
                $selectorGroups[] = $currentSelectorGroup;
                $currentSelectorGroup = '';

                continue;
            }

            $currentSelectorGroup .= $char;
        }

        $selectorGroups[] = $currentSelectorGroup;

        return $selectorGroups;
    }

    private static function convertSelectorGroupToXPath(string $selectorGroup, CssSelectorConverter $converter): string
    {
        $trimmedSelectorGroup = \trim($selectorGroup);

        if ($trimmedSelectorGroup === '') {
            throw new \RuntimeException('Selector group cannot be empty.');
        }

        if ($trimmedSelectorGroup === 'text') {
            return '//text()';
        }

        if ($trimmedSelectorGroup === 'comment') {
            return '//comment()';
        }

        if (\strpos($trimmedSelectorGroup, '//') === 0) {
            return $trimmedSelectorGroup;
        }

        if (!\in_array($trimmedSelectorGroup[0], self::LEADING_COMBINATORS, true)) {
            return $converter->toXPath($trimmedSelectorGroup);
        }

        $combinator = $trimmedSelectorGroup[0];
        $restSelector = \ltrim(\substr($trimmedSelectorGroup, 1), self::SELECTOR_WHITESPACE_CHARACTERS);
        if ($restSelector === '') {
            throw new \RuntimeException('Selector group cannot end with a combinator (' . $combinator . ').');
        }

        if ($restSelector === 'text') {
            return self::createNodeTestXPath($combinator, 'text()');
        }

        if ($restSelector === 'comment') {
            return self::createNodeTestXPath($combinator, 'comment()');
        }

        return self::replaceLeadingAxis(
            $converter->toXPath($restSelector),
            self::createElementAxisPrefix($combinator)
        );
    }

    private static function createElementAxisPrefix(string $combinator): string
    {
        switch ($combinator) {
            case self::CHILD_COMBINATOR:
                return './';
            case self::ADJACENT_SIBLING_COMBINATOR:
                return './following-sibling::*[1]/self::';
            case self::GENERAL_SIBLING_COMBINATOR:
                return './following-sibling::';
            default:
                throw new \RuntimeException('Unexpected combinator in element axis prefix: ' . $combinator);
        }
    }

    private static function createNodeTestXPath(string $combinator, string $nodeTest): string
    {
        switch ($combinator) {
            case self::CHILD_COMBINATOR:
                return './' . $nodeTest;
            case self::ADJACENT_SIBLING_COMBINATOR:
                return './following-sibling::node()[1]/self::' . $nodeTest;
            case self::GENERAL_SIBLING_COMBINATOR:
                return './following-sibling::' . $nodeTest;
            default:
                throw new \RuntimeException('Unexpected combinator in node test XPath: ' . $combinator);
        }
    }

    private static function replaceLeadingAxis(string $xPathQuery, string $replacement): string
    {
        if (\strpos($xPathQuery, self::DESCENDANT_OR_SELF_AXIS_PREFIX) === 0) {
            return $replacement . \substr($xPathQuery, \strlen(self::DESCENDANT_OR_SELF_AXIS_PREFIX));
        }

        if (\strpos($xPathQuery, '//') === 0) {
            return $replacement . \substr($xPathQuery, 2);
        }

        return $replacement . $xPathQuery;
    }

    private static function createCompiledCacheKey(string $selector, bool $ignoreCssSelectorErrors, bool $isForHtml): string
    {
        $cacheKey = \json_encode([$selector, $ignoreCssSelectorErrors, $isForHtml]);
        if ($cacheKey === false) {
            throw new \RuntimeException('Unable to encode the selector conversion cache key.');
        }

        return $cacheKey;
    }
}
