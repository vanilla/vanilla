/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IServerError } from "@library/@types/api/core";
import { IUser } from "@library/@types/api/users";
import FollowContentDropdown from "@library/followedContent/FollowContentDropdown";
import { FollowedContentNotificationPreferencesContext } from "@library/followedContent/FollowedContentNotificationPreferences.context";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import Message from "@library/messages/Message";
import {
    INotificationPreferencesApi,
    NotificationPreferencesContextProvider,
    useNotificationPreferencesContext,
} from "@library/notificationPreferences";
import NotificationPreferencesApi from "@library/notificationPreferences/NotificationPreferences.api";
import {
    useGetCategoryNotificationPreferences,
    usePatchCategoryNotificationPreferences,
} from "@vanilla/addon-vanilla/categories/CategoryNotificationPreferences.context";
import {
    CATEGORY_NOTIFICATION_TYPES,
    getDefaultCategoryNotificationPreferences,
    IFollowedCategoryNotificationPreferences,
} from "@vanilla/addon-vanilla/categories/CategoryNotificationPreferences.hooks";
import { t } from "@vanilla/i18n";
import React, { useState } from "react";

export function CategoryFollowDropdownImpl(
    props: Omit<React.ComponentProps<typeof FollowContentDropdown>, "notificationTypes" | "recordDetails"> & {
        userID: IUser["userID"];
        notificationPreferences?: IFollowedCategoryNotificationPreferences;
    },
) {
    const { userID, notificationPreferences: initialPreferences, ...rest } = props;
    const { recordID } = rest;
    const { preferences } = useNotificationPreferencesContext();

    const [serverError, setServerError] = useState<IServerError | null>(null);
    const classesFrameBody = frameBodyClasses();
    const preferencesQuery = useGetCategoryNotificationPreferences({
        categoryID: recordID,
        userID,
        initialData: initialPreferences ?? getDefaultCategoryNotificationPreferences(preferences?.data),
    });

    const { mutateAsync } = usePatchCategoryNotificationPreferences({
        categoryID: recordID,
        userID,
        setServerError,
    });

    return (
        <FollowedContentNotificationPreferencesContext.Provider
            value={{ preferences: preferencesQuery.data, setPreferences: mutateAsync }}
        >
            {serverError && (
                <Message error={serverError} stringContents={serverError.message} className={classesFrameBody.error} />
            )}
            <FollowContentDropdown
                {...rest}
                recordDetails={{
                    recordKey: "categoryID",
                    recordFollowedContentText: t("View all followed categories"),
                    recordUnfollowText: t("Unfollow Category"),
                }}
                viewRecordText={t("View Category")}
                notificationTypes={CATEGORY_NOTIFICATION_TYPES}
            />
        </FollowedContentNotificationPreferencesContext.Provider>
    );
}

export default function CategoryFollowDropDown(
    props: React.ComponentProps<typeof CategoryFollowDropdownImpl> & {
        api?: INotificationPreferencesApi;
    },
) {
    const { api = NotificationPreferencesApi, ...rest } = props;
    return (
        <NotificationPreferencesContextProvider userID={rest.userID} api={NotificationPreferencesApi}>
            <CategoryFollowDropdownImpl {...rest} />
        </NotificationPreferencesContextProvider>
    );
}
