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
     * @return HtmlDocument
     */
    public function processDocument(): HtmlDocument {
        $this->applyStripImages();
        return $this->document;
    }

    /**
     * Apply DomUtils::stripImages()
     */
    private function applyStripImages() {
        DomUtils::stripImages($this->document->getDom());
    }
}
