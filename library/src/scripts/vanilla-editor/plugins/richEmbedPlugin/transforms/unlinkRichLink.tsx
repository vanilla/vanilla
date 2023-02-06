/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { queryRichLink } from "@library/vanilla-editor/plugins/richEmbedPlugin/queries/queryRichLink";
import { RichLinkAppearance } from "@library/vanilla-editor/plugins/richEmbedPlugin/types";
import { MyEditor } from "@library/vanilla-editor/typescript";
import { getNodeString, insertText, removeNodes } from "@udecode/plate-headless";
import { Location } from "slate";

/**
 * Convert a link or rich link into plaintext.
 */
export function unlinkRichLink(editor: MyEditor, at?: Location) {
    const currentEmbed = queryRichLink(editor, at);
    if (!currentEmbed) {
        // Can't do anything.
        return;
    }

    removeNodes(editor, {
        at: currentEmbed.path,
    });

    let newTextContents = currentEmbed.url;
    if (!currentEmbed.url) {
        // Some embeds (like an uploading file), may not have a url yet.
        return;
    }

    // Make sure we give it text content.
    insertText(editor, newTextContents || currentEmbed.url);
}
