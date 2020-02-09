<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting;

use Vanilla\Contracts\Formatting\FormatInterface;

/**
 * Base format with simple simple implementations.
 */
abstract class BaseFormat implements FormatInterface {
    /** @var int */
    const EXCERPT_MAX_LENGTH = 325;

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
}
