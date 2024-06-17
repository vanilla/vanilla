/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { EscalationDetails } from "@dashboard/moderation/components/EscalationDetails";
import { CommunityManagementFixture } from "@dashboard/moderation/__fixtures__/CommunityManagement.Fixture";
import { dashboardCssDecorator } from "@dashboard/__tests__/dashboardCssDecorator";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { CommentFixture } from "@vanilla/addon-vanilla/thread/__fixtures__/Comment.Fixture";

export default {
    title: "Dashboard/Community Management",
    decorators: [dashboardCssDecorator],
};

const queryClient = new QueryClient();

export function EscalationDetailsStory() {
    return (
        <QueryClientProvider client={queryClient}>
            <EscalationDetails
                escalation={CommunityManagementFixture.getEscalation({
                    name: "Free iPhone 12! Just complete this survey!",
                    placeRecordName: "Welcomes & Introductions",
                })}
                reports={[
                    CommunityManagementFixture.getReport({
                        dateInserted: new Date(2024, 3, 13).toISOString(),
                        insertUser: UserFixture.createMockUser({ name: "Dan Redmond" }),
                        reasons: CommunityManagementFixture.getReasons(["Spam", "Scam / Fraud"]),
                        noteHtml: "",
                    }),
                    CommunityManagementFixture.getReport({
                        dateInserted: new Date(2024, 3, 12).toISOString(),
                        noteHtml: "<p>Spammy spam spam spam</p>",
                        insertUser: UserFixture.createMockUser({ name: "AdamC" }),
                        reasons: CommunityManagementFixture.getReasons(["Spam"]),
                    }),
                    CommunityManagementFixture.getReport({
                        dateInserted: new Date(2024, 3, 12).toISOString(),
                        noteHtml: "<p>My mother got scammed by this!</p>",
                        insertUser: UserFixture.createMockUser({ name: "ShaunaM" }),
                        reasons: CommunityManagementFixture.getReasons(["Scam / Fraud"]),
                    }),
                    CommunityManagementFixture.getReport({
                        dateInserted: new Date(2024, 3, 11).toISOString(),
                        insertUser: UserFixture.createMockUser({ name: "Jarnel Simmons" }),
                        reasons: CommunityManagementFixture.getReasons(["Spam"]),
                        noteHtml: "",
                    }),
                ]}
                comments={[
                    CommentFixture.comment({
                        dateInserted: new Date(2024, 3, 11).toISOString(),
                        insertUser: UserFixture.createMockUser({
                            name: "ShaunaM",
                            title: "Site Admin",
                            countPosts: 425,
                        }),
                        body: "<p>This one is getting pretty bad, I think we should remove it.</p>",
                    }),
                    CommentFixture.comment({
                        dateInserted: new Date(2024, 3, 12).toISOString(),
                        insertUser: UserFixture.createMockUser({
                            name: "AdamC",
                            title: "Super Moderator",
                            countPosts: 43,
                        }),
                        body: "<p>Yup, on it. You're good to remove it.</p>",
                    }),
                    CommentFixture.comment({
                        dateInserted: new Date(2024, 3, 13).toISOString(),
                        insertUser: UserFixture.createMockUser({
                            name: "ShaunaM",
                            title: "Site Admin",
                            countPosts: 425,
                        }),
                        body: "<p>Thanks, it's removed. Let's ban the user as well and setup a ban automation for their IP range. They seem to have a history of this kind of thing.</p>",
                    }),
                ]}
            />
        </QueryClientProvider>
    );
}
