/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { DashboardFormControlGroup, DashboardFormControl } from "@dashboard/forms/DashboardFormControl";
import { widgetSettingsClasses } from "@dashboard/layout/editor/widgetSettings/WidgetSettings.classes";
import { IWidgetConfigurationComponentProps } from "@dashboard/layout/editor/widgetSettings/WidgetSettingsModal";
import WidgetSettingsFormGroupWrapper from "@dashboard/layout/editor/widgetSettings/WidgetSettingsFormGroupWrapper";
import { t } from "@vanilla/i18n";
import { JsonSchemaForm } from "@vanilla/json-schema-forms";
import React from "react";

export function WidgetSettings(props: IWidgetConfigurationComponentProps) {
    const classes = widgetSettingsClasses();

    return (
        <div className={classes.settings}>
            <h3 className={classes.settingsHeader}>{t("Widget Options")}</h3>
            <JsonSchemaForm
                FormControlGroup={DashboardFormControlGroup}
                FormControl={DashboardFormControl}
                schema={props.schema}
                instance={props.value}
                onChange={props.onChange}
                formGroupWrapper={WidgetSettingsFormGroupWrapper}
                expandableFormGroups={["containerOptions", "itemOptions"]}
                formGroupWrapperExclusions={["apiParams"]} //api params are normally first level/not collapsable
                hideDescriptionInLabels
                size="small"
            />
        </div>
    );
}
