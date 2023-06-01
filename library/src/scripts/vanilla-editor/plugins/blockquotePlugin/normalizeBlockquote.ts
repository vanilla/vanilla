/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ELEMENT_BLOCKQUOTE_ITEM } from "@library/vanilla-editor/plugins/blockquotePlugin/createBlockquotePlugin";
import {
    ElementOf,
    ELEMENT_BLOCKQUOTE,
    getChildren,
    getListTypes,
    getPluginType,
    isElement,
    PlateEditor,
    setElements,
    TEditor,
    TNodeEntry,
    unwrapList,
    unwrapNodes,
    Value,
    wrapNodes,
} from "@udecode/plate-headless";

export const normalizeBlockquote = <V extends Value>(editor: PlateEditor<V>) => {
    const { normalizeNode } = editor;
    const blockquoteType = getPluginType(editor, ELEMENT_BLOCKQUOTE);
    const blockquoteItemType = getPluginType(editor, ELEMENT_BLOCKQUOTE_ITEM);

    return ([node, path]: TNodeEntry) => {
        if (!isElement(node)) {
            return normalizeNode([node, path]);
        }

        // Convert children that are not blockquote items into blockquote items
        if (node.type === blockquoteType) {
            const nonItemChild = getChildren([node, path]).find(([child]) => child.type !== blockquoteItemType);

            if (nonItemChild) {
                const [nonChildNode, nonChildPath] = nonItemChild;

                if (!nonChildNode.type) {
                    // the child does not have an assigned type, wrap it in a blockquote item
                    return wrapNodes(editor, { type: blockquoteItemType, children: [] } as ElementOf<TEditor<V>>, {
                        at: nonChildPath,
                    });
                } else if (getListTypes(editor).includes(nonChildNode.type as string)) {
                    // the child is a list and needs to be unwrapped
                    unwrapList(editor, { at: nonChildPath });
                    return setElements(editor, { type: blockquoteItemType }, { at: nonChildPath });
                } else if (nonChildNode.type === blockquoteType) {
                    // the child is another blockquote, unwrap it
                    unwrapNodes(editor, {
                        at: path,
                        match: { type: blockquoteType },
                        split: false,
                    });
                } else {
                    // convert all other types into a blockquote item
                    return setElements(editor, { type: blockquoteItemType }, { at: nonChildPath });
                }
            }
        }

        normalizeNode([node, path]);
    };
};
