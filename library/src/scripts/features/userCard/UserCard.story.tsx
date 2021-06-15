/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { css } from "@emotion/css";
import { LoadStatus } from "@library/@types/api/core";
import { buttonClasses } from "@library/forms/buttonStyles";
import gdn from "@library/gdn";
import { DeviceProvider } from "@library/layout/DeviceContext";
import { PageBox } from "@library/layout/PageBox";
import { StoryContent } from "@library/storybook/StoryContent";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { STORY_USER, STORY_USER_BANNED, STORY_USER_PRIVATE } from "@library/storybook/storyData";
import { IPartialBoxOptions } from "@library/styles/cssUtilsTypes";
import { BorderType } from "@library/styles/styleHelpers";
import { UserCardPopup, useUserCardTrigger } from "@library/features/userCard/UserCard";
import {
    UserCardSkeleton,
    UserCardView,
    UserCardMinimal,
    UserCardError,
} from "@library/features/userCard/UserCard.views";
import React from "react";

export default {
    title: "Components/User Card",
    parameters: {
        chromatic: {
            viewports: [1450, 400],
        },
    },
};

gdn.meta.context = { conversationsEnabled: true };

const boxOptions: IPartialBoxOptions = {
    borderType: BorderType.SHADOW,
    spacing: { vertical: 0, top: 16, horizontal: 8 },
};

const adminConfig = {
    storeState: {
        users: {
            permissions: {
                status: LoadStatus.SUCCESS,
                data: {
                    isAdmin: true,
                    permissions: [],
                },
            },
        },
    },
};

const viewCardConfig = {
    storeState: {
        users: {
            permissions: {
                status: LoadStatus.SUCCESS,
                data: {
                    permissions: [
                        {
                            type: "global",
                            id: 1,
                            permissions: {
                                "profiles.view": true,
                            },
                        },
                    ],
                },
            },
        },
    },
};

const BasicStory = () => (
    <DeviceProvider>
        <StoryContent>
            <div className={css({ display: "flex", "& > *": { flex: 1, margin: 24 } })}>
                <PageBox className={css({ maxWidth: 400, margin: "0 auto" })} options={boxOptions}>
                    <UserCardView user={STORY_USER} />
                </PageBox>
            </div>
        </StoryContent>
    </DeviceProvider>
);

const BannedWithPermissionStory = () => (
    <DeviceProvider>
        <StoryContent>
            <div className={css({ display: "flex", "& > *": { flex: 1, margin: 24 } })}>
                <PageBox className={css({ maxWidth: 400, margin: "0 auto" })} options={boxOptions}>
                    <UserCardView user={STORY_USER_BANNED} />
                </PageBox>
            </div>
        </StoryContent>
    </DeviceProvider>
);

export const Basic = storyWithConfig(viewCardConfig, BasicStory);

export const WithPermissions = storyWithConfig(adminConfig, BasicStory);

export const BannedWithPermission = storyWithConfig(adminConfig, BannedWithPermissionStory);

export const Skeletons = storyWithConfig(viewCardConfig, () => {
    // @ts-ignore
    return (
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
                    <UserCardView
                        user={{
                            ...STORY_USER,
                            photoUrl: "",
                            label: undefined,
                        }}
                    />
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
});

function StoryLinkTrigger() {
    const { props, triggerRef } = useUserCardTrigger();

    return (
        <a href="/profile" className={buttonClasses().text} {...props} ref={triggerRef as any}>
            Some User
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

export const Trigger = storyWithConfig(viewCardConfig, () => {
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

export const Positioning = storyWithConfig(viewCardConfig, () => (
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
