/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { supportsFrames } from "@library/embeddedContent/IFrameEmbed";
import { setMeta } from "@library/utility/appUtils";
import { insertRichEmbed } from "@library/vanilla-editor/plugins/richEmbedPlugin/transforms/insertRichEmbed";
import { RichLinkAppearance } from "@library/vanilla-editor/plugins/richEmbedPlugin/types";
import { MyEditor } from "@library/vanilla-editor/typescript";
import { createVanillaEditor, VanillaEditorImpl } from "@library/vanilla-editor/VanillaEditor";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { mockAPI } from "@library/__tests__/utility";
import { render, screen, waitFor } from "@testing-library/react";
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
    const mockAdapter = mockAPI();
    mockAdapter.onGet("/users/by-names").reply(200, []);

    beforeEach(() => {
        setMeta("trustedDomains", "codesandbox.io");
        supportsFrames(true);
    });

    afterAll(() => {
        mockAdapter.reset();
    });

    it("links are automatically converted into a rich links when inserted", async () => {
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
            <TestReduxProvider>
                <VanillaEditorImpl editor={editor} />
            </TestReduxProvider>,
        );

        insertHtml(editor, `<a href="${url}">${url}</a>`);

        waitFor(async () => {
            const link = await screen.findByRole("link", { name: url });
            expect(link).toHaveAttribute("href", url);

            const embed = await screen.findByTestId(`inline-embed:${url}`);
            expect(embed).toBeVisible();

            const favicon = embed.querySelector("img");
            expect(favicon).toHaveAttribute("src", "https://github.com/favicon.ico");
        });
    });

    it("non-link embeds are automatically converted into a rich cards when inserted", async () => {
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
                <TestReduxProvider>
                    <VanillaEditorImpl editor={editor} />
                </TestReduxProvider>
            </LiveAnnouncer>,
        );

        insertHtml(editor, `<a href="${url}">${url}</a>`);

        waitFor(async () => {
            const link = await screen.findByRole("link", { name: url });
            expect(link).toHaveAttribute("href", url);

            const embed = await screen.findByTestId(`card-embed:${url}`);
            expect(embed).toBeVisible();
        });
    });

    it("Rich embed iframes are rendered inline", async () => {
        const url =
            "https://codesandbox.io/embed/07-final-torus-knot-r3bo1?fontsize=14&hidenavigation=1&theme=dark&view=preview";

        const editor = createVanillaEditor();
        render(
            <LiveAnnouncer>
                <TestReduxProvider>
                    <VanillaEditorImpl editor={editor} />
                </TestReduxProvider>
            </LiveAnnouncer>,
        );

        insertRichEmbed(editor, url, RichLinkAppearance.CARD, "iframe", { height: "300px", width: "600px" });
        waitFor(async () => {
            const embed = await screen.findByTestId(`card-embed:${url}`);
            expect(embed).toBeVisible();

            const iframe = await screen.findByTestId(`iframe-embed`);
            expect(iframe).toBeVisible();
        });
    });
});
