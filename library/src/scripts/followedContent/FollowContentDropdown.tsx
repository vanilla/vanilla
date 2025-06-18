/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    FollowedContentNotificationPreferences,
    IFollowedContentNotificationPreferencesContext,
} from "@library/followedContent/FollowedContent.types";
import { FollowedContentNotificationPreferencesContext } from "@library/followedContent/FollowedContentNotificationPreferences.context";
import { FollowedNotificationPreferencesTable } from "@library/followedContent/FollowedNotificationPreferencesTable/FollowedNotificationPreferencesTable";
import { NotificationType, useNotificationPreferencesContext } from "@library/notificationPreferences";
import { isINotificationPreference } from "@library/notificationPreferences/utils";
import { FollowDropdown } from "@vanilla/addon-vanilla/forms/FollowDropdown";
import { useFormik } from "formik";
import debounce from "lodash-es/debounce";
import React, { useCallback, useContext } from "react";

export default function FollowContentDropdown<T extends Record<string, NotificationType>>(
    props: Omit<
        React.ComponentProps<typeof FollowDropdown<T>>,
        "updatePreferences" | "unfollowAndResetPreferences" | "isFollowed" | "defaultUserPreferences"
    >,
) {
    const { recordID, emailDigestEnabled } = props;

    const { preferences, setPreferences } = useContext(
        FollowedContentNotificationPreferencesContext,
    ) as IFollowedContentNotificationPreferencesContext<FollowedContentNotificationPreferences<T>>;

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

    const { values, setValues, submitForm } = useFormik<FollowedContentNotificationPreferences<T>>({
        enableReinitialize: true,
        initialValues: preferences,
        onSubmit: async (values) => {
            await debouncedSetPreferences(values);
        },
    });

    async function unfollowAndResetPreferences() {
        await setValues((values) => ({
            // set everything to false
            ...Object.entries(values).reduce((acc, [key, type]) => {
                acc[key] = false;
                return acc;
            }, {} as FollowedContentNotificationPreferences<T>),
            ...(props.emailDigestEnabled && { "preferences.email.digest": false }),
        }));
        await submitForm();
    }

    const { "preferences.followed": followed } = values;

    return (
        <FollowDropdown
            {...props}
            recordID={recordID}
            emailDigestEnabled={emailDigestEnabled}
            updatePreferences={async (preferences) => {
                await setPreferences(preferences);
            }}
            unfollowAndResetPreferences={unfollowAndResetPreferences}
            isFollowed={preferences?.["preferences.followed"] ?? false}
            defaultUserPreferences={defaultUserPreferences}
        >
            <form
                role="form"
                onSubmit={async (e) => {
                    e.preventDefault();
                    await submitForm();
                }}
            >
                <FollowedNotificationPreferencesTable
                    canIncludeInDigest={canIncludeInDigest}
                    notificationTypes={props.notificationTypes}
                    preferences={values}
                    onPreferenceChange={async function (delta) {
                        await setValues((values) => ({ ...values, ...delta, "preferences.followed": followed }));
                        await submitForm();
                    }}
                    preview={props.preview}
                />
            </form>
        </FollowDropdown>
    );
}
