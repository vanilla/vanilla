/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { PlateEditor, Value, getAboveNode } from "@udecode/plate-common";

import { ELEMENT_CALLOUT } from "@library/vanilla-editor/plugins/calloutPlugin/createCalloutPlugin";
import { insertBreakCallout } from "@library/vanilla-editor/plugins/calloutPlugin/insertBreakCallout";
import { normalizeCallout } from "@library/vanilla-editor/plugins/calloutPlugin/normalizeCallout";

// AIDEV-NOTE: Up arrow key prevention is handled in onKeyDownCallout.ts to prevent cursor from moving above callout when it's the first element

export const withCallout = <V extends Value = Value, E extends PlateEditor<V> = PlateEditor<V>>(editor: E) => {
    const { insertBreak } = editor;

    editor.insertBreak = () => {
        // Check if we're inside a callout by looking for a callout ancestor
        const calloutAbove = getAboveNode(editor, {
            match: { type: ELEMENT_CALLOUT },
        });

        if (calloutAbove && insertBreakCallout(editor)) {
            return;
        }

        insertBreak();
    };

    editor.normalizeNode = normalizeCallout(editor);

    return editor;
};
