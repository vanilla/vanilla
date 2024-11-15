/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IUser } from "@library/@types/api/users";
import React, { useCallback } from "react";
import { isINotificationPreference } from "@library/notificationPreferences/utils";
import {
    INotificationPreferencesApi,
    NotificationPreferencesContextProvider,
    useNotificationPreferencesContext,
} from "@library/notificationPreferences";
import NotificationPreferencesApi from "@library/notificationPreferences/NotificationPreferences.api";
import debounce from "lodash-es/debounce";
import { useFormik } from "formik";
import {
    useCategoryNotificationPreferencesContext,
    getDefaultCategoryNotificationPreferences,
    ICategoryPreferences,
    CATEGORY_NOTIFICATION_TYPES,
} from "@vanilla/addon-vanilla/categories/CategoryNotificationPreferences.hooks";
import { CategoryNotificationPreferencesContextProvider } from "@vanilla/addon-vanilla/categories/CategoryNotificationPreferences.context";
import { FollowDropdown, IFollowDropdownProps } from "@vanilla/addon-vanilla/forms/FollowDropdown";
import { CategoryPreferencesTable } from "@library/preferencesTable/CategoryPreferencesTable";

interface IProps extends IFollowDropdownProps {
    categoryID: number;
    categoryName: string;
    notificationPreferences?: ICategoryPreferences;
}

export function CategoryFollowDropDownImpl(props: IProps) {
    const { categoryID, emailDigestEnabled } = props;

    const { preferences: categoryNotificationPreferences, setPreferences } =
        useCategoryNotificationPreferencesContext();

    const debouncedSetPreferences = useCallback(
        debounce(setPreferences, 1250, {
            leading: true,
        }),
        [setPreferences],
    );

    const { preferences: globalNotificationPreferences } = useNotificationPreferencesContext();
    const defaultUserPreferences = globalNotificationPreferences?.data ?? undefined;

    const canIncludeInDigest =
        emailDigestEnabled &&
        isINotificationPreference(defaultUserPreferences?.DigestEnabled) &&
        defaultUserPreferences?.DigestEnabled?.email;

    const { values, setValues, submitForm } = useFormik<ICategoryPreferences>({
        enableReinitialize: true,
        initialValues: categoryNotificationPreferences,
        onSubmit: async (values) => {
            await debouncedSetPreferences(values);
        },
    });

    async function unfollowAndResetPreferences() {
        setValues((values) => ({
            // set everything to false
            ...Object.entries(values).reduce((acc, [key, type]) => {
                acc[key] = false;
                return acc;
            }, {} as ICategoryPreferences),
            ...(props.emailDigestEnabled && { "preferences.email.digest": false }),
        }));
        await submitForm();
    }

    return (
        <FollowDropdown
            {...props}
            recordID={categoryID}
            name={props.categoryName}
            emailDigestEnabled={emailDigestEnabled}
            preferencesTable={
                <CategoryPreferencesTable
                    canIncludeInDigest={canIncludeInDigest}
                    preferences={values}
                    onPreferenceChange={async function (delta) {
                        setValues((values) => ({ ...values, ...delta }));
                        await submitForm();
                    }}
                    preview={props.preview}
                    notificationTypes={CATEGORY_NOTIFICATION_TYPES}
                />
            }
            notificationTypes={CATEGORY_NOTIFICATION_TYPES}
            updatePreferences={setPreferences}
            submitForm={submitForm}
            unfollowAndResetPreferences={unfollowAndResetPreferences}
            isFollowed={categoryNotificationPreferences?.["preferences.followed"] ?? false}
            defaultUserPreferences={defaultUserPreferences}
            onPreferencesChange={props.onPreferencesChange}
        />
    );
}

export function CategoryFollowDropDownWithCategoryNotificationsContext(
    props: React.ComponentProps<typeof CategoryFollowDropDownImpl> & {
        userID: IUser["userID"];
    },
) {
    const { userID, ...rest } = props;
    const { preferences } = useNotificationPreferencesContext();

    return (
        <CategoryNotificationPreferencesContextProvider
            userID={props.userID}
            categoryID={props.categoryID}
            initialPreferences={
                props.notificationPreferences ?? getDefaultCategoryNotificationPreferences(preferences?.data)
            }
        >
            <CategoryFollowDropDownImpl {...rest} />
        </CategoryNotificationPreferencesContextProvider>
    );
}

export default function CategoryFollowDropdownWithNotificationPreferencesContext(
    props: React.ComponentProps<typeof CategoryFollowDropDownImpl> & {
        userID: IUser["userID"];
        api?: INotificationPreferencesApi;
    },
) {
    const { api = NotificationPreferencesApi, ...rest } = props;
    return (
        <NotificationPreferencesContextProvider userID={rest.userID} api={NotificationPreferencesApi}>
            <CategoryFollowDropDownWithCategoryNotificationsContext {...rest} />
        </NotificationPreferencesContextProvider>
    );
}
