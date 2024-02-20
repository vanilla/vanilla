/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    ELEMENT_SPOILER,
    ELEMENT_SPOILER_CONTENT,
    ELEMENT_SPOILER_ITEM,
} from "@library/vanilla-editor/plugins/spoilerPlugin/createSpoilerPlugin";
import { unwrapSpoiler } from "@library/vanilla-editor/plugins/spoilerPlugin/unwrapSpoiler";
import {
    PlateEditor,
    TElement,
    Value,
    getPluginType,
    setElements,
    someNode,
    withoutNormalizing,
    wrapNodes,
} from "@udecode/plate-common";

export const toggleSpoiler = <V extends Value>(editor: PlateEditor<V>) => {
    if (!editor.selection) return;

    const spoilerType = getPluginType(editor, ELEMENT_SPOILER);
    const spoilerContentType = getPluginType(editor, ELEMENT_SPOILER_CONTENT);
    const spoilerItemType = getPluginType(editor, ELEMENT_SPOILER_ITEM);

    const isActive = someNode(editor, {
        match: { type: spoilerType },
    });

    withoutNormalizing(editor, () => {
        unwrapSpoiler(editor);

        if (!isActive) {
            setElements(editor, { type: spoilerItemType });

            const spoilerBlock = { type: spoilerType, children: [] };
            wrapNodes<TElement>(editor, spoilerBlock);

            const spoilerContent = { type: spoilerContentType, children: [] };
            wrapNodes<TElement>(editor, spoilerContent);
        }
    });
};
