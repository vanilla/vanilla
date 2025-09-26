/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { AdminSidebarFilters } from "@dashboard/components/AdminSidebarFilters";
import { DashboardSchemaForm } from "@dashboard/forms/DashboardSchemaForm";
import { css } from "@emotion/css";
import { SchemaFormBuilder } from "@library/json-schema-forms";
import { t } from "@vanilla/i18n";

export interface IManageIconsForm {
    iconSize: string;
    iconFilter: string;
    iconColor: string;
    iconType: "all" | "custom" | "system";
}

interface IProps {
    value: IManageIconsForm;
    onChange: (value: IManageIconsForm) => void;
}

export function ManageIconsForm(props: IProps) {
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
                    .textBox("iconFilter", t("Icon Name"), null)
                    .withControlParams({ placeholder: t("Search...") })
                    .withoutBorder()
                    .radioGroup("iconType", t("Icon Type"), null, [
                        {
                            value: "all",
                            label: t("All"),
                        },
                        {
                            value: "custom",
                            label: t("Custom Icon"),
                            description: t("Only show icons that have been overridden with custom icons."),
                        },
                        {
                            value: "system",
                            label: t("System Icon"),
                            description: t("Only show icons that are default system icons."),
                        },
                    ])
                    .withoutBorder()
                    .getSchema()}
            />
            <h3>{t("Previews")}</h3>
            <DashboardSchemaForm
                forceVerticalLabels={true}
                instance={value}
                onChange={onChange}
                groupTag={"div"}
                schema={SchemaFormBuilder.create()

                    .radioGroup("iconSize", t("Icon Size"), null, [
                        {
                            value: "24px",
                            label: "24x24",
                            description: t("This is the actual size most icons render at."),
                        },
                        {
                            value: "48px",
                            label: "48x48",
                        },
                        {
                            value: "96px",
                            label: "96x96",
                        },
                    ])
                    .withoutBorder()
                    .custom("iconColor", {
                        type: "string",
                        "x-control": {
                            label: t("Preview Color"),
                            tooltip: t(
                                "This color will be used to preview the icon color. It will replace any usage of #000000 in your uploaded icons. In the actual application, colors will be dynamic.",
                            ),
                            inputType: "color",
                            placeholder: "#3E3E3E",
                        },
                    })
                    .withoutBorder()
                    .getSchema()}
            />
        </AdminSidebarFilters>
    );
}
