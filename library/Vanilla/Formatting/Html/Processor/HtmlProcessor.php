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

    /**
     * Dynamic processors should be re-applied on every render.
     */
    const TYPE_DYNAMIC = "dynamic";

    /**
     * Results from dynamic processors can be cached indefinitely.
     */
    const TYPE_STATIC = "static";

    /**
     * Get the processor type.
     *
     * @return string One of TYPE_DYNAMIC or TYPE_STATIC.
     */
    public function getProcessorType(): string {
        return self::TYPE_STATIC;
    }

    /**
     * Process the HTML document in some way.
     *
     * @param HtmlDocument $document The document to process.
     * @return HtmlDocument The modified document.
     */
    abstract public function processDocument(HtmlDocument $document): HtmlDocument;
}
