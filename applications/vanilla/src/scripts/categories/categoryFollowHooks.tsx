/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import apiv2 from "@library/apiv2";
import { stableObjectHash } from "@vanilla/utils";
import { useState } from "react";

export type CategoryPreference = "follow" | "discussions" | "all" | null;

export function useCategoryNotifications(userID: number, categoryID: number) {
    /**
     * Starting a new reducer for a single component seems excessive.
     * Going to treat a state object as a cache instead
     */
    const [state, setState] = useState<Record<number, CategoryPreference>>({});
    const [notificationPreference, setPreference] = useState<CategoryPreference>(null);

    const getPreferenceState = async () => {
        const response = await apiv2.get(`/categories/${categoryID}/preferences/${userID}`);
        return response.data;
    };

    const patchPreferenceState = async (value: CategoryPreference) => {
        const response = await apiv2.patch(`/categories/${categoryID}/preferences/${userID}`, {
            postNotifications: value,
        });
        return response.data;
    };

    const getNotificationPreference = () => {
        // Create a hash
        const hash = stableObjectHash([userID, categoryID]);
        const cachedResponse = state[hash];
        // Check if the response already existing in our pseudo-store
        if (cachedResponse) {
            setPreference(state[hash]);
        } else {
            // Otherwise make a new API call
            getPreferenceState().then((response) => {
                setState((prevState) => ({ ...prevState, [hash]: response.postNotifications }));
                setPreference(response.postNotifications);
            });
        }
    };

    const setNotificationPreference = (value: CategoryPreference) => {
        // Only patch if values are not equal already
        if (notificationPreference !== value) {
            // Create a hash
            const hash = stableObjectHash([userID, categoryID]);
            patchPreferenceState(value).then((response) => {
                setState((prevState) => ({ ...prevState, [hash]: response.postNotifications }));
                setPreference(response.postNotifications);
            });
        }
    };

    return {
        getNotificationPreference,
        setNotificationPreference,
        notificationPreference,
    };
}
