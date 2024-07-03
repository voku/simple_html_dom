<?php

declare(strict_types=1);

namespace voku\helper;

final class HtmlDomHelper
{
    /**
     * @param string $html
     * @param string $optionStr
     * @param string $htmlCssSelector
     *
     * @return string
     */
    public static function mergeHtmlAttributes(
        string $html,
        string $optionStr,
        string $htmlCssSelector
    ): string {
        if (!$optionStr) {
            return $html;
        }

        $dom = \voku\helper\HtmlDomParser::str_get_html($html);
        $domNew = \voku\helper\HtmlDomParser::str_get_html('<textarea ' . $optionStr . '></textarea>');

        $domElement = $dom->findOneOrFalse($htmlCssSelector);
        if ($domElement === false) {
            return $html;
        }
        $attributes = $domElement->getAllAttributes();
        if (!$attributes) {
            return $html;
        }

        $domElementNew = $domNew->findOneOrFalse('textarea');
        if ($domElementNew === false) {
            return $html;
        }
        $attributesNew = $domElementNew->getAllAttributes();
        if (!$attributesNew) {
            return $html;
        }

        foreach ($attributesNew as $attributeNameNew => $attributeValueNew) {
            $attributeNameNew = \strtolower($attributeNameNew);

            if (
                $attributeNameNew === 'class'
                ||
                $attributeNameNew === 'style'
                ||
                \strpos($attributeNameNew, 'on') === 0 // e.g. onClick, ...
            ) {
                if (isset($attributes[$attributeNameNew])) {
                    $attributes[$attributeNameNew] .= ' ' . $attributeValueNew;
                } else {
                    $attributes[$attributeNameNew] = $attributeValueNew;
                }
            } else {
                $attributes[$attributeNameNew] = $attributeValueNew;
            }
        }

        foreach ($attributes as $attributeName => $attributeValue) {
            $domElement->setAttribute($attributeName, $attributeValue, true);
        }

        return $domElement->html();
    }
}
