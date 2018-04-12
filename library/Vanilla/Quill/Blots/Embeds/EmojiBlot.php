<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill\Blots\Embeds;

class EmojiBlot extends AbstractInlineEmbedBlot {

    /**
     * @inheritDoc
     */
    protected static function getContainerHTMLTag(): string {
        return "span";
    }

    /**
     * @inheritDoc
     */
    protected function getContainerHMTLAttributes(): array {
        return [
            "class" => "safeEmoji nativeEmoji"
        ];
    }

    /**
     * @inheritDoc
     */
    protected static function getInsertKey(): string {
        return "insert.emoji.emojiChar";
    }
}
