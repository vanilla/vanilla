/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import Button from "@library/forms/Button";
import { t } from "@vanilla/i18n";
import React, { ReactNode, useState } from "react";
import { MemoryRouter } from "react-router";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import DefaultCategoriesModal, {
    IFollowedCategory,
    ILegacyCategoryPreferences,
} from "@dashboard/userPreferences/DefaultCategoriesModal";
import DefaultNotificationPreferences from "@dashboard/userPreferences/DefaultNotificationPreferences/DefaultNotificationPreferences";
import { logDebug } from "@vanilla/utils";
import { List } from "@library/lists/List";
import { PageBox } from "@library/layout/PageBox";
import { BorderType } from "@library/styles/styleHelpersBorders";
import Heading from "@library/layout/Heading";
import { NotificationPreferencesContextProvider, api } from "@library/notificationPreferences";
import { useConfigMutation, useConfigQuery } from "@library/config/configHooks";
import { convertOldConfig } from "./DefaultCategories.utils";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
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

const CONFIG_KEY = "preferences.categoryFollowed.defaults";

function useDefaultCategories(): IFollowedCategory[] | ILegacyCategoryPreferences[] | undefined {
    const { data } = useConfigQuery([CONFIG_KEY]);

    return data?.[CONFIG_KEY] ? JSON.parse(data[CONFIG_KEY]) : undefined;
}

function usePatchDefaultFollowedCategories() {
    const mutation = useConfigMutation();

    return (defaultFollowedCategories: IFollowedCategory[]) =>
        mutation.mutateAsync({ [`${CONFIG_KEY}`]: JSON.stringify(defaultFollowedCategories) });
}

export function UserPreferences() {
    const defaultCategories = useDefaultCategories();
    const patchDefaultCategories = usePatchDefaultFollowedCategories();
    const [showDefaultCategoriesModal, setShowEditDefaultCategoriesModal] = useState(false);

    return (
        <MemoryRouter>
            <NotificationPreferencesContextProvider userID={"defaults"} api={api}>
                <DashboardHeaderBlock title={t("User Preferences")} />

                <DefaultNotificationPreferences />
                <DashboardFormGroup
                    label={t("Default Followed Categories")}
                    labelType={DashboardLabelType.WIDE}
                    description={t(
                        "Users can follow categories to subscribe to notifications for new posts. Select which categories new users should follow by default.",
                    )}
                >
                    <span className={"input-wrap-right"}>
                        <Button
                            onClick={() => {
                                setShowEditDefaultCategoriesModal(true);
                            }}
                            disabled={!defaultCategories}
                        >
                            {t("Edit Default Categories")}
                        </Button>
                    </span>
                </DashboardFormGroup>

                {UserPreferences.extraPreferences.map((preference) => (
                    <React.Fragment key={preference.key}>{preference.component}</React.Fragment>
                ))}

                {!!defaultCategories && (
                    <DefaultCategoriesModal
                        isVisible={showDefaultCategoriesModal}
                        initialValues={convertOldConfig(defaultCategories)}
                        onSubmit={async (values) => {
                            await patchDefaultCategories(values);
                        }}
                        onCancel={() => setShowEditDefaultCategoriesModal(false)}
                    />
                )}
            </NotificationPreferencesContextProvider>
        </MemoryRouter>
    );
}
