/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IReport } from "@dashboard/moderation/CommunityManagementTypes";
import { CommunityManagementFixture } from "@dashboard/moderation/__fixtures__/CommunityManagement.Fixture";
import { ReportListItem as ReportListItemComponent } from "@dashboard/moderation/components/ReportListItem";
import { IUserFragment } from "@library/@types/api/users";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";

export default {
    title: "Dashboard/Community Management",
};

const reportPartial: Partial<IReport> = {
    recordName: "Make $10,000 in a week! Click here!",
    recordHtml:
        "Hey everyone! I just stumbled upon this amazing opportunity to make $10,000 in a week! Its really changed my life and I know it can change yours too! Simply go to 👉makemoneyfast[dot]com👈 and input your social insurance number.",
    placeRecordName: "General",
    insertUser: {
        name: "Samantha",
        photoUrl: "https://us.v-cdn.net/6030677/uploads/userpics/732/nRK2ANA30M5HN.jpg",
    } as IUserFragment,
    recordUser: {
        name: "Milo",
        photoUrl: "https://us.v-cdn.net/6030677/uploads/userpics/1PEUJTENJ7YM/nKQQZ8A0LHB3X.jpg",
    } as IUserFragment,
    noteHtml: "This post is spam. It's a scam.",
};
const queryClient = new QueryClient();

export const ReportListItem = storyWithConfig({}, () => {
    const report = CommunityManagementFixture.getReport(reportPartial);
    return (
        <QueryClientProvider client={queryClient}>
            <ReportListItemComponent report={report} />
        </QueryClientProvider>
    );
});
