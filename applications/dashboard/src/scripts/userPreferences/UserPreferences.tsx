/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import Button from "@library/forms/Button";
import { t } from "@vanilla/i18n";
import React, { ReactNode, useState } from "react";
import { MemoryRouter } from "react-router";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import DefaultCategoriesModal from "@dashboard/userPreferences/DefaultCategoriesModal";
import DefaultNotificationPreferences from "@dashboard/userPreferences/DefaultNotificationPreferences/DefaultNotificationPreferences";
import { logDebug } from "@vanilla/utils";
import { List } from "@library/lists/List";
import { PageBox } from "@library/layout/PageBox";
import { BorderType } from "@library/styles/styleHelpersBorders";
import Heading from "@library/layout/Heading";

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

            <List className={dashboardClasses().extendRow}>
                <PageBox as="li" options={{ borderType: BorderType.SEPARATOR }}>
                    <DefaultNotificationPreferences />
                </PageBox>
                <PageBox as="li" options={{ borderType: BorderType.SEPARATOR }}>
                    <div className={dashboardClasses().buttonRow}>
                        <div className="label-wrap">
                            <Heading depth={3}>{t("Default Followed Categories")}</Heading>
                            <p>
                                {t(
                                    "Users can follow categories to subscribe to notifications for new posts. Select which categories new users should follow by default.",
                                )}
                            </p>
                        </div>
                        <Button
                            onClick={() => {
                                setShowEditDefaultCategoriesModal(true);
                            }}
                        >
                            {t("Edit Default Categories")}
                        </Button>
                    </div>
                </PageBox>
            </List>

            {UserPreferences.extraPreferences.map((preference) => (
                <React.Fragment key={preference.key}>{preference.component}</React.Fragment>
            ))}

            <DefaultCategoriesModal
                isVisible={showDefaultCategoriesModal}
                onCancel={() => setShowEditDefaultCategoriesModal(false)}
            />
        </MemoryRouter>
    );
}
