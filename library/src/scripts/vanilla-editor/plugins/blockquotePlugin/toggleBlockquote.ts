/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ELEMENT_BLOCKQUOTE_ITEM } from "@library/vanilla-editor/plugins/blockquotePlugin/createBlockquotePlugin";
import { ELEMENT_BLOCKQUOTE } from "@udecode/plate-block-quote";
import {
    getPluginType,
    PlateEditor,
    setElements,
    someNode,
    TElement,
    Value,
    withoutNormalizing,
    wrapNodes,
} from "@udecode/plate-common";
import { unwrapBlockquote } from "@library/vanilla-editor/plugins/blockquotePlugin/unwrapBlockquote";

export const toggleBlockquote = <V extends Value>(editor: PlateEditor<V>) => {
    if (!editor.selection) return;

    const blockquoteType = getPluginType(editor, ELEMENT_BLOCKQUOTE);
    const blockquoteItemType = getPluginType(editor, ELEMENT_BLOCKQUOTE_ITEM);

    const isActive = someNode(editor, {
        match: { type: blockquoteType },
    });

    withoutNormalizing(editor, () => {
        unwrapBlockquote(editor);

        if (!isActive) {
            setElements(editor, {
                type: blockquoteItemType,
            });

            const codeBlock = {
                type: blockquoteType,
                children: [],
            };

            wrapNodes<TElement>(editor, codeBlock);
        }
    });
};
