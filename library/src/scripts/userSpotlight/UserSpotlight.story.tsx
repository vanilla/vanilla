/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { BorderType } from "@library/styles/styleHelpers";
import { UserSpotlightWidget } from "@library/userSpotlight/UserSpotlightWidget";
import { DeepPartial } from "redux";
import { IUserSpotlightOptions } from "@library/userSpotlight/UserSpotlight.variables";
import { STORY_USER, STORY_IPSUM_MEDIUM } from "@library/storybook/storyData";
import { UserSpotlightWidgetPreview } from "@library/userSpotlight/UserSpotlightWidget.preview";

export default {
    title: "Widgets/UserSpotlight",
    parameters: {},
};

function UserSpotlightInit(props: Partial<React.ComponentProps<typeof UserSpotlightWidget>>) {
    const content = {
        title: props.title,
        description: props?.description,
        userTextAlignment: props?.userTextAlignment as "left" | "right",
        userInfo: {
            userID: STORY_USER.userID,
            dateLastActive: STORY_USER.dateLastActive,
            title: props?.userInfo?.title ? props.userInfo.title : "",
            name: STORY_USER.name,
            photoUrl: STORY_USER.photoUrl,
            url: "/somewhere",
        },
    };

    const options: DeepPartial<IUserSpotlightOptions> = {
        borderType: BorderType.SHADOW,
        ...props?.containerOptions,
    };
    return <UserSpotlightWidget {...content} containerOptions={options} />;
}

export const TitleDescriptionBorderAlignmentPanelVariants = storyWithConfig({}, () => (
    <>
        <StoryHeading>With Title, Description, User Title and Shadow</StoryHeading>
        <StoryContent>
            <UserSpotlightInit
                title={"My Title"}
                description={STORY_IPSUM_MEDIUM}
                userInfo={{
                    userID: STORY_USER.userID,
                    dateLastActive: STORY_USER.dateLastActive,
                    title: STORY_USER.title,
                    name: STORY_USER.name,
                    photoUrl: STORY_USER.photoUrl,
                }}
                containerOptions={{
                    borderType: BorderType.SHADOW,
                }}
            />
        </StoryContent>
        <StoryHeading>User Name and User Title aligned to right</StoryHeading>
        <StoryContent>
            <UserSpotlightInit
                title={"My Title"}
                description={STORY_IPSUM_MEDIUM}
                userInfo={{
                    userID: STORY_USER.userID,
                    dateLastActive: STORY_USER.dateLastActive,
                    title: STORY_USER.title,
                    name: STORY_USER.name,
                    photoUrl: STORY_USER.photoUrl,
                }}
                userTextAlignment={"right"}
                containerOptions={{
                    borderType: BorderType.SHADOW,
                }}
            />
        </StoryContent>
        <StoryHeading>Without Description and Bordered</StoryHeading>
        <StoryContent>
            <UserSpotlightInit
                title={"My Title"}
                userInfo={{
                    userID: STORY_USER.userID,
                    dateLastActive: STORY_USER.dateLastActive,
                    title: STORY_USER.title,
                    name: STORY_USER.name,
                    photoUrl: STORY_USER.photoUrl,
                }}
                containerOptions={{
                    borderType: BorderType.BORDER,
                }}
            />
        </StoryContent>
        <StoryHeading>Without Title and User Title, no Border</StoryHeading>
        <StoryContent>
            <UserSpotlightInit
                description={STORY_IPSUM_MEDIUM}
                containerOptions={{
                    borderType: BorderType.NONE,
                }}
            />
        </StoryContent>
        <StoryHeading>In Panel</StoryHeading>
        <StoryContent>
            <div style={{ width: 200, margin: "0 auto" }}>
                <UserSpotlightInit
                    description={STORY_IPSUM_MEDIUM}
                    title={"My Title"}
                    userInfo={{
                        userID: STORY_USER.userID,
                        dateLastActive: STORY_USER.dateLastActive,
                        title: STORY_USER.title,
                        name: STORY_USER.name,
                        photoUrl: STORY_USER.photoUrl,
                    }}
                    containerOptions={{
                        borderType: BorderType.SHADOW,
                    }}
                />
            </div>
        </StoryContent>
    </>
));

export const UserSpotlightPreview = storyWithConfig({}, () => (
    <>
        <StoryHeading>User Spotlight Widget Preview (e.g. in Layout editor/overview pages) </StoryHeading>
        <StoryContent>
            <UserSpotlightWidgetPreview />
        </StoryContent>
    </>
));
