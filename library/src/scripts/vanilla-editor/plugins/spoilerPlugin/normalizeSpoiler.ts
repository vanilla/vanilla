/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import {
    ELEMENT_SPOILER,
    ELEMENT_SPOILER_CONTENT,
    ELEMENT_SPOILER_ITEM,
} from "@library/vanilla-editor/plugins/spoilerPlugin/createSpoilerPlugin";
import {
    ElementOf,
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

export const normalizeSpoiler = <V extends Value>(editor: PlateEditor<V>) => {
    const { normalizeNode } = editor;
    const spoilerType = getPluginType(editor, ELEMENT_SPOILER);
    const spoilerContentType = getPluginType(editor, ELEMENT_SPOILER_CONTENT);
    const spoilerItemType = getPluginType(editor, ELEMENT_SPOILER_ITEM);

    return ([node, path]: TNodeEntry) => {
        if (!isElement(node)) {
            return normalizeNode([node, path]);
        }

        if (node.type === spoilerType) {
            const nonContentChild = getChildren([node, path]).find(
                ([child]) => ![spoilerContentType, spoilerItemType, spoilerType].includes(child.type as string),
            );

            if (nonContentChild) {
                return wrapNodes(editor, { type: spoilerContentType, children: [] } as ElementOf<TEditor<V>>, {
                    at: nonContentChild[1],
                });
            }
        }

        if (node.type === spoilerContentType) {
            const nonItemChild = getChildren([node, path]).find(([child]) => child.type !== spoilerItemType);

            if (nonItemChild) {
                const [nonChildNode, nonChildPath] = nonItemChild;

                if (!nonChildNode.type) {
                    // the child does not have an assigned type, wrap it in a spoiler item
                    return wrapNodes(editor, { type: spoilerItemType, children: [] } as ElementOf<TEditor<V>>, {
                        at: nonChildPath,
                    });
                } else if (getListTypes(editor).includes(nonChildNode.type as string)) {
                    // the child is a list and needs to be unwrapped
                    unwrapList(editor, { at: nonChildPath });
                    return setElements(editor, { type: spoilerItemType }, { at: nonChildPath });
                } else if (nonChildNode.type === spoilerType) {
                    // the child is another spoiler, unwrap it
                    unwrapNodes(editor, {
                        at: nonChildPath,
                        match: { type: [spoilerContentType, spoilerType] },
                        split: false,
                    });
                } else {
                    // convert all other types into a spoiler item
                    return setElements(editor, { type: spoilerItemType }, { at: nonChildPath });
                }
            }
        }

        normalizeNode([node, path]);
    };
};
