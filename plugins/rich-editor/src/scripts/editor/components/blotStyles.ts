/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { accessibilityCSS } from "@rich-editor/editor/components/accessibilityStyles";
import { blockQuoteCSS } from "@rich-editor/editor/components/blockQuoteStyles";
import { emojiCSS } from "@rich-editor/editor/components/emojiStyles";
import { spoilerCSS } from "@rich-editor/editor/components/spoilerStyles";
import { codeBlockCSS } from "@rich-editor/editor/components/codeBlockStyles";
import { atMentionCSS } from "@library/editor/pieces/atMentionStyles";

export const blotCSS = () => {
    accessibilityCSS();
    atMentionCSS();
    blockQuoteCSS();
    emojiCSS();
    spoilerCSS();
    codeBlockCSS();
};
