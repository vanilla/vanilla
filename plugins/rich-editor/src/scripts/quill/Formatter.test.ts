/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import Quill from "./index";
import Formatter from "@rich-editor/quill/Formatter";
import { RangeStatic } from "quill/core";
import { expect } from "chai";
import cloneDeep from "lodash/cloneDeep";
import OpUtils from "@rich-editor/__tests__/opUtils";

describe("Formatter", () => {
    let quill: Quill;
    let formatter: Formatter;

    function getFullRange(): RangeStatic {
        return {
            index: 0,
            length: quill.scroll.length() - 1,
        };
    }

    beforeEach(() => {
        document.body.innerHTML = `
            <div id="quill"></div>
        `;
        quill = new Quill("#quill");
        formatter = new Formatter(quill);
    });

    function testStackedInlineFormatting(
        formatToTest: string,
        formatterFunction: () => void,
        testAgainst: Exclude<Array<keyof typeof OpUtils>, "prototype">,
        enableValue: any = true,
    ) {
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
    }

    function testBasicFormatting(formattingFunction: () => void, finalOpCreator: () => any) {
        const ops = [OpUtils.op()];
        quill.setContents(ops, Quill.sources.USER);
        formattingFunction();
        const result = quill.getContents().ops;
        expect(result).deep.equals([finalOpCreator(), OpUtils.newline()]);
    }

    describe("bold()", () => {
        const formattingFunction = () => formatter.bold(getFullRange());
        it("Can format plainText", () => {
            testBasicFormatting(formattingFunction, OpUtils.bold);
        });

        describe("Adding bold to existing inline formats", () => {
            testStackedInlineFormatting("bold", formattingFunction, ["italic", "strike", "link", "codeInline"]);
        });
    });

    describe("italic()", () => {
        const formattingFunction = () => formatter.italic(getFullRange());
        it("Can format plainText", () => {
            testBasicFormatting(formattingFunction, OpUtils.italic);
        });

        describe("Adding italic to existing inline formats", () => {
            testStackedInlineFormatting("italic", formattingFunction, ["bold", "strike", "link", "codeInline"]);
        });
    });

    describe("strike()", () => {
        const formattingFunction = () => formatter.strike(getFullRange());
        it("Can format plainText", () => {
            testBasicFormatting(formattingFunction, OpUtils.strike);
        });

        describe("Adding strike to existing inline formats", () => {
            testStackedInlineFormatting("strike", formattingFunction, ["bold", "italic", "link", "codeInline"]);
        });
    });

    describe("link()", () => {
        const formattingFunction = () => formatter.link(getFullRange(), OpUtils.DEFAULT_LINK);
        it("Can format plainText", () => {
            testBasicFormatting(formattingFunction, OpUtils.link);
        });

        describe("Adding link to existing inline formats", () => {
            testStackedInlineFormatting(
                "link",
                formattingFunction,
                ["bold", "italic", "strike", "codeInline"],
                OpUtils.DEFAULT_LINK,
            );
        });
    });

    describe("codeInline()", () => {
        const formattingFunction = () => formatter.codeInline(getFullRange());
        it("Can format plainText", () => {
            testBasicFormatting(formattingFunction, OpUtils.codeInline);
        });

        describe("Adding code to existing inline formats", () => {
            testStackedInlineFormatting("code-inline", formattingFunction, ["bold", "italic", "strike", "link"]);
        });
    });
});
