/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { mockAPI } from "@library/__tests__/utility";
import {
    LegacyFormVanillaEditor,
    deserializeHtml,
    emailLinkCheck,
} from "@library/vanilla-editor/VanillaEditor.loadable";
import { act, fireEvent, render } from "@testing-library/react";

describe("deserializeHtml", () => {
    const MOCK_HTML = `<p>This is a <strong>test</strong> html <span class="some-class-name">fragment</span></p>`;
    const INVALID_MOCK_HTML = `<div>This is a <example>test<example/> html <span class="some-class-name">fragment</span><p>`;
    const MOCK_TEXT = "test text";
    const MOCK_RICH2 = [
        {
            children: [{ text: "This is a " }, { bold: true, text: "test" }, { text: " html " }, { text: "fragment" }],
            type: "p",
        },
    ];
    const INVALID_MOCK_RICH2 = [
        { type: "p", children: [{ text: "This is a " }, { text: "test" }, { text: " html " }, { text: "fragment" }] },
        { type: "p", children: [{ text: "" }] },
    ];
    const MOCK_RICH2_FROM_TEXT = [
        {
            children: [{ text: "test text" }],
            type: "p",
        },
    ];
    const MOCK_TEXT_EXTRA = `<-- La La La -->Some text broken <br> up by new line <Br /> and line breaks`;
    const EXPECTED_TEXT_EXTRA = [
        { type: "p", children: [{ text: "<-- La La La -->Some text broken \n up by new line \n and line breaks" }] },
    ];
    const MOCK_HTML_MULTILINE = `This is a <strong>test</strong> html with multiple lines.<br>Each new line is separated with the br tag.<br />It should check for each variation of the br tag.<br >And place each new line in a paragraph tag.<br>`;
    const EXPECTED_HTML_MULTILINE = [
        {
            type: "p",
            children: [
                { text: "This is a " },
                { text: "test", bold: true },
                {
                    text: " html with multiple lines.\nEach new line is separated with the br tag.\nIt should check for each variation of the br tag.\nAnd place each new line in a paragraph tag.\n",
                },
            ],
        },
    ];
    const MOCK_HTML_EMOJI = `This is HTML formatted with emoji <img class="emoji" src="https://dev.vanilla.local/resources/emoji/smile.png" title=":)" alt=":)" height="20" loading="lazy"></img> from another format. The emoji <img class="emoji" src="https://dev.vanilla.local/resources/emoji/frowning.png" title=":(" alt=":(" height="20" loading="lazy"></img> should remain on the same line.`;
    const EXPECTED_HTML_EMOJI = [
        {
            type: "p",
            children: [
                { text: "This is HTML formatted with emoji " },
                {
                    type: "legacy_emoji_image",
                    children: [{ text: "" }],
                    attributes: {
                        alt: ":)",
                        title: ":)",
                        src: "https://dev.vanilla.local/resources/emoji/smile.png",
                        height: 16,
                        width: 16,
                    },
                },
                { text: " from another format. The emoji " },
                {
                    type: "legacy_emoji_image",
                    children: [{ text: "" }],
                    attributes: {
                        alt: ":(",
                        title: ":(",
                        src: "https://dev.vanilla.local/resources/emoji/frowning.png",
                        height: 16,
                        width: 16,
                    },
                },
                { text: " should remain on the same line." },
            ],
        },
    ];
    const MOCK_DOUBLE_LINE_BREAKS = `This is a line.<br>This line should be a soft break.<br ><br />This line should be a hard break.<BR><Br>This should be a new paragraph with <strong>bolded text</strong> in the middle.`;
    const EXPECTED_DOUBLE_LINE_BREAKS = [
        {
            type: "p",
            children: [{ text: "This is a line.\nThis line should be a soft break." }],
        },
        {
            type: "p",
            children: [{ text: "This line should be a hard break." }],
        },
        {
            type: "p",
            children: [
                { text: "This should be a new paragraph with " },
                { text: "bolded text", bold: true },
                { text: " in the middle." },
            ],
        },
    ];
    const MOCK_MIXED_VALID_INVALID = `<ul><li>List Item</li><li>List Item</li></ul>Unwrapped text with <em>italic text</em> and a<br>soft break inside.<br><br>New paragraph from double line break.<p>Valid paragraph tag.</p>`;
    const EXPECTED_MIXED_VALID_INVALID = [
        {
            type: "ul",
            children: [
                { type: "li", children: [{ text: "List Item" }] },
                { type: "li", children: [{ text: "List Item" }] },
            ],
        },
        {
            type: "p",
            children: [
                { text: "Unwrapped text with " },
                { text: "italic text", italic: true },
                { text: " and a\nsoft break inside." },
            ],
        },
        { type: "p", children: [{ text: "New paragraph from double line break." }] },
        { type: "p", children: [{ text: "Valid paragraph tag." }] },
    ];
    it("Is undefined on empty HTML", () => {
        const actual = deserializeHtml("");
        expect(actual).toBeUndefined();
    });
    it("Converts valid HTML to Rich2 format", () => {
        const actual = deserializeHtml(MOCK_HTML);
        expect(actual).toStrictEqual(MOCK_RICH2);
    });
    it("Converts invalid HTML to Rich2 approximation", () => {
        const actual = deserializeHtml(INVALID_MOCK_HTML);
        expect(actual).toStrictEqual(INVALID_MOCK_RICH2);
    });
    it("Converts text into paragraph type Rich2 format", () => {
        const actual = deserializeHtml(MOCK_TEXT);
        expect(actual).toStrictEqual(MOCK_RICH2_FROM_TEXT);
    });
    it("Converts text to paragraph and preserves line breaks", () => {
        const actual = deserializeHtml(MOCK_HTML_MULTILINE);
        expect(actual).toStrictEqual(EXPECTED_HTML_MULTILINE);
    });
    it("Converts text to paragraph and preserves line breaks with <> characters", () => {
        const actual = deserializeHtml(MOCK_TEXT_EXTRA);
        expect(actual).toStrictEqual(EXPECTED_TEXT_EXTRA);
    });
    it("Keeps emoji images inline", () => {
        const actual = deserializeHtml(MOCK_HTML_EMOJI);
        expect(actual).toStrictEqual(EXPECTED_HTML_EMOJI);
    });
    it("Creates paragraphs when there are double line breaks", () => {
        const actual = deserializeHtml(MOCK_DOUBLE_LINE_BREAKS);
        expect(actual).toStrictEqual(EXPECTED_DOUBLE_LINE_BREAKS);
    });
    it("Converts mix of valid and invalid HTML into valid HTML", () => {
        const actual = deserializeHtml(MOCK_MIXED_VALID_INVALID);
        expect(actual).toStrictEqual(EXPECTED_MIXED_VALID_INVALID);
    });
});

describe("VanillaEditor", () => {
    let form: HTMLFormElement | undefined;
    const mockText = "test-text";

    const mockAdapter = mockAPI();
    mockAdapter.onGet("/users/by-names").reply(200, []);

    beforeAll(() => {
        const textarea = document.createElement("textarea");
        textarea.value = `[{"type":"p","children":[{"text":"${mockText}"}]}]`;
        form = document.createElement("form");
        form.appendChild(textarea);
    });

    afterAll(() => {
        mockAdapter.reset();
    });

    it("Editor content should be empty when comment is posted/event is fired", async () => {
        const { queryByText } = render(
            <LegacyFormVanillaEditor legacyTextArea={form?.firstChild as HTMLInputElement} initialFormat={"rich2"} />,
        );

        // Assert that it exists
        expect(queryByText(mockText)).not.toBeNull();

        await act(async () => {
            // Fire the clear event
            form && fireEvent(form, new CustomEvent("X-ClearCommentForm", {}));
        });
        expect(queryByText(mockText)).toBeNull();
    });
});

describe("VanillaEditor Email Links", () => {
    it("Converts email mailto link to proper format in a simple paragraph", () => {
        const INITIAL_VALUE = [
            {
                type: "p",
                children: [
                    { text: "This is an " },
                    {
                        type: "a",
                        url: "http://mailto:test@email.com",
                        target: "_self",
                        children: [{ text: "Email Address" }],
                    },
                    { text: " link" },
                ],
            },
        ];

        const EXPECTED_VALUE = [
            {
                type: "p",
                children: [
                    { text: "This is an " },
                    {
                        type: "a",
                        url: "mailto:test@email.com",
                        target: "_self",
                        children: [{ text: "Email Address" }],
                    },
                    { text: " link" },
                ],
            },
        ];

        const actual = emailLinkCheck(INITIAL_VALUE);
        expect(actual).toStrictEqual(EXPECTED_VALUE);
    });

    it("Converts email mailto link to proper format in a nested element", () => {
        const INITIAL_VALUE = [
            { type: "p", children: [{ text: "Below is a list." }] },
            {
                type: "ul",
                children: [
                    { type: "li", children: [{ type: "lic", children: [{ text: "List item" }] }] },
                    {
                        type: "li",
                        children: [
                            {
                                type: "lic",
                                children: [
                                    { text: "This item is an " },
                                    {
                                        type: "a",
                                        url: "https://mailto:test@email.com",
                                        children: [{ text: "Email" }],
                                    },
                                    { text: " link" },
                                ],
                            },
                        ],
                    },
                ],
            },
        ];

        const EXPECTED_VALUE = [
            { type: "p", children: [{ text: "Below is a list." }] },
            {
                type: "ul",
                children: [
                    { type: "li", children: [{ type: "lic", children: [{ text: "List item" }] }] },
                    {
                        type: "li",
                        children: [
                            {
                                type: "lic",
                                children: [
                                    { text: "This item is an " },
                                    {
                                        type: "a",
                                        url: "mailto:test@email.com",
                                        children: [{ text: "Email" }],
                                    },
                                    { text: " link" },
                                ],
                            },
                        ],
                    },
                ],
            },
        ];

        const actual = emailLinkCheck(INITIAL_VALUE);
        expect(actual).toStrictEqual(EXPECTED_VALUE);
    });

    it("emailLinkCheck() function will return the initial/same content if hydrated with links with bad value (URL is null)", () => {
        const INITIAL_VALUE = [
            {
                type: "p",
                children: [
                    { text: "This is an " },
                    {
                        type: "a",
                        url: null,
                        target: "_self",
                        children: [{ text: "Email Address" }],
                    },
                    { text: " link" },
                ],
            },
        ];

        const actual = emailLinkCheck(INITIAL_VALUE);
        expect(actual).toStrictEqual(INITIAL_VALUE);
    });
});
