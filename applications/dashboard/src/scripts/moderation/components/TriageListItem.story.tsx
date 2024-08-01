import { IDiscussion } from "@dashboard/@types/api/discussion";
import { TriageListItem as TriageListItemComponent } from "@dashboard/moderation/components/TriageListItem";
import { STORY_DISCUSSION } from "@library/storybook/storyData";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";

export default {
    title: "Dashboard/Community Management",
};

const queryClient = new QueryClient();

export function TriageListItem() {
    return (
        <QueryClientProvider client={queryClient}>
            <TriageListItemComponent
                discussion={STORY_DISCUSSION}
                onEscalate={(discussion: IDiscussion) => null}
                onMessageAuthor={(authorID, recordUrl) => null}
            />
        </QueryClientProvider>
    );
}
