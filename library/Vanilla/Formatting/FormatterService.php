<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting;

use Vanilla\Formatting\Exception\FormatterNotFoundException;

/**
 * Simple service for calling out to formatters registered in FormatFactory.
 */
class FormatterService {

    /** @var FormatFactory */
    private $formatFactory;

    /**
     * Setup the FormatterService instance.
     *
     * @param FormatFactory $formatFactory
     */
    public function __construct(FormatFactory $formatFactory) {
        $this->formatFactory = $formatFactory;
    }

    /**
     * Parse attachment data from a message.
     *
     * @param string $content
     * @param string $format
     * @return Vanilla\Formatting\Attachment[]
     */
    public function parseAttachments(string $content, string $format): array {
        try {
            $formatter = $this->formatFactory->getFormatter($format);
        } catch (FormatterNotFoundException $e) {
            return [];
        }

        $result = $formatter->parseAttachments($content);
        return $result;
    }
}
