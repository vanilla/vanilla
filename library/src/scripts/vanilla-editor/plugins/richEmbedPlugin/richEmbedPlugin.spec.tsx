/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { MyEditor } from "@library/vanilla-editor/typescript";
import { createVanillaEditor, VanillaEditor } from "@library/vanilla-editor/VanillaEditor";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { mockAPI } from "@library/__tests__/utility";
import { render, screen } from "@testing-library/react";
import React from "react";
import { LiveAnnouncer } from "react-aria-live";

function insertHtml(editor: MyEditor, html: string) {
    const dataTransfer = new DataTransfer();
    dataTransfer.setData("text/html", html);
    editor.insertData(dataTransfer);
}

// These tests can be a bit slow in CI. Extend their timeout.
jest.setTimeout(100000);

describe("RichEmbedPlugin", () => {
    it("links are automatically converted into a rich links when inserted", async () => {
        const mockAdapter = mockAPI();
        const url = "https://github.com";

        mockAdapter.onPost("/media/scrape").replyOnce(201, {
            name: "Github site",
            body: "Github description",
            url,
            embedType: "link",
            faviconUrl: "https://github.com/favicon.ico",
        });

        const editor = createVanillaEditor();
        render(
            <TestReduxProvider state={{}}>
                <VanillaEditor editor={editor} />
            </TestReduxProvider>,
        );

        insertHtml(editor, `<a href="${url}">${url}</a>`);

        const link = await screen.findByRole("link", { name: url });
        expect(link).toHaveAttribute("href", url);

        const embed = await screen.findByTestId(`inline-embed:${url}`);
        expect(embed).toBeVisible();

        const favicon = embed.querySelector("img");
        expect(favicon).toHaveAttribute("src", "https://github.com/favicon.ico");
    });

    it("non-link embeds are automatically converted into a rich cards when inserted", async () => {
        const mockAdapter = mockAPI();
        const url = "https://www.youtube.com/watch?v=otAT5XpLG7M";

        mockAdapter.onPost("/media/scrape").replyOnce(201, {
            name: "Some Youtube - Video",
            body: "Github description",
            url,
            embedType: "video",
        });

        const editor = createVanillaEditor();
        render(
            <LiveAnnouncer>
                <TestReduxProvider state={{}}>
                    <VanillaEditor editor={editor} />
                </TestReduxProvider>
            </LiveAnnouncer>,
        );

        insertHtml(editor, `<a href="${url}">${url}</a>`);

        const link = await screen.findByRole("link", { name: url });
        expect(link).toHaveAttribute("href", url);

        const embed = await screen.findByTestId(`card-embed:${url}`);
        expect(embed).toBeVisible();
    });
});
