/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useMemo } from "react";
import { LinkContextProvider } from "@library/routing/links/LinkContextProvider";
import { Router as ReactRouter, Switch, Route } from "react-router-dom";
import { formatUrl } from "@library/utility/appUtils";
import { createBrowserHistory } from "history";
import NotFoundPage from "@library/routing/NotFoundPage";

interface IProps {
    disableDynamicRouting?: boolean;
    sectionRoot?: string;
}

export function Router(props: IProps) {
    const history = useMemo(() => createBrowserHistory({ basename: formatUrl("") }), []);

    let routes = (
        <Switch>
            {Router._routes}
            <Route key="@not-found" component={NotFoundPage} />
        </Switch>
    );

    if (!props.disableDynamicRouting) {
        routes = (
            <LinkContextProvider linkContext={formatUrl(props.sectionRoot || "/", true)}>{routes}</LinkContextProvider>
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
