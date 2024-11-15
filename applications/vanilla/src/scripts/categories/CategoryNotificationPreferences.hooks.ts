/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    INotificationPreferences,
    NotificationType,
} from "@library/notificationPreferences/NotificationPreferences.types";
import { createContext, useContext } from "react";

interface ICategoryNotificationPreferencesContextValue {
    preferences: ICategoryPreferences;
    setPreferences: (preferences: ICategoryPreferences) => Promise<ICategoryPreferences>;
}

export const CategoryNotificationPreferencesContext = createContext<ICategoryNotificationPreferencesContextValue>({
    preferences: {} as ICategoryPreferences,
    setPreferences: async function (_preferences) {
        return {} as ICategoryPreferences;
    },
});

export function useCategoryNotificationPreferencesContext() {
    return useContext(CategoryNotificationPreferencesContext);
}

export type ICategoryPreferences = {
    [Property in keyof typeof CATEGORY_NOTIFICATION_TYPES as `preferences.email.${string & Property}`]:
        | boolean
        | undefined;
} & {
    [Property in keyof typeof CATEGORY_NOTIFICATION_TYPES as `preferences.popup.${string & Property}`]:
        | boolean
        | undefined;
} & {
    "preferences.followed": boolean;
    "preferences.email.digest"?: boolean;
};

export const CATEGORY_NOTIFICATION_TYPES: Record<string, NotificationType> = {
    comments: {
        getDescription: () => "new comments",
        getDefaultPreferences: (userPreferences) => {
            const { NewComment } = userPreferences ?? {};
            return {
                "preferences.popup.comments": NewComment?.popup ?? false,
                "preferences.email.comments": NewComment?.email ?? false,
            };
        },
    },
    posts: {
        getDescription: () => "new posts",
        getDefaultPreferences: (userPreferences) => {
            const { NewDiscussion } = userPreferences ?? {};
            return {
                "preferences.popup.posts": NewDiscussion?.popup ?? false,
                "preferences.email.posts": NewDiscussion?.email ?? false,
            };
        },
    },
};

export function registerCategoryNotificationType(id: string, type: NotificationType) {
    CATEGORY_NOTIFICATION_TYPES[id] = type;
}

export function getDefaultCategoryNotificationPreferences(
    userPreferences?: INotificationPreferences,
): ICategoryPreferences {
    let defaults: ICategoryPreferences = {
        "preferences.followed": false,
        "preferences.email.digest": false,
    };

    Object.values(CATEGORY_NOTIFICATION_TYPES).forEach((type) => {
        defaults = {
            ...defaults,
            ...type.getDefaultPreferences?.(userPreferences),
        };
    });

    return defaults;
}
