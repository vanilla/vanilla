/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ErrorPage } from "@library/errorPages/ErrorComponent";
import { LayoutRenderer } from "@library/features/Layout/LayoutRenderer";
import { useLayoutSpec } from "@library/features/Layout/LayoutPage.hooks";
import { WidgetLayout } from "@library/layout/WidgetLayout";
import { ILayoutQuery, type IHydratedLayoutSpec } from "@library/features/Layout/LayoutRenderer.types";
import { LayoutOverviewSkeleton } from "@dashboard/layout/overview/LayoutOverviewSkeleton";
import { PageBoxDepthContextProvider } from "@library/layout/PageBox.context";
import { useEmailConfirmationToast } from "@library/features/Layout/EmailConfirmation.hook";
import { AnalyticsData } from "@library/analytics/AnalyticsData";
import { getSiteSection } from "@library/utility/appUtils";
import { LayoutQueryContextProvider } from "@library/features/Layout/LayoutQueryProvider";
import { useEffect, useState } from "react";
import DocumentTitle from "@library/routing/DocumentTitle";

interface IProps {
    layoutQuery: ILayoutQuery;
}

export function LayoutPage(props: IProps) {
    // Keep the layout query stable even with location updates. If you want to a layout to refresh it's layout spec
    // Based off of some URL parameter, add them as part of the `key` of the `LayoutPage`.
    const { layoutQuery } = props; // useMemo(() => props.layoutQuery, []);
    const layout = useLayoutSpec({
        layoutViewType: layoutQuery.layoutViewType,
        recordID: layoutQuery.recordID ?? -1,
        recordType: layoutQuery.recordType ?? "global",
        params: {
            locale: getSiteSection().contentLocale,
            siteSectionID: getSiteSection().sectionID,
            ...layoutQuery.params,
        },
    });

    useEmailConfirmationToast();
    const [lastTitleBar, setLastTitleBar] = useState<IHydratedLayoutSpec["titleBar"] | null>(null);
    useEffect(() => {
        if (layout.data && layout.data.titleBar) {
            setLastTitleBar(layout.data.titleBar);
        }
    }, [layout.data]);

    if (layout.error) {
        return (
            <>
                {lastTitleBar && <LayoutRenderer noSuspense={true} allowInternalProps layout={[lastTitleBar]} />}
                <ErrorPage error={layout.error} />
            </>
        );
    }

    if (!layout.data) {
        return (
            <>
                {lastTitleBar && <LayoutRenderer noSuspense={true} allowInternalProps layout={[lastTitleBar]} />}
                <LayoutOverviewSkeleton />
            </>
        );
    }

    return (
        <WidgetLayout>
            <DocumentTitle title={layout?.data?.seo?.title ?? document.title} />
            <AnalyticsData
                uniqueKey={`customLayout_${layoutQuery.layoutViewType}_${layoutQuery.recordID}_${layoutQuery.recordType}`}
                data={layoutQuery}
            />
            <PageBoxDepthContextProvider depth={0}>
                <LayoutQueryContextProvider layoutQuery={layoutQuery}>
                    <LayoutRenderer noSuspense={true} allowInternalProps layout={[layout.data.titleBar]} />
                    <LayoutRenderer
                        key={layout?.data?.layoutID}
                        layout={layout.data.layout}
                        contexts={layout.data.contexts}
                    />
                </LayoutQueryContextProvider>
            </PageBoxDepthContextProvider>
        </WidgetLayout>
    );
}
