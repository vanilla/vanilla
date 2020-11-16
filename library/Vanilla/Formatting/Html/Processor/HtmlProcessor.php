<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Html\Processor;

use Vanilla\Formatting\Html\HtmlDocument;

/**
 * Processor an HtmlDocument.
 */
abstract class HtmlProcessor {

    use HtmlProcessorTrait;

    /** @var HtmlDocument */
    protected $document;

    /**
     * Constructor.
     *
     * @param HtmlDocument $document
     */
    public function __construct(HtmlDocument $document) {
        $this->document = $document;
    }

    /**
     * @return HtmlDocument
     */
    protected function getDocument(): HtmlDocument {
        return $this->document;
    }

    /**
     * @param HtmlDocument $document
     */
    public function setDocument(HtmlDocument $document) {
        $this->document = $document;
    }

    /**
     * Process the HTML document in some way.
     *
     * @return HtmlDocument
     */
    abstract public function processDocument(): HtmlDocument;
}
