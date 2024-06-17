/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ErrorPage } from "@library/errorPages/ErrorComponent";
import { LayoutRenderer } from "@library/features/Layout/LayoutRenderer";
import { useLayoutSpec } from "@library/features/Layout/LayoutPage.hooks";
import { WidgetLayout } from "@library/layout/WidgetLayout";
import { ILayoutQuery } from "@library/features/Layout/LayoutRenderer.types";
import { LayoutOverviewSkeleton } from "@dashboard/layout/overview/LayoutOverviewSkeleton";
import { PageBoxDepthContextProvider } from "@library/layout/PageBox.context";
import { useEmailConfirmationToast } from "@library/features/Layout/EmailConfirmation.hook";
import { AnalyticsData } from "@library/analytics/AnalyticsData";

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
            ...layoutQuery.params,
        },
    });

    useEmailConfirmationToast();

    if (layout.error) {
        return <ErrorPage error={layout.error} />;
    }

    if (!layout.data) {
        return <LayoutOverviewSkeleton />;
    }

    return (
        <WidgetLayout>
            <AnalyticsData
                uniqueKey={`customLayout_${layoutQuery.layoutViewType}_${layoutQuery.recordID}_${layoutQuery.recordType}`}
                data={layoutQuery}
            />
            <PageBoxDepthContextProvider depth={0}>
                <LayoutRenderer layout={layout.data.layout} />
            </PageBoxDepthContextProvider>
        </WidgetLayout>
    );
}
