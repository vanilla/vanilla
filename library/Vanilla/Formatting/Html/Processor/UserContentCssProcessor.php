<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Html\Processor;

use Vanilla\Formatting\Html\HtmlDocument;
use Vanilla\Utility\HtmlUtils;

/**
 * Process user content to ensure certain CSS classes are applied.
 */
class UserContentCssProcessor extends HtmlProcessor {

    /**
     * @inheritdoc
     */
    public function processDocument(HtmlDocument $document): HtmlDocument {
        $this->cleanupBlockquotes($document);
        $this->cleanupImages($document);
        $this->cleanupCodeBlocks($document);
        $this->cleanupInlineCodeBlocks($document);
        return $document;
    }

    /**
     * Format HTML of code blocks imported from other formats.
     *
     * @param HtmlDocument $document The document to parse.
     */
    private function cleanupCodeBlocks(HtmlDocument $document) {
        $blockCodeBlocks = $document->queryXPath('.//*[self::pre]');
        foreach ($blockCodeBlocks as $codeBlock) {
            if (!($codeBlock instanceof \DOMElement)) {
                continue;
            }

            $child = $codeBlock->firstChild;

            if ($child instanceof \DOMElement) {
                if ($child->tagName === "code") {
                    $children = $child->childNodes;
                    $codeBlock->removeChild($child);
                    foreach ($children as $child) {
                        $codeBlock->appendChild($child);
                    }
                }
            }


            $classes = $this->getClasses($codeBlock);
            if (!$this->hasClass($classes, "code")) {
                $this->appendClass($codeBlock, "code");
            }

            if (!$this->hasClass($classes, "codeBlock")) {
                $this->appendClass($codeBlock, "codeBlock");
            }

            $this->setAttribute($codeBlock, "spellcheck", "false");
            $this->setAttribute($codeBlock, "tabindex", "0");
        }
    }

    /**
     * Format HTML of inline code blocks imported from other formats.
     *
     * @param HtmlDocument $document The document to parse.
     */
    private function cleanupInlineCodeBlocks(HtmlDocument $document) {
        $inlineCodeBlocks = $document->queryXPath('.//*[self::code]');
        foreach ($inlineCodeBlocks as $c) {
            $this->appendClass($c, "code");
            $this->appendClass($c, "codeInline");
            $this->setAttribute($c, "spellcheck", "false");
            $this->setAttribute($c, "tabindex", "0");
        }
    }

    /**
     * Format HTML of images imported from other formats.
     *
     * @param HtmlDocument $document The document to parse.
     */
    private function cleanupImages(HtmlDocument $document) {
        $images = $document->queryXPath(ImageHtmlProcessor::EMBED_IMAGE_XPATH);
        foreach ($images as $image) {
            HtmlUtils::appendClass($image, 'embedImage-img');
            HtmlUtils::appendClass($image, 'importedEmbed-img');
        }
    }

    /**
     * Format HTML of blockquotes imported from other formats.
     *
     * @param HtmlDocument $document The document to parse.
     */
    private function cleanupBlockquotes(HtmlDocument $document) {
        $blockQuotes = $document->queryXPath('.//*[self::blockquote]');
        foreach ($blockQuotes as $blockQuote) {
            HtmlUtils::appendClass($blockQuote, 'blockquote');
            $children = $blockQuote->childNodes;
            foreach ($children as $child) {
                if (property_exists($child, "tagName") && $child->tagName === "div") {
                    HtmlUtils::appendClass($child, "blockquote-content");
                    $grandChildren = $child->childNodes;
                    foreach ($grandChildren as $grandChild) {
                        if (property_exists($grandChild, "tagName") && $grandChild->tagName === "p") {
                            HtmlUtils::appendClass($grandChild, "blockquote-line");
                        }
                    }
                }
            }
        }
    }
}
