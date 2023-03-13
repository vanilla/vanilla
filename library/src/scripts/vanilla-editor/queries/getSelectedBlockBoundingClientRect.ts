/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { getAboveNode, TReactEditor } from "@udecode/plate-core";
import { ELEMENT_LIC, ELEMENT_OL, ELEMENT_UL, toDOMNode } from "@udecode/plate-headless";

export function getSelectedBlockBoundingClientRect(editor: TReactEditor): DOMRect | null {
    if (!editor.selection) {
        return null;
    }

    const selectedBlockEntry = getAboveNode(editor, {
        at: editor.selection,
        block: true,
        // Make sure we select list lines instead of list content or groups.
        match: (elem) => elem.type !== ELEMENT_UL && elem.type !== ELEMENT_OL && elem.type !== ELEMENT_LIC,
    });
    if (!selectedBlockEntry) {
        return null;
    }

    const [selectedBlockNode] = selectedBlockEntry;

    const selectedDomNode = toDOMNode(editor, selectedBlockNode);
    const selectedDomRect = selectedDomNode?.getBoundingClientRect() ?? null;
    return selectedDomRect;
}
