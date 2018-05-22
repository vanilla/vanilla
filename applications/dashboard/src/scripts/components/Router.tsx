/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import { BrowserRouter, Route, RouteProps, Switch } from "react-router-dom";
import { getRoutes, getMeta } from "@dashboard/application";
import NotFoundPage from "@dashboard/components/NotFoundPage";

/**
 * The root application component.
 *
 * This component renders the top-level pages that have been registered with {@link module:application.addRoutes}.
 */
export default class Router extends React.PureComponent {
    public render() {
        const routes = getRoutes().map((route: React.ReactElement<RouteProps>) => {
            return <route.type key={route.key || route.props.path + (route.props.exact ? "!" : "")} {...route.props} />;
        });

        routes.push(<Route key="@not-found" component={NotFoundPage} />);

        return (
            <BrowserRouter basename={getMeta("context.basePath", "")}>
                <Switch>{routes}</Switch>
            </BrowserRouter>
        );
    }
}
