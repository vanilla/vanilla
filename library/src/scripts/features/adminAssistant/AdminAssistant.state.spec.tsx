/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useAdminAssistantState } from "@library/features/adminAssistant/AdminAssistant.state";
import { ProductMessageFixture } from "@library/features/adminAssistant/ProductMessage.fixture";
import { ProductMessagesApi } from "@library/features/adminAssistant/ProductMessages.api";
import { renderHook } from "@testing-library/react-hooks";

describe("AdminAssistantState", () => {
    it("should have an initial state of closed when there are no messages", () => {
        const { result } = renderHook(
            () =>
                useAdminAssistantState({
                    messagesQuery: {
                        isLoading: false,
                        data: [],
                    },
                })[0],
        );
        expect(result.current).toEqual({ type: "closed" });
    });

    it("should have an initial state of closed when there all messages are dismissed", () => {
        const { result } = renderHook(
            () =>
                useAdminAssistantState({
                    messagesQuery: {
                        isLoading: false,
                        data: [
                            ProductMessageFixture.message({
                                announcementType: "Inbox",
                                isDismissed: true,
                            }),
                            ProductMessageFixture.message({
                                announcementType: "Direct",
                                isDismissed: true,
                            }),
                        ],
                    },
                })[0],
        );
        expect(result.current).toEqual({ type: "closed" });
    });

    it("should have an initial state of closed when we pass an initial state directly", () => {
        const { result } = renderHook(
            () =>
                useAdminAssistantState({
                    initialState: { type: "closed" },
                    messagesQuery: {
                        isLoading: false,
                        data: [
                            ProductMessageFixture.message({
                                announcementType: "Inbox",
                                isDismissed: false,
                            }),
                            ProductMessageFixture.message({
                                announcementType: "Direct",
                                isDismissed: false,
                            }),
                        ],
                    },
                })[0],
        );
        expect(result.current).toEqual({ type: "closed" });
    });

    it("should have an initial state of inbox if there are unread inbox messages", () => {
        const { result } = renderHook(
            () =>
                useAdminAssistantState({
                    messagesQuery: {
                        isLoading: false,
                        data: [
                            ProductMessageFixture.message({
                                announcementType: "Inbox",
                                isDismissed: false,
                            }),
                            ProductMessageFixture.message({
                                announcementType: "Direct",
                                isDismissed: true,
                            }),
                        ],
                    },
                })[0],
        );
        expect(result.current).toEqual({ type: "messageInbox" });
    });

    it("should have an initial state of messageDetails if there are unread direct messages", () => {
        const { result } = renderHook(
            () =>
                useAdminAssistantState({
                    messagesQuery: {
                        isLoading: false,
                        data: [
                            ProductMessageFixture.message({
                                announcementType: "Inbox",
                                isDismissed: false,
                            }),
                            ProductMessageFixture.message({
                                productMessageID: "helloworld",
                                announcementType: "Direct",
                                isDismissed: false,
                            }),
                        ],
                    },
                })[0],
        );
        expect(result.current).toEqual({ type: "messageDetails", productMessageID: "helloworld" });
    });
});
