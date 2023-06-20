/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { queryRichLink } from "@library/vanilla-editor/plugins/richEmbedPlugin/queries/queryRichLink";
import { useVirtualFloatingLink } from "@library/vanilla-editor/plugins/richEmbedPlugin/toolbar/useVirtualFloatingLink";
import { useMyEditorRef } from "@library/vanilla-editor/typescript";
import { toDOMNode } from "@udecode/plate-core";
import { getDefaultBoundingClientRect } from "@udecode/plate-floating";
import { mergeProps, UseVirtualFloatingOptions, UseVirtualFloatingReturn } from "@udecode/plate-headless";
import { useCallback } from "react";
import { offset } from "@udecode/plate-headless";

export const useFloatingQuoteEdit = ({
    floatingOptions,
}: {
    floatingOptions?: UseVirtualFloatingOptions;
} = {}): UseVirtualFloatingReturn => {
    const editor = useMyEditorRef();

    const getBoundingClientRect = useCallback(() => {
        const entry = queryRichLink(editor);

        if (entry) {
            const { element } = entry;

            const embedNode = toDOMNode(editor, element);
            if (embedNode) {
                return embedNode.getBoundingClientRect();
            }
        }

        return getDefaultBoundingClientRect();
    }, [editor]);

    const floatingResult = useVirtualFloatingLink({
        ...mergeProps(floatingOptions),
        getBoundingClientRect,
        middleware: [offset(-12)],
        editorId: editor.id,
    });

    return floatingResult;
};
