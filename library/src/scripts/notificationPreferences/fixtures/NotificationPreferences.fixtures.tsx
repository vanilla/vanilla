/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    INotificationPreference,
    INotificationPreferences,
    INotificationPreferencesApi,
    NotificationPreferencesSchemaType,
} from "@library/notificationPreferences";
import { JSONSchemaType } from "@vanilla/json-schema-forms";

const preferenceSchema: JSONSchemaType<INotificationPreference> = {
    type: "object",
    properties: {
        email: {
            type: "boolean",
            nullable: true,
            "x-control": {
                inputType: "checkBox",
                label: "Email",
            },
        },
        popup: {
            type: "boolean",
            nullable: true,
            "x-control": {
                inputType: "checkBox",
                label: "Notification popup",
            },
        },
    },
    required: [],
};

export const mockSchema: NotificationPreferencesSchemaType = {
    type: "object",
    properties: {
        notifications: {
            type: "object",
            nullable: true,
            "x-control": {
                label: "Notifications",
            },
            properties: {
                groupOne: {
                    type: "object",
                    nullable: true,
                    "x-control": {
                        label: "Group One",
                    },
                    properties: {
                        mentions: {
                            "x-control": {
                                description: "Mentions",
                            },
                            ...preferenceSchema,
                            nullable: true,
                        },
                        commentsOnMyDiscussions: {
                            "x-control": {
                                description: "Comments on my discussions",
                            },
                            ...preferenceSchema,
                            nullable: true,
                        },
                    },
                    required: [],
                },
                groupTwo: {
                    type: "object",
                    nullable: true,
                    "x-control": {
                        label: "Group Two",
                    },
                    properties: {
                        reactionsToMyComments: {
                            "x-control": {
                                description: "Reactions to my comments",
                            },
                            ...preferenceSchema,
                            nullable: true,
                        },
                    },
                    required: [],
                },
            },
            required: [],
        },
    },
    required: [],
};

export const mockPreferences: INotificationPreferences = {
    mentions: {
        email: true,
        popup: false,
    },
    commentsOnMyDiscussions: {
        email: false,
        popup: false,
    },
    reactionsToMyComments: {
        email: true,
        popup: false,
    },
    emailDigest: {
        email: true,
    },
};

export function createMockApi(preferences?: INotificationPreferences): INotificationPreferencesApi {
    return {
        getSchema: vitest.fn().mockReturnValue(mockSchema),
        getUserPreferences: vitest.fn().mockReturnValue({
            ...mockPreferences,
            ...preferences,
        }),
        patchUserPreferences: vitest.fn(async function (params) {
            return {
                ...params.preferences,
            };
        }),
    };
}

export const mockEditPreferencesParams: INotificationPreferences = {
    commentsOnMyDiscussions: {
        email: true,
        popup: true,
    },
};
