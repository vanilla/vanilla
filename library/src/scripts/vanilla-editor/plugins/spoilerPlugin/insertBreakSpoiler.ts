/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ELEMENT_SPOILER } from "@library/vanilla-editor/plugins/spoilerPlugin/createSpoilerPlugin";
import { getTypeByPath } from "@library/vanilla-editor/utils/getTypeByPath";
import {
    ELEMENT_DEFAULT,
    exitBreak,
    getBlockAbove,
    getNextNode,
    getPluginType,
    isBlockAboveEmpty,
    PlateEditor,
    removeNodes,
    Value,
} from "@udecode/plate-headless";

export const insertBreakSpoiler = <V extends Value>(editor: PlateEditor<V>) => {
    if (!editor.selection) return;

    if (isBlockAboveEmpty(editor)) {
        const [, path] = getBlockAbove(editor) ?? [];
        const nextNode = getNextNode(editor);

        if (path && nextNode && path[0] !== nextNode[1][0]) {
            // If the current fragment types is a spoiler, we should remove the trailing new line before escape
            if (getTypeByPath(editor) === ELEMENT_SPOILER) {
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
