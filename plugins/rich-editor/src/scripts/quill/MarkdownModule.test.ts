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
import { promiseTimeout } from "@vanilla/utils";

describe.only("NewLineClickInsertionModule", () => {
    let quill: Quill;
    let markdownModule: MarkdownModule;

    beforeEach(() => {
        quill = setupTestQuill();
        markdownModule = new MarkdownModule(quill);
        markdownModule.registerHandler();
    });

    testInlineFormat({
        name: "inline code",
        text: "te1`st",
        wrapWith: "`",
        expectedFormats: {
            code: true,
        },
        type: MarkdownMacroType.INLINE,
        trailingCharacter: " ",
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

    interface ITestSpaceFormat {
        name: string;
        text: string;
        wrapWith: string;
        expectedFormats: IFormats;
        type: MarkdownMacroType;
        trailingCharacter?: string;
    }

    function testInlineFormat(options: ITestSpaceFormat) {
        const { text, wrapWith, expectedFormats, type, name } = options;

        const trailingCharacter = options.trailingCharacter || "";

        describe(name, () => {
            const wrappedText = wrapWith + text + wrapWith;

            const triggerKeys =
                type === MarkdownMacroType.BLOCK
                    ? Object.values(MarkdownBlockTriggers)
                    : Object.values(MarkdownInlineTriggers);

            describe("triggering on various keypress marks", () => {
                // This is not all keys covered by the regex but it is most of them.
                for (const triggerKey of triggerKeys) {
                    it("converts a line of just the format for the trigger key " + triggerKey, async () => {
                        quill.setContents([OpUtils.op(wrappedText)]);
                        quill.setSelection(wrappedText.length, 0);
                        quill.root.dispatchEvent(new KeyboardEvent("keypress", { key: triggerKey }));
                        expect(quill.getContents().ops).deep.eq([
                            OpUtils.op(text, expectedFormats),
                            OpUtils.op(`${trailingCharacter}\n`),
                        ]);
                    });

                    it("converts later in the line for the trigger key " + triggerKey, () => {
                        const startPadding = "asdf42 asdf asdf_!@#$%^&*( ";
                        quill.setContents([OpUtils.op(startPadding + wrappedText)]);
                        quill.setSelection(startPadding.length + wrappedText.length, 0);
                        quill.root.dispatchEvent(new KeyboardEvent("keypress", { key: triggerKey }));
                        expect(quill.getContents().ops).deep.eq([
                            OpUtils.op(startPadding),
                            OpUtils.op(text, expectedFormats),
                            OpUtils.op(`${trailingCharacter}\n`),
                        ]);
                    });

                    it(
                        "does not convert except right at the end of formatted part for trigger key " + triggerKey,
                        () => {
                            quill.setContents([OpUtils.op(wrappedText + " ")]);
                            quill.setSelection(wrappedText.length + 1, 0);
                            quill.root.dispatchEvent(new KeyboardEvent("keypress", { key: triggerKey }));
                            expect(quill.getContents().ops).deep.eq([OpUtils.op(wrappedText + ` \n`)]);
                        },
                    );
                }
            });
        });
    }
});
