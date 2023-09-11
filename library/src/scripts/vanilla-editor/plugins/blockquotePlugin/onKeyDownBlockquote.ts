/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import isHotkey from "is-hotkey";
import { Value, PlateEditor, WithPlatePlugin, HotkeyPlugin, KeyboardHandlerReturnType } from "@udecode/plate-headless";
import { VanillaEditorFormatter } from "@library/vanilla-editor/VanillaEditorFormatter";
import castArray from "lodash/castArray";
import { MyEditor, MyValue } from "@library/vanilla-editor/typescript";
import { unwrapBlockquote } from "@library/vanilla-editor/plugins/blockquotePlugin/unwrapBlockquote";

export const onKeyDownBlockquote =
    <V extends Value = Value, E extends PlateEditor<V> = PlateEditor<V>>(
        editor: E,
        { type, options: { hotkey } }: WithPlatePlugin<HotkeyPlugin, V, E>,
    ): KeyboardHandlerReturnType =>
    (e) => {
        if (!hotkey) return;
        const formatter = new VanillaEditorFormatter(editor as MyEditor);
        const hotkeys = castArray(hotkey);

        for (const _hotkey of hotkeys) {
            if (isHotkey(_hotkey, e as any)) {
                e.preventDefault();
                if (formatter.isBlockquote()) {
                    unwrapBlockquote(editor);
                } else {
                    formatter.blockquote();
                }
                return;
            }
        }
    };
