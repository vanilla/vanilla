/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ListType } from "@rich-editor/quill/blots/lists/ListUtils";
import ClipboardModule from "@rich-editor/quill/ClipboardModule";
import OpUtils from "@rich-editor/__tests__/OpUtils";
import { setupTestQuill } from "@rich-editor/__tests__/quillUtils";
import { wait, waitFor } from "@testing-library/react";
import { registerEmbed } from "@vanilla/library/src/scripts/embeddedContent/embedService";
import { IFrameEmbed, supportsFrames } from "@vanilla/library/src/scripts/embeddedContent/IFrameEmbed";
import { expect } from "chai";
import Quill, { DeltaOperation } from "quill/core";

describe("ClipboardModule", () => {
    let quill: Quill;
    let clipboard: ClipboardModule;
    const reset = () => {
        quill = setupTestQuill();
        clipboard = quill.clipboard as ClipboardModule;
    };
    beforeEach(reset);

    describe("pasteHtml", () => {
        describe("simple values", () => {
            const testSimplePaste = (value: string | string[]) => {
                let text = value;
                let expected = text + "\n";
                if (Array.isArray(text)) {
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

            it("can paste it's own type of list blot", () => {
                const ops = [
                    OpUtils.op("Line 1"),
                    OpUtils.list(ListType.BULLETED, 0),
                    OpUtils.op("Line 1.1"),
                    OpUtils.list(ListType.ORDERED, 1),
                    OpUtils.op("Line 1.1.1"),
                    OpUtils.list(ListType.BULLETED, 2),
                    OpUtils.op("Line 1.1.1.1"),
                    OpUtils.list(ListType.ORDERED, 3),
                ];
                quill.setContents(ops);
                const html = quill.scroll.domNode.innerHTML;
                reset();

                clipboard.dangerouslyPasteHTML(html);
                expect(quill.getContents().ops).deep.eq(ops);
            });
        });

        describe("pasting images", () => {
            it("can paste some images", () => {
                const html = `
                    <p><img src="/image1.png" alt="image-1"></p>
                    <img src="/image-no-alt.jpg">
                    <div class="js-embed embedResponsive" contenteditable="false">
                        <div class="embedExternal embedImage">
                            <div class="embed-focusableElement embedExternal-content" aria-label="External embed content - image" tabindex="-1">
                                <a class="embedImage-link" href="/embed-image.jpg" rel="nofollow noopener">
                                    <img class="embedImage-img" src="/embed-image.jpg" alt="image">
                                </a>
                            </div>
                        </div>
                        <span class="sr-only">Embed Description</span>
                    </div>
                `;
                clipboard.dangerouslyPasteHTML(html);

                expect(quill.getContents().ops).deep.eq([
                    OpUtils.image("/image1.png", "image-1"),
                    OpUtils.newline(),
                    OpUtils.image("/image-no-alt.jpg", null),
                    OpUtils.image("/embed-image.jpg", "image"),
                    OpUtils.newline(),
                ]);
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

    describe("Pasting into lists", () => {
        it("can paste content into a list item", () => {
            quill.setContents([OpUtils.op("1"), OpUtils.list()]);
            quill.clipboard.dangerouslyPasteHTML(1, "line");
            expect(quill.getContents().ops).deep.eq([OpUtils.op("1line"), OpUtils.list()]);
        });

        it("can paste contents into an empty list item", () => {
            quill.setContents([OpUtils.list()]);
            quill.clipboard.dangerouslyPasteHTML(0, "line");
            expect(quill.getContents().ops).deep.eq([OpUtils.op("line"), OpUtils.list()]);
        });
    });

    // To be completed, it seems the image cannot be inserted correctly
    // using the provided methods

    describe("Pasting an embed-external into a list", () => {
        it("can paste an image into a simple list of items", async () => {
            const imageUrl =
                "https://avatars0.githubusercontent.com/u/1770056?s=460&u=bd05532b8b91da6ccfe30b6bb598a86332d694dd&v=4";
            const html = `
                <div class="embedImage-link">
                    <img src="${imageUrl}" >
                </div>
            `;
            quill.setContents([OpUtils.op("a"), OpUtils.list(), OpUtils.op("b"), OpUtils.list()]);
            quill.clipboard.dangerouslyPasteHTML(1, html);
            const ops = quill.getContents().ops!;
            await waitFor(() => {
                expect(ops.length).eq(5);
                expect(ops[0].insert).eq("a");
                expect(ops[2].insert["embed-external"].data.url).eq(imageUrl);
                expect(ops[3].insert).eq("b");
            });
        });

        it("can paste a nested list of items", () => {
            const html = `
                <ul>
                    <li>1</li>
                    <li>Nested</li>
                </ul>
            `;
            quill.setContents([OpUtils.op("a"), OpUtils.list(), OpUtils.op("b"), OpUtils.list()]);
            quill.clipboard.dangerouslyPasteHTML(1, html);
            const ops = quill.getContents().ops!;

            // Please note this test is not necessarily of the desirable behaviour.
            // If you are able to make better behaviour please feel free to update this test.

            expect(ops).deep.equals([
                OpUtils.op("a1"),
                OpUtils.list(ListType.BULLETED),
                OpUtils.op("Nested"),
                OpUtils.list(ListType.BULLETED),
                OpUtils.op("b"),
                OpUtils.list(ListType.BULLETED),
            ]);
        });
    });

    describe("Embed conversion", () => {
        const frameSrc = `https://www.example.com/embed/M7lc1UVf-VE?origin=http://example.com`;
        const height = 500;
        const width = 1000;
        const iframeHtml = `
Hello<iframe type="text/html" width="${width}" height="${height}" src="${frameSrc}" frameborder="0"></iframe>
`;

        it("Does not convert when iframes are disabled", () => {
            supportsFrames(false);
            quill.clipboard.dangerouslyPasteHTML(iframeHtml);
            expect(quill.scroll.domNode.innerHTML).equal("<p>Hello</p>");
        });

        it("Does convert when iframes are enabled", async () => {
            supportsFrames(true);
            registerEmbed("iframe", IFrameEmbed);
            reset();
            quill.clipboard.dangerouslyPasteHTML(iframeHtml);

            await waitFor(() => {
                const frameHtml = quill.scroll.domNode.querySelector("iframe")?.outerHTML;
                expect(frameHtml).equal(
                    `<iframe sandbox="allow-same-origin allow-scripts allow-forms" src="https://www.example.com/embed/M7lc1UVf-VE?origin=http://example.com" class="embedIFrame-iframe" frameborder="0"></iframe>`,
                );
                expect(quill.getContents().ops?.[1]).deep.equals({
                    insert: {
                        "embed-external": {
                            loaderData: {
                                type: "link",
                            },
                            data: {
                                embedType: "iframe",
                                url: frameSrc,
                                height,
                                width,
                            },
                        },
                    },
                });
            });
        });
    });
});
