<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Html;

use Garden\StaticCacheTranslationTrait;

/**
 * Class for converting HTML into plain text.
 */
class HtmlPlainTextConverter
{
    use StaticCacheTranslationTrait;

    /** @var string; */
    private $addNewLinesAfterDiv = false;

    /**
     * Replaces opening html list tags with an asterisk and closing list tags with new lines.
     *
     * Accepts both encoded and decoded html strings.
     *
     * @param string $html An HTML-formatted string.
     * @return string Plain text.
     */
    public function convert(string $html): string
    {
        $result = $html;
        $result = $this->replaceHtmlElementsWithStrings(
            $result,
            [
                "Spoiler" => "",
                "UserSpoiler" => "",
                "Quote" => "",
                "UserQuote" => "",
            ],
            [
                "img" => "",
            ]
        );
        $result = $this->replaceListItems($result);
        $result = $this->replaceHtmlTags($result);
        $result = trim($result);
        return $result;
    }

    /**
     * @param string $addNewLinesAfterDiv
     */
    public function setAddNewLinesAfterDiv(string $addNewLinesAfterDiv): void
    {
        $this->addNewLinesAfterDiv = $addNewLinesAfterDiv;
    }

    /**
     * Check to see if a string has spoilers and replace them with an innocuous string.
     *
     * Good for displaying excerpts from discussions and without showing the spoiler text.
     *
     * @param string $html An HTML-formatted string.
     * @param array $classStringMapping A mapping of CSSClass => replacement string.
     * @param array $tagNameStringMapping A mapping of NodeName => replacement string.
     *
     * @return string Returns the html with spoilers removed.
     */
    protected function replaceHtmlElementsWithStrings(
        string $html,
        array $classStringMapping,
        array $tagNameStringMapping
    ) {
        $htmlDocument = new HtmlDocument($html);

        // Replace emojis with their placetext versions.
        $emojiImages = $htmlDocument->queryCssSelector(".emoji");
        /** @var \DOMElement $emojiImage */
        foreach ($emojiImages as $emojiImage) {
            $this->replaceNodeWithString(
                $htmlDocument->getDom(),
                $emojiImage,
                $emojiImage->getAttribute("alt"),
                "inline"
            );
        }

        foreach ($classStringMapping as $cssClass => $replacementString) {
            $foundItems = $htmlDocument->queryCssSelector(".{$cssClass}");

            /** @var \DOMNode $foundItem */
            foreach ($foundItems as $foundItem) {
                $this->replaceNodeWithString($htmlDocument->getDom(), $foundItem, $replacementString);
            }
        }

        foreach ($tagNameStringMapping as $nodeName => $replacementString) {
            $foundItems = $htmlDocument->getDom()->getElementsByTagName($nodeName);

            /** @var \DOMNode $foundItem */
            foreach ($foundItems as $foundItem) {
                $this->replaceNodeWithString($htmlDocument->getDom(), $foundItem, $replacementString);
            }
        }

        $htmlBodyString = $htmlDocument->renderHTML();
        return $htmlBodyString;
    }

    /**
     * Replace a dom node with a string.
     *
     * @param \DOMDocument $dom
     * @param \DOMNode $node
     * @param string $replacement
     * @param string $elementType Either "auto", "inline" or "block".
     */
    private function replaceNodeWithString(
        \DOMDocument $dom,
        \DOMNode $node,
        string $replacement,
        string $elementType = "auto"
    ) {
        if ($elementType === "auto") {
            if ($node instanceof \DOMElement) {
                $elementType = in_array($node->tagName, HtmlDocument::TAG_INLINE_TEXT) ? "inline" : "block";
            } else {
                $elementType = "inline";
            }
        }
        /** @var \DOMElement $parent */
        $parent = $node->parentNode;
        $nextSibling = $node->nextSibling;
        $isNextSiblingInline =
            $nextSibling &&
            $nextSibling instanceof \DOMElement &&
            in_array($nextSibling->tagName, HtmlDocument::TAG_INLINE_TEXT);
        if (empty($replacement)) {
            $parent->removeChild($node);
        } else {
            $replacement = self::t($replacement);
            if ($elementType === "inline" && $nextSibling && !$isNextSiblingInline) {
                $replacement .= " ";
            }
            $textNode = $dom->createTextNode($replacement);
            $parent->replaceChild($textNode, $node);
        }
        if ($elementType === "block") {
            // Add a break if it wasn't an inline element.
            $breakNode = $dom->createElement("br");
            $parent->insertBefore($breakNode, $nextSibling);
        }
    }

    /**
     * Convert common tags in an HTML strings to plain text. You still need to sanitize your string!!!
     *
     * @param string $html An HTML-formatted string.
     * @param bool $collapse Treat a group of closing block tags as one when replacing with newlines.
     *
     * @return string An HTML-formatted strings with common tags replaced with plainText
     */
    private function replaceHtmlTags(string $html, bool $collapse = false): string
    {
        // Remove returns and then replace html return tags with returns.
        $result = str_replace(["\n", "\r"], "", $html);
        $result = preg_replace("`<br\s*/?>`", "\n", $result);

        // Fix lists.
        $result = $this->replaceListItems($result);

        $newLineBlocks = [
            "table",
            "dl",
            "pre",
            "blockquote",
            "address",
            "p",
            "h[1-6]",
            "section",
            "article",
            "aside",
            "hgroup",
            "header",
            "footer",
            "nav",
            "figure",
            "figcaption",
            "details",
            "menu",
            "summary",
        ];

        if ($this->addNewLinesAfterDiv) {
            $result = preg_replace("`</(?:div)>`", "\n", $result);
        }

        $endTagRegex = "</(?:" . implode("|", $newLineBlocks) . ")>";
        if ($collapse) {
            $endTagRegex = "((\s+)?{$endTagRegex})+";
        }

        // Put back the code blocks.
        $result = preg_replace("`{$endTagRegex}`", "\n\n", $result);

        // TODO: Fix hard returns within pre blocks.

        // Cleanup the starting tags and any other stray tags.
        // We have to be careful of our code blocks here, so we'll replace them with some well known value.
        // THIS IS NOT A SECURITY MEASURE. YOU STILL NEED TO HTML ESCAPE CONTENT WHERE NECESSARY.
        $result = strip_tags($result);
        $result = htmlspecialchars_decode($result);
        return $result;
    }

    /**
     * Replaces opening html list tags with an asterisk and closing list tags with new lines.
     *
     * Accepts both encoded and decoded html strings.
     *
     * @param string $html An HTML-formatted string.
     * @return string Returns the HTML with all list items removed.
     */
    private function replaceListItems(string $html): string
    {
        // Strip the wrapping tags.
        $html = str_replace(["<ul>", "<ol>"], "", $html);
        $html = str_replace(["</ul>", "</ol>"], "<br>", $html);

        // Replace starting tags.
        $html = str_replace(["<li>", "&lt;li&gt;"], "* ", $html);

        $regexes = [
            "/(<\/?(?:li|ul|ol)([^>]+)?>)/", // UTF-8 encoded
            "/(&lt;\/?(?:li|ul|ol)([^&]+)?&gt;)/", // HtmlEncoded
        ];

        // Replace closing tags.
        $html = preg_replace($regexes, "<br>", $html);
        return $html;
    }
}
