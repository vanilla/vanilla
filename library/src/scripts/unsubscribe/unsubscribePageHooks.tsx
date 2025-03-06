/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import apiv2 from "@library/apiv2";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { getFollowedContentReason, getUnsubscribeReason } from "@library/unsubscribe/getUnsubscribeReason";
import { t } from "@library/utility/appUtils";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import {
    IDecodedToken,
    IUnsubscribeData,
    IUnsubscribePreference,
    IUnsubscribeResult,
    IUnsubscribeToken,
} from "@library/unsubscribe/unsubscribePage.types";
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

            if (
                response.data.preferences?.length === 0 &&
                (response.data.followCategory?.length === 0 || response.data.followContent?.length === 0)
            ) {
                data.isAllProcessed = true;
            }

            return data;
        },
        onSuccess: () => {
            void queryClient.invalidateQueries();
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
            void queryClient.invalidateQueries();
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

            const { preferences = [], followedContent } = unsubscribeData;

            const params: IUnsubscribeResult = {
                preferences: preferences.map(({ preferenceRaw, enabled }) => ({
                    preference: preferenceRaw,
                    enabled: enabled ? "1" : "0",
                })),
            };
            if (followedContent) {
                const isContentCategory = followedContent.contentType === "category";
                const commonParams = {
                    preference: followedContent.preferenceRaw,
                    enabled: followedContent.enabled ? "1" : "0",
                    name: followedContent.contentName,
                };
                if (isContentCategory) {
                    params.followCategory = {
                        categoryID: followedContent.contentID,
                        ...commonParams,
                    };
                } else {
                    params.followContent = {
                        contentType: followedContent.contentType as string,
                        contentID: followedContent.contentID,
                        userID: decodedToken.user.userID,
                        ...commonParams,
                    };
                }
            }

            const response = await apiv2.patch(`/unsubscribe/${token}`, params);

            return {
                ...decodedToken,
                ...reduceUnsubscribe(response.data),
            } as IUnsubscribeData;
        },
        onSuccess: () => {
            void queryClient.invalidateQueries();
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
        const decodedToken = JSON.parse(atob(token.split(".")[1]));

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

    const preferences: IUnsubscribePreference[] = (data.preferences ?? []).map(({ preference, enabled }) => {
        const preferenceName = preference.replace("Email.", "");

        const tmpPreference: IUnsubscribePreference = {
            preferenceName,
            preferenceRaw: preference,
            enabled: enabled === "1" || enabled === true,
            label: <></>,
            optionID: preference.replace(/\./g, "||"),
        };

        if (preferenceName !== "DigestEnabled" && preferenceName !== "Digest") {
            tmpPreference.label = getUnsubscribeReason(tmpPreference);
        }

        return tmpPreference;
    });

    let followedContent: IUnsubscribeData["followedContent"];
    let isUnfollowContent: boolean = false;
    let isDigestHideContent: boolean = false;

    const followContentFromAPI = data.followCategory || data.followContent;
    if (!Array.isArray(followContentFromAPI) && followContentFromAPI) {
        const isContentCategory = Boolean(data.followCategory);
        const preference = isContentCategory ? data.followCategory?.["preference"] : data.followContent?.["preference"];
        const preferenceData = (preference ?? "")
            .replace(`.${isContentCategory ? data.followCategory?.categoryID : data.followContent?.contentID}`, "")
            .split(".");
        const preferenceName = preferenceData[preferenceData.length - 1];
        followedContent = {
            preferenceName,
            preferenceRaw: followContentFromAPI.preference,
            enabled: followContentFromAPI.enabled === "1" || followContentFromAPI.enabled === true,
            label: <></>,
            contentID: isContentCategory ? followContentFromAPI["categoryID"] : followContentFromAPI["contentID"],
            contentName: followContentFromAPI["name"],
            contentType: isContentCategory ? "category" : followContentFromAPI["contentType"],
            contentUrl: followContentFromAPI["contentUrl"],
            optionID: followContentFromAPI.preference.replace(/\./g, "||"),
        };
        isUnfollowContent = preferenceName.toLowerCase() === "follow";
        isDigestHideContent = preferenceName.toLowerCase() === "digest";
        followedContent.label = getFollowedContentReason(
            followedContent,
            isUnfollowContent,
            isDigestHideContent,
            isContentCategory,
        );
    }

    const hasMultiple = preferences.length > 1 || (preferences.length > 0 && Boolean(followedContent));
    const disabledPreferences = preferences.filter(({ enabled }) => enabled).length > 0;
    const contentDisabled = !followedContent || (followedContent && followedContent.enabled);

    return {
        preferences,
        followedContent,
        hasMultiple,
        isAllProcessed: !hasMultiple && disabledPreferences && contentDisabled,
        isEmailDigest: preferences.length === 1 && preferences[0].preferenceName === "DigestEnabled",
        isUnfollowContent,
    };
}
