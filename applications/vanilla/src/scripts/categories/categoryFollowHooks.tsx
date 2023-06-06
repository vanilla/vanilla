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
import { useToast } from "@library/features/toaster/ToastContext";
import { t } from "@vanilla/i18n";

export type PostNotificationType = "follow" | "discussions" | "all" | null;

export function useCategoryNotifications(userID: number, categoryID: number, initialPreferences: ICategoryPreferences) {
    /**
     * Starting a new reducer for a single component seems excessive.
     * Going to treat a state object as a cache instead
     */
    const [localPreference, setLocalPreference] = useState<ICategoryPreferences>(initialPreferences);
    const toast = useToast();

    const notificationPrefsMutation = useMutation({
        mutationFn: async (newPreferences: ICategoryPreferences) => {
            await apiv2.patch<ICategoryPreferences>(`/categories/${categoryID}/preferences/${userID}`, newPreferences);

            if (newPreferences.postNotifications === "follow") {
                toast.addToast({
                    autoDismiss: true,
                    body: t("Success! Followed category."),
                });
            } else if (newPreferences.postNotifications === null) {
                toast.addToast({
                    autoDismiss: true,
                    body: t("Success! Unfollowed category."),
                });
            } else {
                toast.addToast({
                    autoDismiss: true,
                    body: t("Success! Preferences saved."),
                });
            }
        },
        mutationKey: [categoryID, userID],
    });

    const debouncedSetNotificationPreferences = useCallback(debounce(notificationPrefsMutation.mutateAsync, 1500), [
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
