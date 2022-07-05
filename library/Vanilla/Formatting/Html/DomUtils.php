<?php
/**
 * @author Dani M. <dani,m@vanillaforums.com>
 * @author Isis Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Html;

use DOMDocument;

/**
 * Class for stripping images and truncating text from a Dom.
 */
final class DomUtils
{
    /** @var string[] */
    private const EMBED_CLASSES = ["js-embed", "embedResponsive", "embedExternal", "embedImage", "VideoWrap", "iframe"];

    /** @var string[] */
    private const TEXT_ATTRIBUTES = ["title", "alt", "aria-label"];

    /** @var string[] */
    public const TAG_BLOCK = [
        "address",
        "article",
        "aside",
        "blockquote",
        "canvas",
        "dd",
        "div",
        "dl",
        "dt",
        "fieldset",
        "figcaption",
        "figure",
        "footer",
        "form",
        "h1",
        "h2",
        "h3",
        "h4",
        "h5",
        "h6",
        "header",
        "hr",
        "li",
        "main",
        "nav",
        "noscript",
        "ol",
        "p",
        "pre",
        "section",
        "table",
        "tfoot",
        "ul",
        "video",
    ];

    /** @var string[]  */
    public const TAG_INLINE_TEXT = [
        "a",
        "abbr",
        "acronym",
        "b",
        "bdo",
        "big",
        "br",
        "cite",
        "code",
        "dfn",
        "em",
        "i",
        "img",
        "kbd",
        "map",
        "q",
        "samp",
        "small",
        "span",
        "strong",
        "sub",
        "sup",
        "time",
        "tt",
        "var",
    ];

    /** @var string[] */
    public const TAG_INLINE_OTHER = ["button", "input", "label", "object", "output", "script", "select", "textarea"];

    /**
     * Remove embeds from the dom.
     *
     * @param DOMDocument $dom
     * @param array $embedClasses
     */
    public static function stripEmbeds(DOMDocument $dom, array $embedClasses = self::EMBED_CLASSES): void
    {
        $xpath = new \DomXPath($dom);
        foreach ($embedClasses as $key => $value) {
            $xpathQuery = $xpath->query(".//*[contains(@class, '$embedClasses[$key]')]");
            $xpathTagsQuery = $xpath->query("//div[@data-embedjson] | //video | //iframe");
            $dataClassItem = $xpathQuery->item(0);
            $dataTagsItem = $xpathTagsQuery->item(0);
            if ($dataClassItem) {
                $dataClassItem->parentNode->removeChild($dataClassItem);
            } elseif ($dataTagsItem) {
                $dataTagsItem->parentNode->removeChild($dataTagsItem);
            }
        }
    }

    /**
     * Remove images from the dom.
     *
     * @param DOMDocument $dom
     */
    public static function stripImages(DOMDocument $dom): void
    {
        $domImages = $dom->getElementsByTagName("img");
        $imagesArray = [];
        foreach ($domImages as $domImage) {
            $imagesArray[] = $domImage;
        }
        foreach ($imagesArray as $domImage) {
            $domImage->parentNode->removeChild($domImage);
        }
    }

    /**
     * Prepare the html string that will be truncated.
     *
     * @param DOMDocument $dom
     * @param int $wordCount Number of words to truncate to
     */
    public static function trimWords(DOMDocument $dom, int $wordCount = 100): void
    {
        $wordCounter = $wordCount;
        self::truncateWordsRecursive($dom->documentElement, $wordCounter, $wordCount);
    }

    /**
     * Recursively truncate text while preserving html tags.
     *
     * @param mixed $element Dom element.
     * @param int $wordCounter Counter of number of word to trucnate to.
     * @param int $wordCount Number of words to truncate to.
     * @return int Return limit used to count remaining tags.
     */
    private static function truncateWordsRecursive($element, int $wordCounter, int $wordCount): int
    {
        if ($wordCounter > 0) {
            // Nodetype text
            if ($element->nodeType == XML_TEXT_NODE) {
                $wordCounter -= str_word_count($element->data);
                if ($wordCounter < 0) {
                    $element->nodeValue = implode(" ", array_slice(explode(" ", $element->data), 0, $wordCount));
                }
            } else {
                for ($i = 0; $i < $element->childNodes->length; $i++) {
                    if ($wordCounter > 0) {
                        $wordCounter = self::truncateWordsRecursive(
                            $element->childNodes->item($i),
                            $wordCounter,
                            $wordCount
                        );
                    } else {
                        $element->removeChild($element->childNodes->item($i));
                        $i--;
                    }
                }
            }
        }
        return $wordCounter;
    }

    /**
     * Search and replace dom text while preserving html tags.
     * Setting $escapeHtml to false will return HTML.
     *
     * @param DOMDocument $dom
     * @param string|string[] $pattern Regex pattern.
     * @param callable $callback Callback function.
     * @param bool $escapeHtml To return unescaped html.
     * @param array $attributes The attributes to search for.
     * @return int Return the number of replacements.
     */
    public static function pregReplaceCallback(
        DOMDocument $dom,
        $pattern,
        callable $callback,
        bool $escapeHtml = true,
        array $attributes = self::TEXT_ATTRIBUTES
    ): int {
        $xpath = new \DOMXPath($dom);
        $xpathQuery = $xpath->query("//text() | //@" . implode(" | //@", $attributes));
        $replacementCount = 0;
        if ($xpathQuery->length > 0) {
            foreach ($xpathQuery as $node) {
                $replaced = preg_replace_callback($pattern, $callback, $node->nodeValue, $limit = -1, $count);
                if ($count > 0 && $replaced !== $node->nodeValue) {
                    $replacementCount += $count;
                    $hasTags = preg_match("/<[^<]+>/", $replaced, $match) != 0;
                    if ($escapeHtml || $node instanceof \DOMAttr) {
                        $node->nodeValue = $replaced;
                    } else {
                        static::setOuterHTML($node, $replaced);
                    }
                }
            }
        }
        return $replacementCount;
    }

    /**
     * Get the inner HTML of a node.
     *
     * @param \DOMNode $node The parent node to get the content of.
     * @return string Returns an HTML encoded string.
     */
    public static function getInnerHTML(\DOMNode $node): string
    {
        $result = "";
        if ($node->hasChildNodes() === false) {
            return $result;
        }

        foreach ($node->childNodes as $child) {
            /** @var \DOMNode $child */
            $result .= $child->ownerDocument->saveHTML($child);
        }
        return $result;
    }

    /**
     * Get the HTML from a range of DOM nodes.
     *
     * @param \DOMNode $from The range to start from.
     * @param \DOMNode $to The range to go to.
     * @return string Returns an HTML string.
     */
    public static function getHtmlRange(\DOMNode $from, \DOMNode $to): string
    {
        if ($from->parentNode !== $to->parentNode) {
            throw new \InvalidArgumentException(
                __CLASS__ . "::" . __FUNCTION__ . '() expects $from and $to to be siblings.',
                400
            );
        }

        $result = "";

        $sanity = $from->parentNode->childNodes->count();
        for ($node = $from, $i = 0; $node !== $to->nextSibling && $i < $sanity; $node = $node->nextSibling, $i++) {
            /** @var \DOMNode $node */
            $result .= $node->ownerDocument->saveHTML($node);
        }
        return $result;
    }

    /**
     * Sets inner html of an existing node.
     *
     * @param \DOMNode $node
     * @param string $content Content to add to the dom.
     */
    public static function setInnerHTML(\DOMNode $node, string $content): void
    {
        while ($node->hasChildNodes()) {
            $node->removeChild($node->firstChild);
        }

        $fragment = $node->ownerDocument->createDocumentFragment();
        $fragment->appendXML($content);
        $node->ownerDocument->importNode($fragment, true);
        $node->appendChild($fragment);
    }

    /**
     * Sets outer html of an existing node.
     *
     * @param \DOMNode $node The node to replace.
     * @param string $content Content to add to the dom.
     */
    public static function setOuterHTML(\DOMNode $node, string $content): void
    {
        $fragment = $node->ownerDocument->createDocumentFragment();
        $fragment->appendXML($content);
        $newNode = $node->ownerDocument->importNode($fragment, true);
        $node->parentNode->replaceChild($newNode, $node);
    }

    /**
     * Set the HTML from a range of DOM nodes, replacing their content.
     *
     * @param \DOMNode $from The range to start from.
     * @param \DOMNode $to The range to go to.
     * @param string $content The new content of the replacement.
     * @return \DOMNode[] Returns an array in the form `[$newFrom, $newTo]`.
     */
    public static function setHtmlRange(\DOMNode $from, \DOMNode $to, string $content): array
    {
        if ($from->parentNode !== $to->parentNode) {
            throw new \InvalidArgumentException(
                __CLASS__ . "::" . __FUNCTION__ . '() expects $from and $to to be siblings.',
                400
            );
        }

        // Create and insert the new content before $from.
        $fragment = $from->ownerDocument->createDocumentFragment();
        $fragment->appendXML($content);
        $newFrom = $fragment->firstChild;
        $newTo = $fragment->lastChild;
        $from->parentNode->insertBefore($fragment, $from);

        // Remove all of the original nodes.
        $sanity = $from->parentNode->childNodes->count();
        $remove = [];
        for ($node = $from, $i = 0; $node !== $to->nextSibling && $i < $sanity; $node = $node->nextSibling, $i++) {
            $remove[] = $node;
        }
        foreach ($remove as $node) {
            /** @var \DOMNode $node */
            $node->parentNode->removeChild($node);
        }

        return [$newFrom, $newTo];
    }

    /**
     * Trim whitespace nodes from a range of DOM nodes.
     *
     * @param \DOMNode $from The range to start from.
     * @param \DOMNode $to The range to go to.
     * @return ?\DOMNode[] Returns the trimmed range in the form `[$from, $to]` or **null** if the range is completely trimmed.
     */
    public static function trimRange(\DOMNode $from, \DOMNode $to): ?array
    {
        if ($from->parentNode !== $to->parentNode) {
            throw new \InvalidArgumentException(
                __CLASS__ . "::" . __FUNCTION__ . '() expects $from and $to to be siblings.',
                400
            );
        }

        // First trim from the beginning.
        while ($from !== $to->nextSibling) {
            switch ($from->nodeType) {
                case XML_COMMENT_NODE:
                // Skip.
                case XML_TEXT_NODE:
                    if (!preg_match('`^\s*$`', $from->nodeValue)) {
                        break 2;
                    } elseif ($from === $to) {
                        // The entire string has been trimmed.
                        return null;
                    }
                    break;
                default:
                    break 2;
            }

            $from = $from->nextSibling;
        }

        // Next trim from the end.
        while ($to !== $from->previousSibling) {
            switch ($to->nodeType) {
                case XML_COMMENT_NODE:
                // Skip.
                case XML_TEXT_NODE:
                    if (!preg_match('`^\s*$`', $to->nodeValue)) {
                        break 2;
                    }
                    break;
                default:
                    break 2;
            }

            $to = $to->previousSibling;
        }

        return [$from, $to];
    }
}
