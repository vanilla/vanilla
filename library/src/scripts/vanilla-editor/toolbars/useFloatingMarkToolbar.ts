/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useVirtualFloating, UseVirtualFloatingOptions, UseVirtualFloatingReturn } from "@udecode/plate-floating";
import {
    collapseSelection,
    focusEditor,
    getSelectionText,
    isRangeAcrossBlocks,
    isSelectionExpanded,
    mergeProps,
    useHotkeys,
} from "@udecode/plate-headless";
import { useEffect, useState } from "react";
import { useVanillaEditorFocus } from "@library/vanilla-editor/VanillaEditorFocusContext";
import { useEventPlateId, usePlateEditorState } from "@udecode/plate-headless";
import { useFocused } from "slate-react";

export const useFloatingMarkToolbar = ({
    floatingOptions,
}: {
    floatingOptions?: UseVirtualFloatingOptions;
} = {}): UseVirtualFloatingReturn & {
    open: boolean;
} => {
    const editor = usePlateEditorState(useEventPlateId());

    const isSlateFocused = useFocused();
    const _isEditorFocused = useVanillaEditorFocus().isFocusWithinEditor;
    // Workaround for a bug in the focus watcher, that can be replicated as follows:
    // - Make a text selection with the mouse but end the selection with the cursor outside of the editor.
    // - When the cursor drops outside of the editor our focus watcher doesn't see that the focus remained on the contenteditable.
    // Fixing this would be quite complicated so for now are just or-ing this with slate's editor focus, which can't be easily hoisted, because our context lives outside of the slate context.
    const isEditorFocused = _isEditorFocused || isSlateFocused;

    const [waitForCollapsedSelection, setWaitForCollapsedSelection] = useState(false);

    const [open, setOpen] = useState(false);

    const selectionText = getSelectionText(editor);
    const selectionTextLength = selectionText?.length ?? 0;
    const selectionExpanded = isSelectionExpanded(editor);

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

    useEffect(() => {
        if (
            !selectionExpanded ||
            !selectionText ||
            isRangeAcrossBlocks(editor) || //Do not open if the selection spans multiple blocks
            waitForCollapsedSelection
        ) {
            setOpen(false);
        } else if (isEditorFocused && selectionExpanded && selectionTextLength > 0) {
            update();
            setOpen(true);
        }
    }, [
        editor.id,
        editor.selection,
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
