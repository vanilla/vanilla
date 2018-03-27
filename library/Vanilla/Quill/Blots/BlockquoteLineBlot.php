<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill\Blots;

class BlockquoteLineBlot extends AbstractLineBlot {

    /**
     * @inheritDoc
     */
    protected static function getLineType(): string {
        return "blockquote";
    }

    /**
     * @inheritDoc
     */
    public function getGroupOpeningTag(): string {
        $wrapperClass = static::getLineType();
        $contentClass = static::getLineType() . "-content";

        return "<div class=\"$wrapperClass\"><div class=\"$contentClass\">";
    }

    /**
     * @inheritDoc
     */
    public function getGroupClosingTag(): string {
        return "</div></div>";
    }
}
