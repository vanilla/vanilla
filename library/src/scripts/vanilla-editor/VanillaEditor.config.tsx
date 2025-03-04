import { AutoformatPlugin } from "@udecode/plate-autoformat";
import { ExitBreakPlugin, SoftBreakPlugin } from "@udecode/plate-break";
import { ELEMENT_CODE_BLOCK } from "@udecode/plate-code-block";
import { isBlockAboveEmpty, isSelectionAtBlockStart } from "@udecode/plate-common";
import {
    ELEMENT_H1,
    ELEMENT_H2,
    ELEMENT_H3,
    ELEMENT_H4,
    ELEMENT_H5,
    ELEMENT_H6,
    KEYS_HEADING,
} from "@udecode/plate-heading";
import { ELEMENT_HR } from "@udecode/plate-horizontal-rule";
import { IndentPlugin } from "@udecode/plate-indent";
import { ELEMENT_TODO_LI } from "@udecode/plate-list";
import { ELEMENT_PARAGRAPH } from "@udecode/plate-paragraph";
import { ResetNodePlugin } from "@udecode/plate-reset-node";
import { SelectOnBackspacePlugin } from "@udecode/plate-select";
import { ELEMENT_TD } from "@udecode/plate-table";
import { TrailingBlockPlugin } from "@udecode/plate-trailing-block";
import { autoformatRules } from "./autoformat/autoformatRules";
import { MyEditor, MyPlatePlugin, MyValue } from "./typescript";
import { ELEMENT_IMAGE } from "@library/vanilla-editor/VanillaEditor.components";

export const CONFIG = {
    indent: {
        inject: {
            props: {
                validTypes: [
                    ELEMENT_H1,
                    ELEMENT_H2,
                    ELEMENT_H3,
                    ELEMENT_H4,
                    ELEMENT_H5,
                    ELEMENT_H6,
                    ELEMENT_CODE_BLOCK,
                ],
            },
        },
    } as Partial<MyPlatePlugin<IndentPlugin>>,

    resetBlockType: {
        options: {
            rules: [
                {
                    types: [ELEMENT_TODO_LI],
                    defaultType: ELEMENT_PARAGRAPH,
                    hotkey: "enter",
                    predicate: isBlockAboveEmpty,
                },
                {
                    types: [ELEMENT_TODO_LI],
                    defaultType: ELEMENT_PARAGRAPH,
                    hotkey: "backspace",
                    predicate: isSelectionAtBlockStart,
                },
            ],
        },
    } as Partial<MyPlatePlugin<ResetNodePlugin>>,
    trailingBlock: { type: ELEMENT_PARAGRAPH } as Partial<MyPlatePlugin<TrailingBlockPlugin>>,
    exitBreak: {
        options: {
            rules: [
                {
                    hotkey: "mod+enter",
                },
                {
                    // exit to the previous block
                    hotkey: "mod+shift+enter",
                    before: true,
                },
                {
                    hotkey: "enter",
                    query: {
                        start: true,
                        end: true,
                        allow: KEYS_HEADING,
                    },
                },
            ],
        },
    } as Partial<MyPlatePlugin<ExitBreakPlugin>>,
    softBreak: {
        options: {
            rules: [
                {
                    hotkey: "shift+enter",
                },
                {
                    hotkey: "enter",

                    query: {
                        allow: [ELEMENT_TD],
                    },
                },
            ],
        },
    } as Partial<MyPlatePlugin<SoftBreakPlugin>>,
    selectOnBackspace: {
        options: {
            query: {
                allow: [ELEMENT_IMAGE, ELEMENT_HR],
            },
        },
    } as Partial<MyPlatePlugin<SelectOnBackspacePlugin>>,
    autoformat: {
        options: {
            rules: autoformatRules,
        },
    } as Partial<MyPlatePlugin<AutoformatPlugin<MyValue, MyEditor>>>,
};
