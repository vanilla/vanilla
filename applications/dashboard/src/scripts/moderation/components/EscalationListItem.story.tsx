import { CommunityManagementFixture } from "@dashboard/moderation/__fixtures__/CommunityManagement.Fixture";
import { EscalationListItem as EscalationListItemComponent } from "@dashboard/moderation/components/EscalationListItem";

import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";

export default {
    title: "Dashboard/Community Management",
};

const queryClient = new QueryClient();

const mockEscalation = CommunityManagementFixture.getEscalation({
    name: "Make $10,000 in a week! Click here!",
    placeRecordName: "Automotive Support",
    countComments: 0,
    countReports: 8,
    reportUsers: [
        UserFixture.createMockUser({ name: "Don" }),
        UserFixture.createMockUser({ name: "User 5" }),
        UserFixture.createMockUser({ name: "User 7" }),
        UserFixture.createMockUser({ name: "User 3" }),
    ],
    reportReasons: CommunityManagementFixture.getReasons(["Spam", "Scam / Misleading"]),
});

export function EscalationListItem() {
    return (
        <QueryClientProvider client={queryClient}>
            <EscalationListItemComponent
                escalation={mockEscalation}
                onMessageAuthor={(messageInfo) => null}
                onRecordVisibilityChange={(boolean) => null}
            />
        </QueryClientProvider>
    );
}
