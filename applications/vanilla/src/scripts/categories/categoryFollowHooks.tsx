/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import apiv2 from "@library/apiv2";
import { ICategoryPreferences } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { useAsyncFn } from "@vanilla/react-utils";
import debounce from "lodash/debounce";
import { useCallback, useState } from "react";

export type PostNotificationType = "follow" | "discussions" | "all" | null;

export function useCategoryNotifications(userID: number, categoryID: number, initialPreferences: ICategoryPreferences) {
    /**
     * Starting a new reducer for a single component seems excessive.
     * Going to treat a state object as a cache instead
     */
    const [localPreference, setLocalPreference] = useState<ICategoryPreferences>(initialPreferences);

    const [setNotificationPreferencesState, _setNotificationPreferences] = useAsyncFn(
        async (newPreferences: ICategoryPreferences) => {
            await apiv2.patch<ICategoryPreferences>(`/categories/${categoryID}/preferences/${userID}`, newPreferences);
        },
        [],
    );

    const debouncedSetNotificationPreferences = useCallback(debounce(_setNotificationPreferences, 750), [
        _setNotificationPreferences,
    ]);

    // Small wrapper to allow using a partial.
    // useAsyncFn can't change the method reference in the middle of an async call
    // so we need a wrapper.
    const setNotificationPreferences = async (change: Partial<ICategoryPreferences>) => {
        const newPreferences = {
            ...localPreference,
            ...change,
        };
        setLocalPreference(newPreferences);
        await debouncedSetNotificationPreferences(newPreferences);
    };

    return {
        notificationPreferences: localPreference,
        setNotificationPreferences,
        setNotificationPreferencesState,
    };
}
