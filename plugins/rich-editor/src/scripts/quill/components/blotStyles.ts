/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { atMentionCSS } from "@rich-editor/quill/components/atMentionStyles";
import { accessibilityCSS } from "@rich-editor/quill/components/accessibilityStyles";
import { blockQuoteCSS } from "@rich-editor/quill/components/blockQuoteStyles";
import { emojiCSS } from "@rich-editor/quill/components/emojiStyles";
import { spoilerCSS } from "@rich-editor/quill/components/spoilerStyles";
import { codeBlockCSS } from "@rich-editor/quill/components/codeBlockStyles";

export const blotCSS = () => {
    accessibilityCSS();
    atMentionCSS();
    blockQuoteCSS();
    emojiCSS();
    spoilerCSS();
    codeBlockCSS();
};
