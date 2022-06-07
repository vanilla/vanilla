/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import SmartLink from "@library/routing/links/SmartLink";
import { widgetSettingsClasses } from "@dashboard/layout/editor/widgetSettings/WidgetSettings.classes";
import Translate from "@library/content/Translate";
import { IWidgetConfigurationComponentProps } from "@dashboard/layout/editor/widgetSettings/WidgetSettingsModal";
import { HtmlWidgetCodeEditor } from "@library/htmlWidget/HtmlWidgetEditor";
import { LayoutRenderer } from "@library/features/Layout/LayoutRenderer";
import { FauxWidget, fetchOverviewComponent } from "@dashboard/layout/overview/LayoutOverview";
import { WidgetContextProvider } from "@library/layout/Widget";
import { IWidgetCatalog } from "@dashboard/layout/layoutSettings/LayoutSettings.types";

interface IWidgetSettingsPreviewProps {
    widgetCatalog: IWidgetCatalog;
    widgetID: string;
    config?: object;
}

export function WidgetSettingsPreview(props: IWidgetConfigurationComponentProps & IWidgetSettingsPreviewProps) {
    const schemaIncludesHtml = "html" in props.schema.properties;

    const classes = widgetSettingsClasses();
    const { widgetCatalog, widgetID, config } = props;

    const rendererConfig = {
        layout: [
            {
                $reactComponent: widgetCatalog[widgetID].$reactComponent,
                $reactProps: {
                    $hydrate: widgetID,
                    ...config,
                },
            },
        ],
        componentFetcher: fetchOverviewComponent,
        fallbackWidget: FauxWidget,
    };

    return (
        <div className={classes.preview}>
            <div className={classes.previewHeader}>
                <Translate
                    source="Add or edit your widget here. You can choose your widget options by selecting a layout option, title and description if applicable. Set your widget conditions to specify where the widget will appear along with who the widget will be visible to. Find out more in the <1>documentation.</1>"
                    c1={(text) => (
                        //TODO documentation link should be here when its ready
                        <SmartLink to="">{text}</SmartLink>
                    )}
                />
            </div>

            {schemaIncludesHtml ? (
                <HtmlWidgetCodeEditor
                    value={{ html: props.value.html, css: props.value.css }}
                    onChange={({ html, css }) => props.onChange({ ...props.value, html, css })}
                />
            ) : (
                <div className={classes.previewBody}>
                    <WidgetContextProvider
                        // To override outer context
                        extraClasses={classes.previewContent}
                    >
                        <LayoutRenderer {...rendererConfig} />
                    </WidgetContextProvider>
                </div>
            )}
        </div>
    );
}
