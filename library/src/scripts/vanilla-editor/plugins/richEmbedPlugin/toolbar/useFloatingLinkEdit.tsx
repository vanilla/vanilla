/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { queryRichLink } from "@library/vanilla-editor/plugins/richEmbedPlugin/queries/queryRichLink";
import { triggerFloatingLinkEdit } from "@library/vanilla-editor/plugins/richEmbedPlugin/toolbar/triggerFloatingLinkEdit";
import { useFloatingLinkEscape } from "@library/vanilla-editor/plugins/richEmbedPlugin/toolbar/useFloatingLinkEscape";
import { useVirtualFloatingLink } from "@library/vanilla-editor/plugins/richEmbedPlugin/toolbar/useVirtualFloatingLink";
import {
    ELEMENT_LINK_AS_BUTTON,
    ELEMENT_RICH_EMBED_CARD,
    ELEMENT_RICH_EMBED_INLINE,
} from "@library/vanilla-editor/plugins/richEmbedPlugin/types";
import { MyEditor, MyValue } from "@library/vanilla-editor/typescript";
import { useMyEditorRef } from "@library/vanilla-editor/getMyEditor";
import {
    getEndPoint,
    getPluginOptions,
    getStartPoint,
    isCollapsed,
    mergeProps,
    toDOMNode,
    useHotkeys,
    usePlateSelectors,
} from "@udecode/plate-common";
import {
    UseVirtualFloatingOptions,
    UseVirtualFloatingReturn,
    getDefaultBoundingClientRect,
    getRangeBoundingClientRect,
} from "@udecode/plate-floating";
import {
    ELEMENT_LINK,
    LinkPlugin,
    floatingLinkActions,
    floatingLinkSelectors,
    useFloatingLinkSelectors,
} from "@udecode/plate-link";
import { useCallback, useEffect } from "react";

export const useFloatingLinkEdit = ({
    floatingOptions,
}: {
    floatingOptions?: UseVirtualFloatingOptions;
} = {}): UseVirtualFloatingReturn & {
    open: boolean;
} => {
    const editor = useMyEditorRef();
    const keyEditor = usePlateSelectors().keyEditor();
    const mode = useFloatingLinkSelectors().mode();
    const open = useFloatingLinkSelectors().isOpen(editor.id);

    const { triggerFloatingLinkHotkeys } = getPluginOptions<LinkPlugin, MyValue, MyEditor>(editor, ELEMENT_LINK);

    const getBoundingClientRect = useCallback(() => {
        const entry = queryRichLink(editor);

        if (entry) {
            const { element, path } = entry;

            switch (element.type) {
                case ELEMENT_LINK:
                    // Get the rect of the link. Keep in mind our cursor is just "on the link", not actually selecting it.
                    return getRangeBoundingClientRect(editor, {
                        anchor: getStartPoint(editor, path),
                        focus: getEndPoint(editor, path),
                    });
                case ELEMENT_RICH_EMBED_INLINE:
                case ELEMENT_RICH_EMBED_CARD:
                case ELEMENT_LINK_AS_BUTTON:
                    // The embeds are "void" elements and as such don't don't get measured well by a range.
                    // The range ends up measuring the "hidden" character at the end of the element.
                    // Instead we grab the dom node and measure that directly.
                    const embedNode = toDOMNode(editor, element);
                    if (embedNode) {
                        return embedNode.getBoundingClientRect();
                    }
                    break;
            }
        }

        return getDefaultBoundingClientRect();
    }, [editor]);

    const isOpen = open && mode === "edit";

    const floatingResult = useVirtualFloatingLink({
        ...mergeProps(
            {
                open: isOpen,
            },
            floatingOptions,
        ),
        getBoundingClientRect,
        editorId: editor.id,
    });

    const { update } = floatingResult;

    useEffect(() => {
        const foundNode = queryRichLink(editor);
        if (
            editor.selection &&
            isCollapsed(editor.selection) && //only show edit toolbar if cursor is within a link
            !!foundNode &&
            (foundNode.element.type === ELEMENT_LINK ||
                foundNode.element.type === ELEMENT_LINK_AS_BUTTON ||
                foundNode.element.dataSourceType === "url") // Only show for link embeds (not images or files)
        ) {
            floatingLinkActions.show("edit", editor.id);
            update();
            return;
        }

        if (floatingLinkSelectors.mode() === "edit") {
            floatingLinkActions.hide();
        }
    }, [editor, editor.selection, keyEditor, update]);

    useHotkeys(
        triggerFloatingLinkHotkeys!,
        (e) => {
            if (floatingLinkSelectors.mode() === "edit" && triggerFloatingLinkEdit(editor)) {
                e.preventDefault();
            }
        },
        {
            enableOnContentEditable: true,
        },
        [],
    );

    // instead of using Plate's useFloatingLinkEnterHook, our editor uses an actual form (with a hidden submit button) that responds to Enter key.
    // useFloatingLinkEnter();

    useFloatingLinkEscape();

    return {
        ...floatingResult,
        open: isOpen,
    };
};
