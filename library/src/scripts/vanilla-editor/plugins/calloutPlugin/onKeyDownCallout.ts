/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { KeyboardHandlerReturnType, PlateEditor, Value, findNode, insertEmptyElement } from "@udecode/plate-common";

import { ELEMENT_CALLOUT } from "@library/vanilla-editor/plugins/calloutPlugin/createCalloutPlugin";
import { ELEMENT_PARAGRAPH } from "@udecode/plate-paragraph";
import { Range } from "slate";

export const onKeyDownCallout =
    <V extends Value = Value, E extends PlateEditor<V> = PlateEditor<V>>(editor: E): KeyboardHandlerReturnType =>
    (e: React.KeyboardEvent) => {
        if (!editor.selection) {
            return;
        }

        // Check if we're inside a callout
        const calloutEntry = findNode(editor, {
            at: editor.selection,
            match: { type: ELEMENT_CALLOUT },
        });

        if (!calloutEntry) {
            return;
        }

        switch (e.key) {
            case "ArrowUp":
                const isFirstElement = Range.includes(editor.selection, [0, 0]);

                if (!isFirstElement) {
                    return;
                }
                e.preventDefault();
                // If we are in the first position, insert a blank line before us.
                insertEmptyElement(editor, ELEMENT_PARAGRAPH, {
                    // TODO find a better way to do this (that would work in tables for example?).
                    at: [0],
                    select: true,
                });
                break;
        }
    };
