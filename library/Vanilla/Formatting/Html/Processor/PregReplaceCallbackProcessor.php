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

    /** @var array $attributes */
    private $attributes;

    /**
     * PregReplaceCallbackProcessor constructor.
     *
     * @param HtmlDocument $document
     * @param array $pattern
     * @param callable $callback
     * @param array $attributes
     */
    public function __construct(HtmlDocument $document, array $pattern, callable $callback, ?array $attributes = []) {
        parent::__construct($document);
        $this->setPattern($pattern);
        $this->setCallback($callback);
        $this->setAttributes($attributes);
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
    public function applyPregReplaceProcessor() {
        if (empty($this->attributes)) {
            DomUtils::pregReplaceCallback($this->document->getDom(), $this->pattern, $this->callback);
        } else {
            DomUtils::pregReplaceCallback($this->document->getDom(), $this->pattern, $this->callback, $this->attributes);
        }
    }

    /**
     * Set pattern
     *
     * @param string|string[] $pattern
     * @return $this
     */
    private function setPattern($pattern) {
        $this->pattern = $pattern;
        return $this;
    }

    /**
     * Set attributes
     *
     * @param array $attributes
     * @return $this
     */
    private function setAttributes(?array $attributes) {
        $this->attributes = $attributes;
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
