/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";

import { css } from "@emotion/css";
import { LoadStatus } from "@library/@types/api/core";
import { buttonClasses } from "@library/forms/Button.styles";
import gdn from "@library/gdn";
import { DeviceProvider } from "@library/layout/DeviceContext";
import { PageBox } from "@library/layout/PageBox";
import { StoryContent } from "@library/storybook/StoryContent";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { STORY_USER, STORY_USER_BANNED, STORY_USER_PRIVATE } from "@library/storybook/storyData";
import { IPartialBoxOptions } from "@library/styles/cssUtilsTypes";
import { BorderType } from "@library/styles/styleHelpers";
import { UserCardPopup, useUserCardTrigger } from "@library/features/userCard/UserCard";
import { UserCardSkeleton, UserCardMinimal, UserCardError } from "@library/features/userCard/UserCard.views";

export const STORY_USER_ID = 1;

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

const adminConfig = {
    users: {
        permissions: {
            status: LoadStatus.SUCCESS,
            data: {
                isAdmin: true,
                permissions: [],
            },
        },
    },
};

export default {
    title: "Components/User Card",
    parameters: {
        chromatic: {
            viewports: [1450, 400],
        },
    },
    excludeStories: ["STORY_USER_ID", "SkeletonStory"],
};

gdn.meta.context = { conversationsEnabled: true };

const boxOptions: IPartialBoxOptions = {
    borderType: BorderType.SHADOW,
    spacing: { vertical: 0, horizontal: 0 },
};

export const Basic = ({ config }: { config?: any }) => {
    return React.createElement(
        storyWithConfig({ storeState: { ...viewCardConfig, ...config } }, () => (
            // <StoryContent>
            <UserCardPopup forceOpen userID={STORY_USER.userID} user={STORY_USER}>
                <StoryLinkTrigger />
            </UserCardPopup>
            // </StoryContent>
        )),
    );
};

export const BannedWithPermission = ({ config }: { config?: any }) => {
    return React.createElement(
        storyWithConfig({ storeState: { ...adminConfig, ...config } }, () => (
            // <StoryContent>
            <UserCardPopup forceOpen userID={STORY_USER.userID} user={STORY_USER_BANNED}>
                <StoryLinkTrigger />
            </UserCardPopup>
            // </StoryContent>
        )),
    );
};

export const WithPermissions = () => <Basic config={adminConfig} />;

export const SkeletonStory = ({ config }: { config?: any }) =>
    React.createElement(
        storyWithConfig({ storeState: { ...viewCardConfig, ...(config ?? {}) } }, () => (
            <UserCardPopup forceOpen forceSkeleton userFragment={STORY_USER} userID={STORY_USER.userID}>
                <StoryLinkTrigger />
            </UserCardPopup>
        )),
    );

export const Skeletons = () => (
    <DeviceProvider>
        <div
            className={css({
                display: "flex",
                flexWrap: "wrap",
                "& > *": { minWidth: 320, margin: "20px !important" },
                alignItems: "flex-start",
            })}
        >
            <PageBox className={css({ maxWidth: 400, margin: "0 auto" })} options={boxOptions}>
                <UserCardSkeleton userFragment={STORY_USER} />
            </PageBox>
            <PageBox className={css({ maxWidth: 400, margin: "0 auto" })} options={boxOptions}>
                <UserCardSkeleton />
            </PageBox>
            <PageBox className={css({ maxWidth: 400, margin: "0 auto" })} options={boxOptions}>
                <UserCardMinimal user={STORY_USER_BANNED} />
            </PageBox>
            <PageBox className={css({ maxWidth: 400, margin: "0 auto" })} options={boxOptions}>
                <UserCardMinimal user={STORY_USER_PRIVATE} />
            </PageBox>
            <PageBox className={css({ maxWidth: 400, margin: "0 auto" })} options={boxOptions}>
                <UserCardError />
            </PageBox>
            <PageBox className={css({ maxWidth: 400, margin: "0 auto" })} options={boxOptions}>
                <UserCardError error={"Failed to load user"} />
            </PageBox>
        </div>
    </DeviceProvider>
);

function StoryLinkTrigger() {
    const context = useUserCardTrigger();

    return (
        <a href="/profile" className={buttonClasses().text} {...context.props} ref={context.triggerRef as any}>
            {STORY_USER.name}
            {context.contents}
        </a>
    );
}

function StoryUserLink(props: { forceOpen?: boolean }) {
    return (
        <UserCardPopup userID={STORY_USER.userID} user={STORY_USER} forceOpen={props.forceOpen}>
            <StoryLinkTrigger />
        </UserCardPopup>
    );
}

export const Trigger = storyWithConfig({ storeState: viewCardConfig }, () => {
    return (
        <StoryContent>
            <button>Before</button>
            <button>Before 2</button>
            <StoryUserLink />
            <button>After</button>
            <StoryUserLink />
            <button>After 2</button>
        </StoryContent>
    );
});

export const Positioning = storyWithConfig({ storeState: viewCardConfig }, () => (
    <DeviceProvider>
        <div
            style={{
                minHeight: "800px",
                height: "100%",
                maxWidth: "100vw",
                overflow: "hidden",
                position: "relative",
                display: "flex",
                alignItems: "space-between",
                justifyContent: "space-between",
                flexWrap: "wrap",
            }}
        >
            {/* Top Row */}
            <span
                style={{
                    width: "33.33333333333333333333%",
                    display: "flex",
                    alignItems: "flex-start",
                    justifyContent: "flex-start",
                }}
            >
                <StoryUserLink forceOpen />
            </span>

            <span
                style={{
                    width: "33.33333333333333333333%",
                    display: "flex",
                    alignItems: "flex-start",
                    justifyContent: "center",
                }}
            >
                <StoryUserLink />
            </span>

            <span
                style={{
                    width: "33.33333333333333333333%",
                    display: "flex",
                    alignItems: "flex-start",
                    justifyContent: "flex-end",
                }}
            >
                <StoryUserLink forceOpen />
            </span>

            {/* Middle Row */}
            <span
                style={{
                    width: "33.33333333333333333333%",
                    display: "flex",
                    alignItems: "center",
                    justifyContent: "flex-start",
                }}
            >
                <StoryUserLink />
            </span>

            <span
                style={{
                    display: "flex",
                    alignItems: "center",
                    justifyContent: "center",
                    width: "33.33333333333333333333%",
                }}
            >
                <StoryUserLink forceOpen />
            </span>

            <span
                style={{
                    width: "33.33333333333333333333%",
                    justifyContent: "flex-end",
                    display: "flex",
                    alignItems: "center",
                }}
            >
                <StoryUserLink />
            </span>

            {/* Bottom Row */}
            <span
                style={{
                    width: "33.33333333333333333333%",
                    display: "flex",
                    alignItems: "flex-end",
                    justifyContent: "flex-start",
                }}
            >
                <StoryUserLink forceOpen />
            </span>

            <span
                style={{
                    marginTop: "auto",
                    width: "33.33333333333333333333%",
                    display: "flex",
                    alignItems: "flex-end",
                    justifyContent: "center",
                }}
            >
                <StoryUserLink />
            </span>

            <span
                style={{
                    marginLeft: "auto",
                    marginTop: "auto",
                    width: "33.33333333333333333333%",
                    display: "flex",
                    alignItems: "flex-end",
                    justifyContent: "flex-end",
                }}
            >
                <StoryUserLink forceOpen />
            </span>
        </div>
    </DeviceProvider>
));
