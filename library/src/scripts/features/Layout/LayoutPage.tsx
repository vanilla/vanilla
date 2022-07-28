/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { Suspense } from "react";
import { ErrorPage } from "@library/errorPages/ErrorComponent";
import { LayoutRenderer } from "@library/features/Layout/LayoutRenderer";
import { useLayoutSpec } from "@library/features/Layout/LayoutPage.hooks";
import Loader from "@library/loaders/Loader";
import { Router } from "@library/Router";
import { Route, RouteComponentProps } from "react-router";
import { WidgetLayout } from "@library/layout/WidgetLayout";
import { ILayoutQuery } from "@library/features/Layout/LayoutRenderer.types";
import { matchPath } from "react-router";
import { LayoutOverviewSkeleton } from "@dashboard/layout/overview/LayoutOverviewSkeleton";

interface IProps {
    layoutQuery: ILayoutQuery;
}

export function LayoutPage(props: IProps) {
    const layout = useLayoutSpec({
        layoutViewType: props.layoutQuery.layoutViewType,
        recordID: props.layoutQuery.recordID ?? -1,
        recordType: props.layoutQuery.recordType ?? "global",
        params: {
            ...props.layoutQuery.params,
        },
    });

    if (layout.error) {
        return <ErrorPage error={layout.error} />;
    }

    if (!layout.data) {
        return <LayoutOverviewSkeleton />;
    }

    return (
        <WidgetLayout>
            <LayoutRenderer layout={layout.data.layout} />
        </WidgetLayout>
    );
}

type IPathLayoutQueryMapper = (params: RouteComponentProps) => ILayoutQuery;

let layoutPaths: string[] = [];

export function registerLayoutPage(path: string | string[], pathMapper: IPathLayoutQueryMapper) {
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
            render={(params) => {
                const mappedQuery = pathMapper(params);
                return (
                    <Suspense fallback={<LayoutOverviewSkeleton />}>
                        <LayoutPage layoutQuery={mappedQuery} />
                    </Suspense>
                );
            }}
        />,
    ]);
}

export function isLayoutRoute(path: string): boolean {
    return !!matchPath(path, layoutPaths);
}
