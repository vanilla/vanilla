/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ELEMENT_CALLOUT } from "@library/vanilla-editor/plugins/calloutPlugin/createCalloutPlugin";
import { getTypeByPath } from "@library/vanilla-editor/utils/getTypeByPath";
import { exitBreak } from "@udecode/plate-break";
import {
    ELEMENT_DEFAULT,
    getBlockAbove,
    getNextNode,
    getPluginType,
    isBlockAboveEmpty,
    PlateEditor,
    removeNodes,
    Value,
} from "@udecode/plate-common";

export const insertBreakCallout = <V extends Value>(editor: PlateEditor<V>) => {
    if (!editor.selection) return;

    if (isBlockAboveEmpty(editor)) {
        const [, path] = getBlockAbove(editor) ?? [];
        const nextNode = getNextNode(editor);

        if (path && nextNode && path[0] !== nextNode[1][0]) {
            // If the current fragment types is a callout, we should remove the trailing new line before escape
            if (getTypeByPath(editor) === ELEMENT_CALLOUT) {
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
