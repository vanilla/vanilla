/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IReport } from "@dashboard/moderation/CommunityManagementTypes";
import { CommunityManagementFixture } from "@dashboard/moderation/__fixtures__/CommunityManagement.Fixture";
import { ReportListItem as ReportListItemComponent } from "@dashboard/moderation/components/ReportListItem";
import { IUserFragment } from "@library/@types/api/users";
import { List } from "@library/lists/List";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";

export default {
    title: "Dashboard/Community Management",
};

function ReportListItemStory(props: { report?: IReport }) {
    const report = CommunityManagementFixture.getReport({
        recordName: "How to make money online fast!",
        placeRecordName: "Automotive Support",
        insertUser: {
            name: "Malcom",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/164/nQQG7FTJACOTX.jpg",
        } as IUserFragment,
    });
    return <ReportListItemComponent to={"#"} report={props?.report ?? report} onEscalate={() => null} />;
}

const reports: Array<Partial<IReport>> = [
    {
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
    },
    {
        recordName: "Re: Upgrading from version 7",
        recordHtml:
            "Don't even bother. The new version is a disaster, it incompatible with Internet Explorer 7! I can't believe they would release something so broken.",
        placeRecordName: "Software and Firmware",
        insertUser: {
            name: "John",
        } as IUserFragment,
        recordUser: {
            name: "Stacy",
            photoUrl: "https://us.v-cdn.net/6030677/uploads/userpics/ZIZVKLY3XC01/nMGCC1K9ZLF6O.jpg",
        } as IUserFragment,
        noteHtml: "This comment is off-topic and unhelpful.",
    },
    {
        recordName: "Re: Selling **Like New** 2019 Toyota Camry",
        recordHtml:
            "This is such a bad deal! Go to sellmycar[dot]com, provide your registration and home address and the car will be off your hands in no time!",
        placeRecordName: "Buy and Sell",
        insertUser: {
            name: "Alice",
        } as IUserFragment,
        recordUser: {
            name: "haxx0r",
        } as IUserFragment,
        noteHtml: "This user is a known scammer and should be banned.",
    },
];

const queryClient = new QueryClient();

export const ReportListItem = storyWithConfig({}, () => {
    return (
        <QueryClientProvider client={queryClient}>
            <List>
                {reports.map((report, index) => {
                    const rep = CommunityManagementFixture.getReport(report);
                    return <ReportListItemStory key={index} report={rep} />;
                })}
            </List>
        </QueryClientProvider>
    );
});
