/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { DashboardFormList } from "@dashboard/forms/DashboardFormList";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import Button from "@library/forms/Button";
import { t } from "@vanilla/i18n";
import React, { ReactNode, useState } from "react";
import { MemoryRouter } from "react-router";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import DefaultCategoriesModal from "@dashboard/userPreferences/DefaultCategoriesModal";
import DefaultNotificationPreferences from "@dashboard/userPreferences/DefaultNotificationPreferences/DefaultNotificationPreferences";
import { logDebug } from "@vanilla/utils";

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
    const [showDefaultCategoriesModal, setShowEditDefaultCategoriesModal] = useState(false);
    return (
        <MemoryRouter>
            <DashboardHeaderBlock title={t("User Preferences")} />

            <DashboardFormList>
                <DefaultNotificationPreferences />
                <DashboardFormGroup
                    label={t("Default Followed Categories")}
                    description={t(
                        "Users can follow categories to subscribe to notifications for new posts. Select which categories new users should follow by default.",
                    )}
                    className={dashboardClasses().spaceBetweenFormGroup}
                >
                    <div className="input-wrap">
                        <Button
                            onClick={() => {
                                setShowEditDefaultCategoriesModal(true);
                            }}
                        >
                            {t("Edit Default Categories")}
                        </Button>
                    </div>
                </DashboardFormGroup>
                {UserPreferences.extraPreferences.map((preference) => (
                    <React.Fragment key={preference.key}>{preference.component}</React.Fragment>
                ))}
            </DashboardFormList>

            <DefaultCategoriesModal
                isVisible={showDefaultCategoriesModal}
                onCancel={() => setShowEditDefaultCategoriesModal(false)}
            />
        </MemoryRouter>
    );
}
