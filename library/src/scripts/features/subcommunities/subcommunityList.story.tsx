/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { DashboardCheckBox } from "@dashboard/forms/DashboardCheckBox";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { DashboardRadioButton } from "@dashboard/forms/DashboardRadioButton";
import { DashboardCheckGroup, DashboardRadioGroup } from "@dashboard/forms/DashboardRadioGroups";
import { dashboardCssDecorator } from "@dashboard/__tests__/dashboardCssDecorator";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React, { useState } from "react";
import SubcommunityList, { SubcommunityListAlignment } from "@library/features/subcommunities/SubcommunityList";

const formsStory = storiesOf("Home Page", module).addDecorator(dashboardCssDecorator);

formsStory.add("Subcommunity List", () =>
    (() => {
        return (
            <>
                <StoryContent>
                    <StoryHeading depth={1}>Sub community List</StoryHeading>
                    <StoryHeading>As Tiles - 2 columns left aligned</StoryHeading>
                </StoryContent>
                <SubcommunityList
                    columns={2}
                    alignment={SubcommunityListAlignment.LEFT}
                    items={[
                        {
                            name: "Development",
                            description: "Processes and guidance for developers.",
                            url: "https://staff.vanillaforums.com/kb/dev",
                            icon: "https://us.v-cdn.net/5022541/uploads/341/G35SLM2LBY4G.png",
                        },
                        {
                            name: "Success",
                            description: "Information for CSMs about troubleshooting & working with Vanilla.",
                            url: "https://staff.vanillaforums.com/kb/success",
                            icon: "https://us.v-cdn.net/5022541/uploads/466/WCXDHD4UMW3K.png",
                        },
                        {
                            name: "Internal Testing",
                            description: "Knowledge for us in internal tests. Don't put anything important here.",
                            url: "https://staff.vanillaforums.com/kb/testing",
                            icon: "https://us.v-cdn.net/5022541/uploads/048/66SQHHGSZT2R.png",
                        },
                        {
                            name: "Information Security",
                            description: "Internal company security practices.",
                            url: "https://staff.vanillaforums.com/kb/infosec",
                            icon: "https://us.v-cdn.net/5022541/uploads/346/B6QMAFIQAXLI.png",
                        },
                        {
                            name: "Information Security",
                            description: "Internal company security practices.",
                            url: "https://staff.vanillaforums.com/kb/infosec",
                            icon: "https://us.v-cdn.net/5022541/uploads/346/B6QMAFIQAXLI.png",
                        },
                    ]}
                    title={"Our Games"}
                    emptyMessage={"No subcommunities found"}
                />
                <StoryContent>
                    <StoryHeading>As Tiles - 3 columns centered</StoryHeading>
                </StoryContent>
                <SubcommunityList
                    columns={3}
                    alignment={SubcommunityListAlignment.CENTER}
                    items={[
                        {
                            name: "Development",
                            description: "Processes and guidance for developers.",
                            url: "https://staff.vanillaforums.com/kb/dev",
                            icon: "https://us.v-cdn.net/5022541/uploads/341/G35SLM2LBY4G.png",
                        },
                        {
                            name: "Success",
                            description: "Information for CSMs about troubleshooting & working with Vanilla.",
                            url: "https://staff.vanillaforums.com/kb/success",
                            icon: "https://us.v-cdn.net/5022541/uploads/466/WCXDHD4UMW3K.png",
                        },
                        {
                            name: "Internal Testing",
                            description: "Knowledge for us in internal tests. Don't put anything important here.",
                            url: "https://staff.vanillaforums.com/kb/testing",
                            icon: "https://us.v-cdn.net/5022541/uploads/048/66SQHHGSZT2R.png",
                        },
                        {
                            name: "Information Security",
                            description: "Internal company security practices.",
                            url: "https://staff.vanillaforums.com/kb/infosec",
                            icon: "https://us.v-cdn.net/5022541/uploads/346/B6QMAFIQAXLI.png",
                        },
                        {
                            name: "Information Security",
                            description: "Internal company security practices.",
                            url: "https://staff.vanillaforums.com/kb/infosec",
                            icon: "https://us.v-cdn.net/5022541/uploads/346/B6QMAFIQAXLI.png",
                        },
                    ]}
                    title={"Our Games"}
                    emptyMessage={"No subcommunities found"}
                />
            </>
        );
    })(),
);
