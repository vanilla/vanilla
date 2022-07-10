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

    /** @var bool $escapeHtml */
    private $escapeHtml;

    /** @var array $attributes */
    private $attributes;

    /**
     * PregReplaceCallbackProcessor constructor.
     *
     * @param array $pattern
     * @param callable $callback
     * @param bool $escapeHtml
     * @param ?array $attributes
     */
    public function __construct(array $pattern, callable $callback, bool $escapeHtml = true, ?array $attributes = []) {
        $this->setPattern($pattern);
        $this->setCallback($callback);
        $this->setEscapeHtml($escapeHtml);
        $this->setAttributes($attributes);
    }

    /**
     * Process the HTML document.
     *
     * @return HtmlDocument
     */
    public function processDocument(HtmlDocument $document): HtmlDocument {
        $this->applyPregReplaceProcessor($document);
        return $document;
    }

    /**
     * Apply DomUtils::pregReplaceCallback()
     *
     * @param HtmlDocument $document
     */
    public function applyPregReplaceProcessor(HtmlDocument $document) {
        if (empty($this->attributes)) {
            DomUtils::pregReplaceCallback($document->getDom(), $this->pattern, $this->callback);
        } else {
            DomUtils::pregReplaceCallback($document->getDom(), $this->pattern, $this->callback, $this->escapeHtml, $this->attributes);
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
     * Set escapeHtml
     *
     * @param bool $escapeHtml
     * @return $this
     */
    private function setEscapeHtml(bool $escapeHtml) {
        $this->escapeHtml = $escapeHtml;
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
