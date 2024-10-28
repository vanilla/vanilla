/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { applyAnyFallbackError, mockAPI } from "@library/__tests__/utility";
import { setMeta } from "@library/utility/appUtils";
import { insertRichEmbed } from "@library/vanilla-editor/plugins/richEmbedPlugin/transforms/insertRichEmbed";
import { RichLinkAppearance } from "@library/vanilla-editor/plugins/richEmbedPlugin/types";
import { createVanillaEditor, LegacyFormVanillaEditor } from "@library/vanilla-editor/VanillaEditor.loadable";
import { getByRole, render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { focusEditor, select } from "@udecode/plate-common";
import React from "react";
import { act } from "react-dom/test-utils";

describe("<RichLinkToolbar />", () => {
    describe("<RichLinkForm />", () => {
        const mockAdapter = mockAPI();
        mockAdapter.onGet("/users/by-names").reply(200, []);
        applyAnyFallbackError(mockAdapter);

        afterAll(() => {
            mockAdapter.reset();
        });

        it("The toolbar can be triggered with a hotkey to create links", async () => {
            const user = userEvent.setup();
            const editor = createVanillaEditor();

            render(
                <TestReduxProvider>
                    <LegacyFormVanillaEditor editor={editor} />
                </TestReduxProvider>,
            );

            act(() => {
                focusEditor(editor);
            });

            await user.keyboard("{Control>}k{/Control}");

            // Opens up the link menu and creates focuses the url field.
            const urlInput = await screen.findByRole("textbox", { name: "URL" });
            expect(urlInput).toBe(document.activeElement);

            await user.keyboard("https://github.com");

            // There should be an input for the label.
            const displayTextInput = await screen.findByRole("textbox", { name: "Text to Display" });
            displayTextInput.focus();
            await user.keyboard("My Link!");
            await user.keyboard("{Enter}");

            // We should now have a link.
            const newLink = await screen.findByRole("link", { name: "My Link!" });
            expect(newLink).toHaveAttribute("href", "https://github.com");
        });

        it("The toolbar works on embeds", async () => {
            const user = userEvent.setup();
            const editor = createVanillaEditor();

            render(
                <TestReduxProvider>
                    <LegacyFormVanillaEditor editor={editor} />
                </TestReduxProvider>,
            );

            act(() => {
                insertRichEmbed(editor, "https://test.com", RichLinkAppearance.INLINE);
                focusEditor(editor);
                select(editor, [0, 1, 0]);
            });

            const linkMenu = await screen.findByTestId("rich-link-menu");
            expect(linkMenu).toBeVisible();
            const editButton = getByRole(linkMenu, "menuitem", { name: "Edit Link" });
            await user.click(editButton);

            const urlInput = await screen.findByRole("textbox", { name: "URL" });
            expect(urlInput).toHaveValue("https://test.com");
        });

        it("The appearance options are removed when the disableUrlEmbeds is set", async () => {
            setMeta("disableUrlEmbeds", true);
            const editor = createVanillaEditor();

            render(
                <TestReduxProvider>
                    <LegacyFormVanillaEditor editor={editor} />
                </TestReduxProvider>,
            );

            act(() => {
                insertRichEmbed(editor, "https://test.com", RichLinkAppearance.INLINE);
                focusEditor(editor);
                select(editor, [0, 1, 0]);
            });

            const linkMenu = await screen.findByTestId("rich-link-menu");
            expect(linkMenu).toBeVisible();
            const displayAsText = await screen.queryByRole("menuitem", { name: "Display as Text" });
            const displayAsRichLink = await screen.queryByRole("menuitem", { name: "Display as Rich Link" });
            const displayAsCard = await screen.queryByRole("menuitem", { name: "Display as Card" });
            expect(displayAsText).not.toBeInTheDocument();
            expect(displayAsRichLink).not.toBeInTheDocument();
            expect(displayAsCard).not.toBeInTheDocument();
        });
    });
});
