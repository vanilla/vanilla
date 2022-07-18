import React, { useMemo } from "react";
import TabWidget from "@library/tabWidget/TabWidget";
import { DiscussionsWidget } from "@library/features/discussions/DiscussionsWidget";
import { Widget, WidgetContextProvider } from "@library/layout/Widget";
import { LayoutLookupContext } from "@library/features/Layout/LayoutRenderer";
import { FauxWidget, fetchOverviewComponent } from "@dashboard/layout/overview/LayoutOverview";
import { css } from "@emotion/css";
import { WidgetSectionContext } from "@library/layout/WidgetLayout.context";
import { widgetLayoutClasses } from "@library/layout/WidgetLayout.styles";

interface IProps {
    tabConfiguration: Array<{
        isHidden?: boolean;
        label: string;
        tabPresetID: string;
    }>;
    limit: number;
}

export function TabWidgetPreview(props: IProps) {
    const { limit, tabConfiguration } = props;

    const previewProps: React.ComponentProps<typeof TabWidget> = {
        tabs: tabConfiguration
            .filter(({ isHidden }) => !isHidden)
            .map((config) => ({
                label: config.label,
                componentName: "DiscussionsWidget",
                componentProps: {
                    apiParams: { limit },
                } as React.ComponentProps<typeof DiscussionsWidget>,
            })),
    };

    const classes = widgetLayoutClasses();

    return (
        <Widget>
            <LayoutLookupContext.Provider
                value={{
                    componentFetcher: fetchOverviewComponent,
                    fallbackWidget: FauxWidget,
                }}
            >
                <WidgetSectionContext.Provider
                    value={{
                        headingBlockClass: classes.headingBlock,
                        widgetWithContainerClass: classes.widgetWithContainer,
                        widgetClass: css({
                            margin: 0,
                        }),
                    }}
                >
                    <WidgetContextProvider>
                        <TabWidget {...previewProps} />
                    </WidgetContextProvider>
                </WidgetSectionContext.Provider>
            </LayoutLookupContext.Provider>
        </Widget>
    );
}
