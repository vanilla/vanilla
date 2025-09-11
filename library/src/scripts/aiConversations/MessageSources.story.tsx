/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import MessageSources, { MessageSourcesList } from "@library/aiConversations/MessageSources";
import { StoryHeading } from "@library/storybook/StoryHeading";

const sampleMessage = {
    messageID: "b4232fb2-6924-46dd-848c-874acdaddff1",
    body: "<div><p>It seems there is no detailed information available specifically about the features of Higher Logic Vanilla in the current resources. However, you may explore community discussions that could provide insight into various functionalities and capabilities.</p><p>For instance, you can start from discussions like <a href='https://demo.vanillawip.com/en/discussion/25453/data' x-hl-key='25453'>Data</a> and others, which might touch on related topics.</p></div>",
    feedback: null,
    confidence: null,
    dateInserted: "2025-06-13T15:00:10+00:00",
    user: "Assistant",
    reaction: null,
    references: [
        {
            recordID: "25453",
            recordType: "discussion",
            name: "Adding CSS/JS for Custom Themes",
            url: "https://demo.vanillawip.com/en/discussion/25453/data",
        },
        {
            recordID: "25451",
            recordType: "discussion",
            name: "New Feature Alert: Curate your Title Bar with role based permissions",
            url: "https://demo.vanillawip.com/en/discussion/25451/test",
        },
        {
            recordID: "25450",
            recordType: "question",
            name: "Which widget is used for this carousel below in the success community?",
            url: "https://demo.vanillawip.com/fr/discussion/25450/test",
        },
    ],
};

export default {
    title: "Ai Conversations/Message Sources",
    parameters: {
        chromatic: {
            viewports: [1400, 500],
        },
    },
};

function ChatMessageSources() {
    return (
        <>
            <div>
                <StoryHeading depth={1}>Inner List</StoryHeading>

                <MessageSourcesList sources={sampleMessage.references} />
            </div>

            <div style={{ marginBottom: "4em" }}>
                <StoryHeading depth={1}>As Tray</StoryHeading>

                <MessageSources message={sampleMessage} />
            </div>
        </>
    );
}

export function MessageSourcesStory() {
    return <ChatMessageSources />;
}
