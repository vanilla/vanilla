import React from "react";
import { addComponent } from "@library/utility/componentRegistry";
import TabWidget from "@library/tabWidget/TabWidget";
import { TabsTypes } from "@library/sectioning/TabsTypes";
import { fakeDiscussions } from "@library/features/discussions/DiscussionList.story";
import { DiscussionListView } from "@library/features/discussions/DiscussionList.views";

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
                        discussions: fakeDiscussions,
                    },
                },
                {
                    label: "Top Discussions",
                    componentName: "DiscussionList",
                    componentProps: {
                        discussions: fakeDiscussions.slice(0, 2),
                    },
                },
            ]}
        />
    );
}
