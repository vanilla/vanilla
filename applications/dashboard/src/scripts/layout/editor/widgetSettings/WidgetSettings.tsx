/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { DashboardFormControlGroup, DashboardFormControl } from "@dashboard/forms/DashboardFormControl";
import { widgetSettingsClasses } from "@dashboard/layout/editor/widgetSettings/WidgetSettings.classes";
import { IWidgetConfigurationComponentProps } from "@dashboard/layout/editor/widgetSettings/WidgetSettingsModal";
import WidgetSettingsFormGroupWrapper from "@dashboard/layout/editor/widgetSettings/WidgetSettingsFormGroupWrapper";
import { t } from "@vanilla/i18n";
import { JsonSchemaForm } from "@vanilla/json-schema-forms";
import { QUICK_LINKS_LIST_AS_MODAL } from "@dashboard/layout/editor/widgetSettings/overrides/QuickLinksListAsModal";
import { TABS_AS_MODAL } from "@dashboard/layout/editor/widgetSettings/overrides/TabsAsModal";
import { SITE_TOTALS_AS_MODAL } from "@dashboard/layout/editor/widgetSettings/overrides/SiteTotalsAsModal";
import {
    widgetsSchemaTransformer,
    showDisplayOptions,
} from "@dashboard/layout/editor/widgetSettings/WidgetSchemaTransformer";

export function WidgetSettings(props: IWidgetConfigurationComponentProps) {
    const classes = widgetSettingsClasses();
    const transformedSchema = widgetsSchemaTransformer(props.schema, props.middlewares, props.value);
    const expandableFormGroups = ["containerOptions", "itemOptions", "$middleware", "displayOptions"];
    if (showDisplayOptions(props.schema.description)) {
        expandableFormGroups.push("apiParams");
        if (props.schema.properties.discussionOptions) {
            expandableFormGroups.push("discussionOptions");
        }
    }

    const formGroupWrapper: React.ComponentProps<typeof JsonSchemaForm>["FormGroupWrapper"] = function (props) {
        return (
            <WidgetSettingsFormGroupWrapper
                expandable={
                    !!props.groupName &&
                    expandableFormGroups.map((name) => name.toLowerCase()).includes(props.groupName.toLowerCase())
                }
                {...props}
            />
        );
    };

    return (
        <div className={classes.settings}>
            <h3 className={classes.settingsHeader}>{t("Widget Options")}</h3>
            <JsonSchemaForm
                FormControlGroup={DashboardFormControlGroup}
                FormControl={(props) =>
                    DashboardFormControl(props, [QUICK_LINKS_LIST_AS_MODAL, TABS_AS_MODAL, SITE_TOTALS_AS_MODAL])
                }
                FormGroupWrapper={formGroupWrapper}
                schema={transformedSchema}
                instance={props.value}
                onChange={props.onChange}
                hideDescriptionInLabels
                size="small"
                autocompleteClassName={classes.autocompleteContainer}
            />
        </div>
    );
}
