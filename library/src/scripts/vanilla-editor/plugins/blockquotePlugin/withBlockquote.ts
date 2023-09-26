/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { insertBreakBlockquote } from "@library/vanilla-editor/plugins/blockquotePlugin/insertBreakBlockquote";
import { normalizeBlockquote } from "@library/vanilla-editor/plugins/blockquotePlugin/normalizeBlockquote";
import { getTypeByPath } from "@library/vanilla-editor/utils/getTypeByPath";
import { ELEMENT_BLOCKQUOTE, PlateEditor, Value } from "@udecode/plate-headless";

export const withBlockquote = <V extends Value = Value, E extends PlateEditor<V> = PlateEditor<V>>(editor: E) => {
    const { insertBreak } = editor;

    editor.insertBreak = () => {
        if (getTypeByPath(editor) === ELEMENT_BLOCKQUOTE && insertBreakBlockquote(editor)) {
            return;
        }

        insertBreak();
    };

    editor.normalizeNode = normalizeBlockquote(editor);

    return editor;
};
