/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { BrowserRouter, Route, RouteProps, Switch } from "react-router-dom";
import { Provider } from "react-redux";
import { getRoutes, getMeta } from "@library/utility/appUtils";
import NotFoundPage from "@library/routing/NotFoundPage";
import getStore from "@library/redux/getStore";

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
            <Provider store={getStore()}>
                <BrowserRouter basename={getMeta("context.basePath", "")}>
                    <Switch>{routes}</Switch>
                </BrowserRouter>
            </Provider>
        );
    }
}
