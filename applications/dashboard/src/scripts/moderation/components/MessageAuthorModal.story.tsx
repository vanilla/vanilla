/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { MessageAuthorModal as MessageAuthorModalComponent } from "@dashboard/moderation/components/MessageAuthorModal";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";

export default {
    title: "Dashboard/Community Management",
};

const queryClient = new QueryClient();

export const MessageAuthorModal = storyWithConfig({}, () => {
    return (
        <QueryClientProvider client={queryClient}>
            <MessageAuthorModalComponent
                messageInfo={{
                    userID: 2,
                    url: "https://vanillaforums.com",
                }}
                isVisible={true}
                onClose={() => null}
            />
        </QueryClientProvider>
    );
});
