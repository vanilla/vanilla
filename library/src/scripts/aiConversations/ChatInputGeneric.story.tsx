/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ChatInput } from "@library/aiConversations/ChatInputGeneric";

export default {
    title: "Ai Conversations/Chat Input",
    parameters: {
        chromatic: {
            viewports: [1400, 500],
        },
    },
};

function ChatInputGenericStory() {
    return (
        <>
            <div>
                <ChatInput onSubmit={() => {}} isLoading={false} />
            </div>
        </>
    );
}

export function ChatInputGeneric() {
    return <ChatInputGenericStory />;
}
