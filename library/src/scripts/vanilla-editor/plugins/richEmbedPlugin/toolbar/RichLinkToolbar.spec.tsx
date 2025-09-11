/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { applyAnyFallbackError, mockAPI } from "@library/__tests__/utility";
import { fireEvent, getByRole, render, screen, within } from "@testing-library/react";
import { focusEditor, select } from "@udecode/plate-common";

import { LegacyFormVanillaEditorLoadable } from "@library/vanilla-editor/VanillaEditor.loadable";
import { RichLinkAppearance } from "@library/vanilla-editor/plugins/richEmbedPlugin/types";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { act } from "react-dom/test-utils";
import { createVanillaEditor } from "@library/vanilla-editor/createVanillaEditor";
import { insertRichEmbed } from "@library/vanilla-editor/plugins/richEmbedPlugin/transforms/insertRichEmbed";
import { setMeta } from "@library/utility/appUtils";
import userEvent from "@testing-library/user-event";

describe("<RichLinkToolbar />", () => {
    describe("<RichLinkForm />", () => {
        const mockAdapter = mockAPI();
        mockAdapter.onGet("/users/by-names").reply(200, []);
        applyAnyFallbackError(mockAdapter);

        // Create a query client for the tests
        const queryClient = new QueryClient({
            defaultOptions: {
                queries: {
                    enabled: false,
                    retry: false,
                    staleTime: Infinity,
                },
            },
        });

        afterAll(() => {
            mockAdapter.reset();
        });

        afterEach(() => {
            // Clean up any meta state that was set during tests
            setMeta("disableUrlEmbeds", undefined);
        });

        it("The toolbar can be triggered with a hotkey to create links. The link form includes an accessible submit button", async () => {
            const user = userEvent.setup();
            const editor = createVanillaEditor();

            render(
                <TestReduxProvider>
                    <QueryClientProvider client={queryClient}>
                        <LegacyFormVanillaEditorLoadable editor={editor} />
                    </QueryClientProvider>
                </TestReduxProvider>,
            );

            await act(async () => {
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

            const form = displayTextInput.closest("form")!;

            const submitButton = await within(form).findByRole<HTMLButtonElement>("button", { name: "Add Link" });

            expect((submitButton as HTMLButtonElement).type).toBe("submit");

            await act(async () => {
                fireEvent.submit(form);
            });

            // We should now have a link.
            const newLink = await screen.findByRole("link", { name: "My Link!" });
            expect(newLink).toHaveAttribute("href", "https://github.com");
        });

        it("The toolbar works on embeds", async () => {
            const user = userEvent.setup();
            const editor = createVanillaEditor();

            render(
                <TestReduxProvider>
                    <QueryClientProvider client={queryClient}>
                        <LegacyFormVanillaEditorLoadable editor={editor} />
                    </QueryClientProvider>
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

        it("The appearance embed options are removed when the disableUrlEmbeds is set", async () => {
            setMeta("disableUrlEmbeds", true);
            const editor = createVanillaEditor();

            render(
                <TestReduxProvider>
                    <QueryClientProvider client={queryClient}>
                        <LegacyFormVanillaEditorLoadable editor={editor} />
                    </QueryClientProvider>
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
            const displayAsButton = await screen.queryByRole("menuitem", { name: "Display as Button" });
            const displayAsRichLink = await screen.queryByRole("menuitem", { name: "Display as Rich Link" });
            const displayAsCard = await screen.queryByRole("menuitem", { name: "Display as Card" });
            expect(displayAsText).toBeInTheDocument();
            expect(displayAsButton).toBeInTheDocument();
            expect(displayAsRichLink).not.toBeInTheDocument();
            expect(displayAsCard).not.toBeInTheDocument();
        });
    });
});
