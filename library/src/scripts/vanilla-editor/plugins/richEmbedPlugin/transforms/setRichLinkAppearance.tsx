/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IBaseEmbedData } from "@library/embeddedContent/embedService";
import { queryRichLink } from "@library/vanilla-editor/plugins/richEmbedPlugin/queries/queryRichLink";
import {
    ELEMENT_RICH_EMBED_CARD,
    ELEMENT_RICH_EMBED_INLINE,
    RichLinkAppearance,
    IRichEmbedElement,
} from "@library/vanilla-editor/plugins/richEmbedPlugin/types";
import { IVanillaLinkElement, MyEditor } from "@library/vanilla-editor/typescript";
import { ELEMENT_LINK, findNode, focusEditor, insertNodes, removeNodes, setSelection } from "@udecode/plate-headless";
import { Location } from "slate";

/**
 * Change the appearance of a rich link.
 *
 * @param editor The editor instance.
 * @param newAppearance The new appearance.
 * @param at The location of the existing rich link.
 * @returns
 */
export function setRichLinkAppearance(editor: MyEditor, newAppearance: RichLinkAppearance, at?: Location) {
    at = at ?? editor.selection!;
    if (!at) {
        // We don't even know what we're working with.
        return;
    }

    const currentEmbed = queryRichLink(editor, at);
    if (!currentEmbed) {
        // Can't do anything.
        return;
    }

    if (currentEmbed.appearance === newAppearance) {
        // Nothing to do.
        return;
    }

    if (newAppearance === RichLinkAppearance.CARD) {
        removeNodes(editor, {
            at: currentEmbed.path,
        });
        // Make sure we give it text content.
        insertNodes<IRichEmbedElement>(editor, [
            {
                type: ELEMENT_RICH_EMBED_CARD,
                dataSourceType: "url",
                url: currentEmbed.url,
                embedData: currentEmbed.embedData,
                children: [{ text: "" }],
            },
        ]);

        return;
    }

    if (newAppearance === RichLinkAppearance.LINK) {
        removeNodes(editor, {
            at: currentEmbed.path,
        });
        // Make sure we give it text content.
        insertNodes(
            editor,
            [
                {
                    forceBasicLink: true, // so we don't auto rich link it again.
                    type: ELEMENT_LINK,
                    url: currentEmbed.url,
                    embedData: currentEmbed.embedData,
                    children: [{ text: currentEmbed.url }],
                } as IVanillaLinkElement,
            ],
            { select: true },
        );

        // Adjust the selection slightly so that we place the selection in the middle of the link.
        const newLinkEntry = findNode(editor, {
            at: currentEmbed.path,
            match: {
                type: ELEMENT_LINK,
            },
        });
        if (newLinkEntry) {
            const [node, path] = newLinkEntry;
            // move our selection into the middle of it.
            const newPointEntry = {
                path: [...path, 0],
                offset: Math.floor(currentEmbed.url.length / 2),
            };
            setSelection(editor, {
                anchor: newPointEntry,
                focus: newPointEntry,
            });
        }

        return;
    }

    if (newAppearance === RichLinkAppearance.INLINE) {
        // Make sure we give it text content.
        removeNodes(editor, {
            at: currentEmbed.path,
        });
        insertNodes(editor, [
            {
                type: ELEMENT_RICH_EMBED_INLINE,
                dataSourceType: "url",
                url: currentEmbed.url,
                embedData: currentEmbed.embedData,
                children: [{ text: "" }],
            },
        ]);

        return;
    }
}
