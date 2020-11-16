/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryTiles } from "@library/storybook/StoryTiles";
import { StoryTileAndTextCompact } from "@library/storybook/StoryTileAndTextCompact";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { IUserFragment } from "@library/@types/api/users";
import { UserIconTypes } from "@library/icons/titleBar";

export default {
    title: "Components/Avatars",
};

const userDummyData = {
    userID: 1,
    name: "Val",
    photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/164/nQQG7FTJACOTX.jpg",
    dateLastActive: null,
};

function StoryUserIcon(props: { title: string; text?: string; userData?: IUserFragment; styleType?: UserIconTypes }) {
    const { title, text, userData = userDummyData, styleType } = props;
    return (
        <StoryContent>
            <StoryHeading depth={1}>{title}</StoryHeading>
            {text && <StoryParagraph>{text}</StoryParagraph>}
            <StoryTiles>
                <StoryTileAndTextCompact title={"X Large"} text={"For User cards and me box on mobile only"}>
                    <UserPhoto userInfo={userData} size={UserPhotoSize.XLARGE} styleType={styleType} />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={"Large"} text={"For User cards and me box on NOT mobile"}>
                    <UserPhoto userInfo={userData} size={UserPhotoSize.LARGE} styleType={styleType} />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={"Medium"}>
                    <UserPhoto userInfo={userData} size={UserPhotoSize.MEDIUM} styleType={styleType} />
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact title={"Small"}>
                    <UserPhoto userInfo={userData} size={UserPhotoSize.SMALL} styleType={styleType} />
                </StoryTileAndTextCompact>
            </StoryTiles>
        </StoryContent>
    );
}

export const Standard = storyWithConfig({}, () => (
    <>
        <StoryUserIcon title="Working Images" text={"Various sizes of user photos"} />
    </>
));

export const BadImages = storyWithConfig({}, () => (
    <>
        <StoryUserIcon
            title="Broken Images"
            text={"Note that the urls here are bad on purpose and the fallback is used instead"}
            userData={{
                ...userDummyData,
                photoUrl: "http://badURL.example",
            }}
        />
    </>
));

export const DefaultAvatarPrimaryInactive = storyWithConfig({}, () => (
    <>
        <StoryUserIcon
            title="User Icon for tab - Inactive"
            text={"This style is for the user icon in the compact mebox styles only when the user tab is not selected"}
            styleType={UserIconTypes.SELECTED_INACTIVE}
            userData={{
                ...userDummyData,
                photoUrl: "http://badURL.example",
            }}
        />
    </>
));

export const DefaultAvatarPrimaryActive = storyWithConfig({}, () => (
    <>
        <StoryUserIcon
            title="User Icon for tab - Active"
            text={"This style is for the user icon in the compact mebox styles only when the user tab is selected"}
            styleType={UserIconTypes.SELECTED_ACTIVE}
            userData={{
                ...userDummyData,
                photoUrl: "http://badURL.example",
            }}
        />
    </>
));
