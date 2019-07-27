<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Formats;

use Vanilla\Formatting\BaseFormat;

/**
 * Class for rendering content of the markdown format.
 */
class TextFormat extends BaseFormat {

    const FORMAT_KEY = "Text";

    /**
     * @inheritdoc
     */
    public function renderHTML(string $content): string {
        $result = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
        $result = preg_replace('`<br\s?/?>`', "\n", $result);
        $result = htmlspecialchars($result, ENT_NOQUOTES, 'UTF-8', false);

        if (c('Garden.Format.ReplaceNewlines', true)) {
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
        return [];
    }
}
