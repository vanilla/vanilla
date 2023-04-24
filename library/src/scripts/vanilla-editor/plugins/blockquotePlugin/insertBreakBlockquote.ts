/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { getTypeByPath } from "@library/vanilla-editor/utils/getTypeByPath";
import {
    ELEMENT_DEFAULT,
    exitBreak,
    getBlockAbove,
    getPluginType,
    isBlockAboveEmpty,
    PlateEditor,
    removeNodes,
    Value,
    getNextNode,
    ELEMENT_BLOCKQUOTE,
} from "@udecode/plate-headless";

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
