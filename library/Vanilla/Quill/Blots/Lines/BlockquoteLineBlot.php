<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill\Blots\Lines;

class BlockquoteLineBlot extends AbstractLineBlot {

    /**
     * @inheritDoc
     */
    public static function matches(array $operations): bool {
        return static::operationsContainKeyWithValue($operations, "blockquote-line");
    }

    /**
     * @inheritDoc
     */
    public function getGroupOpeningTag(): string {
        $wrapperClass = "blockquote";
        $contentClass = "blockquote-content";

        return "<div class=\"$wrapperClass\"><div class=\"$contentClass\">";
    }

    /**
     * @inheritDoc
     */
    public function getGroupClosingTag(): string {
        return "</div></div>";
    }

    public function renderLineStart(): string {
        return '<p class="blockquote-line">';
    }

    public function renderLineEnd(): string {
        return '</p>';
    }
}
