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
 *
 */
 class DomUtils {

    /**
     * Remove embeds from the dom.
     *
     * @param DOMDocument $dom
     * @param array $embedClasses
     */
    public static function stripEmbeds(DOMDocument $dom, array $embedClasses): void {
        $xpath = new \DomXPath($dom);
        foreach ($embedClasses as $key => $value) {
            $xpathQuery = $xpath->query(".//*[contains(@class, '$embedClasses[$key]')]");
            $xpathDivQuery = $xpath->query("//div[@data-embedjson]");
            $dataClassItem = $xpathQuery->item(0);
            $dataDivItem = $xpathDivQuery->item(0);
            if ($dataClassItem) {
                $dataClassItem->parentNode->removeChild($dataClassItem);
            } elseif ($dataDivItem) {
                $dataDivItem->parentNode->removeChild($dataDivItem);
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
    public static function truncateWords(DOMDocument $dom, int $wordCount): void {
        (new self)->truncateWordsRecursive($dom->documentElement, $wordCount);
    }

    /**
     * Recursively truncate text while preserving html tags.
     *
     * @param mixed $element Dom element.
     * @param int $limit Number of words to truncate to.
     * @return int Return limit used to count remaining tags.
     */
    private function truncateWordsRecursive($element, int $limit): int {
        $wordCount = $limit;
        if ($limit > 0) {
            // Nodetype text
            if ($element->nodeType == 3) {
                $limit -= str_word_count($element->data);
                if ($limit < 0) {
                    $element->nodeValue = implode(' ', array_slice(explode(' ', $element->data), 0, $wordCount));
                }
            } else {
                for ($i = 0; $i < $element->childNodes->length; $i++) {
                    if ($limit > 0) {
                        $limit = $this->truncateWordsRecursive($element->childNodes->item($i), $limit);
                    } else {
                        $element->removeChild($element->childNodes->item($i));
                        $i--;
                    }
                }
            }
        }
        return $limit;
    }
}
