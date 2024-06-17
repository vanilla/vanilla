import { EscalationList as EscalationListImpl } from "@dashboard/moderation/components/EscalationList";
import { CommunityManagementFixture } from "@dashboard/moderation/__fixtures__/CommunityManagement.Fixture";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";

export default {
    title: "Dashboard/Community Management",
};

const queryClient = new QueryClient();

export function EscalationList() {
    return (
        <QueryClientProvider client={queryClient}>
            <EscalationListImpl
                escalations={[
                    CommunityManagementFixture.getEscalation({
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
                    }),
                    CommunityManagementFixture.getEscalation({
                        name: "Understanding Quantum Physics",
                        placeRecordName: "Welcome Posts",
                        reportReasons: CommunityManagementFixture.getReasons(["Off Topic"]),
                        countReports: 1,
                        countComments: 0,
                        reportUsers: [UserFixture.createMockUser({ name: "John Smith" })],
                    }),
                    CommunityManagementFixture.getEscalation({
                        name: "Re: Favorite Sci-Fi Movies",
                        placeRecordName: "Awesome Media",
                        insertUser: UserFixture.createMockUser({ name: "Johny Appleseed" }),
                        reportReasons: CommunityManagementFixture.getReasons(["Personal Attacks", "Hate Speech"]),
                        status: "In Progress",
                        countComments: 16,
                        countReports: 4,
                        reportUsers: [
                            UserFixture.createMockUser({ name: "John Smith" }),
                            UserFixture.createMockUser({ name: "User 2" }),
                            UserFixture.createMockUser({ name: "User 3" }),
                            UserFixture.createMockUser({ name: "User 4" }),
                        ],
                        recordIsLive: false,
                    }),
                    CommunityManagementFixture.getEscalation({
                        name: "Re: Crazy Wildfires in Western Canada",
                        placeRecordName: "News & Politics",
                        reportReasons: CommunityManagementFixture.getReasons(["Misinformation"]),
                        assignedUser: UserFixture.createMockUser({ name: "Jane Doe" }),
                        assignedUserID: 5,
                        countReports: 2,
                        reportUsers: [
                            UserFixture.createMockUser({ name: "John Smith" }),
                            UserFixture.createMockUser({ name: "User 2" }),
                        ],
                        status: "In Progress",
                    }),
                ]}
            />
        </QueryClientProvider>
    );
}
