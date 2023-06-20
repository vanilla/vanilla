import {
    AutoformatPlugin,
    ELEMENT_CODE_BLOCK,
    ELEMENT_H1,
    ELEMENT_H2,
    ELEMENT_H3,
    ELEMENT_H4,
    ELEMENT_H5,
    ELEMENT_H6,
    ELEMENT_HR,
    ELEMENT_IMAGE,
    ELEMENT_PARAGRAPH,
    ELEMENT_TD,
    ELEMENT_TODO_LI,
    ExitBreakPlugin,
    IndentPlugin,
    isBlockAboveEmpty,
    isSelectionAtBlockStart,
    KEYS_HEADING,
    ResetNodePlugin,
    SelectOnBackspacePlugin,
    SoftBreakPlugin,
    TrailingBlockPlugin,
} from "@udecode/plate-headless";
import { autoformatRules } from "./autoformat/autoformatRules";
import { MyEditor, MyPlatePlugin, MyValue } from "./typescript";

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
