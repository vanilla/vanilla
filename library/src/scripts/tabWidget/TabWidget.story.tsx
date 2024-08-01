import React from "react";
import { addComponent } from "@library/utility/componentRegistry";
import TabWidget from "@library/tabWidget/TabWidget";
import { TabsTypes } from "@library/sectioning/TabsTypes";
import { DiscussionListView } from "@library/features/discussions/DiscussionList.views";
import { DiscussionFixture } from "@vanilla/addon-vanilla/thread/__fixtures__/Discussion.Fixture";

export default {
    title: "Widgets/TabWidget",
};

addComponent("DiscussionList", DiscussionListView);

export function TabbedDiscussionLists() {
    return (
        <TabWidget
            tabType={TabsTypes.BROWSE}
            largeTabs
            includeBorder={false}
            includeVerticalPadding={false}
            tabs={[
                {
                    label: "Latest Discussions",
                    componentName: "DiscussionList",
                    componentProps: {
                        discussions: DiscussionFixture.fakeDiscussions,
                    },
                },
                {
                    label: "Top Discussions",
                    componentName: "DiscussionList",
                    componentProps: {
                        discussions: DiscussionFixture.fakeDiscussions.slice(0, 2),
                    },
                },
            ]}
        />
    );
}
