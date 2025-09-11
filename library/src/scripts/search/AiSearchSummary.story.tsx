/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import AiSearchSummary, { AiSearchSourcesPanel } from "@library/search/AiSearchSummary";
import { AiSearchSources } from "@library/search/AiSearchSummary";
import { StoryHeading } from "@library/storybook/StoryHeading";
import {
    mockConversation,
    mockConversationArray,
    mockAskCommunityResponse,
} from "@library/aiConversations/AiConversations.fixtures";
import { AiConversationsApiProvider } from "@library/aiConversations/AiConversations.context";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { PermissionsContextProvider } from "@library/features/users/PermissionsContext";
import { setMeta } from "@library/utility/appUtils";
import { LoadStatus } from "@library/@types/api/core";

const mockSources = [
    {
        recordID: "1",
        recordType: "discussion",
        name: "How to set up a new community",
        url: "/discussion/1/how-to-set-up-a-new-community",
        dateUpdated: "2024-01-15T10:31:00Z",
    },
    {
        recordID: "2",
        recordType: "comment",
        name: "Re: Best practices for moderation",
        url: "/discussion/5/comment/2",
        dateUpdated: "2024-01-14T15:45:00Z",
    },
    {
        recordID: "3",
        recordType: "article",
        name: "Community Guidelines and Rules",
        url: "/article/3/community-guidelines",
        dateUpdated: "2024-01-10T09:20:00Z",
    },
    {
        recordID: "4",
        recordType: "discussion",
        name: "How to set up a new community",
        url: "/discussion/1/how-to-set-up-a-new-community",
        dateUpdated: "2024-01-15T10:30:00Z",
    },
    {
        recordID: "5",
        recordType: "discussion",
        name: "How to set up a new community",
        url: "/discussion/1/how-to-set-up-a-new-community",
        dateUpdated: "2024-01-15T10:30:00Z",
    },
    {
        recordID: "6",
        recordType: "discussion",
        name: "How to use the AI Assistant",
        url: "/discussion/1/how-to-use-the-ai-assistant",
        dateUpdated: "2024-01-15T10:30:00Z",
    },
    {
        recordID: "7",
        recordType: "discussion",
        name: "Custom layout pages FAQ",
        url: "/discussion/1/how-to-set-up-a-new-community",
        dateUpdated: "2024-01-15T10:30:00Z",
    },
];

setMeta("featureFlags.aiConversation.Enabled", true);

const mockPermissionsState = {
    users: {
        permissions: {
            status: LoadStatus.SUCCESS,
            data: {
                isAdmin: false,
                permissions: [
                    {
                        type: "global",
                        id: null,
                        permissions: {
                            "aiAssistedSearch.view": true,
                        },
                    },
                ],
            },
        },
    },
};

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
                        postAskCommunity: () => Promise.resolve(mockAskCommunityResponse),
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
                        postAskCommunity: () => Promise.resolve(mockAskCommunityResponse),
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
                        postAskCommunity: () => Promise.resolve(mockAskCommunityResponse),
                    }}
                >
                    <AiSearchSummary conversationID={3} />
                </AiConversationsApiProvider>
            </div>
        </>
    );
}

function AiSearchSourcesStory() {
    return (
        <>
            <StoryHeading depth={1}>{"AI Search Sources"}</StoryHeading>

            <div style={{ margin: "30px 0" }}>
                <h2 style={{ margin: "0 0 20px" }}>{"With Sources"}</h2>
                <AiSearchSources sources={mockSources} />
            </div>
        </>
    );
}

function AiSearchSourcesPanelStory() {
    return (
        <TestReduxProvider state={mockPermissionsState}>
            <PermissionsContextProvider>
                <AiConversationsApiProvider
                    api={{
                        getConversation: () => Promise.resolve(mockConversationArray[0]),
                        getConversations: () => Promise.resolve(mockConversationArray),
                        postNewConversation: () => Promise.resolve(mockConversation),
                        postConversationReply: () => Promise.resolve(mockConversation),
                        putMessageReaction: () => Promise.resolve({ success: true }),
                        postAskCommunity: () => Promise.resolve(mockAskCommunityResponse),
                    }}
                >
                    <StoryHeading depth={1}>{"AI Search Sources Panel"}</StoryHeading>

                    <div style={{ margin: "30px 0" }}>
                        <h2 style={{ margin: "0 0 20px" }}>{"With Sources"}</h2>
                        <AiSearchSourcesPanel conversationID={3} />
                    </div>
                </AiConversationsApiProvider>
            </PermissionsContextProvider>
        </TestReduxProvider>
    );
}

export function AiSearchResultsSummary() {
    return <AiSearchSummaryStory />;
}

export function AiSearchSourcesComponent() {
    return <AiSearchSourcesStory />;
}

export function AiSearchSourcesPanelComponent() {
    return <AiSearchSourcesPanelStory />;
}
