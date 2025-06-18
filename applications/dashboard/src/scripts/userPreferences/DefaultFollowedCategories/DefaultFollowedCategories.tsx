/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import DefaultCategoriesModal, {
    IFollowedCategory,
    ILegacyCategoryPreferences,
} from "@dashboard/userPreferences/DefaultFollowedCategories/DefaultCategoriesModal";
import { useConfigMutation, useConfigQuery } from "@library/config/configHooks";
import Button from "@library/forms/Button";
import { t } from "@vanilla/i18n";
import { useState } from "react";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardLabelType } from "@dashboard/forms/DashboardLabelType";
import { DashboardInputWrap } from "@dashboard/forms/DashboardInputWrap";
import { convertOldConfig } from "./DefaultCategories.utils";

const CONFIG_KEY = "preferences.categoryFollowed.defaults";

function useDefaultCategories(): IFollowedCategory[] | ILegacyCategoryPreferences[] | undefined {
    const { data } = useConfigQuery([CONFIG_KEY]);

    return data?.[CONFIG_KEY] ? JSON.parse(data[CONFIG_KEY]) : undefined;
}

function usePatchDefaultFollowedCategories() {
    const mutation = useConfigMutation(150);

    return async (defaultFollowedCategories: IFollowedCategory[]) =>
        await mutation.mutateAsync({ [`${CONFIG_KEY}`]: JSON.stringify(defaultFollowedCategories) });
}

export default function DefaultFollowedCategories() {
    const defaultCategories = useDefaultCategories();
    const patchDefaultCategories = usePatchDefaultFollowedCategories();
    const [showDefaultCategoriesModal, setShowEditDefaultCategoriesModal] = useState(false);
    return (
        <>
            <DashboardFormGroup
                label={t("Default Followed Categories")}
                labelType={DashboardLabelType.WIDE}
                description={t(
                    "Users can follow categories to subscribe to notifications for new posts. Select which categories new users should follow by default.",
                )}
            >
                <DashboardInputWrap>
                    <Button
                        onClick={() => {
                            setShowEditDefaultCategoriesModal(true);
                        }}
                        disabled={!defaultCategories}
                    >
                        {t("Edit Default Categories")}
                    </Button>
                </DashboardInputWrap>
            </DashboardFormGroup>
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
        </>
    );
}
