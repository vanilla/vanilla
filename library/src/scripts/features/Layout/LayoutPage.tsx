/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { ErrorPage } from "@library/errorPages/ErrorComponent";
import { Layout } from "@library/features/Layout/Layout";
import { useLayoutSpec } from "@library/features/Layout/LayoutPage.hooks";
import { ILayoutQuery } from "@library/features/Layout/LayoutPage.types";
import TitleBar from "@library/headers/TitleBar";
import Loader from "@library/loaders/Loader";
import { Router } from "@library/Router";
import { Route, RouteComponentProps } from "react-router";
import { WidgetLayout } from "@library/layout/WidgetLayout";

interface IProps {
    layoutQuery: ILayoutQuery;
}

export function LayoutPage(props: IProps) {
    //right now some of params are hardcoded, but should come through props
    const layout = useLayoutSpec({
        layoutViewType: props.layoutQuery.layoutViewType,
        recordID: -1,
        recordType: "global",
        params: {},
    });

    if (layout.error) {
        // Temporary error page.
        return <ErrorPage error={layout.error} />;
    }

    if (!layout.data) {
        // Temporary Loader.
        return <Loader />;
    }

    return (
        <WidgetLayout>
            <Layout layout={layout.data.layout} />;
        </WidgetLayout>
    );
}

type IPathLayoutQueryMapper = (params: RouteComponentProps) => ILayoutQuery;

export function registerLayoutPage(path: string | string[], pathMapper: IPathLayoutQueryMapper) {
    Router.addRoutes([
        <Route
            key={[path].flat().join("-")}
            path={path}
            render={(params) => {
                const mappedQuery = pathMapper(params);
                return <LayoutPage layoutQuery={mappedQuery} />;
            }}
        />,
    ]);
}
