/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { FollowedContentNotificationPreferences } from "@library/followedContent/FollowedContent.types";
import {
    INotificationPreferences,
    NotificationType,
} from "@library/notificationPreferences/NotificationPreferences.types";

export type IFollowedCategoryNotificationPreferences = FollowedContentNotificationPreferences<
    typeof CATEGORY_NOTIFICATION_TYPES
>;

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
): IFollowedCategoryNotificationPreferences {
    let defaults: IFollowedCategoryNotificationPreferences = {
        "preferences.followed": false,
        "preferences.email.digest": false,
    };

    Object.values(CATEGORY_NOTIFICATION_TYPES).forEach((type) => {
        defaults = {
            ...defaults,
            ...type.getDefaultPreferences(userPreferences),
        };
    });

    return defaults;
}
