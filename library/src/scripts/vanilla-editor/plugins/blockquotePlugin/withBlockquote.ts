/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { insertBreakBlockquote } from "@library/vanilla-editor/plugins/blockquotePlugin/insertBreakBlockquote";
import { normalizeBlockquote } from "@library/vanilla-editor/plugins/blockquotePlugin/normalizeBlockquote";
import { PlateEditor, Value } from "@udecode/plate-headless";

export const withBlockquote = <V extends Value = Value, E extends PlateEditor<V> = PlateEditor<V>>(editor: E) => {
    const { insertBreak } = editor;

    editor.insertBreak = () => {
        if (insertBreakBlockquote(editor)) {
            return;
        }

        insertBreak();
    };

    editor.normalizeNode = normalizeBlockquote(editor);

    return editor;
};
