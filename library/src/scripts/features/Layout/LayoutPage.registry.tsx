/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { Suspense } from "react";
import { Route, RouteComponentProps } from "react-router";
import { ILayoutQuery } from "@library/features/Layout/LayoutRenderer.types";
import { LayoutOverviewSkeleton } from "@dashboard/layout/overview/LayoutOverviewSkeleton";
import { LayoutPage } from "./LayoutPage";
import { RouterRegistry } from "@library/Router.registry";
import { addLayoutPaths, excludeLayoutPaths, IPathLayoutQueryMapper } from "./LayoutPage.paths";

export function registerLayoutPage<T extends object>(
    path: string | string[],
    pathMapper: IPathLayoutQueryMapper<T>,
    excludePaths?: string | string[],
    render?: (layoutQuery: ILayoutQuery<T>, page: React.ReactNode) => JSX.Element,
) {
    if (Array.isArray(path)) {
        addLayoutPaths(path);
    } else {
        addLayoutPaths([path]);
    }

    if (excludePaths) {
        excludeLayoutPaths([excludePaths].flat());
    }

    RouterRegistry.addRoutes([
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
