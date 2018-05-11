/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import React from "react";
import { getRoutes, getMeta } from "@core/application";
import { BrowserRouter as Router, Route, RouteProps, Switch } from "react-router-dom";
import NotFoundPage from "@core/Main/NotFoundPage";
import { Provider } from "react-redux";
import { getStore } from "./redux/store";

/**
 * The root application component.
 *
 * This component renders the top-level pages that have been registered with {@link module:application.addRoutes}.
 */
export default class App extends React.PureComponent {
    public render() {
        const routes = getRoutes().map((route: React.ReactElement<RouteProps>) => {
            return <route.type key={route.key || route.props.path + (route.props.exact ? "!" : "")} {...route.props} />;
        });

        routes.push(<Route key="@not-found" component={NotFoundPage} />);

        return (
            <Provider store={getStore()}>
                <Router basename={getMeta("context.basePath", "")}>
                    <Switch>{routes}</Switch>
                </Router>
            </Provider>
        );
    }
}
