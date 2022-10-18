/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import StatTable, { IUserAnalyticsProps } from "@library/stats/StatTable";
import { statClasses } from "./Stat.styles";
import DateTime from "@library/content/DateTime";
import SmartLink from "@library/routing/links/SmartLink";
import { Icon } from "@vanilla/icons";

export default {
    component: StatTable,
    title: "Components/StatTable",
};

function AfterLink() {
    const classes = statClasses();
    return (
        <div>
            <SmartLink className={classes.afterLink} to={"https://success.vanillaforums.com/kb/articles/430"}>
                Check Analytics Data
                <Icon icon={"meta-external"} />
            </SmartLink>
        </div>
    );
}

const props: IUserAnalyticsProps = {
    title: "Joe's Analytics",
    userInfo: {
        points: 20,
        posts: 1300,
        visits: 72,
        joinDate: <DateTime timestamp={"2012-07-25 17:51:15"} />,
        lastActive: <DateTime timestamp={"2020-01-15 12:51:15"} />,
    },
    afterLink: <AfterLink />,
};

const noData: IUserAnalyticsProps = {
    title: "Loading Analytics Data...",
    userInfo: {},
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
