/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { ReactNode } from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import PopupUserCard, { IUserCardInfo } from "@library/features/users/ui/PopupUserCard";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { DeviceProvider } from "@library/layout/DeviceContext";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import gdn from "@library/gdn";

export default {
    component: PopupUserCard,
    title: "Components/User Card",
    parameters: {
        chromatic: {
            viewports: [1450, 700, layoutVariables().panelLayoutBreakPoints.xs],
        },
    },
};

const v: IUserCardInfo = {
    email: "val@vanillaforums.com",
    userID: 1,
    name: "Valérie Robitaille",
    photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/164/nQQG7FTJACOTX.jpg",
    dateLastActive: "May 2014",
    dateJoined: "May 2017",
    label: "Product Manager",
    countDiscussions: 207,
    countComments: 3456,
};

const m: IUserCardInfo = {
    email: "anonymous@where.com",
    userID: 0,
    name: "Anonymous",
    photoUrl: "",
    dateLastActive: "May May 2014",
    dateJoined: "May 2017",
    countDiscussions: 207,
    countComments: 3456,
};

gdn.meta.context = { conversationsEnabled: true };

export const UserCardNoPhotoNoLabel = () => (
    <DeviceProvider>
        <StoryContent>
            <PopupUserCard user={m} visible={true} />
        </StoryContent>
    </DeviceProvider>
);

export const UserCardWithoutPermission = storyWithConfig(
    {
        storeState: {
            users: {
                permissions: {
                    status: LoadStatus.SUCCESS,
                    data: {
                        isAdmin: false,
                        permissions: [
                            {
                                type: "global",
                                id: 1,
                                permissions: {
                                    "personalInfo.view": false,
                                },
                            },
                        ],
                    },
                },
            },
        },
    },
    () => (
        <DeviceProvider>
            <StoryContent>
                <PopupUserCard user={v} visible={true} />
            </StoryContent>
        </DeviceProvider>
    ),
);

export const UserCardWithPermission = storyWithConfig(
    {
        storeState: {
            users: {
                permissions: {
                    status: LoadStatus.SUCCESS,
                    data: {
                        isAdmin: false,
                        permissions: [
                            {
                                type: "global",
                                id: 1,
                                permissions: {
                                    "personalInfo.view": true,
                                },
                            },
                        ],
                    },
                },
            },
        },
    },
    () => (
        <DeviceProvider>
            <StoryContent>
                <PopupUserCard user={v} visible={true} />
            </StoryContent>
        </DeviceProvider>
    ),
);

export const UserCardTestingPositions = storyWithConfig(
    {
        storeState: {
            users: {
                permissions: {
                    status: LoadStatus.SUCCESS,
                    data: {
                        isAdmin: false,
                        permissions: [
                            {
                                type: "global",
                                id: 1,
                                permissions: {
                                    "personalInfo.view": false,
                                },
                            },
                        ],
                    },
                },
            },
        },
    },
    () => (
        <DeviceProvider>
            <div
                style={{
                    minHeight: "1000px",
                    height: "100%",
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
                    <PopupUserCard user={v} />
                </span>

                <span
                    style={{
                        width: "33.33333333333333333333%",
                        display: "flex",
                        alignItems: "flex-start",
                        justifyContent: "center",
                    }}
                >
                    <PopupUserCard user={v} />
                </span>

                <span
                    style={{
                        width: "33.33333333333333333333%",
                        display: "flex",
                        alignItems: "flex-start",
                        justifyContent: "flex-end",
                    }}
                >
                    <PopupUserCard user={v} />
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
                    <PopupUserCard user={v} />
                </span>

                <span
                    style={{
                        display: "flex",
                        alignItems: "center",
                        justifyContent: "center",
                        width: "33.33333333333333333333%",
                    }}
                >
                    <PopupUserCard user={v} visible={true} />
                </span>

                <span
                    style={{
                        width: "33.33333333333333333333%",
                        justifyContent: "flex-end",
                        display: "flex",
                        alignItems: "center",
                    }}
                >
                    <PopupUserCard user={v} />
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
                    <PopupUserCard user={v} />
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
                    <PopupUserCard user={v} />
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
                    <PopupUserCard user={v} />
                </span>
            </div>
        </DeviceProvider>
    ),
);
