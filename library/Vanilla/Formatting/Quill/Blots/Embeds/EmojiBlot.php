<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Quill\Blots\Embeds;

/**
 * Blot for rendering out safe browser-compatible emoijs.
 */
class EmojiBlot extends AbstractInlineEmbedBlot {

    /**
     * @inheritDoc
     */
    protected static function getInsertKey(): string {
        return "insert.emoji.emojiChar";
    }

    /**
     * @inheritDoc
     */
    protected function getContainerHTMLTag(): string {
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
}
