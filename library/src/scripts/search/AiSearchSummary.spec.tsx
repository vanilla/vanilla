/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { render, RenderResult, screen } from "@testing-library/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { mockAPI } from "@library/__tests__/utility";
import { IAiConversationsApi } from "@library/aiConversations/AiConversations.types";
import { mockConversation } from "@library/aiConversations/AiConversations.fixtures";
import { AiConversationsApiProvider } from "@library/aiConversations/AiConversations.context";
import AiSearchSummary from "@library/search/AiSearchSummary";

const mockAdapter = mockAPI();

const fakeAPI: IAiConversationsApi = {
    getConversation: async () => mockConversation,
    getConversations: async () => [mockConversation],
    postNewConversation: async () => mockConversation,
    postConversationReply: async () => mockConversation,
    putMessageReaction: async () => ({ success: true }),
};

const mockApi: IAiConversationsApi = {
    getConversation: vitest.fn(fakeAPI.getConversation),
    getConversations: vitest.fn(fakeAPI.getConversations),
    postNewConversation: vitest.fn(fakeAPI.postNewConversation),
    postConversationReply: vitest.fn(fakeAPI.postConversationReply),
    putMessageReaction: vitest.fn(fakeAPI.putMessageReaction),
};

const assistantResponseText =
    "Rob Wenger is the co-founder and current CEO of Higher Logic, a company focused on building solutions for member engagement within associations, non-profits, and businesses. He has a history of success in innovation and industry partnerships and recently returned to the role of CEO after a previous tenure. His leadership is centered on connecting people and knowledge to improve organizational effectiveness. Wenger's return has been met with enthusiasm from the community, highlighting his commitment to enhancing member engagement.";

function queryClientWrapper() {
    const queryClient = new QueryClient();
    const Wrapper = ({ children }) => <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>;

    return Wrapper;
}

function renderSummary(conversationID?: number) {
    const QueryClientWrapper = queryClientWrapper();
    const result = render(
        <QueryClientWrapper>
            <AiConversationsApiProvider api={mockApi}>
                <AiSearchSummary conversationID={conversationID} />
            </AiConversationsApiProvider>
        </QueryClientWrapper>,
    );
    return result;
}

describe("Ai Search Results Summary", () => {
    let result: RenderResult;

    afterEach(() => {
        mockAdapter.reset();
        vitest.clearAllMocks();
    });

    it("should render the summary container with a loader", async () => {
        result = renderSummary();
        await vi.dynamicImportSettled();

        const title = result.getByText("AI Summary");

        expect(title).toBeInTheDocument();

        expect(screen.queryByText(assistantResponseText)).not.toBeInTheDocument();
        expect(await screen.findByText(/Loading/)).toBeInTheDocument();
    });

    it("should render the correct AI response when the loader is finished", async () => {
        result = renderSummary(1);
        await vi.dynamicImportSettled();

        expect(await result.findByText(assistantResponseText)).toBeInTheDocument();
    });

    it("should allow the user to launch a chat window", async () => {
        result = renderSummary(1);
        await vi.dynamicImportSettled();

        const button = result.getByText("Launch chat");
        expect(button).toBeInTheDocument();

        button.click();
        expect(result.getByRole("dialog")).toBeInTheDocument();
    });
});
