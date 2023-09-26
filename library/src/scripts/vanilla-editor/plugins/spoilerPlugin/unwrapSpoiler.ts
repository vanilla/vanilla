/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

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
} from "@udecode/plate-headless";
import {
    ELEMENT_SPOILER,
    ELEMENT_SPOILER_CONTENT,
    ELEMENT_SPOILER_ITEM,
} from "@library/vanilla-editor/plugins/spoilerPlugin/createSpoilerPlugin";
import { Path } from "slate";

export const unwrapSpoiler = <V extends Value>(editor: PlateEditor<V>, { at }: { at?: Path } = {}) => {
    const spoilerType = getPluginType(editor, ELEMENT_SPOILER);
    const spoilerContentType = getPluginType(editor, ELEMENT_SPOILER_CONTENT);
    const spoilerItemType = getPluginType(editor, ELEMENT_SPOILER_ITEM);
    const defaultType = getPluginType(editor, ELEMENT_DEFAULT);

    const ancestorTypeCheck = () => {
        if (getAboveNode(editor, { match: { type: spoilerType, at } })) {
            return true;
        }

        if (!at && editor.selection) {
            const commonNode = getCommonNode(editor, editor.selection.anchor.path, editor.selection.focus.path);
            if (isElement(commonNode[0]) && commonNode[0].type === spoilerType) {
                return true;
            }
        }

        return false;
    };

    withoutNormalizing(editor, () => {
        do {
            const itemEntry = getBlockAbove(editor, {
                at,
                match: { type: spoilerItemType },
            });

            if (itemEntry) {
                setElements(editor, {
                    at,
                    type: defaultType,
                });
            }

            unwrapNodes(editor, {
                at,
                match: { type: [spoilerItemType, spoilerContentType, spoilerType] },
                split: true,
                block: true,
            });
        } while (ancestorTypeCheck());
    });
};
