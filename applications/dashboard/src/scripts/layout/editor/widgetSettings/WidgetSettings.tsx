/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { DashboardFormControlGroup, DashboardFormControl } from "@dashboard/forms/DashboardFormControl";
import { widgetSettingsClasses } from "@dashboard/layout/editor/widgetSettings/WidgetSettings.classes";
import WidgetSettingsAccordion from "@dashboard/layout/editor/widgetSettings/WidgetSettingsAccordion";
import { t } from "@vanilla/i18n";
import { JsonSchema, JsonSchemaForm } from "@vanilla/json-schema-forms";
import React from "react";

interface IProps {
    schema: JsonSchema;
    value: any;
    onChange: (newValue: any) => void;
}

export function WidgetSettings(props: IProps) {
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
                expandableFormGroupWrapper={WidgetSettingsAccordion}
                formGroupWrapperExclusions={["apiParams"]} //api params are normally first level/not collapsable
            />
        </div>
    );
}
