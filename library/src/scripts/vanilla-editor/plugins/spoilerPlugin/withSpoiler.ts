/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ELEMENT_SPOILER } from "@library/vanilla-editor/plugins/spoilerPlugin/createSpoilerPlugin";
import { insertBreakSpoiler } from "@library/vanilla-editor/plugins/spoilerPlugin/insertBreakSpoiler";
import { normalizeSpoiler } from "@library/vanilla-editor/plugins/spoilerPlugin/normalizeSpoiler";
import { getTypeByPath } from "@library/vanilla-editor/utils/getTypeByPath";
import { PlateEditor, Value } from "@udecode/plate-headless";

export const withSpoiler = <V extends Value = Value, E extends PlateEditor<V> = PlateEditor<V>>(editor: E) => {
    const { insertBreak } = editor;

    editor.insertBreak = () => {
        if (getTypeByPath(editor) === ELEMENT_SPOILER && insertBreakSpoiler(editor)) {
            return;
        }

        insertBreak();
    };

    editor.normalizeNode = normalizeSpoiler(editor);

    return editor;
};
