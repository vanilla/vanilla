/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { getTypeByPath } from "@library/vanilla-editor/utils/getTypeByPath";
import { ELEMENT_BLOCKQUOTE } from "@udecode/plate-block-quote";
import { exitBreak } from "@udecode/plate-break";
import {
    ELEMENT_DEFAULT,
    PlateEditor,
    Value,
    getBlockAbove,
    getNextNode,
    getPluginType,
    isBlockAboveEmpty,
    removeNodes,
} from "@udecode/plate-common";

export const insertBreakBlockquote = <V extends Value>(editor: PlateEditor<V>) => {
    if (!editor.selection) return;
    if (isBlockAboveEmpty(editor)) {
        const [, path] = getBlockAbove(editor) ?? [];
        const nextNode = getNextNode(editor);

        if (path && nextNode && path[0] !== nextNode[1][0]) {
            // If the current fragment types is a block quote, we should remove the trailing new line before escape
            if (getTypeByPath(editor) === ELEMENT_BLOCKQUOTE) {
                removeNodes(editor, { at: path });
            }

            const isExit = exitBreak(editor, {
                defaultType: getPluginType(editor, ELEMENT_DEFAULT),
            });
            if (isExit) {
                return true;
            }
        }
    }
};
