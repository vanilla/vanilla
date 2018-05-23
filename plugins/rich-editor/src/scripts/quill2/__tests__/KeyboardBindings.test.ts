/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import Delta from "quill-delta";
import Quill from "../../Quill";
import KeyboardBindings from "../KeyboardBindings";

const LINE_FORMATS = ["blockquote-line", "spoiler-line"];

const MULTI_LINE_FORMATS = [...LINE_FORMATS, "code-block"];

describe("KeyboardBindings", () => {
    let quill: Quill;

    let keyboardBindings: KeyboardBindings;

    beforeAll(() => {
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

    function testMultilineEnterFor(blotName) {
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
        expect(quill.getContents().ops).toEqual(expectedResult);
    }

    describe("handleMultilineEnter", () => {
        LINE_FORMATS.forEach(format => {
            test(format, () => testMultilineEnterFor(format));
        });
    });

    test("handleCodeBlockEnter", () => {
        const delta = new Delta().insert("line\n\n\n", {
            "code-block": true,
        });
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
                    ["code-block"]: true,
                },
            },
            {
                insert: "\n",
            },
        ];
        expect(quill.getContents().ops).toEqual(expectedResult);
    });

    /** ARROW KEYS */

    describe("Newline escapes", () => {
        function testInsertNewlineBefore(blotName) {
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

            expect(quill.getContents().ops).toEqual(expectedResult);
        }

        function testInsertNewlineAfter(blotName) {
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

            expect(quill.getContents().ops).toEqual(expectedResult);
        }

        describe("insertNewLineBeforeRange", () => {
            MULTI_LINE_FORMATS.forEach(format => {
                test(format, () => testInsertNewlineBefore(format));
            });
        });

        describe("insertNewLineAfterRange", () => {
            MULTI_LINE_FORMATS.forEach(format => {
                test(format, () => testInsertNewlineAfter(format));
            });
        });
    });

    /** DELETING THINGS */

    function testBackspaceToClearEmptyFor(blotName) {
        const contents = [{ insert: "123\n" }, { attributes: { [blotName]: true }, insert: "\n" }, { insert: "45\n" }];

        quill.setContents(contents);

        const selection = {
            index: 4,
            length: 0,
        };

        if (blotName === "code-block") {
            keyboardBindings.handleCodeBlockBackspace(selection);
        } else {
            keyboardBindings.handleMultiLineBackspace(selection);
        }

        const expectedResult = [{ insert: "123\n\n45\n" }];
        expect(quill.getContents().ops).toEqual(expectedResult);
    }

    describe("back space to delete empty blot", () => {
        MULTI_LINE_FORMATS.forEach(format => {
            test(format, () => testBackspaceToClearEmptyFor(format));
        });
    });

    function testBackSpaceAtStartFor(blotName) {
        const contents = [{ insert: "123" }, { attributes: { [blotName]: true }, insert: "\n" }, { insert: "45\n" }];

        quill.setContents(contents);

        const selection = {
            index: 0,
            length: 0,
        };

        keyboardBindings.handleBlockStartDelete(selection);

        const expectedResult = [{ insert: "123\n45\n" }];
        expect(quill.getContents().ops).toEqual(expectedResult);
    }

    describe("back space to clear first blot formatting", () => {
        MULTI_LINE_FORMATS.forEach(format => {
            test(format, () => testBackSpaceAtStartFor(format));
        });
    });
});
