/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Quill, { IFormats } from "quill/core";
import { setupTestQuill } from "@rich-editor/__tests__/quillUtils";
import MarkdownModule, {
    MarkdownMacroType,
    MarkdownBlockTriggers,
    MarkdownInlineTriggers,
} from "@rich-editor/quill/MarkdownModule";
import { expect } from "chai";
import OpUtils from "@rich-editor/__tests__/OpUtils";
import BlockquoteLineBlot from "@rich-editor/quill/blots/blocks/BlockquoteBlot";
import SpoilerLineBlot from "@rich-editor/quill/blots/blocks/SpoilerBlot";

const MENTION_INSERT = {
    mention: { name: "meadwayk", userID: 24562 },
};

describe("NewLineClickInsertionModule", () => {
    let quill: Quill;
    let markdownModule: MarkdownModule;

    beforeEach(() => {
        quill = setupTestQuill();
        markdownModule = new MarkdownModule(quill);
        markdownModule.registerHandler();
    });

    function dispatchKey(key: string) {
        quill.root.dispatchEvent(new KeyboardEvent("keydown", { key }));
    }

    testInlineFormat({
        name: "inline code",
        text: "te1`st",
        wrapWith: "`",
        expectedFormats: {
            code: true,
        },
        type: MarkdownMacroType.INLINE,
        trailingCharacter: "", // The space is actually inserted by quill automatically. No need for an extra
    });

    testInlineFormat({
        name: "italic *",
        text: "te*st",
        wrapWith: "*",
        expectedFormats: {
            italic: true,
        },
        type: MarkdownMacroType.INLINE,
    });

    testInlineFormat({
        name: "italic _",
        text: "te_st",
        wrapWith: "_",
        expectedFormats: {
            italic: true,
        },
        type: MarkdownMacroType.INLINE,
    });

    testInlineFormat({
        name: "bold _",
        text: "te__st",
        wrapWith: "__",
        expectedFormats: {
            bold: true,
        },
        type: MarkdownMacroType.INLINE,
    });

    testInlineFormat({
        name: "bold *",
        text: "te**st",
        wrapWith: "**",
        expectedFormats: {
            bold: true,
        },
        type: MarkdownMacroType.INLINE,
    });

    testInlineFormat({
        name: "bold italic *",
        text: "te*s_t",
        wrapWith: "***",
        expectedFormats: {
            bold: true,
            italic: true,
        },
        type: MarkdownMacroType.INLINE,
    });

    testInlineFormat({
        name: "bold italic _",
        text: "te*s_t",
        wrapWith: "___",
        expectedFormats: {
            bold: true,
            italic: true,
        },
        type: MarkdownMacroType.INLINE,
    });

    testInlineFormat({
        name: "Strikethrough",
        text: "as~~asdf~asdf~asdf",
        wrapWith: "~~",
        expectedFormats: {
            strike: true,
        },
        type: MarkdownMacroType.INLINE,
    });

    testBlockFormat({
        name: "header 2",
        text: "##",
        expectedFormats: {
            header: {
                level: 2,
                ref: "",
            },
        },
    });

    testBlockFormat({
        name: "header 3",
        text: "###",
        expectedFormats: {
            header: {
                level: 3,
                ref: "",
            },
        },
    });

    testBlockFormat({
        name: "header 4",
        text: "####",
        expectedFormats: {
            header: {
                level: 4,
                ref: "",
            },
        },
    });

    testBlockFormat({
        name: "header 5",
        text: "#####",
        expectedFormats: {
            header: {
                level: 5,
                ref: "",
            },
        },
    });

    testBlockFormat({
        name: "Quote",
        text: ">",
        expectedFormats: {
            [BlockquoteLineBlot.blotName]: true,
        },
    });

    testBlockFormat({
        name: "Spoiler",
        text: "!>",
        expectedFormats: {
            [SpoilerLineBlot.blotName]: true,
        },
    });

    describe("works with inline embeds on the line", () => {
        it("handles an inline embed at the start of the line", () => {
            quill.setContents([OpUtils.op(MENTION_INSERT), OpUtils.op(" _te@st_")]);
            quill.setSelection(9, 0);
            dispatchKey(MarkdownInlineTriggers.SPACE);
            expect(quill.getContents().ops).deep.eq([
                OpUtils.op(MENTION_INSERT),
                OpUtils.op(" "),
                OpUtils.op("te@st", {
                    italic: true,
                }),
                OpUtils.newline(),
            ]);
        });

        it("handles multiple inline embeds at the start of the line", () => {
            quill.setContents([
                OpUtils.op(MENTION_INSERT),
                OpUtils.op(MENTION_INSERT),
                OpUtils.op("    "),
                OpUtils.op(MENTION_INSERT),
                OpUtils.op("  _te@st_"),
            ]);
            quill.setSelection(16, 0);
            dispatchKey(MarkdownInlineTriggers.SPACE);
            expect(quill.getContents().ops).deep.eq([
                OpUtils.op(MENTION_INSERT),
                OpUtils.op(MENTION_INSERT),
                OpUtils.op("    "),
                OpUtils.op(MENTION_INSERT),
                OpUtils.op("  "),
                OpUtils.op("te@st", {
                    italic: true,
                }),
                OpUtils.newline(),
            ]);
        });
    });

    interface ITestInlineFormat {
        name: string;
        text: string;
        wrapWith: string;
        expectedFormats: IFormats;
        type: MarkdownMacroType;
        trailingCharacter?: string;
    }

    // We run the tests with empty newlines in the beginning to ensure our offests
    // from the beginning of the document are calculated properly.
    const startLines = [OpUtils.op("\n\n\n")];
    const startLinesLength = 3;

    function testInlineFormat(options: ITestInlineFormat) {
        const { text, wrapWith, expectedFormats, type, name } = options;

        const trailingCharacter = options.trailingCharacter || "";

        describe(name, () => {
            const wrappedText = wrapWith + text + wrapWith;
            const triggerKeys = Object.values(MarkdownInlineTriggers);

            describe("triggering on various punctuation marks", () => {
                for (const triggerKey of triggerKeys) {
                    it("converts a line of just the format for the trigger key " + triggerKey, async () => {
                        quill.setContents([...startLines, OpUtils.op(wrappedText)]);
                        quill.setSelection(wrappedText.length + startLinesLength, 0);
                        dispatchKey(triggerKey);
                        expect(quill.getContents().ops).deep.eq([
                            ...startLines,
                            OpUtils.op(text, expectedFormats),
                            OpUtils.op(`${trailingCharacter}\n`),
                        ]);
                    });

                    it("converts later in the line for the trigger key " + triggerKey, () => {
                        const startPadding = "\n\n\nasdf42 asdf asdf_!@#$%^&*( ";
                        quill.setContents([OpUtils.op(startPadding + wrappedText)]);
                        quill.setSelection(startPadding.length + wrappedText.length, 0);
                        dispatchKey(triggerKey);
                        expect(quill.getContents().ops).deep.eq([
                            OpUtils.op(startPadding),
                            OpUtils.op(text, expectedFormats),
                            OpUtils.op(`${trailingCharacter}\n`),
                        ]);
                    });

                    it(
                        "does not convert except right at the end of formatted part for trigger key " + triggerKey,
                        () => {
                            quill.setContents([...startLines, OpUtils.op(wrappedText + " ")]);
                            quill.setSelection(wrappedText.length + startLinesLength + 1, 0);
                            dispatchKey(triggerKey);
                            expect(quill.getContents().ops).deep.eq([OpUtils.op("\n\n\n" + wrappedText + ` \n`)]);
                        },
                    );
                }
            });
        });
    }

    interface ITestBlockFormat {
        name: string;
        text: string;
        expectedFormats: IFormats;
    }

    function testBlockFormat(options: ITestBlockFormat) {
        const { text, expectedFormats, name } = options;

        describe(name, () => {
            for (const triggerKey of Object.values(MarkdownBlockTriggers)) {
                it("can handle it's macro for the trigger key " + triggerKey, () => {
                    quill.setContents([...startLines, OpUtils.op(text)]);
                    quill.setSelection(text.length + startLinesLength, 0);
                    dispatchKey(triggerKey);
                    expect(quill.getContents().ops).deep.eq([...startLines, OpUtils.op("\n", expectedFormats)]);
                });
            }
        });
    }
});
