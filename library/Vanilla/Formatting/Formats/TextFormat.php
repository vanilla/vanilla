<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Formats;

use Vanilla\Formatting\BaseFormat;
use Vanilla\Formatting\FormatConfig;

/**
 * Class for rendering content of the markdown format.
 */
class TextFormat extends BaseFormat {

    const FORMAT_KEY = "text";

    /** @var FormatConfig */
    private $formatConfig;

    /**
     * @param FormatConfig $formatConfig
     */
    public function __construct(FormatConfig $formatConfig) {
        $this->formatConfig = $formatConfig;
    }


    /**
     * @inheritdoc
     */
    public function renderHTML(string $content): string {
        $result = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
        $result = preg_replace('`<br\s?/?>`', "\n", $result);
        $result = htmlspecialchars($result, ENT_NOQUOTES, 'UTF-8', false);

        if ($this->formatConfig->shouldReplaceNewLines()) {
            $result = nl2br(trim($result));
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function renderPlainText(string $content): string {
        return trim($content);
    }

    /**
     * @inheritdoc
     */
    public function filter(string $content): string {
        return $content;
    }

    /**
     * @inheritdoc
     */
    public function parseAttachments(string $content): array {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function parseHeadings(string $content): array {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function parseMentions(string $content): array {
        // Legacy Mention Fetcher.
        // This should get replaced in a future refactoring.
        return getMentions($content);
    }
}
