<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility;

/**
 * Trait offering some simple HTML parsing methods.
 */
trait HtmlParserTrait {

    /**
     * Get all attributes of the first matching HTML tag.
     *
     * @param string $html The HTML to parse.
     * @param string $tagName The tag to check for.
     *
     * @return array|null An array of all attributes if applicable.
     */
    protected function parseSimpleAttrs(string $html, string $tagName): ?array {
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        $tags = $dom->getElementsByTagName($tagName);
        if (count($tags) > 0) {
            $attrs = [];
            $tag = $tags->item(0);
            foreach ($tag->attributes as $attribName => $attribNodeVal) {
                $attrs[$attribName] = $tag->getAttribute($attribName);
            }
            return $attrs;
        }
        return null;
    }
}
