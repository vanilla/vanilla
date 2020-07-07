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
final class DomUtils {

    /** @var array */
    private const EMBED_CLASSES = ['js-embed', 'embedResponsive', 'embedExternal', 'embedImage', 'VideoWrap', 'iframe'];

    /** @var array */
    private const TEXT_ATTRIBUTES = ['title', 'alt', 'aria-label'];

    /**
     * Remove embeds from the dom.
     *
     * @param DOMDocument $dom
     * @param array $embedClasses
     */
    public static function stripEmbeds(DOMDocument $dom, array $embedClasses = self::EMBED_CLASSES): void {
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
    public static function stripImages(DOMDocument $dom): void {
        $domImages = $dom->getElementsByTagName('img');
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
    public static function trimWords(DOMDocument $dom, int $wordCount = 100): void {
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
    private static function truncateWordsRecursive($element, int $wordCounter, int $wordCount): int {
        if ($wordCounter > 0) {
            // Nodetype text
            if ($element->nodeType == XML_TEXT_NODE) {
                $wordCounter -= str_word_count($element->data);
                if ($wordCounter < 0) {
                    $element->nodeValue = implode(' ', array_slice(explode(' ', $element->data), 0, $wordCount));
                }
            } else {
                for ($i = 0; $i < $element->childNodes->length; $i++) {
                    if ($wordCounter > 0) {
                        $wordCounter = self::truncateWordsRecursive($element->childNodes->item($i), $wordCounter, $wordCount);
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
     *
     * @param DOMDocument $dom
     * @param string|string[] $pattern Regex pattern.
     * @param callable $callback Callback function.
     * @param array $attributes The attributes to search for.
     * @return int Return the number of replacements.
     */
    public static function pregReplaceCallback(DOMDocument $dom, $pattern, callable $callback, array $attributes = self::TEXT_ATTRIBUTES): int {
        $xpath = new \DOMXPath($dom);
        $xpathQuery = $xpath->query('//text() | //@'.implode(' | //@', $attributes));
        $replacementCount = 0;
        if ($xpathQuery->length > 0) {
            foreach ($xpathQuery as $node) {
                $replaced = preg_replace_callback($pattern, $callback, $node->nodeValue, $limit = -1, $count);
                if ($count > 0 && $replaced !== $node->nodeValue) {
                    $replacementCount++;
                    $node->nodeValue = $replaced;
                }
            }
        }
        return $replacementCount;
    }
}
