/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import apiv2 from "@library/apiv2";
import { useMutation, useQuery } from "@tanstack/react-query";
import { ICategoryPreferences } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import debounce from "lodash/debounce";
import { useCallback, useEffect, useState } from "react";
import { useToast } from "@library/features/toaster/ToastContext";
import { t } from "@vanilla/i18n";
import NotificationPreferencesApi from "@library/notificationPreferences/NotificationPreferences.api";
import { IUser } from "@library/@types/api/users";
import { IApiError } from "@library/@types/api/core";
import { INotificationPreferences } from "@library/notificationPreferences";

interface ICategoryNotifications {
    userID: IUser["userID"];
    categoryID: number;
    initialPreferences: ICategoryPreferences;
    emailDigestEnabled: boolean;
}

export function useCategoryNotifications(props: ICategoryNotifications) {
    const { userID, categoryID, initialPreferences, emailDigestEnabled } = props;
    /**
     * Starting a new reducer for a single component seems excessive.
     * Going to treat a state object as a cache instead
     */
    const [localPreference, setLocalPreference] = useState<ICategoryPreferences>(() => {
        return initialPreferences;
    });

    const toast = useToast();

    const { data: defaultUserPreferences } = useQuery<unknown, IApiError, INotificationPreferences>({
        queryFn: async () => NotificationPreferencesApi.getUserPreferences({ userID }),
        queryKey: ["defaultUserPreferences", { userID }],
    });

    const notificationPreferencesMutation = useMutation({
        mutationFn: async (newPreferences: ICategoryPreferences) => {
            await apiv2.patch<ICategoryPreferences>(`/categories/${categoryID}/preferences/${userID}`, newPreferences);
            toast.addToast({
                autoDismiss: true,
                body: t("Success! Preferences saved."),
            });
        },
        mutationKey: [categoryID, userID],
    });

    const debouncedSetNotificationPreferences = useCallback(
        debounce(notificationPreferencesMutation.mutateAsync, 1250),
        [notificationPreferencesMutation.mutateAsync],
    );

    const setNotificationPreferences = (change: Partial<ICategoryPreferences>) => {
        let newPreferences = change;
        // If the change is a follow event, apply the default user notification preferences
        if (change.hasOwnProperty("preferences.followed") && change["preferences.followed"]) {
            const { NewComment, NewDiscussion } = defaultUserPreferences ?? {};
            newPreferences = {
                ...change,
                "preferences.popup.comments": NewComment?.popup,
                "preferences.email.comments": NewComment?.email,
                "preferences.popup.posts": NewDiscussion?.popup,
                "preferences.email.posts": NewDiscussion?.email,
                ...(emailDigestEnabled && { "preferences.email.digest": true }),
            };
        }
        setLocalPreference((prev) => {
            debouncedSetNotificationPreferences({
                ...prev,
                ...newPreferences,
            });
            return {
                ...prev,
                ...newPreferences,
            };
        });
    };

    return {
        notificationPreferences: localPreference,
        setNotificationPreferences,
    };
}
