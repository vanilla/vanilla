/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { fireEvent, render, waitFor, screen, act } from "@testing-library/react";
import EmbedFlyout from "@library/editor/flyouts/EmbedFlyout";
import { supportsFrames } from "@library/embeddedContent/IFrameEmbed";
import { vitest } from "vitest";

describe("Embed Flyout", () => {
    it("Render flyout and ensure we properly clear input after submit.", async () => {
        vitest.useFakeTimers();
        render(<EmbedFlyout createEmbed={(url) => {}} createIframe={(options) => {}} />);

        const insertMediaButton = screen.getByRole("button", {
            name: "Insert Media",
        });
        expect(insertMediaButton).toBeInTheDocument();
        fireEvent.click(insertMediaButton);

        //our textbox is rendered with empty value
        await waitFor(() => {
            const urlInput = screen.getByRole("textbox");
            expect(urlInput).toBeInTheDocument();
            expect(urlInput.getAttribute("value")).toBe("");
        });

        //insert buttun is disabled
        let insertButton = screen.getByRole("button", {
            name: "Insert",
        });
        expect(insertButton).toBeInTheDocument();
        expect(insertButton).toBeDisabled();

        //lets input some text and submit
        await act(async () => {
            fireEvent.change(screen.getByRole("textbox"), { target: { value: "youtube.com" } });
            vitest.advanceTimersByTime(500);
        });
        await waitFor(() => {
            expect(screen.getByRole("textbox").getAttribute("value")).toBe("youtube.com");
            expect(insertButton).not.toBeDisabled();
        });

        fireEvent.click(insertButton);

        //submit is done, flyout is closed, no input
        expect(screen.queryAllByRole("textbox").length).toBe(0);

        //now lets open again and try to submit without typing any text in input
        fireEvent.click(insertMediaButton);
        insertButton = screen.getByRole("button", {
            name: "Insert",
        });
        expect(insertButton).toBeInTheDocument();

        //its disabled, clicking on the button again won't submit
        fireEvent.click(insertButton);
        expect(screen.queryAllByRole("textbox").length).toBe(1);
        expect(insertButton).toBeDisabled();
    });

    it("Properly handle iframe creation", async () => {
        vitest.useFakeTimers();
        supportsFrames(true);
        const createIframe = vitest.fn();

        // Boilerplate to render the flyout
        const { container } = render(<EmbedFlyout createEmbed={(url) => {}} createIframe={createIframe} />);

        const insertMediaButton = screen.getByRole("button", {
            name: "Insert Media",
        });

        expect(insertMediaButton).toBeInTheDocument();
        fireEvent.click(insertMediaButton);

        const urlInput = container.getElementsByTagName("textarea")[0];

        await waitFor(() => {
            expect(urlInput).toBeInTheDocument();
        });

        const insertButton = screen.getByRole("button", {
            name: "Insert",
        });
        expect(insertButton).toBeInTheDocument();

        // Iframe code
        const iframeCode = `<iframe src="https://www.youtube.com" width="560" height="315" />`;

        // Insert and submit
        await act(async () => {
            fireEvent.change(screen.getByRole("textbox"), {
                target: { value: iframeCode },
            });
            vitest.advanceTimersByTime(500);
        });

        expect(urlInput.value).toBe(iframeCode);
        fireEvent.click(insertButton);
        vitest.advanceTimersByTime(500);

        // Validate attributes have been extracted
        expect(createIframe).toHaveBeenCalledWith({ url: "https://www.youtube.com", width: "560", height: "315" });
    });
});
