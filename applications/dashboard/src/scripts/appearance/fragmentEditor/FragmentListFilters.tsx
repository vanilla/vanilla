/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import type { FragmentsApi } from "@dashboard/appearance/fragmentEditor/FragmentsApi";
import { AdminSidebarFilters } from "@dashboard/components/AdminSidebarFilters";
import { DashboardSchemaForm } from "@dashboard/forms/DashboardSchemaForm";
import { SchemaFormBuilder } from "@library/json-schema-forms";
import { getRegisteredFragments } from "@library/utility/fragmentsRegistry";
import { t } from "@vanilla/i18n";

export type IFragmentListFilters = {
    name: string;
    appliedStatus: FragmentsApi.AppliedStatus;
    fragmentType?: string;
};

interface IProps {
    value: IFragmentListFilters;
    onChange: (value: IFragmentListFilters) => void;
}

export function FragmentListFilters(props: IProps) {
    const { value, onChange } = props;
    return (
        <AdminSidebarFilters>
            <h3>{t("Filters")}</h3>
            <DashboardSchemaForm
                forceVerticalLabels={true}
                instance={value}
                onChange={onChange}
                groupTag={"div"}
                schema={SchemaFormBuilder.create()
                    .textBox("name", t("Fragment Name"), null)
                    .withControlParams({ placeholder: t("Search...") })
                    .withoutBorder()
                    .selectStatic(
                        "fragmentType",
                        t("Fragment Type"),
                        null,
                        Object.entries(getRegisteredFragments()).map(([key, value]) => ({
                            value: key,
                            label: value.fragmentType,
                        })),
                    )
                    .withoutBorder()
                    .radioGroup("appliedStatus", t("Layout Status"), null, [
                        {
                            value: "all",
                            label: t("All"),
                        },
                        {
                            value: "applied",
                            label: t("Applied"),
                            description: t("Show fragments applied in an active layout."),
                        },
                        {
                            value: "not-applied",
                            label: t("Not Applied"),
                            description: t("Show fragments not applied in any active layout."),
                        },
                    ])
                    .withoutBorder()
                    .getSchema()}
            />
        </AdminSidebarFilters>
    );
}
