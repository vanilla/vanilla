<?php
/**
 * @author Dani M. <dani.m@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Html\Processor;

use Vanilla\Formatting\Html\DomUtils;
use Vanilla\Formatting\Html\HtmlDocument;

/**
 * Processor of DomUtils::pregReplaceCallback()
 */
class PregReplaceCallbackProcessor extends HtmlProcessor {

    /** @var array $pattern; */
    private $pattern;

    /** @var Callable $callback */
    private $callback;

    /**
     * PregReplaceCallbackProcessor constructor.
     *
     * @param HtmlDocument $document
     * @param array $pattern
     * @param callable $callback
     */
    public function __construct(HtmlDocument $document, array $pattern, callable $callback) {
        parent::__construct($document);
        $this->setPattern($pattern);
        $this->setCallback($callback);
    }

    /**
     * Process the HTML document.
     *
     * @return HtmlDocument
     */
    public function processDocument(): HtmlDocument {
        $this->applyPregReplaceProcessor();
        return $this->document;
    }

    /**
     * Apply DomUtils::pregReplaceCallback()
     */
    private function applyPregReplaceProcessor() {
        DomUtils::pregReplaceCallback($this->document->getDom(), $this->pattern, $this->callback);
    }

    /**
     * Set pattern
     *
     * @param array $pattern
     * @return $this
     */
    private function setPattern(array $pattern) {
        $this->pattern = $pattern;
        return $this;
    }

    /**
     * Set callback
     *
     * @param callable $callback
     * @return $this
     */
    private function setCallback($callback) {
        $this->callback = $callback;
        return $this;
    }
}
