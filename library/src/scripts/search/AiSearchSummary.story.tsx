/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import AiSearchSummary from "@library/search/AiSearchSummary";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { mockConversation, mockConversationArray } from "@library/aiConversations/AiConversations.fixtures";
import { AiConversationsApiProvider } from "@library/aiConversations/AiConversations.context";

export default {
    title: "Search/AiSearchResultsSummary",
    parameters: {
        chromatic: {
            viewports: [1400, 500],
        },
    },
};

function AiSearchSummaryStory() {
    return (
        <>
            <StoryHeading depth={1}>{"AI Search Summary"}</StoryHeading>

            <div style={{ margin: "30px 0" }}>
                <h2 style={{ margin: "0 0 20px" }}>{"Success"}</h2>

                <AiConversationsApiProvider
                    api={{
                        getConversation: () => Promise.resolve(mockConversationArray[1]),
                        getConversations: () => Promise.resolve(mockConversationArray),
                        postNewConversation: () => Promise.resolve(mockConversation),
                        postConversationReply: () => Promise.resolve(mockConversation),
                        putMessageReaction: () => Promise.resolve({ success: true }),
                    }}
                >
                    <AiSearchSummary conversationID={1} />
                </AiConversationsApiProvider>
            </div>

            <div style={{ margin: "30px 0" }}>
                <h2 style={{ margin: "0 0 20px" }}>{"Loading"}</h2>

                <AiConversationsApiProvider
                    api={{
                        getConversation: () => Promise.resolve(mockConversationArray[0]),
                        getConversations: () => Promise.resolve(mockConversationArray),
                        postNewConversation: () => Promise.resolve(mockConversation),
                        postConversationReply: () => Promise.resolve(mockConversation),
                        putMessageReaction: () => Promise.resolve({ success: true }),
                    }}
                >
                    {/* Didn't pass conversation ID, to simulate loading state */}
                    <AiSearchSummary />
                </AiConversationsApiProvider>
            </div>

            <div style={{ margin: "30px 0" }}>
                <h2 style={{ margin: "0 0 20px" }}>{"Error"}</h2>

                <AiConversationsApiProvider
                    api={{
                        getConversation: () => Promise.reject(new Error("Error fetching conversation")),
                        getConversations: () => Promise.resolve(mockConversationArray),
                        postNewConversation: () => Promise.resolve(mockConversation),
                        postConversationReply: () => Promise.resolve(mockConversation),
                        putMessageReaction: () => Promise.resolve({ success: true }),
                    }}
                >
                    <AiSearchSummary conversationID={3} />
                </AiConversationsApiProvider>
            </div>
        </>
    );
}

export function AiSearchResultsSummary() {
    return <AiSearchSummaryStory />;
}
