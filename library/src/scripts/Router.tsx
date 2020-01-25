/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useMemo, useEffect, useState } from "react";
import { LinkContextProvider } from "@library/routing/links/LinkContextProvider";
import { Router as ReactRouter, Switch, Route } from "react-router-dom";
import { formatUrl } from "@library/utility/appUtils";
import { createBrowserHistory, History } from "history";
import NotFoundPage from "@library/routing/NotFoundPage";
import { BackRoutingProvider } from "@library/routing/links/BackRoutingProvider";
import { BackgroundsProvider } from "./layout/Backgrounds";
import { SplashContextProvider } from "@library/splash/SplashContext";

interface IProps {
    disableDynamicRouting?: boolean;
    sectionRoot?: string;
    onRouteChange?: (history: History) => void;
}

export function Router(props: IProps) {
    const { onRouteChange } = props;
    const history = useMemo(() => createBrowserHistory({ basename: formatUrl("") }), []);
    const [previousPath, setPreviousPath] = useState(window.location.pathname);

    useEffect(() => {
        if (onRouteChange) {
            const unregister = history.listen(() => {
                if (previousPath !== window.location.pathname) {
                    window.scrollTo(0, 0);
                    onRouteChange(history);
                    setPreviousPath(window.location.pathname);
                }
            });
            // Return the cleanup function.
            return unregister;
        }
    }, [history, onRouteChange, previousPath]);

    let routes = (
        <Switch>
            {Router._routes}
            <Route key="@not-found" component={NotFoundPage} />
        </Switch>
    );

    if (!props.disableDynamicRouting) {
        routes = (
            <LinkContextProvider linkContext={formatUrl(props.sectionRoot || "/", true)}>
                <BackRoutingProvider>
                    <SplashContextProvider>{routes}</SplashContextProvider>
                </BackRoutingProvider>
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
