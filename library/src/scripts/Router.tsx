/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useMemo, useEffect, useState, useCallback } from "react";
import { LinkContextProvider } from "@library/routing/links/LinkContextProvider";
import { Router as ReactRouter, Switch, Route } from "react-router-dom";
import { formatUrl } from "@library/utility/appUtils";
import { createBrowserHistory, History } from "history";
import NotFoundPage from "@library/routing/NotFoundPage";
import { BackRoutingProvider } from "@library/routing/links/BackRoutingProvider";
import { initPageViewTracking, usePageChangeListener } from "@library/pageViews/pageViewTracking";
import { BannerContextProvider } from "./banner/BannerContext";

interface IProps {
    disableDynamicRouting?: boolean;
    sectionRoots?: string[];
    onRouteChange?: (history: History) => void;
}

export function Router(props: IProps) {
    const { onRouteChange } = props;
    const history = useMemo(() => createBrowserHistory({ basename: formatUrl("") }), []);

    useEffect(() => {
        initPageViewTracking(history);
    }, [history]);

    const pageChangeHandler = useCallback(() => {
        window.scrollTo(0, 0);
        onRouteChange?.(history);
    }, [history, onRouteChange]);

    usePageChangeListener(pageChangeHandler);

    let routes = (
        <Switch>
            {Router._routes}
            <Route key="@not-found" component={NotFoundPage} />
        </Switch>
    );

    routes = <BackRoutingProvider>{routes}</BackRoutingProvider>;
    if (!props.disableDynamicRouting) {
        routes = (
            <LinkContextProvider linkContexts={(props.sectionRoots ?? ["/"])?.map((root) => formatUrl(root, true))}>
                {routes}
            </LinkContextProvider>
        );
    }

    return <ReactRouter history={history}>{routes}</ReactRouter>;
}

/**
 * The currently registered routes.
 * @private
 */
Router._routes = [];

/**
 * Register one or more routes to the app component.
 *
 * @param routes An array of routes to add.
 */
Router.addRoutes = (routes: React.ReactNode[]) => {
    if (!Array.isArray(routes)) {
        Router._routes.push(routes);
    } else {
        Router._routes.push(...routes);
    }
};
