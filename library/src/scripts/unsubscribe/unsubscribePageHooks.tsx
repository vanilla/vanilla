/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import apiv2 from "@library/apiv2";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { getCategoryReason, getUnsubscribeReason } from "@library/unsubscribe/getUnsubscribeReason";
import { t } from "@library/utility/appUtils";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { Buffer } from "buffer";
import React from "react";
import {
    IDecodedToken,
    IUnsubscribeData,
    IUnsubscribePreference,
    IUnsubscribeResult,
    IUnsubscribeToken,
} from "./unsubscribePage.types";
import { useCurrentUser } from "@library/features/users/userHooks";

/**
 * Get the token from the url params and decode it
 */
export function useGetUnsubscribe(token: IUnsubscribeToken) {
    const queryClient = useQueryClient();
    const decodedToken = decodeToken(token);

    return useMutation({
        mutationKey: ["unsubscribe", decodedToken?.activityID ?? "invalid"],
        mutationFn: async (token: IUnsubscribeToken) => {
            if (!decodedToken) {
                throw { message: t("Unsubscribe token is invalid.") };
            }

            const response = await apiv2.post(`/unsubscribe/${token}`);

            const data = {
                ...decodedToken,
                ...reduceUnsubscribe(response.data),
            } as IUnsubscribeData;

            if (response.data.preferences.length === 0 && response.data.followCategory.length === 0) {
                data.isAllProcessed = true;
            }

            return data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries();
        },
        onError: (error: IError) => {
            throw error;
        },
    });
}

/**
 * Undo the unsubscribe/unfollow
 */
export function useUndoUnsubscribe(token: IUnsubscribeToken) {
    const queryClient = useQueryClient();
    const decodedToken = decodeToken(token);

    return useMutation({
        mutationKey: ["undo_unsubscribe", decodedToken?.activityID ?? "invalid"],
        mutationFn: async (token: IUnsubscribeToken) => {
            if (!decodedToken) {
                throw { message: t("Unsubscribe token is invalid.") };
            }

            const response = await apiv2.post(`/unsubscribe/resubscribe/${token}`);

            return {
                ...decodedToken,
                ...reduceUnsubscribe(response.data),
            } as IUnsubscribeData;
        },
        onSuccess: () => {
            queryClient.invalidateQueries();
        },
        onError: (error: IError) => {
            throw error;
        },
    });
}

/**
 * Update a list of notification settings when multiple options exist
 */
export function useSaveUnsubscribe(token: IUnsubscribeToken) {
    const queryClient = useQueryClient();
    const decodedToken = decodeToken(token);

    return useMutation({
        mutationKey: ["unsubscribe_multiple", decodedToken?.activityID ?? "invalid"],
        mutationFn: async (unsubscribeData: IUnsubscribeData) => {
            if (!decodedToken) {
                throw { message: t("Unsubscribe token is invalid.") };
            }

            const { preferences = [], followedCategory } = unsubscribeData;

            const params: IUnsubscribeResult = {
                preferences: preferences.map(({ preferenceRaw, enabled }) => ({
                    preference: preferenceRaw,
                    enabled: enabled ? "1" : "0",
                })),
                followCategory: followedCategory
                    ? {
                          preference: followedCategory.preferenceRaw,
                          enabled: followedCategory.enabled ? "1" : "0",
                          name: followedCategory.categoryName,
                          categoryID: followedCategory.categoryID,
                      }
                    : [],
            };

            const response = await apiv2.patch(`/unsubscribe/${token}`, params);

            return {
                ...decodedToken,
                ...reduceUnsubscribe(response.data),
            } as IUnsubscribeData;
        },
        onSuccess: () => {
            queryClient.invalidateQueries();
        },
        onError: (error: IError) => {
            throw error;
        },
    });
}

/**
 * Construct a link to the notification preferences or followed content page
 */
export function usePreferenceLink() {
    const currentUser = useCurrentUser();

    return function (user?: IDecodedToken["user"], isFollowedContent?: boolean): string {
        const selectedUser = user ?? currentUser;
        let url = `/profile/${isFollowedContent ? "followed-content" : "preferences"}`;
        if (selectedUser?.userID !== currentUser?.userID) {
            url += "?accountConflict=true";
        }

        return url;
    };
}

// Decode the token string into an object
function decodeToken(token: IUnsubscribeToken): IDecodedToken | null {
    if (!token) {
        return null;
    }

    try {
        const decodedToken = JSON.parse(Buffer.from(token.split(".")[1], "base64").toString());

        return {
            activityID: decodedToken.ActivityID,
            activityTypes: decodedToken.ActivityTypes,
            activityData: decodedToken.ActivityData,
            user: {
                userID: decodedToken.UserID,
                name: decodedToken.Name,
                email: decodedToken.Email,
                photoUrl: decodedToken.PhotoUrl,
            },
        };
    } catch {
        return null;
    }
}

// Restructure the data for UI rendering
function reduceUnsubscribe(data: IUnsubscribeResult): Partial<IUnsubscribeData> {
    if (!data) {
        return {};
    }

    const preferences: IUnsubscribePreference[] = data.preferences.map(({ preference, enabled }) => {
        const preferenceName = preference.replace("Email.", "");

        const tmpPreference: IUnsubscribePreference = {
            preferenceName,
            preferenceRaw: preference,
            enabled: enabled === "1" || enabled === true,
            label: <></>,
            optionID: preference.replace(/\./g, "||"),
        };

        if (preferenceName !== "DigestEnabled") {
            tmpPreference.label = getUnsubscribeReason(tmpPreference);
        }

        return tmpPreference;
    });

    let followedCategory: IUnsubscribeData["followedCategory"];
    let isUnfollowCategory: boolean = false;
    if (!Array.isArray(data.followCategory)) {
        const preferenceData = data.followCategory.preference
            .replace(`.${data.followCategory.categoryID}`, "")
            .split(".");
        const preferenceName = preferenceData[preferenceData.length - 1];
        followedCategory = {
            preferenceName,
            preferenceRaw: data.followCategory.preference,
            enabled: data.followCategory.enabled === "1" || data.followCategory.enabled === true,
            label: <></>,
            categoryID: data.followCategory.categoryID,
            categoryName: data.followCategory.name,
            optionID: data.followCategory.preference.replace(/\./g, "||"),
        };
        isUnfollowCategory = preferenceName.toLowerCase() === "follow";
        followedCategory.label = getCategoryReason(followedCategory, isUnfollowCategory);
    }

    const hasMultiple = preferences.length > 1 || (preferences.length > 0 && Boolean(followedCategory));
    const disabledPreferences = preferences.filter(({ enabled }) => enabled).length > 0;
    const categoryDisabled = !followedCategory || (followedCategory && followedCategory.enabled);

    return {
        preferences,
        followedCategory,
        hasMultiple,
        isAllProcessed: !hasMultiple && disabledPreferences && categoryDisabled,
        isEmailDigest: preferences.length === 1 && preferences[0].preferenceName === "DigestEnabled",
        isUnfollowCategory,
    };
}
