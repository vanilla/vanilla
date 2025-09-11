/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { createContext } from "react";
import {
    FollowedContentNotificationPreferences,
    IFollowedContentNotificationPreferencesContext,
} from "./FollowedContent.types";

export const FollowedContentNotificationPreferencesContext =
    createContext<IFollowedContentNotificationPreferencesContext>({
        preferences: {
            "preferences.followed": false,
        } as FollowedContentNotificationPreferences<{}>,
        setPreferences: async function (_preferences) {
            return {
                "preferences.followed": false,
            } as FollowedContentNotificationPreferences<{}>;
        },
    });
