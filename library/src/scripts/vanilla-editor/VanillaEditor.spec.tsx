/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { act, fireEvent, render } from "@testing-library/react";
import { VanillaEditor } from "@library/vanilla-editor/VanillaEditor";
import { deserializeHtml } from "@library/vanilla-editor/VanillaEditor";
import { mockAPI } from "@library/__tests__/utility";

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
            <VanillaEditor legacyTextArea={form?.firstChild as HTMLInputElement} initialFormat={"rich2"} />,
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
