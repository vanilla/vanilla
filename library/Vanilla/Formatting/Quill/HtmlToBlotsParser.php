<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Quill;

use Vanilla\Formatting\Html\HtmlDocument;
use Vanilla\Formatting\Quill\Blots\Lines\AbstractLineTerminatorBlot;
use Vanilla\Formatting\Quill\Blots\TextBlot;

/**
 * Utility functions for parsing HTML into bots.
 */
class HtmlToBlotsParser
{
    /**
     * Parse an HTML fragment into blots.
     *
     * @param string $html The HTML to parse.
     * @param BlotGroupCollection $parent The document the group is a part of.
     * @param AbstractLineTerminatorBlot $terminator An optional terminator for the operations.
     * @return array<TextBlot>
     */
    public static function parseInlineHtml(
        string $html,
        BlotGroupCollection $parent,
        AbstractLineTerminatorBlot $terminator = null
    ): array {
        $dom = new HtmlDocument($html);
        $root = $dom->getRoot();

        $operations = static::parseDOMElementOperations($root);
        if ($terminator !== null) {
            $operations[] = $terminator->getCurrentOperation();
        }

        $new = new BlotGroupCollection($operations, $parent->getAllowedBlotClasses(), $parent->getParseMode());

        $group = $new->getGroups()[0] ?? null;
        return $group ? $group->getBlotsAndGroups() : [];
    }

    /**
     * Parse a parent DOM node into an array of basic inline operations.
     *
     * @param \DOMNode $parent The parent node being parsed.
     * @param array $parentOp A parent operation with attributes already set.
     * @return array
     */
    private static function parseDOMElementOperations(\DOMNode $parent, $parentOp = []): array
    {
        $result = [];
        foreach ($parent->childNodes as $node) {
            $op = $parentOp;
            if ($node instanceof \DOMText) {
                $op["insert"] = $node->nodeValue;
                $result[] = $op;
            } elseif ($node instanceof \DOMElement) {
                $op = array_replace_recursive($op, static::attributesFromElement($node));
                $blots = static::parseDOMElementOperations($node, $op);
                $result = array_merge($result, $blots);
            }
        }

        return $result;
    }

    /**
     * Get the operation attributes from an HTML element.
     *
     * @param \DOMElement $node
     * @return array
     */
    private static function attributesFromElement(\DOMElement $node): array
    {
        $op = [];
        switch (strtolower($node->tagName)) {
            case "b":
            case "strong":
                $op["attributes"]["bold"] = true;
                break;
            case "i":
            case "em":
                $op["attributes"]["italic"] = true;
                break;
            case "s":
            case "strike":
                $op["attributes"]["strike"] = true;
                break;
            case "code":
                $op["attributes"]["code"] = true;
                break;
            case "a":
                $op["attributes"]["link"] = (string) $node->getAttribute("href");
                break;
        }
        return $op;
    }
}
