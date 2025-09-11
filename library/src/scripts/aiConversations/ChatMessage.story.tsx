/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import ChatMessage from "@library/aiConversations/ChatMessage";
import { IMessage } from "@library/aiConversations/AiConversations.types";
import { CurrentUserContextProvider } from "@library/features/users/userHooks";
import { STORY_ME_ADMIN } from "@library/storybook/storyData";

export default {
    title: "Ai Conversations/Chat Message",
    parameters: {
        chromatic: {
            viewports: [1400, 500],
        },
    },
};

const sampleHumanMessage: IMessage = {
    messageID: "human-1",
    body: "Hello! Can you help me with a question about my forum?",
    user: "current-user",
};

const sampleAIMessage: IMessage = {
    messageID: "ai-1",
    body: "Of course! I'd be happy to help you with your forum question. What would you like to know?",
    references: [
        {
            recordID: "1",
            recordType: "discussion",
            name: "Help with Forum Setup",
            url: "/discussion/1/help-with-forum-setup",
            dateUpdated: "2024-01-15T10:30:00Z",
        },
    ],
};

const sampleAIMessageWithLongText: IMessage = {
    messageID: "ai-2",
    body: "Here's a detailed response with a lot of information. This message contains multiple paragraphs and should demonstrate how the component handles longer content. The AI assistant can provide comprehensive answers that span multiple lines and include various types of content including links, formatting, and structured information.",
    references: [
        {
            recordID: "2",
            recordType: "discussion",
            name: "Advanced Forum Configuration",
            url: "/discussion/2/advanced-forum-configuration",
            dateUpdated: "2024-01-16T14:20:00Z",
        },
        {
            recordID: "3",
            recordType: "article",
            name: "Forum Best Practices",
            url: "/article/3/forum-best-practices",
            dateUpdated: "2024-01-17T09:15:00Z",
        },
    ],
};

function ChatMessageStory() {
    const handleReaction = (message: IMessage, reaction: "like" | "dislike") => {
        return null;
    };

    return (
        <CurrentUserContextProvider currentUser={STORY_ME_ADMIN}>
            <div style={{ maxWidth: "800px", margin: "0 auto", padding: "20px" }}>
                <h2>Human Message</h2>
                <ChatMessage message={sampleHumanMessage} isAssistant={false} />

                <h2>AI Message (No Reactions)</h2>
                <ChatMessage message={sampleAIMessage} isAssistant={true} />

                <h2>AI Message with Reactions</h2>
                <ChatMessage
                    message={sampleAIMessage}
                    isAssistant={true}
                    handleReaction={handleReaction}
                    currentModel="gpt-4"
                />

                <h2>AI Message with Long Content</h2>
                <ChatMessage
                    message={sampleAIMessageWithLongText}
                    isAssistant={true}
                    handleReaction={handleReaction}
                    currentModel="gpt-4"
                />

                <h2>AI Message with Liked Reaction</h2>
                <ChatMessage
                    message={sampleAIMessage}
                    isAssistant={true}
                    handleReaction={handleReaction}
                    hasBeenLiked={true}
                    currentModel="gpt-4"
                />

                <h2>AI Message with Disliked Reaction</h2>
                <ChatMessage
                    message={sampleAIMessage}
                    isAssistant={true}
                    handleReaction={handleReaction}
                    hasBeenDisliked={true}
                    currentModel="gpt-4"
                />
            </div>
        </CurrentUserContextProvider>
    );
}

export function ChatMessageDefault() {
    return <ChatMessageStory />;
}
