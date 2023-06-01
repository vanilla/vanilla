/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useFloatingLinkEscape } from "@library/vanilla-editor/plugins/richEmbedPlugin/toolbar/useFloatingLinkEscape";
import { useVirtualFloatingLink } from "@library/vanilla-editor/plugins/richEmbedPlugin/toolbar/useVirtualFloatingLink";
import {
    focusEditor,
    getPluginOptions,
    useComposedRef,
    useEditorRef,
    useHotkeys,
    useOnClickOutside,
} from "@udecode/plate-core";
import { getSelectionBoundingClientRect } from "@udecode/plate-floating";
import {
    ELEMENT_LINK,
    floatingLinkActions,
    floatingLinkSelectors,
    LinkPlugin,
    mergeProps,
    triggerFloatingLinkInsert,
    useFloatingLinkSelectors,
    UseVirtualFloatingOptions,
    UseVirtualFloatingReturn,
} from "@udecode/plate-headless";
import { useEffect } from "react";
import { useFocused } from "slate-react";

export const useFloatingLinkInsert = ({
    floatingOptions,
}: { floatingOptions?: UseVirtualFloatingOptions } = {}): UseVirtualFloatingReturn & {
    open: boolean;
} => {
    const editor = useEditorRef();
    const focused = useFocused();
    const mode = useFloatingLinkSelectors().mode();
    const open = useFloatingLinkSelectors().isOpen(editor.id);

    // Trigger the menu on ctrl+k
    useEffect(() => {
        const handler = (e: KeyboardEvent) => {
            if (e.key !== "k" || !(e.metaKey || e.ctrlKey)) {
                return;
            }
            if (
                triggerFloatingLinkInsert(editor, {
                    focused,
                })
            ) {
                e.preventDefault();
            }
        };
        document.addEventListener("keydown", handler);
        return () => {
            document.removeEventListener("keydown", handler);
        };
    }, [focused]);

    const ref = useOnClickOutside(
        () => {
            if (floatingLinkSelectors.mode() === "insert") {
                floatingLinkActions.hide();
                focusEditor(editor, editor.selection!);
            }
        },
        {
            disabled: !open,
        },
    );

    const isOpen = open && mode === "insert";

    const floatingResult = useVirtualFloatingLink({
        ...mergeProps(
            {
                whileElementsMounted: () => {},
            },
            floatingOptions,
        ),
        getBoundingClientRect: getSelectionBoundingClientRect,
        open: isOpen,
        editorId: editor.id,
    });

    const { update, style, floating } = floatingResult;

    // wait for update before focusing input
    useEffect(() => {
        if (open) {
            update();
            floatingLinkActions.updated(true);
        } else {
            floatingLinkActions.updated(false);
        }
    }, [open, update]);

    useFloatingLinkEscape();

    return {
        ...floatingResult,
        floating: useComposedRef<HTMLElement | null>(floating, ref),
        open: isOpen,
    };
};
