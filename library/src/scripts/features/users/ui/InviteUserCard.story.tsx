/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import React from "react";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { DeviceProvider } from "@library/layout/DeviceContext";
import { StoryContent } from "@library/storybook/StoryContent";
import InviteUserCard from "@library/features/users/ui/InviteUserCard";
import { storyWithConfig } from "@library/storybook/StoryContext";

export default {
    component: InviteUserCard,
    title: "Groups",
    parameters: {
        chromatic: {
            viewports: [1450, layoutVariables().panelLayoutBreakPoints.xs],
        },
    },
};

const defaultUsers = [
    {
        value: "Astérix0",
        label: "Astérix0",
    },
    {
        value: "Obélix1",
        label: "Obélix1",
    },
    {
        value: "Idéfix2",
        label: "Idéfix2",
    },
    {
        value: "Panoramix3",
        label: "Panoramix3",
    },
    {
        value: "Astérix4",
        label: "Astérix4",
    },
    {
        value: "Obélix5",
        label: "Obélix5",
    },
    {
        value: "Idéfix6",
        label: "Idéfix6",
    },
    {
        value: "Panoramix7",
        label: "Panoramix7",
    },
    {
        value: "Astérix8",
        label: "Astérix8",
    },
    {
        value: "Obélix9",
        label: "Obélix9",
    },
    {
        value: "Idéfix10",
        label: "Idéfix10",
    },
    {
        value: "Panoramix11",
        label: "Panoramix11",
    },
    {
        value: "Astérix12",
        label: "Astérix12",
    },
    {
        value: "Obélix13",
        label: "Obélix13",
    },
    {
        value: "Idéfix14",
        label: "Idéfix14",
    },
    {
        value: "Panoramix15",
        label: "Panoramix15",
    },
    {
        value: "Astérix16",
        label: "Astérix16",
    },
    {
        value: "Obélix17",
        label: "Obélix17",
    },
    {
        value: "Idéfix18",
        label: "Idéfix18",
    },
    {
        value: "Panoramix19",
        label: "Panoramix19",
    },
    {
        value: "Astérix20",
        label: "Astérix20",
    },
    {
        value: "Obélix21",
        label: "Obélix21",
    },
    {
        value: "Idéfix22",
        label: "Idéfix22",
    },
    {
        value: "Panoramix23",
        label: "Panoramix23",
    },
];

const inputEmails = "123@example.com, 456@example.com, abc@example.com, xyz.example.com, ijk@example.com";

export const InviteCardWithPermission = storyWithConfig(
    {
        storeState: {
            users: {
                permissions: {
                    data: {
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
                <InviteUserCard
                    visible={true}
                    closeModal={() => {}}
                    defaultUsers={defaultUsers}
                    updateStoreInvitees={() => {}}
                    inputEmails={inputEmails}
                    updateStoreEmails={() => {}}
                    sentInvitations={() => {}}
                />
            </StoryContent>
        </DeviceProvider>
    ),
);

export const InviteCardWithoutPermission = storyWithConfig(
    {
        storeState: {
            users: {
                permissions: {
                    data: {
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
                <InviteUserCard
                    visible={true}
                    closeModal={() => {}}
                    defaultUsers={defaultUsers}
                    updateStoreInvitees={() => {}}
                    inputEmails={inputEmails}
                    updateStoreEmails={() => {}}
                    sentInvitations={() => {}}
                />
            </StoryContent>
        </DeviceProvider>
    ),
);
