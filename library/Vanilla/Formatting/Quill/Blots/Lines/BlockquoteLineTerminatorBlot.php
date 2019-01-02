<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Quill\Blots\Lines;

use Vanilla\Formatting\Quill\Parser;

/**
 * Blot for handling blockquote line terminators.
 */
class BlockquoteLineTerminatorBlot extends AbstractLineTerminatorBlot {

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
