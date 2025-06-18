/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import DefaultNotificationPreferences from "@dashboard/userPreferences/DefaultNotificationPreferences/DefaultNotificationPreferences";
import { NotificationPreferencesContextProvider, api } from "@library/notificationPreferences";
import { t } from "@vanilla/i18n";
import { logDebug } from "@vanilla/utils";
import React, { ReactNode } from "react";
import { MemoryRouter } from "react-router";
import DefaultFollowedCategories from "./DefaultFollowedCategories/DefaultFollowedCategories";
interface IExtraUserPreference {
    key: string;
    component: ReactNode;
}

UserPreferences.extraPreferences = [] as IExtraUserPreference[];
UserPreferences.registerExtraPreference = function (preference: IExtraUserPreference) {
    const registeredKeys: Array<IExtraUserPreference["key"]> = UserPreferences.extraPreferences.map(
        (preference) => preference.key,
    );

    if (!registeredKeys.includes(preference.key)) {
        UserPreferences.extraPreferences.push(preference);
    } else {
        logDebug(`A extra user preference with key: ${preference.key} has already been registered`);
    }
};

export function UserPreferences() {
    return (
        <MemoryRouter>
            <NotificationPreferencesContextProvider userID={"defaults"} api={api}>
                <DashboardHeaderBlock title={t("User Preferences")} />

                <DefaultNotificationPreferences />
                <DefaultFollowedCategories />

                {UserPreferences.extraPreferences.map((preference) => (
                    <React.Fragment key={preference.key}>{preference.component}</React.Fragment>
                ))}
            </NotificationPreferencesContextProvider>
        </MemoryRouter>
    );
}
