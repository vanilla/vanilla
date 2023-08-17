/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import {
    ELEMENT_PARAGRAPH,
    getCodeLineEntry,
    getEditorString,
    insertEmptyElement,
    isEndPoint,
    PlateEditor,
    removeNodes,
    Value,
} from "@udecode/plate-headless";
import { Path } from "slate";

/**
 * Modify break insertion for code blocks so that inserting multiple breaks at the end exists the code block.
 */
export const withCodeBlockEscape = <V extends Value = Value, E extends PlateEditor<V> = PlateEditor<V>>(editor: E) => {
    const { insertBreak } = editor;

    const insertEscapeCodeBlock = () => {
        if (!editor.selection) {
            return;
        }

        const res = getCodeLineEntry(editor, {});

        if (!res) {
            return;
        }

        const { codeBlock, codeLine } = res;
        const [codeBlockNode, codeBlockPath] = codeBlock;
        const [_, codeLinePath] = codeLine;

        if (codeBlockNode.children.length < 3) {
            return;
        }

        const isCursorAtEndOfCodeBlock = isEndPoint(editor, editor.selection.focus, codeBlockPath);
        if (!isCursorAtEndOfCodeBlock) {
            return;
        }

        const secondLastCodeLinePath = [codeBlockPath[0], codeBlockNode.children.length - 2];
        const secondLastLineText = getEditorString(editor, secondLastCodeLinePath);
        const lastLineText = getEditorString(editor, codeLinePath);
        if (secondLastLineText.length || lastLineText.length) {
            return;
        }

        removeNodes(editor, { at: codeLinePath });
        removeNodes(editor, { at: secondLastCodeLinePath });

        insertEmptyElement(editor, ELEMENT_PARAGRAPH, {
            // TODO find a better way to do this (that would work in tables for example?).
            at: Path.next(codeBlockPath).slice(0, 1),
            select: true,
        });

        return true;
    };

    editor.insertBreak = () => {
        if (insertEscapeCodeBlock()) {
            return;
        }

        insertBreak();
    };

    return editor;
};
