<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting;

use Vanilla\Contracts\Formatting\FormatInterface;
use Vanilla\Formatting\Html\HtmlDocument;
use Vanilla\Formatting\Html\Processor\HtmlProcessor;

/**
 * Base format with simple simple implementations.
 */
abstract class BaseFormat implements FormatInterface {

    /** @var int */
    const EXCERPT_MAX_LENGTH = 325;

    /** @var bool */
    protected $allowExtendedContent = false;

    /** @var HtmlProcessor[] */
    protected $staticProcessors = [];

    /** @var HtmlProcessor[] */
    protected $dynamicProcessors = [];

    /**
     * Apply an HTML processor to the stack of processors.
     *
     * @param HtmlProcessor $processor
     *
     * @return $this For chaining.
     */
    public function addHtmlProcessor(HtmlProcessor $processor): BaseFormat {
        if ($processor->getProcessorType() === HtmlProcessor::TYPE_DYNAMIC) {
            $this->dynamicProcessors[] = $processor;
        } else {
            $this->staticProcessors[] = $processor;
        }

        return $this;
    }

    /**
     * Apply the registered HTML processors.
     *
     * @param string $html The HTML to apply processors to.
     * @param string|null $processorType The type of HTML processors to apply. See HtmlProcessor::TYPE constants.
     * @return string The processed HTML.
     */
    public function applyHtmlProcessors(string $html, ?string $processorType = null): string {
        $document = new HtmlDocument($html);

        if ($processorType === HtmlProcessor::TYPE_STATIC || $processorType === null) {
            foreach ($this->staticProcessors as $processor) {
                $document = $processor->processDocument($document);
            }
        }

        if ($processorType === HtmlProcessor::TYPE_DYNAMIC || $processorType === null) {
            foreach ($this->dynamicProcessors as $processor) {
                $document = $processor->processDocument($document);
            }
        }

        return $document->getInnerHtml();
    }

    /**
     * Implement rendering of excerpts based on the plain-text version of format.
     *
     * @inheritdoc
     */
    public function renderExcerpt(string $content): string {
        $plainText = $this->renderPlainText($content);

        $excerpt = mb_ereg_replace("\n", ' ', $plainText);
        $excerpt = mb_ereg_replace("\s{2,}", ' ', $excerpt);
        if (mb_strlen($excerpt) > self::EXCERPT_MAX_LENGTH) {
            $excerpt = mb_substr($excerpt, 0, self::EXCERPT_MAX_LENGTH);
            if ($lastSpace = mb_strrpos($excerpt, ' ')) {
                $excerpt = mb_substr($excerpt, 0, $lastSpace);
            }
            $excerpt .= '…';
        }
        return $excerpt;
    }

    /**
     * @inheritdoc
     */
    public function renderQuote(string $content): string {
        return $this->renderHTML($content);
    }

    /**
     * @inheritdoc
     */
    public function getPlainTextLength(string $content): int {
        return mb_strlen($this->renderPlainText($content), 'UTF-8');
    }

    /**
     * Set the status for extended content.
     *
     * @param bool $extendContent
     */
    public function setAllowExtendedContent(bool $extendContent): void {
        $this->allowExtendedContent = $extendContent;
    }
}
