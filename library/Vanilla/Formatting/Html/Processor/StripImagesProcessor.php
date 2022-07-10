<?php
/**
 * @author Dani M. <dani.m@vanillaforums.com>
 * @author Isis Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Html\Processor;

use Vanilla\Formatting\Html\DomUtils;
use Vanilla\Formatting\Html\HtmlDocument;

/**
 * Processor of DomUtils::stripImages()
 */
class StripImagesProcessor extends HtmlProcessor {

    /**
     * Process the HTML document.
     *
     * @param HtmlDocument $document
     * @return HtmlDocument
     */
    public function processDocument(HtmlDocument $document): HtmlDocument {
        $this->applyStripImages($document);
        return $document;
    }

    /**
     * Apply DomUtils::stripImages()
     *
     * @param HtmlDocument $document
     */
    private function applyStripImages(HtmlDocument $document) {
        DomUtils::stripImages($document->getDom());
    }
}
