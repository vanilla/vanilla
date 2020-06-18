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
     * @param HtmlDocument $document
     * @param int $wordCount
     */
    public function __construct(HtmlDocument $document, int $wordCount) {
        parent::__construct($document);
        $this->setWordCount($wordCount);
    }

    private function setWordCount(int $wordCount) {
        $this->wordCount = $wordCount;
        return $this;
    }

    /**
     * Process Html document.
     *
     * @return HtmlDocument
     */
    public function processDocument(): HtmlDocument {
        $this->applyTrimWords();
        return $this->document;
    }

    /**
     * Apply DomUtils::trimWords()
     */
    public function applyTrimWords() {
        DomUtils::trimWords($this->document->getDom(), $this->wordCount);
    }
}
