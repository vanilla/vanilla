/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { queryRichLink } from "@library/vanilla-editor/plugins/richEmbedPlugin/queries/queryRichLink";
import { MyEditor } from "@library/vanilla-editor/typescript";
import {
    ELEMENT_PARAGRAPH,
    getPointNextToVoid,
    insertBreak,
    insertEmptyElement,
    KeyboardHandlerReturnType,
    setSelection,
    someNode,
} from "@udecode/plate-headless";
import { Path, Range } from "slate";

export function onKeyDownRichEmbed(editor: MyEditor): KeyboardHandlerReturnType {
    return (e: React.KeyboardEvent) => {
        if (!editor.selection) {
            return;
        }
        const embedSelected = queryRichLink(editor);
        if (!embedSelected) {
            // Nothing to do.
            return;
        }

        switch (e.key) {
            case "ArrowUp":
                const isFirstElement = Range.includes(editor.selection, [0, 0]);

                if (!isFirstElement) {
                    return;
                }
                e.preventDefault();
                // If we are the in the first position, insert a blank line before us.
                insertEmptyElement(editor, ELEMENT_PARAGRAPH, {
                    // TODO find a better way to do this (that would work in tables for example?).
                    at: [0],
                    select: true,
                });
                break;
            case "Enter":
                e.preventDefault();
                e.stopPropagation();
                // If we are the in the first position, insert a blank line before us.
                insertEmptyElement(editor, ELEMENT_PARAGRAPH, {
                    // TODO find a better way to do this (that would work in tables for example?).
                    at: Path.next(embedSelected.path),
                    select: true,
                });
                // insertBreak(editor);
                break;
        }
    };
}
