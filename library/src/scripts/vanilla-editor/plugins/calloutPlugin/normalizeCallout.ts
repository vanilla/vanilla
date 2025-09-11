/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import {
    ELEMENT_CALLOUT,
    ELEMENT_CALLOUT_ITEM,
} from "@library/vanilla-editor/plugins/calloutPlugin/createCalloutPlugin";
import {
    PlateEditor,
    TNodeEntry,
    Value,
    getChildren,
    getPluginType,
    isElement,
    setElements,
} from "@udecode/plate-common";
import { getListTypes, unwrapList } from "@udecode/plate-list";

export const normalizeCallout = <V extends Value>(editor: PlateEditor<V>) => {
    const { normalizeNode } = editor;
    const calloutType = getPluginType(editor, ELEMENT_CALLOUT);
    const calloutItemType = getPluginType(editor, ELEMENT_CALLOUT_ITEM);

    return ([node, path]: TNodeEntry) => {
        if (!isElement(node)) {
            return normalizeNode([node, path]);
        }

        if (node.type === calloutType) {
            const nonItemChild = getChildren([node, path]).find(([child]) => child.type !== calloutItemType);

            if (nonItemChild) {
                const [nonChildNode, nonChildPath] = nonItemChild;

                // Unwrap lists inside callouts
                const listTypes = getListTypes(editor);
                if (listTypes.includes(nonChildNode.type as string)) {
                    unwrapList(editor, { at: nonChildPath });
                    return;
                }

                // Convert non-callout-item children to callout-item
                setElements(editor, { type: calloutItemType }, { at: nonChildPath });
                return;
            }
        }

        return normalizeNode([node, path]);
    };
};
