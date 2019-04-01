/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ListType } from "@rich-editor/quill/blots/blocks/ListBlot";
import ClipboardModule from "@rich-editor/quill/ClipboardModule";
import OpUtils from "@rich-editor/__tests__/OpUtils";
import { setupTestQuill } from "@rich-editor/__tests__/quillUtils";
import { expect } from "chai";
import Quill, { DeltaOperation } from "quill/core";
import { isArray } from "util";

describe("ClipboardModule", () => {
    let quill: Quill;
    let clipboard: ClipboardModule;
    beforeEach(() => {
        quill = setupTestQuill();
        clipboard = quill.clipboard as ClipboardModule;
    });

    describe("pasteHtml", () => {
        describe("simple values", () => {
            const testSimplePaste = (value: string | string[]) => {
                let text = value;
                let expected = text + "\n";
                if (isArray(text)) {
                    [text, expected] = value;
                    expected = expected === undefined ? text + "\n" : expected + "\n";
                }
                clipboard.dangerouslyPasteHTML(text);
                expect(quill.getContents().ops).deep.eq([{ insert: expected }]);
            };
            const simpleTexts = [
                "Hello world",
                "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Magna fringilla urna porttitor rhoncus dolor purus non. Congue eu consequat ac felis donec. Habitant morbi tristique senectus et netus et. Iaculis eu non diam phasellus vestibulum lorem. Volutpat est velit egestas dui id ornare.",
                ["Line1\n\n\nLine4", "Line1 Line4"], // New lines are not interpretted literally.
                ["Trailing newlines\n\n\n\n\n\n\n", "Trailing newlines"],
            ];
            simpleTexts.forEach((value, index) => {
                it(`handles simple text pasting (${index})`, () => {
                    testSimplePaste(value);
                });
            });
        });

        describe("list items", () => {
            interface ITestValue {
                description?: string;
                in: string;
                out: DeltaOperation[];
            }
            const listData: ITestValue[] = [
                {
                    in: `<li>Item 1</li>`,
                    out: [OpUtils.op("Item 1"), OpUtils.list(ListType.BULLETED)],
                },
                {
                    in: `<ul><li>Unordered</li></ul>`,
                    out: [OpUtils.op("Unordered"), OpUtils.list(ListType.BULLETED)],
                },
                {
                    in: `<ol><li>Ordered</li></ol>`,
                    out: [OpUtils.op("Ordered"), OpUtils.list(ListType.ORDERED)],
                },
                {
                    in: `<ul><li>Unordered</li></ul><ol><li>Ordered</li></ol>`,
                    out: [
                        OpUtils.op("Unordered"),
                        OpUtils.list(ListType.BULLETED),
                        OpUtils.op("Ordered"),
                        OpUtils.list(ListType.ORDERED),
                    ],
                },
                {
                    // Nesting same list type
                    in: `
                    <ul>
                        <li>
                            Line 1
                            <ul>
                                <li>Line 1.1</li>
                                <li>Line 1.2</li>
                            </ul>
                        </li>
                        <li>Line 2</li>
                    </ul>`,
                    out: [
                        OpUtils.op("Line 1"),
                        OpUtils.list(ListType.BULLETED, 0),
                        OpUtils.op("Line 1.1"),
                        OpUtils.list(ListType.BULLETED, 1),
                        OpUtils.op("Line 1.2"),
                        OpUtils.list(ListType.BULLETED, 1),
                        OpUtils.op("Line 2"),
                        OpUtils.list(ListType.BULLETED, 0),
                    ],
                },
                {
                    // Nesting different list types
                    in: `
                        <ul>
                            <li>
                                Line 1
                                <ol>
                                    <li>Line 1.1</li>
                                    <li>Line 1.2</li>
                                </ol>
                            </li>
                            <li>Line 2</li>
                        </ul>`,
                    out: [
                        OpUtils.op("Line 1"),
                        OpUtils.list(ListType.BULLETED, 0),
                        OpUtils.op("Line 1.1"),
                        OpUtils.list(ListType.ORDERED, 1),
                        OpUtils.op("Line 1.2"),
                        OpUtils.list(ListType.ORDERED, 1),
                        OpUtils.op("Line 2"),
                        OpUtils.list(ListType.BULLETED, 0),
                    ],
                },
                {
                    description: "Nesting very deep lists of the same type",
                    in: `
                        <ul>
                            <li>
                                Line 1
                                <ul>
                                    <li>
                                        Line 1.1
                                        <ul>
                                            <li>
                                                Line 1.1.1
                                                <ul>
                                                    <li>Line 1.1.1.1</li>
                                                </ul
                                            </li>
                                        </ul>
                                    </li>
                                </ul>
                            </li>
                        </ul>`,
                    out: [
                        OpUtils.op("Line 1"),
                        OpUtils.list(ListType.BULLETED, 0),
                        OpUtils.op("Line 1.1"),
                        OpUtils.list(ListType.BULLETED, 1),
                        OpUtils.op("Line 1.1.1"),
                        OpUtils.list(ListType.BULLETED, 2),
                        OpUtils.op("Line 1.1.1.1"),
                        OpUtils.list(ListType.BULLETED, 3),
                    ],
                },
                {
                    description: "Nesting very deep lists of different types",
                    in: `
                        <ul>
                            <li>
                                Line 1
                                <ol>
                                    <li>
                                        Line 1.1
                                        <ul>
                                            <li>
                                                Line 1.1.1
                                                <ol>
                                                    <li>Line 1.1.1.1</li>
                                                </ol
                                            </li>
                                        </ul>
                                    </li>
                                </ol>
                            </li>
                        </ul>`,
                    out: [
                        OpUtils.op("Line 1"),
                        OpUtils.list(ListType.BULLETED, 0),
                        OpUtils.op("Line 1.1"),
                        OpUtils.list(ListType.ORDERED, 1),
                        OpUtils.op("Line 1.1.1"),
                        OpUtils.list(ListType.BULLETED, 2),
                        OpUtils.op("Line 1.1.1.1"),
                        OpUtils.list(ListType.ORDERED, 3),
                    ],
                },
            ];

            listData.forEach((item, index) => {
                const extraDescription = item.description ? ` - ${item.description}` : "";
                it(`can paste a list item ${index}${extraDescription}`, () => {
                    clipboard.dangerouslyPasteHTML(item.in);
                    expect(quill.getContents().ops).deep.eq(item.out);
                });
            });
        });
    });

    describe("splitLinkOperationsOutOfText()", () => {
        it("Can parse out a single link", () => {
            const link = "https://test.com";
            const input = `${link}`;
            const expected = [{ insert: link, attributes: { link } }];

            expect(ClipboardModule.splitLinkOperationsOutOfText(input)).deep.equals(expected);
        });

        it("Can parse out multiple links a single link", () => {
            const link = "https://test.com";
            const link2 = "https://othertest.com";
            const input = `text${link} moreText\n\n\n${link2}`;
            const expected = [
                { insert: "text" },
                { insert: link, attributes: { link } },
                { insert: " moreText\n\n\n" },
                { insert: link2, attributes: { link: link2 } },
            ];

            expect(ClipboardModule.splitLinkOperationsOutOfText(input)).deep.equals(expected);
        });

        it("Doesn't alter operations when no links were found.", () => {
            const input = `asdfasdfasfd\n\n\nasdfasdfhtt://asd http// ssh://asdfasfd`;
            expect(ClipboardModule.splitLinkOperationsOutOfText(input)).equals(null);
        });
    });
});
