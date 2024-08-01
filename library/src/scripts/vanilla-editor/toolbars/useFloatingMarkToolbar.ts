/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useVanillaEditorFocus } from "@library/vanilla-editor/VanillaEditorFocusContext";
import {
    collapseSelection,
    focusEditor,
    getSelectionText,
    isSelectionExpanded,
    mergeProps,
    PlateEditor,
    useEventPlateId,
    useHotkeys,
    usePlateEditorState,
    Value,
} from "@udecode/plate-common";
import { useVirtualFloating, UseVirtualFloatingOptions, UseVirtualFloatingReturn } from "@udecode/plate-floating";
import { useEffect, useState } from "react";
import { useFocused } from "slate-react";

export const useFloatingMarkToolbar = ({
    floatingOptions,
}: {
    floatingOptions?: UseVirtualFloatingOptions;
} = {}): UseVirtualFloatingReturn & {
    open: boolean;
} => {
    const editor: PlateEditor<Value> | null | undefined = usePlateEditorState(useEventPlateId());

    const isSlateFocused = useFocused();
    const _isEditorFocused = useVanillaEditorFocus().isFocusWithinEditor;
    // Workaround for a bug in the focus watcher, that can be replicated as follows:
    // - Make a text selection with the mouse but end the selection with the cursor outside of the editor.
    // - When the cursor drops outside of the editor our focus watcher doesn't see that the focus remained on the contenteditable.
    // Fixing this would be quite complicated so for now are just or-ing this with slate's editor focus, which can't be easily hoisted, because our context lives outside of the slate context.
    const isEditorFocused = _isEditorFocused || isSlateFocused;

    const [waitForCollapsedSelection, setWaitForCollapsedSelection] = useState(false);

    const [open, setOpen] = useState(false);

    const selectionText = editor ? getSelectionText(editor) : "";
    const selectionTextLength = selectionText?.length ?? 0;
    const selectionExpanded = editor ? isSelectionExpanded(editor) : false;

    // // On refocus, the editor keeps the previous selection,
    // // so we need to wait it's collapsed at the new position before displaying the floating toolbar.
    useEffect(() => {
        if (!isEditorFocused) {
            setWaitForCollapsedSelection(true);
        }
        if (isEditorFocused) {
            setWaitForCollapsedSelection(false);
        }

        if (!selectionExpanded) {
            setWaitForCollapsedSelection(false);
        }
    }, [isEditorFocused, selectionExpanded]);

    const floatingResult = useVirtualFloating(
        mergeProps(
            {
                open,
                onOpenChange: setOpen,
            },
            floatingOptions,
        ),
    );

    const { update } = floatingResult;
    const { id, selection } = editor ?? {};

    useEffect(() => {
        if (!selectionExpanded || !selectionText || waitForCollapsedSelection) {
            setOpen(false);
        } else if (isEditorFocused && selectionExpanded && selectionTextLength > 0) {
            update();
            setOpen(true);
        }
    }, [
        id,
        selection,
        selectionExpanded,
        selectionText,
        waitForCollapsedSelection,
        editor,
        isEditorFocused,
        selectionTextLength,
        update,
    ]);

    useHotkeys(
        "escape",
        (e) => {
            collapseSelection(editor, { edge: "end" });
            focusEditor(editor, editor.selection ?? undefined);
        },
        {
            enabled: open,
            enableOnContentEditable: true,
        },
        [open],
    );

    useHotkeys(
        "ctrl+shift+i",
        (e) => {
            setOpen(true);
            const menuItems = document.querySelectorAll(`[role="menuitem"]`);
            if (menuItems.length > 0) {
                const firstItem = menuItems[0] as HTMLElement;
                firstItem.focus();
            }
        },
        {
            enabled: isEditorFocused,
            enableOnContentEditable: true,
        },
        [],
    );

    useEffect(() => {
        if (selectionTextLength > 0) {
            update?.(); //moves the floating content while selection text length changes.
        }
    }, [selectionTextLength, update]);

    return {
        ...floatingResult,
        open,
    };
};
