/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { BorderType } from "@library/styles/styleHelpers";
import { UserSpotlight } from "@library/userSpotlight/UserSpotlight";
import { DeepPartial } from "redux";
import { IUserSpotlightOptions } from "@library/userSpotlight/UserSpotlight.variables";
import {
    STORY_USER,
    STORY_IMAGE,
    STORY_IPSUM_LONG,
    STORY_IPSUM_MEDIUM,
    STORY_IPSUM_SHORT,
} from "@library/storybook/storyData";
import { css } from "@emotion/css";

export default {
    title: "Widgets/UserSpotlight",
    parameters: {},
};

function UserSpotlightInit(props: Partial<React.ComponentProps<typeof UserSpotlight>>) {
    const content = {
        title: props.title,
        description: props?.description,
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
        box: {
            borderType: BorderType.SHADOW,
        },
        ...props?.options,
    };
    return <UserSpotlight {...content} options={options} />;
}

export const TitleDescriptionBorderAlignmentPanelVariants = storyWithConfig({}, () => (
    <>
        <StoryHeading>With Title, Description, User Title and Shadow </StoryHeading>
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
                options={{
                    box: {
                        borderType: BorderType.SHADOW,
                    },
                }}
            />
        </StoryContent>
        <StoryHeading>User Name and User Title aligned to right </StoryHeading>
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
                options={{
                    box: {
                        borderType: BorderType.SHADOW,
                    },
                    userTextAlignment: "right",
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
                options={{
                    box: {
                        borderType: BorderType.BORDER,
                    },
                }}
            />
        </StoryContent>
        <StoryHeading>Without Title and User Title, no Border</StoryHeading>
        <StoryContent>
            <UserSpotlightInit
                description={STORY_IPSUM_MEDIUM}
                options={{
                    box: {
                        borderType: BorderType.NONE,
                    },
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
                    options={{
                        box: {
                            borderType: BorderType.SHADOW,
                        },
                    }}
                />
            </div>
        </StoryContent>
    </>
));
