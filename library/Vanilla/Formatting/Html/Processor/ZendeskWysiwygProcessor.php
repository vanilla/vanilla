<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Html\Processor;

use Vanilla\Formatting\Html\HtmlDocument;

/**
 * Processor to remove useless HTML from zendesk imports.
 */
class ZendeskWysiwygProcessor extends HtmlProcessor {

    const ZENDESK_ATTRIBUTES = ['data-editor', 'data-block', 'data-offset-key'];

    const ZENDESK_NODE_XPATH = '//*[@data-editor]|//*[@data-block]|//*[@data-offset-key]';

    /**
     * @inheritDoc
     */
    public function processDocument(): HtmlDocument {
        if ($this->hasZendeskContent()) {
            // Only process if we actually have zendesk content.
            $this->stripNonBreakingSpaces();
            $this->unwrapNestedDivs();
            $this->stripUselessAttributes();
        }
        return $this->document;
    }

    /**
     * A quick check to see if we should even try and process the document.
     */
    private function hasZendeskContent(): bool {
        $html = $this->document->getInnerHtml();
        foreach (self::ZENDESK_ATTRIBUTES as $attribute) {
            if (strpos($html, $attribute) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Some imported formats may have useless non-breaking spaces.
     */
    private function stripNonBreakingSpaces() {
        $editorNodes = $this->queryXPath(self::ZENDESK_NODE_XPATH);

        /** @var \DOMNode $editorNode */
        foreach ($editorNodes as $editorNode) {
            $characters = htmlentities(trim($editorNode->textContent));
            if ($characters === '&nbsp;') {
                $editorNode->parentNode->removeChild($editorNode);
                continue;
            }
        }
    }

    /**
     * Unwrap useless nested divs.
     */
    private function unwrapNestedDivs() {
        $nestedDivs = $this->queryXPath('//div[@data-block]');

        /** @var \DOMNode $nestedDiv */
        foreach ($nestedDivs as $nestedDiv) {
            $grandparent = $nestedDiv->parentNode;

            /** @var \DOMNode $childNode */
            foreach ($nestedDiv->childNodes as $childNode) {
                $newChild = $childNode->cloneNode(true);
                $grandparent->insertBefore($newChild, $nestedDiv);
            }
            $grandparent->removeChild($nestedDiv);
        }
    }

    /**
     * Strip off some attributes that serve no value to vanilla.
     */
    private function stripUselessAttributes() {
        $nodes = $this->queryXPath(self::ZENDESK_NODE_XPATH);

        /** @var \DOMElement $node */
        foreach ($nodes as $node) {
            $node->removeAttribute('data-editor');
            $node->removeAttribute('data-block');
            $node->removeAttribute('data-offset-key');
        }
    }
}
