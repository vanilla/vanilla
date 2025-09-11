/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    ELEMENT_CALLOUT_ITEM,
    ELEMENT_CALLOUT,
    CalloutAppearance,
} from "@library/vanilla-editor/plugins/calloutPlugin/createCalloutPlugin";
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
import { unwrapCallout } from "@library/vanilla-editor/plugins/calloutPlugin/unwrapCallout";

export const toggleCallout = <V extends Value>(editor: PlateEditor<V>, appearance: CalloutAppearance) => {
    if (!editor.selection) return;

    const calloutType = getPluginType(editor, ELEMENT_CALLOUT);
    const calloutItemType = getPluginType(editor, ELEMENT_CALLOUT_ITEM);

    const isActive = someNode(editor, {
        match: { type: calloutType },
    });

    withoutNormalizing(editor, () => {
        unwrapCallout(editor);

        if (!isActive) {
            setElements(editor, {
                type: calloutItemType,
            });

            const callout = {
                type: calloutType,
                children: [],
                appearance,
            };

            wrapNodes<TElement>(editor, callout);
        }
    });
};
