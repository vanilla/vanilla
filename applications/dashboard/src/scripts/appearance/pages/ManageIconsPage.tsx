/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useActiveIconsQuery } from "@dashboard/appearance/manageIcons/ManageIcons.hooks";
import { ManageIconsBulkActions } from "@dashboard/appearance/manageIcons/ManageIconsBulkActions";
import { ManageIconsForm, type IManageIconsForm } from "@dashboard/appearance/manageIcons/ManageIconsForm";
import { ManageIconsFormContextProvider } from "@dashboard/appearance/manageIcons/ManageIconsFormContext";
import { ManageIconsTable } from "@dashboard/appearance/manageIcons/ManageIconsTable";
import { AppearanceAdminLayout } from "@dashboard/components/navigation/AppearanceAdminLayout";
import { QueryLoader } from "@library/loaders/QueryLoader";
import { t } from "@vanilla/i18n";
import { useState } from "react";

export default function IconSettingsPage() {
    const activeIconsQuery = useActiveIconsQuery();
    const [form, setForm] = useState<IManageIconsForm>({
        iconSize: "48px",
        iconFilter: "",
        iconColor: "#3E3E3E",
        iconType: "all",
    });

    return (
        <AppearanceAdminLayout
            title={t("Manage Icons")}
            titleBarActions={<ManageIconsBulkActions />}
            content={
                <QueryLoader
                    query={activeIconsQuery}
                    success={(icons) => {
                        return (
                            <>
                                <ManageIconsFormContextProvider {...form}>
                                    <ManageIconsTable activeIcons={icons} />
                                </ManageIconsFormContextProvider>
                            </>
                        );
                    }}
                />
            }
            rightPanel={
                <div>
                    <ManageIconsForm value={form} onChange={setForm} />
                </div>
            }
        />
    );
}
