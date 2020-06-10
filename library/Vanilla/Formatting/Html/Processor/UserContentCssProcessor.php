<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Html\Processor;

use Vanilla\Formatting\Html\HtmlDocument;

/**
 * Process user content to ensure certain CSS classes are applied.
 */
class UserContentCssProcessor extends HtmlProcessor {

    /**
     * @inheritDoc
     */
    public function processDocument(): HtmlDocument {
        $this->cleanupBlockquotes();
        $this->cleanupImages();
        $this->cleanupCodeBlocks();
        $this->cleanupInlineCodeBlocks();
        return $this->document;
    }

    /**
     * Format HTML of code blocks imported from other formats.
     */
    private function cleanupCodeBlocks() {
        $blockCodeBlocks = $this->queryXPath('.//*[self::pre]');
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
     */
    private function cleanupInlineCodeBlocks() {
        $inlineCodeBlocks = $this->queryXPath('.//*[self::code]');
        foreach ($inlineCodeBlocks as $c) {
            $this->appendClass($c, "code");
            $this->appendClass($c, "codeInline");
            $this->setAttribute($c, "spellcheck", "false");
        }
    }

    /**
     * Format HTML of images imported from other formats.
     */
    private function cleanupImages() {
        $images = $this->queryXPath(ImageHtmlProcessor::EMBED_IMAGE_XPATH);
        foreach ($images as $image) {
            $this->appendClass($image, "embedImage-img");
            $this->appendClass($image, "importedEmbed-img");
        }
    }

    /**
     * Format HTML of blockquotes imported from other formats.
     */
    private function cleanupBlockquotes() {
        $blockQuotes = $this->queryXPath('.//*[self::blockquote]');
        foreach ($blockQuotes as $b) {
            self::appendClass($b, "blockquote");
            $children = $b->childNodes;
            foreach ($children as $child) {
                if (property_exists($child, "tagName")) {
                    if ($child->tagName === "div") {
                        self::setAttribute($child, "class", "blockquote-content");
                        $grandChildren = $child->childNodes;
                        foreach ($grandChildren as $grandChild) {
                            if (property_exists($grandChild, "tagName")) {
                                if ($grandChild->tagName === "p") {
                                    self::appendClass($grandChild, "blockquote-line");
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
