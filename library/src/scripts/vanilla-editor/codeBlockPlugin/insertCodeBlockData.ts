/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ELEMENT_CODE_BLOCK, ELEMENT_CODE_LINE } from "@udecode/plate-code-block";
import { getNode, insertFragment } from "@udecode/plate-common";
import { MyEditor, MyElement } from "../typescript";

export function insertCodeBlockData(editor: MyEditor, data: DataTransfer) {
    const { insertData, point } = editor;
    const text = data.getData("text/plain");
    const path = editor.selection ? [editor.selection.anchor.path[0]] : [0];
    const rootNode = getNode(editor, path) as MyElement;

    if (rootNode.type === ELEMENT_CODE_BLOCK && text) {
        // convert each line into a code line
        const nodes = text.split("\n").map((line) => ({
            type: ELEMENT_CODE_LINE,
            children: [{ text: line }],
        }));
        // insert the converted nodes at the current location
        insertFragment(editor, nodes, { at: point(path) });
    } else {
        insertData(data);
    }
}
