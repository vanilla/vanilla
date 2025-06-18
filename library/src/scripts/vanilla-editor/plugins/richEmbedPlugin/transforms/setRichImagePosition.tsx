/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { MyEditor } from "@library/vanilla-editor/typescript";
import { Location, Element } from "slate";
import { findNode, getPreviousSiblingNode, insertNodes, removeNodes, getParentNode } from "@udecode/plate-common";
import { ELEMENT_TH, ELEMENT_TD } from "@udecode/plate-table";
import { ELEMENT_LI } from "@udecode/plate-list";
import { ELEMENT_CODE_BLOCK } from "@udecode/plate-code-block";
import {
    ELEMENT_RICH_EMBED_CARD,
    ELEMENT_RICH_EMBED_INLINE,
} from "@library/vanilla-editor/plugins/richEmbedPlugin/types";
import { ELEMENT_BLOCKQUOTE_ITEM } from "@library/vanilla-editor/plugins/blockquotePlugin/createBlockquotePlugin";
import { ELEMENT_SPOILER_ITEM } from "@library/vanilla-editor/plugins/spoilerPlugin/createSpoilerPlugin";

/**
 *
 * @param editor The editor instance
 * @param displaySize One of "large" | "medium" | "small" | "inline"
 * @param at The path to the image in the rich editor
 */
export function setRichImagePosition(editor: MyEditor, displaySize?: string, at?: Location) {
    at = at ?? editor.selection!;

    const currentEmbed = findNode(editor, {
        at,
        match: {
            // Normal images are cards, inline images are inline
            // We should look for both to allow converting between inline and other sizes
            type: [ELEMENT_RICH_EMBED_CARD, ELEMENT_RICH_EMBED_INLINE],
        },
    });

    if (!currentEmbed) {
        return;
    }

    // currentEmbed is an array with structure [element, path]
    const currentEmbedPath = currentEmbed[1];

    const isTopLevelNode = currentEmbedPath.length === 1;

    // An image has been added as a top-level node, and the display size changed to inline
    // We need to move it so it's nested inside of the previous node, and change the type
    if (isTopLevelNode && displaySize === "inline") {
        const previousSiblingArray = getPreviousSiblingNode(editor, currentEmbedPath);

        const previousSibling = previousSiblingArray && previousSiblingArray[0];
        // Our target path for moving the image, fall back to the start of the editor if not found
        // (that covers the case of inserting an image as the first thing in an empty editor)
        const previousSiblingPath = (previousSiblingArray && previousSiblingArray[1]) ?? [0];

        editor.withoutNormalizing(() => {
            // Remove the current embed, so we can nest it inline with other content
            removeNodes(editor, {
                at: currentEmbedPath,
            });

            // Also remove the previous node, so we can replace it with the updated version
            removeNodes(editor, {
                at: previousSiblingPath as number[],
            });

            const type = Element.isElement(previousSibling) ? previousSibling.type : "p";
            const children = Element.isElement(previousSibling) ? previousSibling.children : [];

            insertNodes(
                editor,
                [
                    {
                        type: type as string,
                        children: [
                            ...children,
                            {
                                ...currentEmbed[0],
                                // Change the type to allow nesting inline with other content
                                type: ELEMENT_RICH_EMBED_INLINE,
                            },
                            // Add a space, otherwise it's hard to add text after the inline image
                            {
                                text: " ",
                            },
                        ],
                    },
                ],
                {
                    at: previousSiblingPath as number[],
                },
            );
        });
    }

    // An image has been converted from inline back to a normal size
    // We need to bring it back up to the top level, and change the type
    if (!isTopLevelNode && displaySize !== "inline") {
        const parentNode = getParentNode(editor, currentEmbedPath);

        const allowedParents = [
            ELEMENT_BLOCKQUOTE_ITEM,
            ELEMENT_CODE_BLOCK,
            ELEMENT_SPOILER_ITEM,
            ELEMENT_TH,
            ELEMENT_TD,
            ELEMENT_LI,
        ];

        if (
            parentNode &&
            parentNode[0] &&
            parentNode[0]?.type &&
            allowedParents.includes(parentNode[0].type as string)
        ) {
            // We want to allow images of any size in these cases
            return;
        }

        editor.withoutNormalizing(() => {
            removeNodes(editor, {
                at: currentEmbedPath,
            });

            insertNodes(editor, [
                {
                    ...currentEmbed[0],
                    type: ELEMENT_RICH_EMBED_CARD,
                },
            ]);
        });
    }

    return;
}
