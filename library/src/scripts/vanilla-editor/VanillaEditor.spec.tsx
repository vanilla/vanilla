/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { VanillaEditor } from "@library/vanilla-editor/VanillaEditor";
import { deserializeHtml } from "@library/vanilla-editor/VanillaEditor";

describe("deserializeHtml", () => {
    const MOCK_HTML = `<p>This is a <strong>test</strong> html <span class="some-class-name">fragment</span></p>`;
    const INVALID_MOCK_HTML = `<div>This is a <example>test<example/> html <span class="some-class-name">fragment</span><p>`;
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
});

describe("VanillaEditor", () => {
    let form: HTMLFormElement | undefined;
    const mockText = "test-text";

    beforeAll(() => {
        const textarea = document.createElement("textarea");
        textarea.value = `[{"type":"p","children":[{"text":"${mockText}"}]}]`;
        form = document.createElement("form");
        form.appendChild(textarea);
    });

    it("Editor content should be empty when comment is posted/event is fired", async () => {
        render(<VanillaEditor legacyTextArea={form?.firstChild as HTMLInputElement} initialFormat={"rich2"} />);
        // Assert that it exists
        expect(screen.queryByText(mockText)).not.toBeNull();

        waitFor(() => {
            // Fire the clear event
            form && fireEvent(form, new CustomEvent("X-ClearCommentForm", {}));
            expect(screen.queryByText(mockText)).toBeNull();
        });
    });
});
