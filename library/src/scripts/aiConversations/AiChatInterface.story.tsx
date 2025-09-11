/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useState } from "react";
import { StoryHeading } from "@library/storybook/StoryHeading";
import Button from "@library/forms/Button";
import { ButtonType } from "@library/forms/buttonTypes";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { CurrentUserContextProvider } from "@library/features/users/userHooks";
import {
    mockConversation,
    mockConversationArray,
    mockAskCommunityResponse,
} from "@library/aiConversations/AiConversations.fixtures";
import { AiConversationsApiProvider } from "@library/aiConversations/AiConversations.context";
import { AiChatInterfaceImpl, AiChatInterfaceModal } from "@library/aiConversations/AiChatInterface";

export default {
    title: "Ai Conversations/Ai Chat Interface",
    parameters: {
        chromatic: {
            viewports: [1400, 500],
        },
    },
};

function AiChatInterfaceStoryImpl() {
    const [isModalVisible, setIsModalVisible] = useState(false);

    return (
        <div>
            <CurrentUserContextProvider currentUser={UserFixture.adminAsCurrent.data}>
                <AiConversationsApiProvider
                    api={{
                        getConversation: () => Promise.resolve(mockConversationArray[3]),
                        getConversations: () => Promise.resolve(mockConversationArray),
                        postNewConversation: () => Promise.resolve(mockConversation),
                        postConversationReply: () => Promise.resolve(mockConversation),
                        putMessageReaction: () => Promise.resolve({ success: true }),
                        postAskCommunity: () => Promise.resolve(mockAskCommunityResponse),
                    }}
                >
                    <StoryHeading depth={1}>As Modal</StoryHeading>

                    <Button
                        buttonType={ButtonType.PRIMARY}
                        onClick={() => setIsModalVisible(!isModalVisible)}
                        style={{ marginBottom: "4em" }}
                    >
                        Ask AI
                    </Button>

                    <AiChatInterfaceModal
                        isVisible={isModalVisible}
                        onClose={() => setIsModalVisible(false)}
                        conversationID={3}
                    />

                    <StoryHeading depth={1}>Inner Content</StoryHeading>

                    <AiChatInterfaceImpl conversationID={3} />
                </AiConversationsApiProvider>
            </CurrentUserContextProvider>
        </div>
    );
}

export function AiChatInterfaceStory() {
    return <AiChatInterfaceStoryImpl />;
}
