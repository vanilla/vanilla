/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Formatter from "@rich-editor/quill/Formatter";
import Quill, { RangeStatic, DeltaOperation } from "quill/core";
import { expect } from "chai";
import OpUtils, { inlineFormatOps, blockFormatOps } from "@rich-editor/__tests__/OpUtils";
import registerQuill from "./registerQuill";
import CodeBlockBlot from "@rich-editor/quill/blots/blocks/CodeBlockBlot";
import { ListType, ListItem } from "@rich-editor/quill/blots/blocks/ListBlot";
import cloneDeep from "lodash/cloneDeep";

describe("Formatter", () => {
    let quill: Quill;

    before(() => {
        registerQuill();
    });

    beforeEach(() => {
        document.body.innerHTML = `
            <div id="quill"></div>
        `;
        quill = new Quill("#quill");
    });

    const makeFormatter = (range: RangeStatic = getFullRange()): Formatter => {
        return new Formatter(quill, range);
    };

    describe("bold()", () => {
        const formattingFunction = (range?: RangeStatic) => makeFormatter(range).bold();
        testBasicInlineFormatting(formattingFunction, OpUtils.bold);
        testStackedInlineFormatting("bold", formattingFunction, ["italic", "strike", "link", "code"]);
        testInlineAgainstLineFormatting("bold", formattingFunction);
    });

    describe("italic()", () => {
        const formattingFunction = (range?: RangeStatic) => makeFormatter(range).italic();
        testBasicInlineFormatting(formattingFunction, OpUtils.italic);
        testStackedInlineFormatting("italic", formattingFunction, ["bold", "strike", "link", "code"]);
        testInlineAgainstLineFormatting("italic", formattingFunction);
    });

    describe("strike()", () => {
        const formattingFunction = (range?: RangeStatic) => makeFormatter(range).strike();
        testBasicInlineFormatting(formattingFunction, OpUtils.strike);
        testStackedInlineFormatting("strike", formattingFunction, ["bold", "italic", "link", "code"]);
        testInlineAgainstLineFormatting("strike", formattingFunction);
    });

    describe("link()", () => {
        const formattingFunction = (range?: RangeStatic) => makeFormatter(range).link(OpUtils.DEFAULT_LINK);
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
        const formattingFunction = (range?: RangeStatic) => makeFormatter(range).codeInline();
        testBasicInlineFormatting(formattingFunction, OpUtils.code);
        testStackedInlineFormatting("code", formattingFunction, ["bold", "italic", "strike", "link"]);
        testInlineAgainstLineFormatting("code", formattingFunction);
    });

    describe("h2()", () => {
        const formattingFunction = (range?: RangeStatic) => makeFormatter(range).h2();
        testLineFormatInlinePreservation("h2", formattingFunction, OpUtils.heading(2));
        testLineFormatExclusivity("h2", formattingFunction, OpUtils.heading(2));
        testMultiLineFormatting("h2", formattingFunction, OpUtils.heading(2));
    });

    describe("h3()", () => {
        const formattingFunction = (range?: RangeStatic) => makeFormatter(range).h3();
        testLineFormatInlinePreservation("h3", formattingFunction, OpUtils.heading(3));
        testLineFormatExclusivity("h3", formattingFunction, OpUtils.heading(3));
        testMultiLineFormatting("h3", formattingFunction, OpUtils.heading(3));
    });

    describe("blockquote()", () => {
        const formattingFunction = (range?: RangeStatic) => makeFormatter(range).blockquote();
        testLineFormatInlinePreservation("blockquote", formattingFunction, OpUtils.quoteLine());
        testLineFormatExclusivity("blockquote", formattingFunction, OpUtils.quoteLine());
        testMultiLineFormatting("blockquote-line", formattingFunction, OpUtils.quoteLine());
    });

    describe("spoiler()", () => {
        const formattingFunction = (range?: RangeStatic) => makeFormatter(range).spoiler();
        testLineFormatInlinePreservation("spoiler", formattingFunction, OpUtils.spoilerLine());
        testLineFormatExclusivity("spoiler", formattingFunction, OpUtils.spoilerLine());
        testMultiLineFormatting("spoiler-line", formattingFunction, OpUtils.spoilerLine());
    });

    describe("codeBlock()", () => {
        const formattingFunction = (range?: RangeStatic) => makeFormatter(range).codeBlock();
        testNoLineFormatInlinePreservation(CodeBlockBlot.blotName, formattingFunction, OpUtils.codeBlock());
        testLineFormatExclusivity(CodeBlockBlot.blotName, formattingFunction, OpUtils.codeBlock());
        testCodeBlockFormatStripping(formattingFunction);
    });

    describe("orderedList()", () => {
        const formattingFunction = (range?: RangeStatic) => makeFormatter(range).orderedList();
        testLineFormatInlinePreservation("orderedList", formattingFunction, OpUtils.list(ListType.ORDERED));
        testLineFormatExclusivity("orderedList", formattingFunction, OpUtils.list(ListType.ORDERED));
        testMultiLineFormatting("orderedList", formattingFunction, OpUtils.list(ListType.ORDERED));
    });

    describe("bulletedList()", () => {
        const formattingFunction = (range?: RangeStatic) => makeFormatter(range).bulletedList();
        testLineFormatInlinePreservation("bulletedList", formattingFunction, OpUtils.list(ListType.BULLETED));
        testLineFormatExclusivity("bulletedList", formattingFunction, OpUtils.list(ListType.BULLETED));
        testMultiLineFormatting("bulletedList", formattingFunction, OpUtils.list(ListType.BULLETED));
    });

    describe("paragraph()", () => {
        const formattingFunction = (range?: RangeStatic) => makeFormatter(range).paragraph();
        testLineFormatInlinePreservation("paragraph", formattingFunction, OpUtils.newline());
        testLineFormatExclusivity("paragraph", formattingFunction, OpUtils.newline());
        testMultiLineFormatting("paragraph", formattingFunction, OpUtils.newline());
    });

    function assertQuillInputOutput(input: any[], expectedOutput: any[], formattingFunction: () => void) {
        quill.setContents(input, Quill.sources.USER);
        formattingFunction();

        // Kludge out the dynamically generated refs.
        const stripRefs = op => {
            if (op.attributes && op.attributes.header && op.attributes.header.ref) {
                op.attributes.header.ref = "";
            }
            return op;
        };

        const normalize = (ops: DeltaOperation[]) => {
            return cloneDeep(ops)
                .map(stripRefs)
                .reduce((acc: DeltaOperation[], current: DeltaOperation) => {
                    const lastValue = acc[acc.length - 1];
                    if (
                        lastValue &&
                        !current.attributes &&
                        !lastValue.attributes &&
                        typeof lastValue.insert === "string" &&
                        typeof current.insert === "string"
                    ) {
                        lastValue.insert += current.insert;
                    } else {
                        acc.push(current);
                    }
                    return acc;
                }, []);
        };

        const result = normalize(quill.getContents().ops!);
        expectedOutput = normalize(expectedOutput);
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
            { op: OpUtils.list(ListType.ORDERED), name: "ordered list" },
            { op: OpUtils.list(ListType.BULLETED), name: "bulleted list" },
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

    function testMultiLineFormatting(lineFormatName: string, format: (range: RangeStatic) => void, lineOp: any) {
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

        it(`can be unformatted using the paragraph format`, () => {
            const initial = [OpUtils.op(), lineOp, OpUtils.op(), lineOp, OpUtils.op(), lineOp];
            const opInsert = OpUtils.op().insert;
            const expected = [OpUtils.op(`${opInsert}\n${opInsert}\n${opInsert}\n`)];

            const formatterFunction = () => makeFormatter().paragraph();
            assertQuillInputOutput(initial, expected, formatterFunction);
        });

        it(`can be formatted over a nested list`, () => {
            const initial = [
                OpUtils.op(),
                OpUtils.list(ListType.BULLETED, 0),
                OpUtils.op(),
                OpUtils.list(ListType.BULLETED, 1),
                OpUtils.op(),
                OpUtils.list(ListType.BULLETED, 2),
                OpUtils.op(),
                OpUtils.list(ListType.BULLETED, 3),
                OpUtils.op(),
                OpUtils.list(ListType.BULLETED, 1),
                OpUtils.op(),
                OpUtils.list(ListType.BULLETED, 0),
            ];

            const expected = [
                OpUtils.op(),
                lineOp,
                OpUtils.op(),
                lineOp,
                OpUtils.op(),
                lineOp,
                OpUtils.op(),
                lineOp,
                OpUtils.op(),
                lineOp,
                OpUtils.op(),
                lineOp,
            ];
            const formatterFunction = () => format(getFullRange());
            assertQuillInputOutput(initial, expected, formatterFunction);
        });

        describe(`can apply the ${lineFormatName} format to single line of all other multiline blots`, () => {
            blockFormatOps
                .filter(({ name }) => name !== lineFormatName)
                .forEach(({ op, name }) => {
                    const one = OpUtils.op("1");
                    const two = OpUtils.op("2");
                    const three = OpUtils.op("3");
                    const initial = [one, op, two, op, three, op];

                    it(`--- apply the ${lineFormatName} format to the 1st line of 3 lines of the ${name} format`, () => {
                        const expected = [one, lineOp, two, op, three, op];
                        const range: RangeStatic = { index: 0, length: 0 };
                        const formatterFunction = () => format(range);
                        assertQuillInputOutput(initial, expected, formatterFunction);
                    });

                    it(`--- apply the ${lineFormatName} format to the 2nd line of 3 lines of the ${name} format`, () => {
                        const expected = [one, op, two, lineOp, three, op];
                        const range: RangeStatic = { index: 2, length: 0 };
                        const formatterFunction = () => format(range);
                        assertQuillInputOutput(initial, expected, formatterFunction);
                    });

                    it(`--- apply the ${lineFormatName} format to the 3rd line of 3 lines of the ${name} format`, () => {
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
