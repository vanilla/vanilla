/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { render, RenderResult } from "@testing-library/react";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { LoadStatus } from "@library/@types/api/core";
import MessagesCount from "@library/headers/mebox/pieces/MessagesCount";

describe("MessagesCount", () => {
    let result: RenderResult;

    async function renderMessagesCount(unreadConversations: number) {
        result = render(
            <TestReduxProvider
                state={{
                    users: {
                        current: {
                            status: LoadStatus.SUCCESS,
                            data: {
                                ...UserFixture.createMockUser({ userID: 1 }),
                                countUnreadNotifications: 1,
                                countUnreadConversations: unreadConversations,
                            },
                        },
                    },
                }}
            >
                <MessagesCount compact={true} />
            </TestReduxProvider>,
        );
    }

    describe("when there are messages", () => {
        it("renders a label for screen readers", async () => {
            const unreadConversations = 1;

            await renderMessagesCount(unreadConversations);
            await vi.dynamicImportSettled();

            const messagesLabel = await result.findByText(`Messages: ${unreadConversations}`);
            expect(messagesLabel).toBeInTheDocument();
            expect(messagesLabel).toHaveClass("sr-only");
        });

        it("shows the count number visually, but not for screen readers", async () => {
            const unreadConversations = 1;

            await renderMessagesCount(unreadConversations);
            await vi.dynamicImportSettled();

            const messagesCount = await result.findByText(unreadConversations);
            expect(messagesCount).toBeInTheDocument();
            expect(messagesCount).not.toHaveClass("sr-only");
            expect(messagesCount).toHaveAttribute("aria-hidden", "true");
        });
    });

    describe("when there are no messages", () => {
        it("renders a label for screen readers", async () => {
            const unreadConversations = 0;

            await renderMessagesCount(unreadConversations);
            await vi.dynamicImportSettled();

            const messagesLabel = await result.findByText(`Messages: ${unreadConversations}`);
            expect(messagesLabel).toBeInTheDocument();
            expect(messagesLabel).toHaveClass("sr-only");
        });

        it("does not show the count visually", async () => {
            const unreadConversations = 0;

            await renderMessagesCount(unreadConversations);
            await vi.dynamicImportSettled();

            const messagesCount = result.queryByText(unreadConversations);
            expect(messagesCount).not.toBeInTheDocument();
        });
    });
});
