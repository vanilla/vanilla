/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { Suspense } from "react";
import { ErrorPage } from "@library/errorPages/ErrorComponent";
import { LayoutRenderer } from "@library/features/Layout/LayoutRenderer";
import { useLayoutSpec } from "@library/features/Layout/LayoutPage.hooks";
import { Router } from "@library/Router";
import { Route, RouteComponentProps, RouteProps } from "react-router";
import { WidgetLayout } from "@library/layout/WidgetLayout";
import { ILayoutQuery } from "@library/features/Layout/LayoutRenderer.types";
import { matchPath } from "react-router";
import { LayoutOverviewSkeleton } from "@dashboard/layout/overview/LayoutOverviewSkeleton";
import { PageBoxDepthContextProvider } from "@library/layout/PageBox.context";
import { getRelativeUrl, siteUrl } from "@library/utility/appUtils";
import { useEmailConfirmationToast } from "@library/features/Layout/EmailConfirmation.hook";

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
            <PageBoxDepthContextProvider depth={0}>
                <LayoutRenderer layout={layout.data.layout} />
            </PageBoxDepthContextProvider>
        </WidgetLayout>
    );
}

type IPathLayoutQueryMapper<T extends object> = (params: RouteComponentProps<T>) => ILayoutQuery<T>;

let layoutPaths: string[] = [];

export function registerLayoutPage<T extends object>(
    path: string | string[],
    pathMapper: IPathLayoutQueryMapper<T>,
    render?: (layoutQuery: ILayoutQuery<T>, page: React.ReactNode) => JSX.Element,
) {
    if (Array.isArray(path)) {
        layoutPaths = [...layoutPaths, ...path];
    } else {
        layoutPaths = [...layoutPaths, path];
    }
    Router.addRoutes([
        <Route
            key={[path].flat().join("-")}
            path={path}
            exact={true}
            render={(params: RouteComponentProps<T>) => {
                const mappedQuery = pathMapper(params);
                let page = <LayoutPage layoutQuery={mappedQuery} />;
                if (render) {
                    page = render(mappedQuery, page);
                }
                return <Suspense fallback={<LayoutOverviewSkeleton />}>{page}</Suspense>;
            }}
        />,
    ]);
}

export function isLayoutRoute(url: string): boolean {
    if (!url.startsWith(siteUrl(""))) {
        return false;
    }
    const relative = getRelativeUrl(url);
    const result = !!matchPath(relative, {
        path: layoutPaths,
        exact: true,
    });
    return result;
}
