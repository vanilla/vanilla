/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { RenderResult, fireEvent, render, act } from "@testing-library/react";
import { AiChatInterfaceImpl, AiChatInterfaceModal } from "@library/aiConversations/AiChatInterface";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { mockAPI } from "@library/__tests__/utility";
import { IAiConversationsApi } from "@library/aiConversations/AiConversations.types";
import { mockConversation, mockAskCommunityResponse } from "@library/aiConversations/AiConversations.fixtures";
import { AiConversationsApiProvider } from "@library/aiConversations/AiConversations.context";

const mockAdapter = mockAPI();

const fakeAPI: IAiConversationsApi = {
    getConversation: async () => mockConversation,
    getConversations: async () => [mockConversation],
    postNewConversation: async () => mockConversation,
    postConversationReply: async () => mockConversation,
    putMessageReaction: async () => ({ success: true }),
    postAskCommunity: async () => mockAskCommunityResponse,
};

const mockApi: IAiConversationsApi = {
    getConversation: vitest.fn(fakeAPI.getConversation),
    getConversations: vitest.fn(fakeAPI.getConversations),
    postNewConversation: vitest.fn(fakeAPI.postNewConversation),
    postConversationReply: vitest.fn(fakeAPI.postConversationReply),
    putMessageReaction: vitest.fn(fakeAPI.putMessageReaction),
    postAskCommunity: vitest.fn(fakeAPI.postAskCommunity),
};

function queryClientWrapper() {
    const queryClient = new QueryClient();
    const Wrapper = ({ children }) => <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>;

    return Wrapper;
}

function renderChatInterface(conversationID?: number) {
    const QueryClientWrapper = queryClientWrapper();
    const result = render(
        <QueryClientWrapper>
            <AiConversationsApiProvider api={mockApi}>
                <AiChatInterfaceImpl conversationID={conversationID} />
            </AiConversationsApiProvider>
        </QueryClientWrapper>,
    );
    return result;
}

function renderChatModal(conversationID?: number) {
    const QueryClientWrapper = queryClientWrapper();
    const result = render(
        <QueryClientWrapper>
            <AiConversationsApiProvider api={mockApi}>
                <AiChatInterfaceModal conversationID={conversationID} isVisible={true} onClose={() => {}} />
            </AiConversationsApiProvider>
        </QueryClientWrapper>,
    );
    return result;
}

const userQuestionText = "who is rob wenger?";
const assistantResponseText =
    "Rob Wenger is the co-founder and current CEO of Higher Logic, a company focused on building solutions for member engagement within associations, non-profits, and businesses. He has a history of success in innovation and industry partnerships and recently returned to the role of CEO after a previous tenure. His leadership is centered on connecting people and knowledge to improve organizational effectiveness. Wenger's return has been met with enthusiasm from the community, highlighting his commitment to enhancing member engagement.";

describe("Ai Chat Interface", () => {
    let result: RenderResult;

    afterEach(() => {
        mockAdapter.reset();
        vitest.clearAllMocks();
    });

    it("should render the chat interface container with a loader", async () => {
        result = renderChatInterface(1);
        await vi.dynamicImportSettled();

        expect(result.getByText(/Loading/)).toBeInTheDocument();
    });

    it("should render existing messages in the conversation", async () => {
        result = renderChatInterface(3);
        await vi.dynamicImportSettled();

        expect(await result.findByText(userQuestionText)).toBeInTheDocument();
        expect(await result.findByText(assistantResponseText)).toBeInTheDocument();
    });

    it("should render references in the conversation in a collapsible section", async () => {
        result = renderChatInterface(3);
        await vi.dynamicImportSettled();

        expect(await result.findByText(assistantResponseText)).toBeInTheDocument();

        const referencesButton = result.getByRole("button", { name: "6 Sources" });
        fireEvent.click(referencesButton);

        const firstReference = await result.findByText("Adding CSS/JS for Custom Themes");

        expect(firstReference).toBeVisible();

        const firstReferenceLink = firstReference.closest("a") as HTMLAnchorElement;

        // Should have the correct utm parameters
        expect(firstReferenceLink.href).toContain("?utm_source=ai-assistant&utm_medium=chat&utm_campaign=openAI");
    });

    it("should allow the user to reply", async () => {
        result = renderChatInterface(3);
        await vi.dynamicImportSettled();

        const input = result.getByPlaceholderText("Ask me anything...");
        const button = result.getByRole("button");

        await act(async () => {
            input.focus();
            fireEvent.change(input, { target: { value: "tell me about Vanilla" } });
        });

        expect(input).toHaveValue("tell me about Vanilla");

        await act(async () => {
            fireEvent.click(button);
        });

        expect(input).not.toHaveValue("tell me about Vanilla");

        // Check the reply was sent
        expect(mockApi.postConversationReply).toHaveBeenCalledWith({
            conversationID: 3,
            body: "tell me about Vanilla",
        });
        expect(mockApi.postConversationReply).toHaveBeenCalledTimes(1);

        // Check that the user's reply is displayed in the chat window
        expect(await result.findByText("tell me about Vanilla")).toBeInTheDocument();

        // The updated conversation should be fetched after replying
        expect(mockApi.getConversation).toHaveBeenCalledWith({ conversationID: 3 });
    });

    it("should allow the user to react to a message", async () => {
        vitest.useFakeTimers();
        result = renderChatInterface(3);
        await vi.dynamicImportSettled();
        expect(await result.findByText(assistantResponseText)).toBeInTheDocument();

        const likeButton = result.getByRole("button", { name: "Like" });
        const dislikeButton = result.getByRole("button", { name: "Dislike" });

        await act(async () => {
            fireEvent.click(likeButton);
            vi.advanceTimersByTime(1000); // Simulate debounce delay
        });

        // Expect the first reaction to succeed
        expect(mockApi.putMessageReaction).toHaveBeenCalledWith({
            conversationID: 3,
            messageID: "b6c7597c-f36e-4531-85f0-9d722a0ab7c6",
            reaction: "like",
        });
        expect(mockApi.putMessageReaction).toHaveBeenCalledTimes(1);

        await act(async () => {
            fireEvent.click(dislikeButton);
            vi.advanceTimersByTime(1000); // Simulate debounce delay
        });

        // Expect the second reaction to succeed, users can change reactions
        expect(mockApi.putMessageReaction).toHaveBeenCalledWith({
            conversationID: 3,
            messageID: "b6c7597c-f36e-4531-85f0-9d722a0ab7c6",
            reaction: "dislike",
        });

        expect(mockApi.putMessageReaction).toHaveBeenCalledTimes(2);
    });

    it("should allow the user to ask the community", async () => {
        result = renderChatModal(3);
        await vi.dynamicImportSettled();

        expect(await result.findByText(assistantResponseText)).toBeInTheDocument();

        const askCommunityButton = result.getByRole("button", { name: "Ask the Community" });

        await act(async () => {
            fireEvent.click(askCommunityButton);
        });

        expect(mockApi.postAskCommunity).toHaveBeenCalledWith({
            conversationID: 3,
        });
        expect(mockApi.postAskCommunity).toHaveBeenCalledTimes(1);
    });

    describe("starter message", () => {
        const starterMessage =
            "Hi there! I’m your AI Assistant. Ask me anything about this community and I’ll help you find answers—or tell me what you’re looking for, and I’ll help you ask the community.";

        it("should show the starter message when no conversation ID is provided", async () => {
            result = renderChatInterface(undefined);
            await vi.dynamicImportSettled();

            expect(result.getByText(starterMessage)).toBeInTheDocument();
        });

        it("should not show the starter message when a conversation ID is provided", async () => {
            result = renderChatInterface(3);
            await vi.dynamicImportSettled();

            expect(result.queryByText(starterMessage)).not.toBeInTheDocument();
        });
    });
});
