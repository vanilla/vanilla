/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { useEffect } from "react";
import { DashboardFormControl } from "@dashboard/forms/DashboardFormControl";
import { DashboardFormControlGroup } from "@dashboard/forms/DashboardFormControlGroup";
import { widgetSettingsClasses } from "@dashboard/layout/editor/widgetSettings/WidgetSettings.classes";
import { IWidgetConfigurationComponentProps } from "@dashboard/layout/editor/widgetSettings/WidgetSettingsModal";
import WidgetSettingsFormGroupWrapper from "@dashboard/layout/editor/widgetSettings/WidgetSettingsFormGroupWrapper";
import { t } from "@vanilla/i18n";
import { IFieldError, JsonSchemaForm } from "@vanilla/json-schema-forms";
import { QUICK_LINKS_LIST_AS_MODAL } from "@dashboard/layout/editor/widgetSettings/overrides/QuickLinksListAsModal";
import { TABS_AS_MODAL } from "@dashboard/layout/editor/widgetSettings/overrides/TabsAsModal";
import { SITE_TOTALS_AS_MODAL } from "@dashboard/layout/editor/widgetSettings/overrides/SiteTotalsAsModal";
import {
    widgetsSchemaTransformer,
    showDisplayOptions,
} from "@dashboard/layout/editor/widgetSettings/WidgetSchemaTransformer";
import { DashboardFormStyleContext } from "@dashboard/forms/DashboardFormStyleContext";
import { useEditorThemePreview } from "@library/theming/EditorThemePreviewContext";
import get from "lodash-es/get";

interface IProps extends IWidgetConfigurationComponentProps {
    fieldErrors?: Record<string, IFieldError[]>;
}

export function WidgetSettings(props: IProps) {
    const classes = widgetSettingsClasses();
    const { transformedSchema, value } = widgetsSchemaTransformer(props.schema, props.middlewares, props.value);
    const expandableFormGroups = ["containerOptions", "itemOptions", "$middleware", "displayOptions", "$fragmentImpls"];
    if (showDisplayOptions(props.schema.description)) {
        expandableFormGroups.push("apiParams");
    }
    // and these two are special cases, normally for detailed meta options to show/hide etc
    if (props.schema.properties.discussionOptions) {
        expandableFormGroups.push("discussionOptions");
    }
    if (props.schema.properties.categoryOptions) {
        expandableFormGroups.push("categoryOptions");
    }

    if (props.schema.properties.articleOptions) {
        expandableFormGroups.push("articleOptions");
    }

    // Knowledge Bases List widget / asset
    if (props.schema?.description?.includes("Knowledge Bases")) {
        expandableFormGroups.push("display");
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

    /**
     * Call onChange when the value changes because the transformer can update
     * more fields than just the one that the user has interacted with for
     * dynamic titles and descriptions.
     */
    useEffect(() => {
        props.onChange(() => value);
    }, [value]);

    const themePreviewContext = useEditorThemePreview();

    return (
        <div className={classes.settings}>
            <h3 className={classes.settingsHeader}>{t("Widget Options")}</h3>
            <DashboardFormStyleContext.Provider value={{ compact: true }}>
                <JsonSchemaForm
                    FormControlGroup={DashboardFormControlGroup}
                    FormControl={(props) =>
                        DashboardFormControl(props, [QUICK_LINKS_LIST_AS_MODAL, TABS_AS_MODAL, SITE_TOTALS_AS_MODAL])
                    }
                    FormGroupWrapper={formGroupWrapper}
                    schema={transformedSchema}
                    instance={value}
                    onChange={props.onChange}
                    hideDescriptionInLabels
                    size="small"
                    autocompleteClassName={classes.autocompleteContainer}
                    fieldErrors={props.fieldErrors}
                    customConditionValidator={(condition, rootInstance) => {
                        const fieldName = condition.field;
                        let fieldValue = fieldName ? get(rootInstance, fieldName) : null;
                        if (condition.type === "noCustomFragment") {
                            const fragmentType = condition.fragmentType;
                            fieldValue = fieldValue ?? "styleguide";

                            if (fieldValue === "styleguide") {
                                // Go get it from the previewed theme.
                                fieldValue =
                                    themePreviewContext.previewedThemeQuery.data?.assets?.variables?.data
                                        ?.globalFragmentImpls?.[fragmentType]?.fragmentUUID ?? "system";
                            }

                            return fieldValue === "system";
                        }

                        // Use the default validator.
                        return null;
                    }}
                />
            </DashboardFormStyleContext.Provider>
        </div>
    );
}
