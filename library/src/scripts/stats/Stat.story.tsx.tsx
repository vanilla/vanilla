/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import StatTable, { IStatTableProps } from "@library/stats/StatTable";
import DateTime from "@library/content/DateTime";

export default {
    component: StatTable,
    title: "Components/StatTable",
};

const props: IStatTableProps = {
    title: "Joe's Analytics",
    data: {
        points: 20,
        posts: 1300,
        visits: 72,
        joinDate: <DateTime timestamp={"2012-07-25 17:51:15"} />,
        lastActive: <DateTime timestamp={"2020-01-15 12:51:15"} />,
    },
};

const noData: IStatTableProps = {
    title: "Loading Analytics Data...",
    data: {},
};

export const Default = () => (
    <StoryContent>
        <StoryHeading depth={1}>The default view of the Stat Table component</StoryHeading>
        <StatTable {...props} />
    </StoryContent>
);

export const Skeleton = () => (
    <StoryContent>
        <StoryHeading depth={1}>The Stat Table component with no data</StoryHeading>
        <StatTable {...noData} />
    </StoryContent>
);
