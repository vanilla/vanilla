/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { CommunityManagementFixture } from "@dashboard/moderation/__fixtures__/CommunityManagement.Fixture";
import { ReportGroup as ReportGroupComponent } from "@dashboard/moderation/components/ReportGroup";
import { storyWithConfig } from "@library/storybook/StoryContext";

export default {
    title: "Dashboard/Community Management",
};

function ReportGroupStory() {
    const reportGroup = CommunityManagementFixture.getReportGroup({
        recordName: "How to fix a flat tire",
        placeRecordName: "Automotive Support",
    });
    return <ReportGroupComponent to={"#"} reportGroup={reportGroup} />;
}

export const ReportGroup = storyWithConfig({}, () => {
    return <ReportGroupStory />;
});
