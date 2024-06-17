/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { deserializeHtml } from "@library/vanilla-editor/VanillaEditor.loadable";

describe("createMentionPlugin", () => {
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
});
