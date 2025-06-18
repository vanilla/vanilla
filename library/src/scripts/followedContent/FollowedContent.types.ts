/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

export type FollowedContentNotificationPreferences<T> = {
    [Property in keyof T as `preferences.email.${string & Property}`]: boolean;
} & {
    [Property in keyof T as `preferences.popup.${string & Property}`]: boolean;
} & {
    "preferences.followed": boolean;
    "preferences.email.digest"?: boolean;
};

export interface IFollowedContentNotificationPreferencesContext<T extends object = {}> {
    preferences: T;
    setPreferences: (preferences: T) => Promise<T>;
}
