/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import apiv2 from "@library/apiv2";
import { useMutation } from "@tanstack/react-query";
import { ICategoryPreferences } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import debounce from "lodash/debounce";
import { useCallback, useState } from "react";

export type PostNotificationType = "follow" | "discussions" | "all" | null;

export function useCategoryNotifications(userID: number, categoryID: number, initialPreferences: ICategoryPreferences) {
    /**
     * Starting a new reducer for a single component seems excessive.
     * Going to treat a state object as a cache instead
     */
    const [localPreference, setLocalPreference] = useState<ICategoryPreferences>(initialPreferences);

    const notificationPrefsMutation = useMutation({
        mutationFn: async (newPreferences: ICategoryPreferences) => {
            return await apiv2.patch<ICategoryPreferences>(
                `/categories/${categoryID}/preferences/${userID}`,
                newPreferences,
            );
        },
        mutationKey: [categoryID, userID],
    });

    const debouncedSetNotificationPreferences = useCallback(debounce(notificationPrefsMutation.mutateAsync, 750), [
        notificationPrefsMutation.mutateAsync,
    ]);

    const setNotificationPreferences = async (change: Partial<ICategoryPreferences>) => {
        const newPreferences = {
            ...localPreference,
            ...change,
        };
        // Update locally.
        setLocalPreference(newPreferences);
        // Then persist (debounced).
        await debouncedSetNotificationPreferences(newPreferences);
    };

    return {
        notificationPreferences: localPreference,
        setNotificationPreferences,
    };
}
