/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Delta from "quill-delta";
import Quill from "quill/core";
import KeyboardBindings from "@rich-editor/quill/KeyboardBindings";
import { expect } from "chai";
import CodeBlockBlot from "@rich-editor/quill/blots/blocks/CodeBlockBlot";
import OpUtils from "@rich-editor/__tests__/OpUtils";
const LINE_FORMATS = ["blockquote-line", "spoiler-line"];

const MULTI_LINE_FORMATS = [...LINE_FORMATS, CodeBlockBlot.blotName];

describe("KeyboardBindings", () => {
    let quill: Quill;

    let keyboardBindings: KeyboardBindings;

    before(() => {
        document.body.innerHTML = `<form><div class="js-richText"></div></form>`;

        const container = document.querySelector(".js-richText");

        quill = new Quill(container as HTMLElement);

        // Selection doesn't work in JSDom.
        quill.setSelection = () => {
            return;
        };
        keyboardBindings = new KeyboardBindings(quill);
    });

    /** CUSTOM ENTER KEY BEHAVIOUR */

    function itMultilineEnterFor(blotName) {
        const delta = new Delta().insert("line\n\n", {
            [blotName]: true,
        });
        quill.setContents(delta);

        // Place selection one the second line (newline);
        const selection = {
            index: 5,
            length: 0,
        };
        keyboardBindings.handleMultilineEnter(selection);

        const expectedResult = [
            { insert: "line" },
            {
                insert: "\n",
                attributes: {
                    [blotName]: true,
                },
            },
            {
                insert: "\n",
            },
        ];
        expect(quill.getContents().ops).deep.equals(expectedResult);
    }

    describe("handleMultilineEnter", () => {
        LINE_FORMATS.forEach(format => {
            it(format, () => itMultilineEnterFor(format));
        });
    });

    describe("handleCodeBlockEnter", () => {
        it("enter at the end", () => {
            const delta = [OpUtils.op("line"), OpUtils.codeBlock("\n\n\n")];

            quill.setContents(delta);

            // Place selection one the second line (newline);
            const selection = {
                index: 6,
                length: 0,
            };
            keyboardBindings.handleCodeBlockEnter(selection);

            const expectedResult = [
                { insert: "line" },
                {
                    insert: "\n",
                    attributes: {
                        [CodeBlockBlot.blotName]: true,
                    },
                },
                {
                    insert: "\n",
                },
            ];
            expect(quill.getContents().ops).deep.equals(expectedResult);
        });

        it("ignore enter in the middle", () => {
            const delta = [OpUtils.op("line"), OpUtils.codeBlock("\n\n\n")];

            quill.setContents(delta);

            // Place selection in the middle of the blot.
            const selection = {
                index: 4,
                length: 0,
            };
            keyboardBindings.handleCodeBlockEnter(selection);

            // Nothing changed.
            const expectedResult = delta;
            expect(quill.getContents().ops).deep.equals(expectedResult);
        });
    });

    /** ARROW KEYS */

    describe("Newline escapes", () => {
        function itInsertNewlineBefore(blotName) {
            const delta = new Delta().insert("line\n\n", {
                [blotName]: true,
            });

            quill.setContents(delta);

            // Place selection at the beginning
            const selection = {
                index: 0,
                length: 0,
            };

            keyboardBindings.insertNewlineBeforeRange(selection);

            const expectedResult = [
                { insert: "\nline" }, // Yes this actually how quill represents the newline before here!!
                {
                    insert: "\n\n",
                    attributes: {
                        [blotName]: true,
                    },
                },
            ];

            expect(quill.getContents().ops).deep.equals(expectedResult);
        }

        function itInsertNewlineAfter(blotName) {
            const initialValue = [
                { insert: "1" },
                { attributes: { [blotName]: true }, insert: "\n\n\n" },
                { insert: "4" },
                { attributes: { [blotName]: true }, insert: "\n" },
            ];

            quill.setContents(initialValue);

            // Place selection at last line
            const selection = {
                index: 5,
                length: 0,
            };

            keyboardBindings.insertNewlineAfterRange(selection);

            const expectedResult = [
                { insert: "1" },
                { attributes: { [blotName]: true }, insert: "\n\n\n" },
                { insert: "4" },
                { attributes: { [blotName]: true }, insert: "\n" },
                { insert: "\n" },
            ];

            expect(quill.getContents().ops).deep.equals(expectedResult);
        }

        describe("insertNewLineBeforeRange", () => {
            MULTI_LINE_FORMATS.forEach(format => {
                it(format, () => itInsertNewlineBefore(format));
            });
        });

        describe("insertNewLineAfterRange", () => {
            MULTI_LINE_FORMATS.forEach(format => {
                it(format, () => itInsertNewlineAfter(format));
            });
        });
    });

    /** DELETING THINGS */

    function itBackspaceToClearEmptyFor(blotName) {
        const contents = [{ insert: "123\n" }, { attributes: { [blotName]: true }, insert: "\n" }, { insert: "45\n" }];

        quill.setContents(contents);

        const selection = {
            index: 4,
            length: 0,
        };

        if (blotName === CodeBlockBlot.blotName) {
            keyboardBindings.handleCodeBlockBackspace(selection);
        } else {
            keyboardBindings.handleMultiLineBackspace(selection);
        }

        const expectedResult = [{ insert: "123\n\n45\n" }];
        expect(quill.getContents().ops).deep.equals(expectedResult);
    }

    describe("back space to delete empty blot", () => {
        MULTI_LINE_FORMATS.forEach(format => {
            it(format, () => itBackspaceToClearEmptyFor(format));
        });
    });

    function itBackSpaceAtStartFor(blotName) {
        const contents = [{ insert: "123" }, { attributes: { [blotName]: true }, insert: "\n" }, { insert: "45\n" }];

        quill.setContents(contents);

        const selection = {
            index: 0,
            length: 0,
        };

        keyboardBindings.handleBlockStartDelete(selection);

        const expectedResult = [{ insert: "123\n45\n" }];
        expect(quill.getContents().ops).deep.equals(expectedResult);
    }

    describe("back space to clear first blot formatting", () => {
        MULTI_LINE_FORMATS.forEach(format => {
            it(format, () => itBackSpaceAtStartFor(format));
        });
    });
});
