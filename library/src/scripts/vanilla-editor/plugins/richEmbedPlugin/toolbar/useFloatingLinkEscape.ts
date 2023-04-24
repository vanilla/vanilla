/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { focusEditor, useEditorRef, useHotkeys } from "@udecode/plate-core";
import { floatingLinkActions, floatingLinkSelectors, useFloatingLinkSelectors } from "@udecode/plate-headless";

export const useFloatingLinkEscape = () => {
    const editor = useEditorRef();

    const open = useFloatingLinkSelectors().isOpen(editor.id);

    useHotkeys(
        "escape",
        (e) => {
            if (!floatingLinkSelectors.mode()) return;

            e.preventDefault();

            if (floatingLinkSelectors.mode() === "edit" && floatingLinkSelectors.isEditing()) {
                floatingLinkActions.show("edit", editor.id);
                focusEditor(editor, editor.selection!);
                return;
            }

            focusEditor(editor, editor.selection!);
            setTimeout(() => {
                floatingLinkActions.hide();
            }, 1);
        },
        {
            enabled: open,
            enableOnTags: ["INPUT"],
            enableOnContentEditable: true,
        },
        [],
    );
};
