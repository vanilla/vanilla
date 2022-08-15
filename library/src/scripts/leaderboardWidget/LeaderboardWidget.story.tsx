import React from "react";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { LeaderboardWidget } from "@library/leaderboardWidget/LeaderboardWidget";
import { STORY_LEADERS } from "@library/storybook/storyData";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { LoadStatus } from "@library/@types/api/core";
import { STORY_USER_ID } from "@library/features/userCard/UserCard.story";
import SectionTwoColumns from "@library/layout/TwoColumnSection";
import { WidgetLayout } from "@library/layout/WidgetLayout";
import { WidgetContainerDisplayType } from "@library/homeWidget/HomeWidgetContainer.styles";

export default {
    title: "Widgets/Leaderboard",
};

const baseProps = {
    title: "Widget title (All Time Leaders)",
    subtitle: "Widget subtitle",
    description: "Members with the most points in your community",
    leaders: STORY_LEADERS,
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
                    <SectionTwoColumns
                        mainBottom={<LeaderboardWidget {...baseProps} />}
                        secondaryBottom={<LeaderboardWidget {...baseProps} />}
                    ></SectionTwoColumns>
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
                    <SectionTwoColumns
                        mainBottom={
                            <LeaderboardWidget
                                containerOptions={{ displayType: WidgetContainerDisplayType.GRID, maxColumnCount: 3 }}
                                {...baseProps}
                            />
                        }
                        rightBottom={
                            <LeaderboardWidget
                                containerOptions={{ displayType: WidgetContainerDisplayType.GRID, maxColumnCount: 2 }}
                                {...baseProps}
                            />
                        }
                    ></SectionTwoColumns>
                </WidgetLayout>
            </>
        )),
    );
};
