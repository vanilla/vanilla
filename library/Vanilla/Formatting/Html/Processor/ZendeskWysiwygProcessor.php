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
     * @inheritdoc
     */
    public function processDocument(HtmlDocument $document): HtmlDocument {
        if ($this->hasZendeskContent($document)) {
            // Only process if we actually have zendesk content.
            $this->stripNonBreakingSpaces($document);
            $this->unwrapNestedDivs($document);
            $this->stripUselessAttributes($document);
        }
        return $document;
    }

    /**
     * A quick check to see if we should even try and process the document.
     *
     * @param HtmlDocument $document The document to parse.
     *
     * @return bool
     */
    private function hasZendeskContent(HtmlDocument $document): bool {
        $html = $document->getInnerHtml();
        foreach (self::ZENDESK_ATTRIBUTES as $attribute) {
            if (strpos($html, $attribute) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Some imported formats may have useless non-breaking spaces.
     *
     * @param HtmlDocument $document The document to parse.
     */
    private function stripNonBreakingSpaces(HtmlDocument $document) {
        $editorNodes = $document->queryXPath(self::ZENDESK_NODE_XPATH);

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
     *
     * @param HtmlDocument $document The document to parse.
     */
    private function unwrapNestedDivs(HtmlDocument $document) {
        $nestedDivs = $document->queryXPath('//div[@data-block]');

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
     *
     * @param HtmlDocument $document The document to parse.
     */
    private function stripUselessAttributes(HtmlDocument $document) {
        $nodes = $document->queryXPath(self::ZENDESK_NODE_XPATH);

        /** @var \DOMElement $node */
        foreach ($nodes as $node) {
            $node->removeAttribute('data-editor');
            $node->removeAttribute('data-block');
            $node->removeAttribute('data-offset-key');
        }
    }
}
