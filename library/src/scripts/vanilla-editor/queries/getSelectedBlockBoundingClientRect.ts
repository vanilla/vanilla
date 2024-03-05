/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { TReactEditor, getAboveNode, getNextNodeStartPoint, getNode, toDOMNode } from "@udecode/plate-common";
import { ELEMENT_LIC, ELEMENT_OL, ELEMENT_UL } from "@udecode/plate-list";

export function getSelectedBlockBoundingClientRect(editor?: TReactEditor): DOMRect | null {
    if (!editor?.selection) {
        return null;
    }

    const selectedBlockEntry = getAboveNode(editor, {
        at: editor.selection,
        block: true,
        // Make sure we select list lines instead of list content or groups.
        match: (elem) => elem.type !== ELEMENT_UL && elem.type !== ELEMENT_OL && elem.type !== ELEMENT_LIC,
    });

    if (!selectedBlockEntry) {
        const tmpNode = getNode(editor, editor.selection.focus.path);
        if (tmpNode) {
            const selectedNode = toDOMNode(editor, tmpNode);
            return selectedNode?.getBoundingClientRect() ?? null;
        }
        return null;
    }

    const [selectedBlockNode] = selectedBlockEntry;

    const selectedDomNode = toDOMNode(editor, selectedBlockNode);
    const selectedDomRect = selectedDomNode?.getBoundingClientRect() ?? null;
    return selectedDomRect;
}
