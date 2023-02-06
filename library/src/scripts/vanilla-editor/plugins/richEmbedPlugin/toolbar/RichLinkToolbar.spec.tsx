/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { insertRichEmbed } from "@library/vanilla-editor/plugins/richEmbedPlugin/transforms/insertRichEmbed";
import { RichLinkAppearance } from "@library/vanilla-editor/plugins/richEmbedPlugin/types";
import { createVanillaEditor, VanillaEditor } from "@library/vanilla-editor/VanillaEditor";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { applyAnyFallbackError, mockAPI } from "@library/__tests__/utility";
import { getByRole, render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { focusEditor, select } from "@udecode/plate-core";
import React from "react";
import { act } from "react-dom/test-utils";

// These tests can be a bit slow in CI. Extend their timeout.
jest.setTimeout(100000);

describe("<RichLinkToolbar />", () => {
    describe("<RichLinkForm />", () => {
        it("The toolbar can be triggered with a hotkey to create links", async () => {
            const user = userEvent.setup();
            const editor = createVanillaEditor();
            applyAnyFallbackError(mockAPI());

            render(
                <TestReduxProvider state={{}}>
                    <VanillaEditor editor={editor} />
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
            applyAnyFallbackError(mockAPI());

            render(
                <TestReduxProvider state={{}}>
                    <VanillaEditor editor={editor} />
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
    });
});
