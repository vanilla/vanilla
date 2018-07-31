/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import Quill from "./index";
import Formatter from "@rich-editor/quill/Formatter";
import { RangeStatic } from "quill/core";
import { expect } from "chai";
import OpUtils, { inlineFormatOps, blockFormatOps } from "@rich-editor/__tests__/opUtils";

describe("Formatter", () => {
    let quill: Quill;
    let formatter: Formatter;

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
        testStackedInlineFormatting("bold", formattingFunction, ["italic", "strike", "link", "codeInline"]);
        testInlineAgainstLineFormatting("bold", formattingFunction);
    });

    describe("italic()", () => {
        const formattingFunction = () => formatter.italic(getFullRange());
        testBasicInlineFormatting(formattingFunction, OpUtils.italic);
        testStackedInlineFormatting("italic", formattingFunction, ["bold", "strike", "link", "codeInline"]);
        testInlineAgainstLineFormatting("italic", formattingFunction);
    });

    describe("strike()", () => {
        const formattingFunction = () => formatter.strike(getFullRange());
        testBasicInlineFormatting(formattingFunction, OpUtils.strike);
        testStackedInlineFormatting("strike", formattingFunction, ["bold", "italic", "link", "codeInline"]);
        testInlineAgainstLineFormatting("strike", formattingFunction);
    });

    describe("link()", () => {
        const formattingFunction = () => formatter.link(getFullRange(), OpUtils.DEFAULT_LINK);
        testBasicInlineFormatting(formattingFunction, OpUtils.link);
        testStackedInlineFormatting(
            "link",
            formattingFunction,
            ["bold", "italic", "strike", "codeInline"],
            OpUtils.DEFAULT_LINK,
        );
        testInlineAgainstLineFormatting("link", formattingFunction, OpUtils.DEFAULT_LINK);
    });

    describe("codeInline()", () => {
        const formattingFunction = () => formatter.codeInline(getFullRange());
        testBasicInlineFormatting(formattingFunction, OpUtils.codeInline);
        testStackedInlineFormatting("code-inline", formattingFunction, ["bold", "italic", "strike", "link"]);
        testInlineAgainstLineFormatting("code-inline", formattingFunction);
    });

    describe("h2()", () => {
        const formattingFunction = () => formatter.h2(getFullRange());
        testLineFormatInlinePreservation("h2", formattingFunction, OpUtils.heading(2));
        testLineFormatExclusivity("h2", formattingFunction, OpUtils.heading(2));
    });

    describe("h3()", () => {
        const formattingFunction = () => formatter.h3(getFullRange());
        testLineFormatInlinePreservation("h3", formattingFunction, OpUtils.heading(3));
        testLineFormatExclusivity("h3", formattingFunction, OpUtils.heading(3));
    });

    describe("blockquote()", () => {
        const formattingFunction = () => formatter.blockquote(getFullRange());
        testLineFormatInlinePreservation("blockquote", formattingFunction, OpUtils.quoteLine());
        testLineFormatExclusivity("blockquote", formattingFunction, OpUtils.quoteLine());
    });

    describe("spoiler()", () => {
        const formattingFunction = () => formatter.spoiler(getFullRange());
        testLineFormatInlinePreservation("spoiler", formattingFunction, OpUtils.spoilerLine());
        testLineFormatExclusivity("spoiler", formattingFunction, OpUtils.spoilerLine());
    });

    describe("codeBlock()", () => {
        const formattingFunction = () => formatter.codeBlock(getFullRange());
        testNoLineFormatInlinePreservation("codeBlock", formattingFunction, OpUtils.codeBlock());
        testLineFormatExclusivity("codeBlock", formattingFunction, OpUtils.codeBlock(), true);
    });

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
        testAgainst: Exclude<Array<keyof typeof OpUtils>, "prototype">,
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
                    quill.setContents(initial, Quill.sources.USER);
                    formatterFunction();
                    const result = quill.getContents().ops;
                    expect(result).deep.equals(expected);
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
                    quill.setContents(initial, Quill.sources.USER);
                    formatterFunction();
                    const result = quill.getContents().ops;
                    expect(result).deep.equals(expected);
                });
            });

            it("Does nothing to Code Blocks", () => {
                const initial = [OpUtils.op(), OpUtils.codeBlock()];
                const expected = [OpUtils.op(), OpUtils.codeBlock()];
                quill.setContents(initial, Quill.sources.USER);
                formatterFunction();
                const result = quill.getContents().ops;
                expect(result).deep.equals(expected);
            });
        });
    }

    function testBasicInlineFormatting(formattingFunction: () => void, finalOpCreator: () => any) {
        it("Can format plainText", () => {
            const ops = [OpUtils.op()];
            quill.setContents(ops, Quill.sources.USER);
            formattingFunction();
            const result = quill.getContents().ops;
            expect(result).deep.equals([finalOpCreator(), OpUtils.newline()]);
        });
    }

    function testLineFormatInlinePreservation(lineFormatName: string, formatterFunction: () => void, lineOp: any) {
        describe(`${lineFormatName} inline format preservation`, () => {
            inlineFormatOps.forEach(({ op, name }) => {
                it(`preserves the ${name} format when added`, () => {
                    const initial = [op];
                    const expected = [op, lineOp];
                    quill.setContents(initial, Quill.sources.USER);
                    formatterFunction();
                    const result = quill.getContents().ops;
                    expect(result).deep.equals(expected);
                });
            });
        });
    }

    function testNoLineFormatInlinePreservation(lineFormatName: string, formatterFunction: () => void, lineOp: any) {
        describe(`${lineFormatName} inline format preservation`, () => {
            inlineFormatOps.forEach(({ op, name }) => {
                it(`it removes the ${name} format when added`, () => {
                    const initial = [op];
                    const expected = [OpUtils.op(), lineOp, OpUtils.newline()];
                    quill.setContents(initial, Quill.sources.USER);
                    formatterFunction();
                    const result = quill.getContents().ops;
                    expect(result).deep.equals(expected);
                });
            });
        });
    }

    function testLineFormatExclusivity(
        lineFormatName: string,
        formatterFunction: () => void,
        lineOp: any,
        needsExtraNewLine: boolean = false,
    ) {
        describe(`${lineFormatName} line format exclusivity`, () => {
            blockFormatOps.forEach(({ op, name }) => {
                it(`it removes the ${name} format`, () => {
                    const initial = [OpUtils.op(), op];
                    const expected = [OpUtils.op(), lineOp];
                    if (needsExtraNewLine) {
                        expected.push(OpUtils.newline());
                    }
                    quill.setContents(initial, Quill.sources.USER);
                    formatterFunction();
                    const result = quill.getContents().ops;
                    expect(result).deep.equals(expected);
                });
            });
        });
    }
});
