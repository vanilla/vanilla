<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Formatting\Quill\Blots\Lines;

use Vanilla\Formatting\Quill\Parser;

/**
 * Class for handling blockquote operations.
 */
class BlockquoteLineBlot extends AbstractLineBlot {

    /**
     * @inheritDoc
     */
    public static function matches(array $operation): bool {
        return static::opAttrsContainKeyWithValue($operation, "blockquote-line");
    }

    /**
     * @inheritDoc
     */
    public function getGroupOpeningTag(): string {
        $wrapperClass = "blockquote";
        $contentClass = "blockquote-content";

        return $this->parseMode === Parser::PARSE_MODE_QUOTE ? "" : "<div class=\"$wrapperClass\"><div class=\"$contentClass\">";
    }

    /**
     * @inheritDoc
     */
    public function getGroupClosingTag(): string {
        return $this->parseMode === Parser::PARSE_MODE_QUOTE ? "" : "</div></div>";
    }

    /**
     * @inheritdoc
     */
    public function renderLineStart(): string {
        return $this->parseMode === Parser::PARSE_MODE_QUOTE ? "<p>" : '<p class="blockquote-line">';
    }

    /**
     * @inheritdoc
     */
    public function renderLineEnd(): string {
        return '</p>';
    }
}
