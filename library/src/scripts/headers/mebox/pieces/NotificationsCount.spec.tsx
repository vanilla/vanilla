/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { render, RenderResult } from "@testing-library/react";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { LoadStatus } from "@library/@types/api/core";
import NotificationsCount from "@library/headers/mebox/pieces/NotificationsCount";

describe("NotificationsCount", () => {
    let result: RenderResult;

    async function renderNotifcationsCount(unreadNotifications: number) {
        result = render(
            <TestReduxProvider
                state={{
                    users: {
                        current: {
                            status: LoadStatus.SUCCESS,
                            data: {
                                ...UserFixture.createMockUser({ userID: 1 }),
                                countUnreadNotifications: unreadNotifications,
                                countUnreadConversations: 1,
                            },
                        },
                    },
                }}
            >
                <NotificationsCount compact={true} />
            </TestReduxProvider>,
        );
    }

    describe("when there are notifications", () => {
        it("renders a label for screen readers when there are notifications", async () => {
            const unreadNotifications = 1;
            await renderNotifcationsCount(unreadNotifications);
            await vi.dynamicImportSettled();

            const notificationsLabel = await result.findByText(`Notifications: ${unreadNotifications}`);
            expect(notificationsLabel).toBeInTheDocument();
            expect(notificationsLabel).toHaveClass("sr-only");
        });

        it("shows the count number visually, but not for screen readers", async () => {
            const unreadNotifications = 1;
            await renderNotifcationsCount(unreadNotifications);
            await vi.dynamicImportSettled();

            const notificationsCount = await result.findByText(unreadNotifications);
            expect(notificationsCount).toBeInTheDocument();
            expect(notificationsCount).not.toHaveClass("sr-only");
            expect(notificationsCount).toHaveAttribute("aria-hidden", "true");
        });
    });

    describe("when there are no notifications", () => {
        it("renders a label for screen readers", async () => {
            const unreadNotifications = 0;
            await renderNotifcationsCount(unreadNotifications);
            await vi.dynamicImportSettled();

            const notificationsLabel = await result.findByText(`Notifications: ${unreadNotifications}`);
            expect(notificationsLabel).toBeInTheDocument();
            expect(notificationsLabel).toHaveClass("sr-only");
        });

        it("does not show the count visually", async () => {
            const unreadNotifications = 0;
            await renderNotifcationsCount(unreadNotifications);
            await vi.dynamicImportSettled();

            const notificationsCount = result.queryByText(unreadNotifications);
            expect(notificationsCount).not.toBeInTheDocument();
        });
    });
});
