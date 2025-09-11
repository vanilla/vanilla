/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import apiv2 from "@library/apiv2";
import { getFollowedContentReason, getUnsubscribeReason } from "@library/unsubscribe/getUnsubscribeReason";
import { t } from "@library/utility/appUtils";
import { QueryKey, useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
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
export function useUnsubscribeData(token: IUnsubscribeToken) {
    const queryKey: QueryKey = ["unsubscribe", token];

    return useQuery({
        queryKey,
        queryFn: async () => {
            const decodedToken = decodeToken(token);

            if (!decodedToken) {
                throw { message: t("Unsubscribe token is invalid.") };
            }

            const response = await apiv2.post<IUnsubscribeResult>(`/unsubscribe/${token}`);

            const data = {
                ...decodedToken,
                ...reduceUnsubscribe(response.data),
            } as IUnsubscribeData;

            return data;
        },
    });
}

/**
 * Undo the unsubscribe/unfollow
 */
export function useUndoUnsubscribe(token: IUnsubscribeToken) {
    const queryClient = useQueryClient();
    const decodedToken = decodeToken(token);
    const queryKey: QueryKey = ["unsubscribe", token];

    return useMutation({
        mutationKey: ["undo_unsubscribe", decodedToken?.activityID ?? "invalid"],
        mutationFn: async (token: IUnsubscribeToken) => {
            if (!decodedToken) {
                throw { message: t("Unsubscribe token is invalid.") };
            }

            const response = await apiv2.post<IUnsubscribeResult>(`/unsubscribe/resubscribe/${token}`);

            return {
                ...decodedToken,
                ...reduceUnsubscribe(response.data),
            } as IUnsubscribeData;
        },
        onSuccess: (newData) => {
            queryClient.setQueryData<IUnsubscribeData>(queryKey, newData);
        },
    });
}

/**
 * Update a list of notification settings when multiple options exist
 */
export function useSaveUnsubscribe(token: IUnsubscribeToken) {
    const queryClient = useQueryClient();
    const decodedToken = decodeToken(token);
    const queryKey: QueryKey = ["unsubscribe", token];

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

            const response = await apiv2.patch<IUnsubscribeResult>(`/unsubscribe/${token}`, params);

            return {
                ...decodedToken,
                ...reduceUnsubscribe(response.data),
            } as IUnsubscribeData;
        },
        onSuccess: (newData) => {
            queryClient.setQueryData<IUnsubscribeData>(queryKey, newData);
        },
    });
}

/**
 * Construct a link to the notification preferences or followed content page
 */
export function useGetPreferenceLink() {
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
function decodeToken(token: IUnsubscribeToken): IDecodedToken | undefined {
    if (!token) {
        return;
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
        return;
    }
}

// Restructure the data for UI rendering
function reduceUnsubscribe(data: IUnsubscribeResult): Partial<IUnsubscribeData> {
    if (!data) {
        return {};
    }

    let mutedContent: IUnsubscribeData["mutedContent"] | undefined;
    if (data.mute) {
        mutedContent = {
            discussionID: data.mute.discussionID,
            label: data.mute.discussionName,
        };
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
    const { followCategory, followContent } = data;

    if (!Array.isArray(followContentFromAPI) && followContentFromAPI) {
        const isContentCategory = !!followCategory && !Array.isArray(followCategory);
        const preference = isContentCategory ? followCategory?.["preference"] : followContent?.["preference"];
        const preferenceData = (preference ?? "")
            .replace(`.${isContentCategory ? followCategory.categoryID : followContent?.["contentID"]}`, "")
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

    let isAlreadyProcessed = false;

    if (!mutedContent) {
        isAlreadyProcessed = !hasMultiple && disabledPreferences && contentDisabled;
        if (
            preferences?.length === 0 &&
            ((Array.isArray(followCategory) && followCategory?.length === 0) ||
                (Array.isArray(followContent) && followContent?.length === 0))
        ) {
            isAlreadyProcessed = true;
        }
    }

    return {
        preferences,
        followedContent,
        hasMultiple,
        isAlreadyProcessed,
        isEmailDigest: preferences.length === 1 && preferences[0].preferenceName === "DigestEnabled",
        isUnfollowContent,
        mutedContent,
    };
}
