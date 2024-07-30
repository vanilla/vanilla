/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { INotificationPreferences } from "@library/notificationPreferences/NotificationPreferences.types";
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

interface CategoryNotificationType {
    /**
     * The phrase returned should be translatable; it will be interpolated into a sentence like "Notify users of `${getDescription()}`".
     * When adding a new category notification type, make sure to add a the phrase to the locales file.
     */
    getDescription(): string;

    /**
     * Return the default preferences for this category type.
     * When available, the user's global notification preferences will be passed as an argument.
     * Implementations should default to false for all properties.
     */
    getDefaultPreferences?(userPreferences?: INotificationPreferences): Partial<ICategoryPreferences>;
}

export const CATEGORY_NOTIFICATION_TYPES: Record<string, CategoryNotificationType> = {
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

export function registerCategoryNotificationType(id: string, type: CategoryNotificationType) {
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
