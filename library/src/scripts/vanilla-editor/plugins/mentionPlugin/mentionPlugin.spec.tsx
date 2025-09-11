/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { createVanillaEditor } from "@library/vanilla-editor/createVanillaEditor";
import { deserializeHtml } from "@library/vanilla-editor/deserializeHtml";
import { MyEditor, MyNode } from "@library/vanilla-editor/typescript";
import { ELEMENT_DEFAULT } from "@udecode/plate-common";

function insertHtml(editor: MyEditor, data: { text?: string; html?: string; rtf?: string }) {
    const dataTransfer = new DataTransfer();
    if (data.text) {
        dataTransfer.setData("text/plain", data.text);
    }
    if (data.html) {
        dataTransfer.setData("text/html", data.html);
    }
    if (data.rtf) {
        dataTransfer.setData("text/rtf", data.rtf);
    }
    editor.insertNodes({
        type: ELEMENT_DEFAULT,
        children: [{ text: "" }],
    } as MyNode);
    editor.setPoint({ path: [0, 0] });
    editor.insertData(dataTransfer);
}

describe("Vanilla Editor - Mention Plugin", () => {
    describe("paste data into editor", () => {
        it("converts plain text with possible mentions", async () => {
            const editor = createVanillaEditor();

            insertHtml(editor, {
                text: "@testuser\nline with @mention in middle\n@another user",
            });

            const expected = [
                {
                    type: "p",
                    children: [
                        { text: "" },
                        {
                            type: "@",
                            children: [{ text: "" }],
                            name: "testuser",
                        },
                        { text: " \nline with " },
                        {
                            type: "@",
                            children: [{ text: "" }],
                            name: "mention in middle",
                        },
                        { text: " \n" },
                        {
                            type: "@",
                            children: [{ text: "" }],
                            name: "another user",
                        },
                        { text: " " },
                    ],
                },
            ];

            // Using toMatchObject to ignore the unique IDs added to mentions in insertDataCustom
            expect(editor.children).toMatchObject(expected);
        });

        it("converts multiple mentions in the same line", async () => {
            const editor = createVanillaEditor();
            insertHtml(editor, {
                text: "@testuser @another user",
            });
            const expected = [
                {
                    type: "p",
                    children: [
                        { text: "" },
                        {
                            type: "@",
                            children: [{ text: "" }],
                            name: "testuser ",
                        },
                        { text: " " },
                        {
                            type: "@",
                            children: [{ text: "" }],
                            name: "another user",
                        },
                        { text: " " },
                    ],
                },
            ];
            expect(editor.children).toMatchObject(expected);
        });

        it("converts mentions with spaces in the name", async () => {
            const editor = createVanillaEditor();
            insertHtml(editor, {
                text: "@test user",
            });
            const expected = [
                {
                    type: "p",
                    children: [
                        { text: "" },
                        {
                            type: "@",
                            children: [{ text: "" }],
                            name: "test user",
                        },
                        { text: " " },
                    ],
                },
            ];
            expect(editor.children).toMatchObject(expected);
        });

        it("won't include punctuation in the mention name", async () => {
            const editor = createVanillaEditor();
            insertHtml(editor, {
                text: "@testuser, @another user!",
            });
            const expected = [
                {
                    type: "p",
                    children: [
                        { text: "" },
                        {
                            type: "@",
                            children: [{ text: "" }],
                            name: "testuser",
                        },
                        { text: " , " },
                        {
                            type: "@",
                            children: [{ text: "" }],
                            name: "another user",
                        },
                        { text: " !" },
                    ],
                },
            ];

            expect(editor.children).toMatchObject(expected);
        });

        it("does not delete text after a mention with quotes", async () => {
            const editor = createVanillaEditor();

            insertHtml(editor, {
                text: '@cell<> "", HAS(@cell, Status@row)',
            });

            const expected = [
                {
                    type: "p",
                    children: [
                        {
                            text: "",
                        },
                        {
                            type: "@",
                            children: [
                                {
                                    text: "",
                                },
                            ],
                            name: "cell",
                        },

                        {
                            text: ' <> "", HAS(',
                        },
                        {
                            type: "@",
                            children: [
                                {
                                    text: "",
                                },
                            ],
                            name: "cell",
                        },
                        {
                            text: " , Status",
                        },
                        {
                            type: "@",
                            children: [
                                {
                                    text: "",
                                },
                            ],
                            name: "row",
                        },
                        {
                            text: " )",
                        },
                    ],
                },
            ];

            expect(editor.children).toMatchObject(expected);
        });

        it("converts CSV table with possible mentions", () => {
            const editor = createVanillaEditor();
            insertHtml(editor, {
                text: "Text,Notes\n@testuser,Mention\nuser name,Not a mention",
            });

            const expected = [
                { type: "p", children: [{ text: "" }] },
                {
                    type: "table",
                    children: [
                        {
                            type: "thead",
                            children: [
                                {
                                    type: "tr",
                                    children: [
                                        {
                                            type: "th",
                                            children: [{ type: "p", children: [{ text: "Text" }] }],
                                        },
                                        {
                                            type: "th",
                                            children: [{ type: "p", children: [{ text: "Notes" }] }],
                                        },
                                    ],
                                },
                            ],
                        },
                        {
                            type: "tbody",
                            children: [
                                {
                                    type: "tr",
                                    children: [
                                        {
                                            type: "td",
                                            children: [
                                                {
                                                    type: "p",
                                                    children: [
                                                        { text: "" },
                                                        {
                                                            type: "@",
                                                            children: [{ text: "" }],
                                                            name: "testuser",
                                                        },
                                                        { text: " " },
                                                    ],
                                                },
                                            ],
                                        },
                                        {
                                            type: "td",
                                            children: [{ type: "p", children: [{ text: "Mention" }] }],
                                        },
                                    ],
                                },
                                {
                                    type: "tr",
                                    children: [
                                        {
                                            type: "td",
                                            children: [{ type: "p", children: [{ text: "user name" }] }],
                                        },
                                        {
                                            type: "td",
                                            children: [{ type: "p", children: [{ text: "Not a mention" }] }],
                                        },
                                    ],
                                },
                            ],
                        },
                    ],
                },
                { type: "p", children: [{ text: "" }] },
            ];

            expect(editor.children).toMatchObject(expected);
        });

        it("converts markdown with list with possible mentions", () => {
            const editor = createVanillaEditor();
            insertHtml(editor, {
                text: "- @testuser\n- no user\n- @another user",
            });

            const expected = [
                { type: "p", children: [{ text: "" }] },
                {
                    type: "ul",
                    children: [
                        {
                            type: "li",
                            children: [
                                {
                                    type: "lic",
                                    children: [
                                        { text: "" },
                                        {
                                            type: "@",
                                            children: [{ text: "" }],
                                            name: "testuser",
                                        },
                                        { text: " " },
                                    ],
                                },
                            ],
                        },
                        {
                            type: "li",
                            children: [
                                {
                                    type: "lic",
                                    children: [{ text: "no user" }],
                                },
                            ],
                        },
                        {
                            type: "li",
                            children: [
                                {
                                    type: "lic",
                                    children: [
                                        { text: "" },
                                        {
                                            type: "@",
                                            children: [{ text: "" }],
                                            name: "another user",
                                        },
                                        { text: " " },
                                    ],
                                },
                            ],
                        },
                    ],
                },
                { type: "p", children: [{ text: "" }] },
            ];

            expect(editor.children).toMatchObject(expected);
        });

        it("converts plain html with possible mentions", () => {
            const editor = createVanillaEditor();
            insertHtml(editor, {
                text: "@testuser\nusername\nmention in @middle of line",
                html: "<p>@testuser</p><p>username</p><p>mention in @middle of line</p>",
            });

            const expected = [
                {
                    type: "p",
                    children: [
                        { text: "" },
                        {
                            type: "@",
                            children: [{ text: "" }],
                            name: "testuser",
                        },
                        { text: " " },
                    ],
                },
                { type: "p", children: [{ text: "username" }] },
                {
                    type: "p",
                    children: [
                        { text: "mention in " },
                        {
                            type: "@",
                            children: [{ text: "" }],
                            name: "middle of line",
                        },
                        { text: " " },
                    ],
                },
            ];

            expect(editor.children).toMatchObject(expected);
        });
    });

    describe("deserialize mention html", () => {
        it("deserializes HTML from another post with atMention elements", () => {
            const inputHTML = `<p>Hello, <a class="atMention" data-username="test-user-1" data-userid="123" href="https://example.com/profile/test-user-1">@test-user-1</a> and <a class="atMention" data-username="test-user-2" data-userid="456" href="https://example.com/profile/test-user-2">@test-user-2</a>! Welcome to Aperture Science!</p>`;

            const expectedOutput = [
                {
                    type: "p",
                    children: [
                        { text: "Hello, " },
                        {
                            type: "@",
                            name: "test-user-1",
                            userID: "123",
                            url: "https://example.com/profile/test-user-1",
                            domID: "mentionSuggestion123",
                            children: [{ text: "" }],
                        },
                        { text: " and " },
                        {
                            type: "@",
                            name: "test-user-2",
                            userID: "456",
                            url: "https://example.com/profile/test-user-2",
                            domID: "mentionSuggestion456",
                            children: [{ text: "" }],
                        },
                        { text: "! Welcome to Aperture Science!" },
                    ],
                },
            ];

            const actualOutput = deserializeHtml(inputHTML);
            expect(actualOutput).toStrictEqual(expectedOutput);
        });

        it("deserializes HTML from another atMention within the editor", () => {
            const inputHTML = `<p>Hello, <a class="atMention" href="https://example.com/profile/test-user">@test-user</a>. Welcome to Aperture Science!</p>`;

            const expectedOutput = [
                {
                    type: "p",
                    children: [
                        { text: "Hello, " },
                        {
                            type: "@",
                            name: "test-user",
                            url: "https://example.com/profile/test-user",
                            children: [{ text: "" }],
                            domID: "mentionSuggestionundefined",
                            userID: undefined,
                        },
                        { text: ". Welcome to Aperture Science!" },
                    ],
                },
            ];

            const actualOutput = deserializeHtml(inputHTML);
            expect(actualOutput).toStrictEqual(expectedOutput);
        });
    });
});
