/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ToastProvider } from "@library/features/toaster/ToastContext";
import { RenderResult, act, fireEvent, render } from "@testing-library/react";
import { ThreadItemContextProvider } from "@vanilla/addon-vanilla/thread/ThreadItemContext";
import { DiscussionFixture } from "@vanilla/addon-vanilla/thread/__fixtures__/Discussion.Fixture";
import { vitest } from "vitest";
import ThreadItemShareMenu from "./ThreadItemShareMenu";

const mockWriteText = vitest.fn().mockImplementation(async () => {});

Object.assign(navigator, {
    clipboard: {
        writeText: mockWriteText,
    },
});

const mockDiscussion = DiscussionFixture.mockDiscussion;

async function renderInProvider() {
    return render(
        <ToastProvider>
            <ThreadItemContextProvider
                recordID={mockDiscussion.discussionID}
                recordType="discussion"
                recordUrl={mockDiscussion.url}
                timestamp={mockDiscussion.dateInserted}
                name={mockDiscussion.name}
            >
                <ThreadItemShareMenu />
            </ThreadItemContextProvider>
        </ToastProvider>,
    );
}

describe("ThreadItemShareMenu", () => {
    let result: RenderResult;

    let shareMenu: HTMLElement;

    beforeEach(async () => {
        result = await renderInProvider();
        shareMenu = await result.findByRole("button", { name: "Share", exact: false });
    });

    describe("Menu items", () => {
        beforeEach(async () => {
            await act(async () => {
                fireEvent.click(shareMenu);
            });
        });

        describe("Copy link button", () => {
            let copyLinkButton: HTMLElement;
            beforeEach(async () => {
                copyLinkButton = await result.findByRole("button", { name: "Copy Link", exact: false });
                await act(async () => {
                    fireEvent.click(copyLinkButton);
                });
            });

            it("copies the URL to the clipboard", async () => {
                expect(mockWriteText).toHaveBeenCalledWith(expect.stringContaining(mockDiscussion.url));
            });
            it("adds the UTM parameters", async () => {
                expect(mockWriteText).toHaveBeenCalledWith(expect.stringContaining("?utm_source=community-share"));
            });

            it("shows a success message", async () => {
                const successMessage = await result.findByText("Link copied to clipboard.", { exact: false });
                expect(successMessage).toBeInTheDocument();
            });
        });

        describe("Email link button", () => {
            let emailLinkButton: HTMLElement;
            beforeEach(async () => {
                emailLinkButton = await result.findByRole("link", { name: "Email Link", exact: false });
            });

            it("Contains an email link button", async () => {
                const href = (emailLinkButton as HTMLAnchorElement).href;
                expect(href).toContain("mailto:");
                expect(href).toContain(encodeURIComponent(mockDiscussion.url));
            });
        });
    });
});
