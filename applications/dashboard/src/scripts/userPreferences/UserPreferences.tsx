/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { DashboardFormList } from "@dashboard/forms/DashboardFormList";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import Button from "@library/forms/Button";
import { DashboardToggle } from "@dashboard/forms/DashboardToggle";
import { t } from "@vanilla/i18n";
import React, { useState } from "react";
import { MemoryRouter } from "react-router";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import DefaultCategoriesModal from "@dashboard/userPreferences/DefaultCategoriesModal";

export function UserPreferences() {
    const [showDefaultCategoriesModal, setShowEditDefaultCategoriesModal] = useState(false);

    return (
        <MemoryRouter>
            <DashboardHeaderBlock title={t("User Preferences")} />

            <DashboardFormList>
                <DashboardFormGroup
                    label={t("Default Followed Categories")}
                    description={t(
                        "Users can follow categories to subscribe to notifications for new posts. Select which categories new users should follow by default.",
                        // "Users can follow categories to subscribe to notifications for new posts, and/or to include content from specific categories in their email digest. Select which categories new users should follow by default.",
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

                {/* <DashboardFormGroup
                    label={t("Anonymize Analytics Data by Default")}
                    description={t(
                        "When this setting is enabled, user fragments in Analytics data will be anonymized by default, until the user consents to Analytics cookies. Learn more",
                    )}
                    className={dashboardClasses().spaceBetweenFormGroup}
                >
                    <DashboardToggle onChange={() => {}} checked={true} />
                </DashboardFormGroup> */}
            </DashboardFormList>

            {showDefaultCategoriesModal && (
                <DefaultCategoriesModal onCancel={() => setShowEditDefaultCategoriesModal(false)} />
            )}
        </MemoryRouter>
    );
}
