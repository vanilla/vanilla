import React from "react";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { LeaderboardWidget } from "@library/leaderboardWidget/LeaderboardWidget";
import { STORY_USER } from "@library/storybook/storyData";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { LoadStatus } from "@library/@types/api/core";
import { STORY_USER_ID } from "@library/features/userCard/UserCard.story";
import TwoColumnSection from "@library/layout/TwoColumnSection";
import { WidgetLayout } from "@library/layout/WidgetLayout";

export default {
    title: "Widgets/Leaderboard",
};

const userData = [
    {
        user: STORY_USER,
        points: 320,
    },
    {
        user: {
            ...STORY_USER,
            name: "Christina Morton",
            photoUrl: "https://us.v-cdn.net/6032207/uploads/defaultavatar/nOGOPGSGY4ISZ.jpg",
            userID: 2,
        },
        points: 280,
    },
    {
        user: {
            ...STORY_USER,
            name: "Nazeem Kanaan",
            photoUrl: "https://us.v-cdn.net/6032207/uploads/avatarstock/nA5BAUNMSEDPV.png",
            userID: 3,
        },
        points: 278,
    },
    {
        user: {
            ...STORY_USER,
            name: "Aiden Rosenstengel",
            photoUrl: "https://us.v-cdn.net/6032207/uploads/avatarstock/n2K5HYT9EZOF6.png",
            userID: 4,
        },
        points: 254,
    },
    {
        user: {
            ...STORY_USER,
            name: "Tomás Barros",
            photoUrl: "https://us.v-cdn.net/6032207/uploads/defaultavatar/nOGOPGSGY4ISZ.jpg",
            userID: 5,
        },
        points: 243,
    },
    {
        user: { ...STORY_USER, name: "Lan Tai", userID: 6 },
        points: 241,
    },
    {
        user: {
            ...STORY_USER,
            name: "Ella Jespersen",
            photoUrl: "https://us.v-cdn.net/6032207/uploads/avatarstock/nA5BAUNMSEDPV.png",
            userID: 7,
        },
        points: 221,
    },
    {
        user: { ...STORY_USER, name: "Teus van Uum", userID: 8 },
        points: 212,
    },
    {
        user: {
            ...STORY_USER,
            name: "Michael Baker",
            photoUrl: "https://us.v-cdn.net/6032207/uploads/avatarstock/nA5BAUNMSEDPV.png",
            userID: 9,
        },
        points: 206,
    },
    {
        user: {
            ...STORY_USER,
            name: "Nicholas Lebrun",
            photoUrl: "https://us.v-cdn.net/6032207/uploads/defaultavatar/nOGOPGSGY4ISZ.jpg",
            userID: 10,
        },
        points: 196,
    },
    {
        user: { ...STORY_USER, name: "Matthias Friedman", userID: 11 },
        points: 184,
    },
    {
        user: {
            ...STORY_USER,
            name: "Pupa Zito",
            photoUrl: "https://us.v-cdn.net/6032207/uploads/avatarstock/n2K5HYT9EZOF6.png",
            userID: 12,
        },
        points: 165,
    },
    {
        user: {
            ...STORY_USER,
            name: "Phoebe Cunningham",
            photoUrl: "https://us.v-cdn.net/6032207/uploads/defaultavatar/nOGOPGSGY4ISZ.jpg",
            userID: 13,
        },
        points: 164,
    },
];

const baseProps = {
    title: "Widget title (All Time Leaders)",
    subtitle: "Widget subtitle",
    description: "Members with the most points in your community",
    leaders: userData,
};

const viewCardConfig = {
    users: {
        permissions: {
            status: LoadStatus.SUCCESS,
            data: {
                permissions: [
                    {
                        type: "global",
                        id: STORY_USER_ID,
                        permissions: {
                            "profiles.view": true,
                        },
                    },
                ],
            },
        },
    },
};

export const List = ({ config }: { config?: any }) => {
    return React.createElement(
        storyWithConfig({ storeState: { ...viewCardConfig, ...config } }, () => (
            <>
                <StoryContent>
                    <StoryHeading depth={1}>Leaderboard Widget</StoryHeading>
                    <StoryParagraph>
                        This widget displays a sorted list of users and their associated data. The default use case for
                        this widget is to display user points but can be extended to display other metrics or multiple
                        metrics by augmenting the data.
                    </StoryParagraph>
                </StoryContent>
                <WidgetLayout>
                    <TwoColumnSection
                        mainBottom={<LeaderboardWidget {...baseProps} />}
                        rightBottom={<LeaderboardWidget {...baseProps} />}
                    ></TwoColumnSection>
                </WidgetLayout>
            </>
        )),
    );
};
export const Grid = ({ config }: { config?: any }) => {
    return React.createElement(
        storyWithConfig({ storeState: { ...viewCardConfig, ...config } }, () => (
            <>
                <WidgetLayout>
                    <TwoColumnSection
                        mainBottom={
                            <LeaderboardWidget containerOptions={{ isGrid: true, maxColumnCount: 3 }} {...baseProps} />
                        }
                        rightBottom={
                            <LeaderboardWidget containerOptions={{ isGrid: true, maxColumnCount: 2 }} {...baseProps} />
                        }
                    ></TwoColumnSection>
                </WidgetLayout>
            </>
        )),
    );
};
