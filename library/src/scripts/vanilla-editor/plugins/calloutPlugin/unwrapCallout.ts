/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    ELEMENT_CALLOUT,
    ELEMENT_CALLOUT_ITEM,
} from "@library/vanilla-editor/plugins/calloutPlugin/createCalloutPlugin";
import {
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
} from "@udecode/plate-common";
import { Path } from "slate";

export const unwrapCallout = <V extends Value>(editor: PlateEditor<V>, { at }: { at?: Path } = {}) => {
    const calloutType = getPluginType(editor, ELEMENT_CALLOUT);
    const calloutItemType = getPluginType(editor, ELEMENT_CALLOUT_ITEM);
    const defaultType = getPluginType(editor, ELEMENT_DEFAULT);

    const ancestorTypeCheck = () => {
        if (getAboveNode(editor, { match: { type: calloutType, at } })) {
            return true;
        }

        if (!at && editor.selection) {
            const commonNode = getCommonNode(editor, editor.selection.anchor.path, editor.selection.focus.path);
            if (isElement(commonNode[0]) && commonNode[0].type === calloutType) {
                return true;
            }
        }

        return false;
    };

    withoutNormalizing(editor, () => {
        do {
            const itemEntry = getBlockAbove(editor, {
                at,
                match: { type: calloutItemType },
            });

            if (itemEntry) {
                setElements(editor, {
                    at,
                    type: defaultType,
                });
            }

            unwrapNodes(editor, {
                at,
                match: { type: calloutType },
                split: true,
            });
        } while (ancestorTypeCheck());
    });
};
