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
 * Processor of DomUtils::trimWords()
 */
class TrimWordsProcessor extends HtmlProcessor {

    /**
     * TrimWordsProcessor constructor.
     *
     * @param HtmlDocument $document
     * @param ?int $wordCount
     */
    public function __construct(?int $wordCount = null) {
        $this->setWordCount($wordCount);
    }

    /**
     * Set wordCount
     *
     * @param ?int $wordCount
     * @return $this
     */
    private function setWordCount(?int $wordCount) {
        $this->wordCount = $wordCount;
        return $this;
    }

    /**
     * Process Html document.
     *
     * @param HtmlDocument $document
     * @return HtmlDocument
     */
    public function processDocument(HtmlDocument $document): HtmlDocument {
        $this->applyTrimWords($document);
        return $document;
    }

    /**
     * Apply DomUtils::trimWords()
     *
     * @param HtmlDocument $document
     */
    public function applyTrimWords(HtmlDocument $document) {
        if (empty($this->wordCount)) {
            DomUtils::trimWords($document->getDom());
        } else {
            DomUtils::trimWords($document->getDom(), $this->wordCount);
        }
    }
}
