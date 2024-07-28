/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { getDefaultCategoryNotificationPreferences } from "@vanilla/addon-vanilla/categories/CategoryNotificationPreferences.hooks";
import { IFollowedCategory, ILegacyCategoryPreferences } from "./DefaultCategoriesModal";

// Check if we have  legacy config and it needs to be converted
const isOldConfig = (
    configs: IFollowedCategory[] | ILegacyCategoryPreferences[],
): configs is ILegacyCategoryPreferences[] => {
    // Assume we don't have some weird mix of config shapes
    const config = configs[0];
    return config && Object.keys(config).some((key) => ["postNotifications", "useEmailNotifications"].includes(key));
};

/**
 * Convert the old notification preference structure to the new one
 *
 * This really shouldn't be needed as we ought to convert all sites which has the config with
 * some other script. But in the event we do not, this function will translate the old values
 * to the new granular ones
 */
export function convertOldConfig(config: IFollowedCategory[] | ILegacyCategoryPreferences[]): IFollowedCategory[] {
    if (isOldConfig(config)) {
        return config.reduce((acc, current) => {
            const converted = {
                categoryID: current.categoryID,
                preferences: {
                    ...getDefaultCategoryNotificationPreferences(),
                    "preferences.email.digest": false,
                    "preferences.followed": true,
                    /**
                     * The nesting of conditional values here is a little strange,
                     * but gets the job done without a huge if-else chain
                     */
                    ...(current.postNotifications === "discussions" && {
                        "preferences.popup.posts": true,
                        ...(current.useEmailNotifications && {
                            "preferences.email.posts": true,
                        }),
                    }),
                    ...(current.postNotifications === "all" && {
                        "preferences.popup.posts": true,
                        "preferences.popup.comments": true,
                        ...(current.useEmailNotifications && {
                            "preferences.email.comments": true,
                            "preferences.email.posts": true,
                        }),
                    }),
                },
            };
            return [...acc, converted];
        }, []);
    }
    return config;
}
