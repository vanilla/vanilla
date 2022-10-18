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
import { LayoutLookupContext, LayoutRenderer } from "@library/features/Layout/LayoutRenderer";
import { FauxWidget, fetchOverviewComponent } from "@dashboard/layout/overview/LayoutOverview";
import { WidgetContextProvider } from "@library/layout/Widget";
import { LinkContext } from "@library/routing/links/LinkContextProvider";
import { IWidgetCatalog } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { MemoryRouter } from "react-router-dom";

interface IWidgetSettingsPreviewProps {
    widgetCatalog: IWidgetCatalog;
    widgetID: string;
    config?: object;
    assetCatalog?: IWidgetCatalog;
}

export function WidgetSettingsPreview(props: IWidgetConfigurationComponentProps & IWidgetSettingsPreviewProps) {
    const schemaIncludesHtml = props.schema.properties && "html" in props.schema.properties;

    const classes = widgetSettingsClasses();
    const { widgetCatalog, assetCatalog, widgetID, config } = props;

    const reactComponent = widgetCatalog[widgetID]
        ? widgetCatalog[widgetID].$reactComponent
        : assetCatalog && assetCatalog[widgetID]
        ? assetCatalog[widgetID].$reactComponent
        : "";
    const rendererConfig = {
        layout: [
            {
                $reactComponent: reactComponent,
                $reactProps: {
                    $hydrate: widgetID,
                    ...config,
                },
            },
        ],
    };

    return (
        <div className={classes.preview}>
            <div className={classes.previewHeader}>
                <Translate
                    source="Add or edit your widget here. You can choose your widget options by selecting a layout option, title and description if applicable.
                    Set your widget conditions to specify where the widget will appear along with who the widget will be visible to.
                    Styles will be inherited from <1>Style Guide. </1>Find out more in the <2>documentation.</2>"
                    c1={(text) => <SmartLink to="https://success.vanillaforums.com/kb/articles/279">{text}</SmartLink>}
                    c2={(text) => <SmartLink to="https://success.vanillaforums.com/kb/articles/547">{text}</SmartLink>}
                />
            </div>

            {schemaIncludesHtml ? (
                <HtmlWidgetCodeEditor
                    value={props.value}
                    onChange={(newValue) => props.onChange({ ...props.value, ...newValue })}
                />
            ) : (
                <div className={classes.previewBody}>
                    <WidgetContextProvider
                        // To override outer context
                        extraClasses={classes.previewContent}
                    >
                        <MemoryRouter>
                            <LinkContext.Provider
                                value={{
                                    linkContexts: [""],
                                    isDynamicNavigation: () => {
                                        return true;
                                    },
                                    pushSmartLocation: () => {},
                                    makeHref: () => {
                                        return "";
                                    },
                                    areLinksDisabled: false,
                                }}
                            >
                                <LayoutLookupContext.Provider
                                    value={{
                                        componentFetcher: fetchOverviewComponent,
                                        fallbackWidget: FauxWidget,
                                    }}
                                >
                                    <LayoutRenderer {...rendererConfig} />
                                </LayoutLookupContext.Provider>
                            </LinkContext.Provider>
                        </MemoryRouter>
                    </WidgetContextProvider>
                </div>
            )}
        </div>
    );
}
