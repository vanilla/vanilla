/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ELEMENT_BLOCKQUOTE_ITEM } from "@library/vanilla-editor/plugins/blockquotePlugin/createBlockquotePlugin";
import {
    ELEMENT_BLOCKQUOTE,
    ELEMENT_DEFAULT,
    getAboveNode,
    getBlockAbove,
    getCommonNode,
    getPluginType,
    isElement,
    PlateEditor,
    setElements,
    unwrapNodes,
    Value,
    withoutNormalizing,
} from "@udecode/plate-headless";
import { Path } from "slate";

export const unwrapBlockquote = <V extends Value>(editor: PlateEditor<V>, { at }: { at?: Path } = {}) => {
    const blockquoteType = getPluginType(editor, ELEMENT_BLOCKQUOTE);
    const blockquoteItemType = getPluginType(editor, ELEMENT_BLOCKQUOTE_ITEM);
    const defaultType = getPluginType(editor, ELEMENT_DEFAULT);

    const ancestorTypeCheck = () => {
        if (getAboveNode(editor, { match: { type: blockquoteType, at } })) {
            return true;
        }

        if (!at && editor.selection) {
            const commonNode = getCommonNode(editor, editor.selection.anchor.path, editor.selection.focus.path);
            if (isElement(commonNode[0]) && commonNode[0].type === blockquoteType) {
                return true;
            }
        }

        return false;
    };

    withoutNormalizing(editor, () => {
        do {
            const itemEntry = getBlockAbove(editor, {
                at,
                match: { type: blockquoteItemType },
            });

            if (itemEntry) {
                setElements(editor, {
                    at,
                    type: defaultType,
                });
            }

            unwrapNodes(editor, {
                at,
                match: { type: [blockquoteItemType, blockquoteType] },
                split: true,
                block: true,
            });
        } while (ancestorTypeCheck());
    });
};
