/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Formatter from "@rich-editor/quill/Formatter";
import Quill, { RangeStatic } from "quill/core";
import { expect } from "chai";
import OpUtils, { inlineFormatOps, blockFormatOps } from "@rich-editor/__tests__/OpUtils";
import registerQuill from "./registerQuill";
import CodeBlockBlot from "@rich-editor/quill/blots/blocks/CodeBlockBlot";

describe("Formatter", () => {
    let quill: Quill;
    let formatter: Formatter;

    before(() => {
        registerQuill();
    });

    beforeEach(() => {
        document.body.innerHTML = `
            <div id="quill"></div>
        `;
        quill = new Quill("#quill");
        formatter = new Formatter(quill);
    });

    describe("bold()", () => {
        const formattingFunction = () => formatter.bold(getFullRange());
        testBasicInlineFormatting(formattingFunction, OpUtils.bold);
        testStackedInlineFormatting("bold", formattingFunction, ["italic", "strike", "link", "code"]);
        testInlineAgainstLineFormatting("bold", formattingFunction);
    });

    describe("italic()", () => {
        const formattingFunction = () => formatter.italic(getFullRange());
        testBasicInlineFormatting(formattingFunction, OpUtils.italic);
        testStackedInlineFormatting("italic", formattingFunction, ["bold", "strike", "link", "code"]);
        testInlineAgainstLineFormatting("italic", formattingFunction);
    });

    describe("strike()", () => {
        const formattingFunction = () => formatter.strike(getFullRange());
        testBasicInlineFormatting(formattingFunction, OpUtils.strike);
        testStackedInlineFormatting("strike", formattingFunction, ["bold", "italic", "link", "code"]);
        testInlineAgainstLineFormatting("strike", formattingFunction);
    });

    describe("link()", () => {
        const formattingFunction = () => formatter.link(getFullRange(), OpUtils.DEFAULT_LINK);
        testBasicInlineFormatting(formattingFunction, OpUtils.link);
        testStackedInlineFormatting(
            "link",
            formattingFunction,
            ["bold", "italic", "strike", "code"],
            OpUtils.DEFAULT_LINK,
        );
        testInlineAgainstLineFormatting("link", formattingFunction, OpUtils.DEFAULT_LINK);
    });

    describe("codeInline()", () => {
        const formattingFunction = () => formatter.codeInline(getFullRange());
        testBasicInlineFormatting(formattingFunction, OpUtils.code);
        testStackedInlineFormatting("code", formattingFunction, ["bold", "italic", "strike", "link"]);
        testInlineAgainstLineFormatting("code", formattingFunction);
    });

    describe("h2()", () => {
        const formattingFunction = (range = getFullRange()) => formatter.h2(range);
        testLineFormatInlinePreservation("h2", formattingFunction, OpUtils.heading(2));
        testLineFormatExclusivity("h2", formattingFunction, OpUtils.heading(2));
        testMultiLineFormatting("h2", formattingFunction, OpUtils.heading(2));
    });

    describe("h3()", () => {
        const formattingFunction = (range = getFullRange()) => formatter.h3(range);
        testLineFormatInlinePreservation("h3", formattingFunction, OpUtils.heading(3));
        testLineFormatExclusivity("h3", formattingFunction, OpUtils.heading(3));
        testMultiLineFormatting("h3", formattingFunction, OpUtils.heading(3));
    });

    describe("blockquote()", () => {
        const formattingFunction = (range = getFullRange()) => formatter.blockquote(range);
        testLineFormatInlinePreservation("blockquote", formattingFunction, OpUtils.quoteLine());
        testLineFormatExclusivity("blockquote", formattingFunction, OpUtils.quoteLine());
        testMultiLineFormatting("blockquote-line", formattingFunction, OpUtils.quoteLine());
    });

    describe("spoiler()", () => {
        const formattingFunction = (range = getFullRange()) => formatter.spoiler(range);
        testLineFormatInlinePreservation("spoiler", formattingFunction, OpUtils.spoilerLine());
        testLineFormatExclusivity("spoiler", formattingFunction, OpUtils.spoilerLine());
        testMultiLineFormatting("spoiler-line", formattingFunction, OpUtils.spoilerLine());
    });

    describe("codeBlock()", () => {
        const formattingFunction = (range = getFullRange()) => formatter.codeBlock(range);
        testNoLineFormatInlinePreservation(CodeBlockBlot.blotName, formattingFunction, OpUtils.codeBlock());
        testLineFormatExclusivity(CodeBlockBlot.blotName, formattingFunction, OpUtils.codeBlock());
        testCodeBlockFormatStripping(formattingFunction);
    });

    function assertQuillInputOutput(input: any[], expectedOutput: any[], formattingFunction: () => void) {
        quill.setContents(input, Quill.sources.USER);
        formattingFunction();

        const stripRefs = op => {
            if (op.attributes && op.attributes.header && op.attributes.header.ref) {
                op.attributes.header.ref = "";
            }
            return op;
        };
        // Kludge out the dynamically generated refs.
        const result = quill.getContents().ops!.map(stripRefs);
        expectedOutput = expectedOutput.map(stripRefs);
        expect(result).deep.equals(expectedOutput);
    }

    // Inline testing utilities

    function getFullRange(): RangeStatic {
        return {
            index: 0,
            length: quill.scroll.length() - 1,
        };
    }

    function testStackedInlineFormatting(
        formatToTest: string,
        formatterFunction: () => void,
        testAgainst: string[],
        enableValue: any = true,
    ) {
        describe(`Adding ${formatToTest} to existing inline formats`, () => {
            testAgainst.forEach(opName => {
                it(opName + " + " + formatToTest, () => {
                    const opMethod = OpUtils[opName] as () => any;
                    const initial = [opMethod()];
                    const finalOp = opMethod();
                    finalOp.attributes[formatToTest] = enableValue;
                    const expected = [finalOp, OpUtils.newline()];
                    assertQuillInputOutput(initial, expected, formatterFunction);
                });
            });
        });
    }

    function testInlineAgainstLineFormatting(
        formatToTest: string,
        formatterFunction: () => void,
        enableValue: any = true,
    ) {
        const testAgainst = [
            {
                op: OpUtils.spoilerLine(),
                name: "spoiler",
            },
            { op: OpUtils.quoteLine(), name: "quote" },
            { op: OpUtils.heading(2), name: "h2" },
            { op: OpUtils.heading(3), name: "h3" },
            { op: OpUtils.list("ordered"), name: "ordered list" },
            { op: OpUtils.list("bullet"), name: "bulleted list" },
        ];
        describe(`Add ${formatToTest} to line formats`, () => {
            testAgainst.forEach(({ op, name }) => {
                it(name + " + " + formatToTest, () => {
                    const initial = [OpUtils.op(), op];
                    const finalOp = OpUtils.op();
                    finalOp.attributes = {
                        [formatToTest]: enableValue,
                    };
                    const expected = [finalOp, op];
                    assertQuillInputOutput(initial, expected, formatterFunction);
                });
            });

            it("Does nothing to Code Blocks", () => {
                const initial = [OpUtils.op(), OpUtils.codeBlock()];
                const expected = [OpUtils.op(), OpUtils.codeBlock()];
                assertQuillInputOutput(initial, expected, formatterFunction);
            });
        });
    }

    function testBasicInlineFormatting(formattingFunction: () => void, finalOpCreator: () => any) {
        it("Can format plainText", () => {
            const initial = [OpUtils.op()];
            const expected = [finalOpCreator(), OpUtils.newline()];
            assertQuillInputOutput(initial, expected, formattingFunction);
        });
    }

    function testLineFormatInlinePreservation(lineFormatName: string, formatterFunction: () => void, lineOp: any) {
        describe(`${lineFormatName} inline format preservation`, () => {
            inlineFormatOps.forEach(({ op, name }) => {
                it(`preserves the ${name} format when added`, () => {
                    const initial = [op];
                    const expected = [op, lineOp];
                    assertQuillInputOutput(initial, expected, formatterFunction);
                });
            });
        });
    }

    function testNoLineFormatInlinePreservation(lineFormatName: string, formatterFunction: () => void, lineOp: any) {
        describe(`${lineFormatName} inline format preservation`, () => {
            inlineFormatOps.forEach(({ op, name }) => {
                it(`it removes the ${name} format when added`, () => {
                    const initial = [op];
                    const expected = [OpUtils.op(), lineOp];
                    assertQuillInputOutput(initial, expected, formatterFunction);
                });
            });
        });
    }

    function testLineFormatExclusivity(lineFormatName: string, formatterFunction: () => void, lineOp: any) {
        describe(`${lineFormatName} line format exclusivity`, () => {
            blockFormatOps.forEach(({ op, name }) => {
                it(`it removes the ${name} format`, () => {
                    const initial = [OpUtils.op(), op];
                    const expected = [OpUtils.op(), lineOp];
                    assertQuillInputOutput(initial, expected, formatterFunction);
                });
            });
        });
    }

    function testCodeBlockFormatStripping(formatterFunction: () => void) {
        it("can strip all other formats before applying the codeBlock format", () => {
            const initial = [
                { insert: "asd" },
                { attributes: { link: "http://vanillafactory.spawn/en/post/discussion/asdfasdf" }, insert: "f asd" },
                { insert: "f " },
                {
                    attributes: { strike: true, italic: true, bold: true },
                    insert: { mention: { name: "member", userID: 8 } },
                },
                { attributes: { strike: true }, insert: "  " },
                { attributes: { strike: true, bold: true }, insert: "asd" },
                { attributes: { bold: true }, insert: "fasdf " },
                { attributes: { italic: true, bold: true }, insert: "asl;dfjad" },
                { attributes: { italic: true, bold: true, codeInline: true }, insert: "asdf" },
                { attributes: { italic: true, bold: true }, insert: " " },
                { insert: "\n" },
            ];

            const expected = [
                {
                    insert: "asdf asdf @member  asdfasdf asl;dfjadasdf ",
                },
                { attributes: { [CodeBlockBlot.blotName]: true }, insert: "\n" },
            ];
            assertQuillInputOutput(initial, expected, formatterFunction);
        });
    }

    function testMultiLineFormatting(
        lineFormatName: string,
        format: (range: RangeStatic) => void,
        lineOp: any,
        needsExtraNewLine: boolean = false,
    ) {
        it(`can apply the ${lineFormatName} format to multiple lines`, () => {
            const initial = [
                OpUtils.op(),
                OpUtils.newline(),
                OpUtils.op(),
                OpUtils.newline(),
                OpUtils.op(),
                OpUtils.newline(),
            ];
            const expected = [OpUtils.op(), lineOp, OpUtils.op(), lineOp, OpUtils.op(), lineOp];
            const formatterFunction = () => format(getFullRange());
            assertQuillInputOutput(initial, expected, formatterFunction);
        });

        describe(`can apply the ${lineFormatName} format to single line of all other multiline blots`, () => {
            blockFormatOps.filter(({ name }) => name !== lineFormatName).forEach(({ op, name }) => {
                const one = OpUtils.op("1");
                const two = OpUtils.op("2");
                const three = OpUtils.op("3");
                const initial = [one, op, two, op, three, op];

                it(`can apply the ${lineFormatName} format to the 1st line of 3 lines of the ${name} format`, () => {
                    const expected = [one, lineOp, two, op, three, op];
                    const range: RangeStatic = { index: 0, length: 0 };
                    const formatterFunction = () => format(range);
                    assertQuillInputOutput(initial, expected, formatterFunction);
                });

                it(`can apply the ${lineFormatName} format to the 2nd line of 3 lines of the ${name} format`, () => {
                    const expected = [one, op, two, lineOp, three, op];
                    const range: RangeStatic = { index: 2, length: 0 };
                    const formatterFunction = () => format(range);
                    assertQuillInputOutput(initial, expected, formatterFunction);
                });

                it(`can apply the ${lineFormatName} format to the 3rd line of 3 lines of the ${name} format`, () => {
                    const expected = [one, op, two, op, three, lineOp];
                    const range: RangeStatic = { index: 4, length: 0 };
                    const formatterFunction = () => format(range);
                    assertQuillInputOutput(initial, expected, formatterFunction);
                });
            });
        });

        // Check formatting over multiple lines (splitting things properly as well).
    }
});
