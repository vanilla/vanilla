/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { queryRichLink } from "@library/vanilla-editor/plugins/richEmbedPlugin/queries/queryRichLink";
import { RichLinkAppearance } from "@library/vanilla-editor/plugins/richEmbedPlugin/types";
import { MyEditor } from "@library/vanilla-editor/typescript";
import { insertText, removeNodes, unwrapLink } from "@udecode/plate-headless";
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

    if (currentEmbed.appearance === RichLinkAppearance.LINK) {
        unwrapLink(editor);
        return;
    }

    removeNodes(editor, {
        at: currentEmbed.path,
    });

    if (!currentEmbed.url) {
        // Some embeds (like an uploading file), may not have a url yet.
        return;
    }

    const newTextContents = currentEmbed.text;

    // Make sure we give it text content.
    insertText(editor, newTextContents);
}
